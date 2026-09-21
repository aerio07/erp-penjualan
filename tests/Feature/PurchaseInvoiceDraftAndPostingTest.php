<?php

namespace Tests\Feature;

use App\Enums\PurchaseInvoiceStatus;
use App\Models\ChartOfAccount;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseInvoiceDraftAndPostingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Supplier $supplier;
    private Warehouse $warehouse;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->supplier = Supplier::create([
            'code' => 'SUP-TEST',
            'name' => 'PT Supplier Utama',
            'is_active' => true,
            'payment_term_days' => 30,
        ]);

        $this->warehouse = Warehouse::create([
            'code' => 'WH-MAIN',
            'name' => 'Gudang Utama',
            'is_active' => true,
        ]);

        $category = ProductCategory::create([
            'code' => 'CAT-01',
            'name' => 'Kategori Utama',
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'code' => 'PROD-01',
            'sku' => 'SKU-001',
            'name' => 'Produk Tes',
            'unit' => 'pcs',
            'cost_price' => 100000,
            'selling_price' => 150000,
            'min_stock' => 5,
            'is_active' => true,
        ]);

        $this->setupAccounts();
    }

    private function setupAccounts(): void
    {
        $accounts = [
            ['code' => '1-1100', 'name' => 'Kas / Bank', 'type' => 'asset'],
            ['code' => '1-1400', 'name' => 'Persediaan Barang Dagang', 'type' => 'asset'],
            ['code' => '1-1500', 'name' => 'PPN Masukan', 'type' => 'asset'],
            ['code' => '2-1100', 'name' => 'Hutang Usaha', 'type' => 'liability'],
            ['code' => '2-20001', 'name' => 'Hutang Usaha (Alt)', 'type' => 'liability'],
            ['code' => '2-20002', 'name' => 'Hutang Belum Difakturkan', 'type' => 'liability'],
        ];

        foreach ($accounts as $acc) {
            ChartOfAccount::firstOrCreate(['code' => $acc['code']], [
                'name' => $acc['name'],
                'type' => $acc['type'],
                'is_active' => true,
            ]);
        }
    }

    private function createPoAndGrn(int $qty = 10, float $price = 100000): array
    {
        $po = PurchaseOrder::create([
            'po_number' => 'PO-' . uniqid(),
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'order_date' => now()->toDateString(),
            'expected_date' => now()->addDays(7)->toDateString(),
            'status' => 'confirmed',
            'subtotal' => $qty * $price,
            'tax_rate' => 11,
            'tax_amount' => ($qty * $price) * 0.11,
            'total_amount' => ($qty * $price) * 1.11,
        ]);

        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->product->id,
            'qty_ordered' => $qty,
            'qty_received' => $qty,
            'unit_price' => $price,
            'discount_percent' => 0,
            'subtotal' => $qty * $price,
        ]);

        $grn = GoodsReceipt::create([
            'receipt_number' => 'GRN-' . uniqid(),
            'purchase_order_id' => $po->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'received_date' => now()->toDateString(),
            'is_invoiced' => false,
        ]);

        $grnItem = GoodsReceiptItem::create([
            'goods_receipt_id' => $grn->id,
            'purchase_order_item_id' => $poItem->id,
            'qty_received' => $qty,
            'qty_rejected' => 0,
            'unit_cost' => $price,
            'warehouse_id' => $this->warehouse->id,
        ]);

        return [$po, $grn, $poItem, $grnItem];
    }

    public function test_create_invoice_stores_as_draft_without_journal(): void
    {
        [$po, $grn] = $this->createPoAndGrn(10, 100000);

        $response = $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grn->id,
            'supplier_invoice_number' => 'INV-SUPP-123',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'tax_rate' => 11,
        ]);

        $invoice = PurchaseInvoice::where('purchase_order_id', $po->id)->first();
        $this->assertNotNull($invoice);
        $response->assertRedirect(route('purchase.invoices.index'));

        // Verifikasi status draft
        $this->assertEquals(PurchaseInvoiceStatus::Draft, $invoice->status);
        $this->assertTrue($invoice->isDraft());
        $this->assertFalse($invoice->isPosted());

        // Verifikasi LPB terkait telah ditandai is_invoiced
        $grn->refresh();
        $this->assertTrue((bool) $grn->is_invoiced);
        $this->assertEquals($invoice->id, $grn->purchase_invoice_id);

        // Verifikasi TIDAK ADA jurnal akuntansi yang terbit
        $journal = JournalEntry::where('reference_type', PurchaseInvoice::class)
            ->where('reference_id', $invoice->id)
            ->first();
        $this->assertNull($journal);
    }

    public function test_draft_invoice_is_excluded_from_payables_and_reports(): void
    {
        [$po, $grn] = $this->createPoAndGrn(10, 100000);

        $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grn->id,
            'supplier_invoice_number' => 'INV-DRAFT-01',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'tax_rate' => 11,
        ]);

        $invoice = PurchaseInvoice::where('purchase_order_id', $po->id)->first();
        $this->assertEquals(PurchaseInvoiceStatus::Draft, $invoice->status);

        // Cek Laporan Hutang (AP Aging)
        $apResponse = $this->actingAs($this->admin)->get(route('accounting.reports.payables'));
        $apResponse->assertOk();
        $openInvoices = $apResponse->viewData('openInvoices');
        $this->assertFalse($openInvoices->contains('id', $invoice->id));

        // Cek Laporan Tagihan Akan Jatuh Tempo
        $upcomingResponse = $this->actingAs($this->admin)->get(route('accounting.reports.payables-upcoming'));
        $upcomingResponse->assertOk();
        $upcomingInvoices = $upcomingResponse->viewData('invoices');
        $this->assertFalse($upcomingInvoices->contains('id', $invoice->id));

        // Cek Dashboard total hutang
        $dashResponse = $this->actingAs($this->admin)->get(route('dashboard'));
        $dashResponse->assertOk();
        $totalHutang = $dashResponse->viewData('totalHutang');
        $this->assertEquals(0, $totalHutang);
    }

    public function test_cannot_record_payment_for_draft_invoice(): void
    {
        [$po, $grn] = $this->createPoAndGrn(10, 100000);

        $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grn->id,
            'supplier_invoice_number' => 'INV-DRAFT-02',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'tax_rate' => 11,
        ]);

        $invoice = PurchaseInvoice::where('purchase_order_id', $po->id)->first();

        // Form create payment tidak menampilkan invoice draft
        $createPayResponse = $this->actingAs($this->admin)->get(route('purchase.payments.create'));
        $createPayResponse->assertOk();
        $unpaidInvoices = $createPayResponse->viewData('unpaidInvoices');
        $this->assertFalse($unpaidInvoices->contains('id', $invoice->id));

        // Submit pembayaran untuk draft invoice harus ditolak
        $cashCoa = ChartOfAccount::where('code', '1-1100')->first();
        $payResponse = $this->actingAs($this->admin)->post(route('purchase.payments.store'), [
            'purchase_invoice_id' => $invoice->id,
            'chart_of_account_id' => $cashCoa->id,
            'payment_type' => 'bank',
            'amount' => 500000,
            'payment_date' => now()->toDateString(),
            'method' => 'transfer',
        ]);

        $payResponse->assertSessionHas('error');
        $this->assertEquals(PurchaseInvoiceStatus::Draft, $invoice->fresh()->status);
    }

    public function test_cannot_return_goods_if_invoice_is_draft(): void
    {
        [$po, $grn, $poItem, $grnItem] = $this->createPoAndGrn(10, 100000);

        $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grn->id,
            'supplier_invoice_number' => 'INV-DRAFT-03',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'tax_rate' => 11,
        ]);

        $returnResponse = $this->actingAs($this->admin)->post(route('purchase.returns.store'), [
            'goods_receipt_id' => $grn->id,
            'return_date' => now()->toDateString(),
            'reason' => 'Barang cacat produksi',
            'items' => [
                [
                    'goods_receipt_item_id' => $grnItem->id,
                    'product_id' => $this->product->id,
                    'source_type' => 'accepted',
                    'qty' => 2,
                    'unit_cost' => 100000,
                    'notes' => 'Rusak',
                ]
            ],
        ]);

        $returnResponse->assertSessionHas('error');
    }

    public function test_edit_and_update_draft_invoice(): void
    {
        [$po, $grn] = $this->createPoAndGrn(10, 100000);

        $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grn->id,
            'supplier_invoice_number' => 'INV-ORIGINAL',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'tax_rate' => 11,
        ]);

        $invoice = PurchaseInvoice::where('purchase_order_id', $po->id)->first();

        // Edit page loads
        $editResponse = $this->actingAs($this->admin)->get(route('purchase.invoices.edit', $invoice));
        $editResponse->assertOk();

        // Update draft invoice
        $updateResponse = $this->actingAs($this->admin)->put(route('purchase.invoices.update', $invoice), [
            'supplier_invoice_number' => 'INV-UPDATED-99',
            'invoice_date' => now()->addDay()->toDateString(),
            'due_date' => now()->addDays(45)->toDateString(),
            'tax_rate' => 12,
            'notes' => 'Catatan diubah',
        ]);

        $updateResponse->assertRedirect(route('purchase.invoices.show', $invoice));
        $invoice->refresh();

        $this->assertEquals('INV-UPDATED-99', $invoice->supplier_invoice_number);
        $this->assertEquals(12, (float) $invoice->tax_rate);
        $this->assertEquals(120000, (float) $invoice->tax_amount);
        $this->assertEquals(1120000, (float) $invoice->total_amount);
        $this->assertEquals('Catatan diubah', $invoice->notes);
    }

    public function test_destroy_draft_invoice_releases_goods_receipt(): void
    {
        [$po, $grn] = $this->createPoAndGrn(10, 100000);

        $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grn->id,
            'supplier_invoice_number' => 'INV-TO-DELETE',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'tax_rate' => 11,
        ]);

        $invoice = PurchaseInvoice::where('purchase_order_id', $po->id)->first();
        $this->assertNotNull($invoice);

        $grn->refresh();
        $this->assertTrue((bool) $grn->is_invoiced);

        // Delete draft invoice
        $deleteResponse = $this->actingAs($this->admin)->delete(route('purchase.invoices.destroy', $invoice));
        $deleteResponse->assertRedirect(route('purchase.invoices.index'));

        // Invoice harus terhapus
        $this->assertNull(PurchaseInvoice::find($invoice->id));

        // LPB harus dilepaskan (is_invoiced = false, purchase_invoice_id = null)
        $grn->refresh();
        $this->assertFalse((bool) $grn->is_invoiced);
        $this->assertNull($grn->purchase_invoice_id);
    }

    public function test_post_draft_invoice_publishes_journal_and_sets_unpaid(): void
    {
        [$po, $grn] = $this->createPoAndGrn(10, 100000);

        $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grn->id,
            'supplier_invoice_number' => 'INV-POST-01',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'tax_rate' => 11,
        ]);

        $invoice = PurchaseInvoice::where('purchase_order_id', $po->id)->first();
        $this->assertEquals(PurchaseInvoiceStatus::Draft, $invoice->status);

        // Klik Posting Jurnal
        $postResponse = $this->actingAs($this->admin)->post(route('purchase.invoices.post', $invoice));
        $postResponse->assertRedirect(route('purchase.invoices.index'));

        $invoice->refresh();
        $this->assertEquals(PurchaseInvoiceStatus::Unpaid, $invoice->status);
        $this->assertTrue($invoice->isPosted());
        $this->assertFalse($invoice->isDraft());

        // Jurnal Akuntansi otomatis terbentuk dan berstatus posted
        $journal = JournalEntry::where('reference_type', PurchaseInvoice::class)
            ->where('reference_id', $invoice->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertEquals('posted', $journal->status);
        $this->assertEquals($invoice->total_amount, (float) $journal->total_debit);
        $this->assertEquals($invoice->total_amount, (float) $journal->total_credit);

        // Setelah diposting, invoice tidak dapat diedit atau dihapus
        $deleteResponse = $this->actingAs($this->admin)->delete(route('purchase.invoices.destroy', $invoice));
        $deleteResponse->assertRedirect(route('purchase.invoices.show', $invoice));
        $this->assertNotNull(PurchaseInvoice::find($invoice->id));

        $editResponse = $this->actingAs($this->admin)->get(route('purchase.invoices.edit', $invoice));
        $editResponse->assertRedirect(route('purchase.invoices.show', $invoice));
    }
}
