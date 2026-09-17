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

    public function test_can_record_partial_and_full_payment_with_auto_journal(): void
    {
        $invoice = $this->createUnpaidInvoice(1000000);

        // 1. Partial payment: Rp 400.000
        $responsePartial = $this->actingAs($this->finance)->post(route('purchase.payments.store'), [
            'purchase_invoice_id' => $invoice->id,
            'amount'              => 400000,
            'payment_date'        => now()->toDateString(),
            'method'              => 'transfer',
            'reference_number'    => 'TRF-001',
            'notes'               => 'Cicilan 1',
        ]);

        $responsePartial->assertRedirect(route('purchase.payments.index'));
        $responsePartial->assertSessionHas('success');

        $invoice->refresh();
        $this->assertEquals('partial', $invoice->status);
        $this->assertEquals(600000, $invoice->outstanding_amount);

        // Verify Journal Entry for partial payment
        $payment1 = PurchasePayment::where('purchase_invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment1);
        $this->assertDatabaseHas('journal_entries', [
            'reference_type' => PurchasePayment::class,
            'reference_id'   => $payment1->id,
            'status'         => 'posted',
        ]);

        // 2. Full payment: remaining Rp 600.000
        $responseFull = $this->actingAs($this->finance)->post(route('purchase.payments.store'), [
            'purchase_invoice_id' => $invoice->id,
            'amount'              => 600000,
            'payment_date'        => now()->toDateString(),
            'method'              => 'cash',
            'reference_number'    => 'CASH-002',
            'notes'               => 'Pelunasan',
        ]);

        $responseFull->assertRedirect(route('purchase.payments.index'));

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(0, $invoice->outstanding_amount);
    }

    public function test_cannot_overpay_more_than_outstanding_amount(): void
    {
        $invoice = $this->createUnpaidInvoice(500000);

        $response = $this->actingAs($this->finance)->post(route('purchase.payments.store'), [
            'purchase_invoice_id' => $invoice->id,
            'amount'              => 600000, // Exceeds 500.000
            'payment_date'        => now()->toDateString(),
            'method'              => 'transfer',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(0, PurchasePayment::where('purchase_invoice_id', $invoice->id)->count());
        $invoice->refresh();
        $this->assertEquals('unpaid', $invoice->status);
    }

    public function test_can_view_payment_show_detail(): void
    {
        $invoice = $this->createUnpaidInvoice(500000);

        $payment = PurchasePayment::create([
            'purchase_invoice_id' => $invoice->id,
            'user_id'             => $this->admin->id,
            'amount'              => 500000,
            'payment_date'        => now()->toDateString(),
            'method'              => 'transfer',
            'reference_number'    => 'TRF-TEST-DETAIL',
            'notes'               => 'Catatan Pembayaran',
        ]);

        $response = $this->actingAs($this->finance)->get(route('purchase.payments.show', $payment));
        $response->assertStatus(200);
        $response->assertSee('TRF-TEST-DETAIL');
        $response->assertSee('PT Pemasok Sukses');
        $response->assertSee('500.000');
    }

    public function test_search_payments_by_supplier_name(): void
    {
        $invoice = $this->createUnpaidInvoice(500000);

        $payment = PurchasePayment::create([
            'purchase_invoice_id' => $invoice->id,
            'user_id'             => $this->admin->id,
            'amount'              => 500000,
            'payment_date'        => now()->toDateString(),
            'method'              => 'transfer',
            'reference_number'    => 'TRF-SEARCH-SUPP',
        ]);

        $response = $this->actingAs($this->finance)->get(route('purchase.payments.index', ['q' => 'Pemasok Sukses']));
        $response->assertStatus(200);
        $response->assertSee('TRF-SEARCH-SUPP');
    }

    public function test_can_view_edit_payment_page(): void
    {
        $invoice = $this->createUnpaidInvoice(1000000);

        $payment = PurchasePayment::create([
            'purchase_invoice_id' => $invoice->id,
            'user_id'             => $this->finance->id,
            'amount'              => 400000,
            'payment_date'        => now()->toDateString(),
            'method'              => 'transfer',
            'reference_number'    => 'TRF-EDIT-TEST',
        ]);

        $response = $this->actingAs($this->finance)->get(route('purchase.payments.edit', $payment));
        $response->assertStatus(200);
        $response->assertSee('Edit Pembayaran Hutang');
        $response->assertSee($invoice->invoice_number);
    }

    public function test_can_update_purchase_payment_and_sync_invoice_and_journal(): void
    {
        $invoice = $this->createUnpaidInvoice(1000000);

        // Record initial payment
        $responseStore = $this->actingAs($this->finance)->post(route('purchase.payments.store'), [
            'purchase_invoice_id' => $invoice->id,
            'amount'              => 500000,
            'payment_date'        => now()->toDateString(),
            'method'              => 'transfer',
            'reference_number'    => 'TRF-INIT',
        ]);
        $responseStore->assertRedirect(route('purchase.payments.index'));

        $payment = PurchasePayment::where('purchase_invoice_id', $invoice->id)->first();
        $invoice->refresh();
        $this->assertEquals('partial', $invoice->status);

        // Update payment to full (1.000.000)
        $responseUpdate = $this->actingAs($this->finance)->put(route('purchase.payments.update', $payment), [
            'amount'           => 1000000,
            'payment_date'     => now()->toDateString(),
            'method'           => 'cash',
            'reference_number' => 'CASH-FULL',
            'notes'            => 'Diubah jadi lunas',
        ]);

        $responseUpdate->assertRedirect(route('purchase.payments.show', $payment));
        $responseUpdate->assertSessionHas('success');

        $payment->refresh();
        $this->assertEquals(1000000, $payment->amount);
        $this->assertEquals('cash', $payment->method);
        $this->assertEquals('CASH-FULL', $payment->reference_number);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(0, $invoice->outstanding_amount);

        // Verify Journal Entry was updated to 1.000.000
        $journal = JournalEntry::where('reference_type', PurchasePayment::class)
            ->where('reference_id', $payment->id)
            ->first();
        $this->assertNotNull($journal);
        $this->assertEquals(1000000, $journal->lines->sum('debit'));
    }
}
