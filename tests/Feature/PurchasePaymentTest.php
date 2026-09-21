<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PurchasePaymentTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $finance;
    private Supplier $supplier;
    private Warehouse $warehouse;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->finance = User::factory()->create(['role' => 'finance']);

        $this->supplier = Supplier::create([
            'code' => 'SUP-PAY-' . uniqid(),
            'name' => 'PT Pemasok Sukses',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'code' => 'WH-PAY-' . uniqid(),
            'name' => 'Gudang Utama Payment',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'sku' => 'SKU-PAY-' . uniqid(),
            'name' => 'Produk Pembayaran',
            'unit' => 'pcs',
            'purchase_price' => 100000,
            'sell_price' => 150000,
            'is_active' => true,
        ]);

        // Ensure basic Chart of Accounts exist
        ChartOfAccount::firstOrCreate(
            ['code' => '2-1100'],
            ['name' => 'Hutang Usaha', 'type' => 'liability', 'normal_balance' => 'credit', 'is_active' => true]
        );
        ChartOfAccount::firstOrCreate(
            ['code' => '1-1100'],
            ['name' => 'Kas & Bank', 'type' => 'asset', 'normal_balance' => 'debit', 'is_active' => true]
        );
    }

    private function createUnpaidInvoice(float $amount = 1000000): PurchaseInvoice
    {
        $po = PurchaseOrder::create([
            'po_number' => 'PO-PAY-' . uniqid(),
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->admin->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'total_amount' => $amount,
        ]);

        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'unit_price' => $amount / 10,
            'subtotal' => $amount,
        ]);

        $gr = GoodsReceipt::create([
            'receipt_number' => 'GRN-PAY-' . uniqid(),
            'purchase_order_id' => $po->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'received_date' => now()->toDateString(),
            'qc_status' => 'passed',
        ]);

        $grItem = GoodsReceiptItem::create([
            'goods_receipt_id' => $gr->id,
            'purchase_order_item_id' => $poItem->id,
            'product_id' => $this->product->id,
            'qty_received' => 10,
            'unit_cost' => $amount / 10,
            'invoiced_qty' => 10,
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'INV-PAY-' . uniqid(),
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $gr->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'amount' => $amount,
            'tax_amount' => 0,
            'total_amount' => $amount,
            'status' => 'unpaid',
        ]);

        PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $invoice->id,
            'goods_receipt_item_id' => $grItem->id,
            'product_id' => $this->product->id,
            'qty_invoiced' => 10,
            'unit_price' => $amount / 10,
            'subtotal' => $amount,
        ]);

        return $invoice;
    }

    public function test_can_view_payments_index_and_create_page(): void
    {
        $invoice = $this->createUnpaidInvoice(1000000);

        $responseIndex = $this->actingAs($this->finance)->get(route('purchase.payments.index'));
        $responseIndex->assertStatus(200);
        $responseIndex->assertSee('Pembayaran Hutang Supplier');

        $responseCreate = $this->actingAs($this->finance)->get(route('purchase.payments.create', ['invoice_id' => $invoice->id]));
        $responseCreate->assertStatus(200);
        $responseCreate->assertSee($invoice->invoice_number);
        $responseCreate->assertSee('PT Pemasok Sukses');
    }

    public function test_can_store_payment_as_draft_without_posting(): void
    {
        $invoice = $this->createUnpaidInvoice(1000000);
        $coa = ChartOfAccount::where('code', '1-1100')->first();

        $response = $this->actingAs($this->finance)->post(route('purchase.payments.store'), [
            'purchase_invoice_id' => $invoice->id,
            'chart_of_account_id' => $coa->id,
            'payment_type'        => 'bank',
            'amount'              => 500000,
            'payment_date'        => now()->toDateString(),
            'method'              => 'transfer',
            'action'              => 'draft',
        ]);

        $response->assertRedirect(route('purchase.payments.index'));
        $response->assertSessionHas('success');

        $payment = PurchasePayment::where('purchase_invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('draft', $payment->status);
        $this->assertTrue($payment->isDraft());

        // Invoice status must NOT be changed while in draft
        $invoice->refresh();
        $this->assertEquals('unpaid', $invoice->status);
        $this->assertEquals(1000000, $invoice->outstanding_amount);

        // Journal Entry must NOT be created
        $this->assertDatabaseMissing('journal_entries', [
            'reference_type' => PurchasePayment::class,
            'reference_id'   => $payment->id,
        ]);
    }

    public function test_can_update_draft_payment(): void
    {
        $invoice = $this->createUnpaidInvoice(1000000);
        $coa = ChartOfAccount::where('code', '1-1100')->first();

        $payment = PurchasePayment::create([
            'purchase_invoice_id' => $invoice->id,
            'chart_of_account_id' => $coa->id,
            'payment_type'        => 'bank',
            'user_id'             => $this->finance->id,
            'amount'              => 300000,
            'status'              => 'draft',
            'payment_date'        => now()->toDateString(),
            'method'              => 'transfer',
            'reference_number'    => 'TRF-DRAFT-1',
        ]);

        $response = $this->actingAs($this->finance)->put(route('purchase.payments.update', $payment), [
            'chart_of_account_id' => $coa->id,
            'payment_type'        => 'kas',
            'amount'              => 450000,
            'payment_date'        => now()->toDateString(),
            'method'              => 'cash',
            'reference_number'    => 'CASH-DRAFT-2',
            'action'              => 'update',
        ]);

        $response->assertRedirect(route('purchase.payments.index'));
        $payment->refresh();
        $this->assertEquals(450000, $payment->amount);
        $this->assertEquals('draft', $payment->status);
        $this->assertEquals('cash', $payment->method);
    }

    public function test_can_post_draft_payment(): void
    {
        $invoice = $this->createUnpaidInvoice(1000000);
        $coa = ChartOfAccount::where('code', '1-1100')->first();

        $payment = PurchasePayment::create([
            'purchase_invoice_id' => $invoice->id,
            'chart_of_account_id' => $coa->id,
            'payment_type'        => 'bank',
            'user_id'             => $this->finance->id,
            'amount'              => 400000,
            'status'              => 'draft',
            'payment_date'        => now()->toDateString(),
            'method'              => 'transfer',
        ]);

        $response = $this->actingAs($this->finance)->post(route('purchase.payments.post', $payment));

        $response->assertRedirect();
        $payment->refresh();
        $this->assertEquals('posted', $payment->status);
        $this->assertTrue($payment->isPosted());

        $invoice->refresh();
        $this->assertEquals('partial', $invoice->status);
        $this->assertEquals(600000, $invoice->outstanding_amount);

        // Journal Entry must exist and be posted
        $this->assertDatabaseHas('journal_entries', [
            'reference_type' => PurchasePayment::class,
            'reference_id'   => $payment->id,
            'status'         => 'posted',
        ]);
    }

    public function test_cannot_edit_or_update_posted_payment(): void
    {
        $invoice = $this->createUnpaidInvoice(1000000);
        $coa = ChartOfAccount::where('code', '1-1100')->first();

        $payment = PurchasePayment::create([
            'purchase_invoice_id' => $invoice->id,
            'chart_of_account_id' => $coa->id,
            'payment_type'        => 'bank',
            'user_id'             => $this->finance->id,
            'amount'              => 400000,
            'status'              => 'posted',
            'payment_date'        => now()->toDateString(),
            'method'              => 'transfer',
        ]);

        // Attempting to view edit page for posted payment returns 403
        $responseEdit = $this->actingAs($this->finance)->get(route('purchase.payments.edit', $payment));
        $responseEdit->assertStatus(403);

        // Attempting to update posted payment returns 403
        $responseUpdate = $this->actingAs($this->finance)->put(route('purchase.payments.update', $payment), [
            'chart_of_account_id' => $coa->id,
            'payment_type'        => 'kas',
            'amount'              => 500000,
            'payment_date'        => now()->toDateString(),
            'method'              => 'cash',
        ]);
        $responseUpdate->assertStatus(403);

        // Attempting to delete posted payment returns 403
        $responseDelete = $this->actingAs($this->finance)->delete(route('purchase.payments.destroy', $payment));
        $responseDelete->assertStatus(403);
    }

    public function test_can_record_and_post_payment_immediately(): void
    {
        $invoice = $this->createUnpaidInvoice(1000000);
        $coa = ChartOfAccount::where('code', '1-1100')->first();

        $response = $this->actingAs($this->finance)->post(route('purchase.payments.store'), [
            'purchase_invoice_id' => $invoice->id,
            'chart_of_account_id' => $coa->id,
            'payment_type'        => 'bank',
            'amount'              => 1000000,
            'payment_date'        => now()->toDateString(),
            'method'              => 'transfer',
            'reference_number'    => 'TRF-FULL',
            'action'              => 'post',
        ]);

        $response->assertRedirect(route('purchase.payments.index'));
        $payment = PurchasePayment::where('purchase_invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('posted', $payment->status);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(0, $invoice->outstanding_amount);
    }

    public function test_cannot_overpay_more_than_outstanding_amount(): void
    {
        $invoice = $this->createUnpaidInvoice(500000);
        $coa = ChartOfAccount::where('code', '1-1100')->first();

        $response = $this->actingAs($this->finance)->post(route('purchase.payments.store'), [
            'purchase_invoice_id' => $invoice->id,
            'chart_of_account_id' => $coa->id,
            'payment_type'        => 'bank',
            'amount'              => 600000, // Exceeds 500.000
            'payment_date'        => now()->toDateString(),
            'method'              => 'transfer',
            'action'              => 'post',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(0, PurchasePayment::where('purchase_invoice_id', $invoice->id)->count());
        $invoice->refresh();
        $this->assertEquals('unpaid', $invoice->status);
    }

    public function test_auto_generates_payment_number_bbk_for_bank_and_bkk_for_cash(): void
    {
        $date = now()->format('d/m/Y');
        $coa = ChartOfAccount::where('code', '1-1100')->first();

        // 1. Bank transfer payment -> BBK
        $invoice1 = $this->createUnpaidInvoice(500000);
        $this->actingAs($this->finance)->post(route('purchase.payments.store'), [
            'purchase_invoice_id' => $invoice1->id,
            'chart_of_account_id' => $coa->id,
            'payment_type'        => 'bank',
            'amount'              => 200000,
            'payment_date'        => now()->toDateString(),
            'method'              => 'transfer',
        ]);
        $payment1 = PurchasePayment::where('purchase_invoice_id', $invoice1->id)->first();
        $this->assertNotNull($payment1);
        $this->assertNotNull($payment1->payment_number);
        $this->assertStringStartsWith("BBK-{$date}-", $payment1->payment_number);
        $this->assertEquals($payment1->payment_number, $payment1->voucher_number);

        // 2. Cash payment -> BKK
        $invoice2 = $this->createUnpaidInvoice(300000);
        $this->actingAs($this->finance)->post(route('purchase.payments.store'), [
            'purchase_invoice_id' => $invoice2->id,
            'chart_of_account_id' => $coa->id,
            'payment_type'        => 'kas',
            'amount'              => 100000,
            'payment_date'        => now()->toDateString(),
            'method'              => 'cash',
        ]);
        $payment2 = PurchasePayment::where('purchase_invoice_id', $invoice2->id)->first();
        $this->assertNotNull($payment2);
        $this->assertNotNull($payment2->payment_number);
        $this->assertStringStartsWith("BKK-{$date}-", $payment2->payment_number);
        $this->assertEquals($payment2->payment_number, $payment2->voucher_number);
    }

    public function test_cannot_store_purchase_payment_with_giro_method(): void
    {
        $invoice = $this->createUnpaidInvoice(1000000);
        $coa = ChartOfAccount::where('code', '1-1100')->first();

        $response = $this->actingAs($this->finance)->post(route('purchase.payments.store'), [
            'purchase_invoice_id' => $invoice->id,
            'chart_of_account_id' => $coa->id,
            'payment_type'        => 'bank',
            'amount'              => 500000,
            'payment_date'        => now()->toDateString(),
            'method'              => 'giro',
        ]);

        $response->assertSessionHasErrors('method');
        $this->assertDatabaseMissing('purchase_payments', [
            'purchase_invoice_id' => $invoice->id,
        ]);
    }
}

