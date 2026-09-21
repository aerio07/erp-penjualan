<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\SalesInvoiceStatus;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesPayment;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SalesPaymentTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $finance;
    private Customer $customer;
    private Warehouse $warehouse;
    private Product $product;
    private ChartOfAccount $cashAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->finance = User::factory()->create(['role' => 'finance']);

        $this->customer = Customer::create([
            'code' => 'CUST-PAY-' . uniqid(),
            'name' => 'PT Pelanggan Sejahtera',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'code' => 'WH-SPAY-' . uniqid(),
            'name' => 'Gudang Utama Sales Payment',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'sku' => 'SKU-SPAY-' . uniqid(),
            'name' => 'Produk Penjualan',
            'unit' => 'pcs',
            'purchase_price' => 100000,
            'sell_price' => 150000,
            'is_active' => true,
        ]);

        // Ensure basic Chart of Accounts exist
        ChartOfAccount::firstOrCreate(
            ['code' => '1-1200'],
            ['name' => 'Piutang Usaha', 'type' => 'asset', 'normal_balance' => 'debit', 'is_active' => true]
        );
        $this->cashAccount = ChartOfAccount::firstOrCreate(
            ['code' => '1-1100'],
            ['name' => 'Kas & Bank', 'type' => 'asset', 'normal_balance' => 'debit', 'is_active' => true]
        );
    }

    private function createUnpaidSalesInvoice(float $amount = 1500000): SalesInvoice
    {
        $so = SalesOrder::create([
            'so_number' => 'SO-PAY-' . uniqid(),
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'total_amount' => $amount,
        ]);

        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'unit_price' => $amount / 10,
            'subtotal' => $amount,
        ]);

        $delivery = Delivery::create([
            'delivery_number' => 'DO-PAY-' . uniqid(),
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'delivery_date' => now()->toDateString(),
            'status' => 'delivered',
        ]);

        $deliveryItem = DeliveryItem::create([
            'delivery_id' => $delivery->id,
            'sales_order_item_id' => $soItem->id,
            'product_id' => $this->product->id,
            'qty_delivered' => 10,
        ]);

        $invoice = SalesInvoice::create([
            'invoice_number' => 'SINV-PAY-' . uniqid(),
            'sales_order_id' => $so->id,
            'delivery_id' => $delivery->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'amount' => $amount,
            'tax_amount' => 0,
            'total_amount' => $amount,
            'status' => 'unpaid',
        ]);

        SalesInvoiceItem::create([
            'sales_invoice_id' => $invoice->id,
            'delivery_item_id' => $deliveryItem->id,
            'product_id' => $this->product->id,
            'qty_invoiced' => 10,
            'unit_price' => $amount / 10,
            'subtotal' => $amount,
        ]);

        return $invoice;
    }

    public function test_can_view_sales_payments_index_and_create(): void
    {
        $invoice = $this->createUnpaidSalesInvoice(1500000);

        $responseIndex = $this->actingAs($this->finance)->get(route('sales.payments.index'));
        $responseIndex->assertStatus(200);
        $responseIndex->assertSee('Pembayaran Piutang Customer');

        $responseCreate = $this->actingAs($this->finance)->get(route('sales.payments.create', ['invoice_id' => $invoice->id]));
        $responseCreate->assertStatus(200);
        $responseCreate->assertSee($invoice->invoice_number);
    }

    public function test_can_store_sales_payment_as_draft_without_posting(): void
    {
        $invoice = $this->createUnpaidSalesInvoice(1500000);
        $coa = ChartOfAccount::where('code', '1-1100')->first();

        $response = $this->actingAs($this->finance)->post(route('sales.payments.store'), [
            'sales_invoice_id'    => $invoice->id,
            'chart_of_account_id' => $coa->id,
            'payment_type'        => 'bank',
            'amount'              => 500000,
            'payment_date'        => now()->toDateString(),
            'method'              => 'transfer',
            'action'              => 'draft',
        ]);

        $response->assertRedirect(route('sales.payments.index'));
        $payment = SalesPayment::where('sales_invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(PaymentStatus::Draft, $payment->status);
        $this->assertTrue($payment->isDraft());

        $invoice->refresh();
        $this->assertEquals(SalesInvoiceStatus::Unpaid, $invoice->status);
        $this->assertEquals(1500000, $invoice->outstanding_amount);

        $this->assertDatabaseMissing('journal_entries', [
            'reference_type' => SalesPayment::class,
            'reference_id'   => $payment->id,
        ]);
    }

    public function test_can_update_draft_sales_payment(): void
    {
        $invoice = $this->createUnpaidSalesInvoice(1500000);
        $coa = ChartOfAccount::where('code', '1-1100')->first();

        $payment = SalesPayment::create([
            'sales_invoice_id'    => $invoice->id,
            'chart_of_account_id' => $coa->id,
            'payment_type'        => 'bank',
            'user_id'             => $this->finance->id,
            'amount'              => 300000,
            'status'              => 'draft',
            'payment_date'        => now()->toDateString(),
            'method'              => 'transfer',
        ]);

        $response = $this->actingAs($this->finance)->put(route('sales.payments.update', $payment), [
            'chart_of_account_id' => $coa->id,
            'payment_type'        => 'kas',
            'amount'              => 600000,
            'payment_date'        => now()->toDateString(),
            'method'              => 'cash',
            'action'              => 'update',
        ]);

        $response->assertRedirect(route('sales.payments.index'));
        $payment->refresh();
        $this->assertEquals(600000, $payment->amount);
        $this->assertEquals(PaymentStatus::Draft, $payment->status);
    }

    public function test_can_post_draft_sales_payment(): void
    {
        $invoice = $this->createUnpaidSalesInvoice(1500000);
        $coa = ChartOfAccount::where('code', '1-1100')->first();

        $payment = SalesPayment::create([
            'sales_invoice_id'    => $invoice->id,
            'chart_of_account_id' => $coa->id,
            'payment_type'        => 'bank',
            'user_id'             => $this->finance->id,
            'amount'              => 600000,
            'status'              => 'draft',
            'payment_date'        => now()->toDateString(),
            'method'              => 'transfer',
        ]);

        $response = $this->actingAs($this->finance)->post(route('sales.payments.post', $payment));

        $response->assertRedirect();
        $payment->refresh();
        $this->assertEquals(PaymentStatus::Posted, $payment->status);
        $this->assertTrue($payment->isPosted());

        $invoice->refresh();
        $this->assertEquals(SalesInvoiceStatus::Partial, $invoice->status);
        $this->assertEquals(900000, $invoice->outstanding_amount);

        $this->assertDatabaseHas('journal_entries', [
            'reference_type' => SalesPayment::class,
            'reference_id'   => $payment->id,
            'status'         => 'posted',
        ]);
    }

    public function test_cannot_edit_or_update_posted_sales_payment(): void
    {
        $invoice = $this->createUnpaidSalesInvoice(1500000);
        $coa = ChartOfAccount::where('code', '1-1100')->first();

        $payment = SalesPayment::create([
            'sales_invoice_id'    => $invoice->id,
            'chart_of_account_id' => $coa->id,
            'payment_type'        => 'bank',
            'user_id'             => $this->finance->id,
            'amount'              => 600000,
            'status'              => 'posted',
            'payment_date'        => now()->toDateString(),
            'method'              => 'transfer',
        ]);

        $responseEdit = $this->actingAs($this->finance)->get(route('sales.payments.edit', $payment));
        $responseEdit->assertStatus(403);

        $responseUpdate = $this->actingAs($this->finance)->put(route('sales.payments.update', $payment), [
            'chart_of_account_id' => $coa->id,
            'payment_type'        => 'kas',
            'amount'              => 800000,
            'payment_date'        => now()->toDateString(),
            'method'              => 'cash',
        ]);
        $responseUpdate->assertStatus(403);

        $responseDelete = $this->actingAs($this->finance)->delete(route('sales.payments.destroy', $payment));
        $responseDelete->assertStatus(403);
    }

    public function test_auto_generates_payment_number_bbm_for_bank_and_bkm_for_cash(): void
    {
        $date = now()->format('d/m/Y');
        $coa = ChartOfAccount::where('code', '1-1100')->first();

        // 1. Bank transfer payment -> BBM
        $invoice1 = $this->createUnpaidSalesInvoice(750000);
        $this->actingAs($this->finance)->post(route('sales.payments.store'), [
            'sales_invoice_id'    => $invoice1->id,
            'chart_of_account_id' => $coa->id,
            'payment_type'        => 'bank',
            'amount'              => 300000,
            'payment_date'        => now()->toDateString(),
            'method'              => 'transfer',
        ]);
        $payment1 = SalesPayment::where('sales_invoice_id', $invoice1->id)->first();
        $this->assertNotNull($payment1);
        $this->assertNotNull($payment1->payment_number);
        $this->assertStringStartsWith("BBM-{$date}-", $payment1->payment_number);
        $this->assertEquals($payment1->payment_number, $payment1->voucher_number);

        // 2. Cash payment -> BKM
        $invoice2 = $this->createUnpaidSalesInvoice(500000);
        $this->actingAs($this->finance)->post(route('sales.payments.store'), [
            'sales_invoice_id'    => $invoice2->id,
            'chart_of_account_id' => $coa->id,
            'payment_type'        => 'kas',
            'amount'              => 200000,
            'payment_date'        => now()->toDateString(),
            'method'              => 'cash',
        ]);
        $payment2 = SalesPayment::where('sales_invoice_id', $invoice2->id)->first();
        $this->assertNotNull($payment2);
        $this->assertNotNull($payment2->payment_number);
        $this->assertStringStartsWith("BKM-{$date}-", $payment2->payment_number);
        $this->assertEquals($payment2->payment_number, $payment2->voucher_number);
    }

    public function test_cannot_store_sales_payment_with_giro_method(): void
    {
        $invoice = $this->createUnpaidSalesInvoice(1000000);
        $coa = ChartOfAccount::where('code', '1-1100')->first();

        $response = $this->actingAs($this->finance)->post(route('sales.payments.store'), [
            'sales_invoice_id'    => $invoice->id,
            'chart_of_account_id' => $coa->id,
            'payment_type'        => 'bank',
            'amount'              => 500000,
            'payment_date'        => now()->toDateString(),
            'method'              => 'giro',
        ]);

        $response->assertSessionHasErrors('method');
        $this->assertDatabaseMissing('sales_payments', [
            'sales_invoice_id' => $invoice->id,
        ]);
    }
}
