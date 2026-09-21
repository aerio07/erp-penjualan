<?php

namespace Tests\Feature;

use App\Enums\PurchaseReturnStatus;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PurchaseReturnAutoJournalTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private Supplier $supplier;
    private Product $product;
    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->supplier = Supplier::create([
            'code' => 'SUP-RET-JRN',
            'name' => 'Supplier Retur Jurnal',
            'is_active' => true,
        ]);
        $this->product = Product::create([
            'sku' => 'PROD-RET-JRN',
            'name' => 'Produk Retur Jurnal',
            'category' => 'General',
            'unit' => 'pcs',
            'purchase_price' => 50000,
            'sell_price' => 75000,
            'is_active' => true,
        ]);
        $this->warehouse = Warehouse::create([
            'code' => 'WH-RET-JRN',
            'name' => 'Gudang Retur Jurnal',
            'is_active' => true,
        ]);

        (new \Database\Seeders\ChartOfAccountSeeder)->run();
    }

    public function test_completing_purchase_return_creates_posted_journal_entry(): void
    {
        // 1. PO: 10 pcs @ 50.000 = 500.000 (PPN 11% = 55.000, Total = 555.000)
        $po = PurchaseOrder::create([
            'po_number' => 'PO-RET-JRN-01',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'status' => 'confirmed',
            'order_date' => now()->toDateString(),
            'tax_rate' => 11,
            'tax_amount' => 55000,
            'total_amount' => 555000,
        ]);
        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'unit_price' => 50000,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 500000,
        ]);

        // 2. LPB: Diterima 10 pcs kondisi baik
        $grn = GoodsReceipt::create([
            'receipt_number' => 'LPB-RET-JRN-01',
            'purchase_order_id' => $po->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'qc_status' => 'passed',
            'received_date' => now()->toDateString(),
        ]);
        $grnItem = GoodsReceiptItem::create([
            'goods_receipt_id' => $grn->id,
            'purchase_order_item_id' => $poItem->id,
            'warehouse_id' => $this->warehouse->id,
            'qty_received' => 10,
            'qty_rejected' => 0,
            'unit_cost' => 50000,
            'condition' => 'Good',
        ]);

        // Catat stok masuk awal agar stok fisik di gudang mencukupi saat diretur
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'in',
            'quantity' => 10,
            'unit_cost' => 50000,
            'reference_type' => GoodsReceipt::class,
            'reference_id' => $grn->id,
            'movement_date' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        // 3. Purchase Invoice diterbitkan via store
        $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grn->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'tax_rate' => 11,
        ]);

        $invoice = PurchaseInvoice::where('purchase_order_id', $po->id)->first();
        $this->assertNotNull($invoice);

        // 4. Buat Draft Retur Pembelian: 4 pcs barang diterima
        $return = PurchaseReturn::create([
            'return_number' => 'PRET-JRN-01',
            'goods_receipt_id' => $grn->id,
            'supplier_id' => $this->supplier->id,
            'return_date' => now()->toDateString(),
            'status' => 'sent',
        ]);
        PurchaseReturnItem::create([
            'purchase_return_id' => $return->id,
            'product_id' => $this->product->id,
            'goods_receipt_item_id' => $grnItem->id,
            'source_type' => 'accepted',
            'qty' => 4,
            'unit_cost' => 50000,
        ]);

        // 5. Complete Retur Pembelian
        $response = $this->actingAs($this->admin)->patch(route('purchase.returns.complete', $return));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $return->refresh();
        $this->assertEquals(PurchaseReturnStatus::Completed, $return->status);

        // 6. Verifikasi JURNAL OTOMATIS: Harus terbuat dan statusnya langsung 'posted' (bukan draft!)
        $journal = JournalEntry::where('reference_type', PurchaseReturn::class)
            ->where('reference_id', $return->id)
            ->with('lines')
            ->first();

        $this->assertNotNull($journal, 'Jurnal Retur Pembelian harus terbuat.');
        $this->assertEquals('posted', $journal->status, 'Status Jurnal Retur Pembelian harus posted (bukan draft).');
        $this->assertNotNull($journal->posted_at);

        // Pastikan debit dan kredit balance
        $totalDebit = $journal->lines->sum('debit');
        $totalCredit = $journal->lines->sum('credit');
        $this->assertEquals($totalDebit, $totalCredit);
        $this->assertGreaterThan(0, $totalDebit);
    }
}
