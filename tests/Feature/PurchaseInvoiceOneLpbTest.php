<?php

namespace Tests\Feature;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseInvoiceOneLpbTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Product $productA;
    protected Product $productB;
    protected Warehouse $warehouse;
    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\ChartOfAccountSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->supplier = Supplier::create([
            'code' => 'SUP-LPB-TEST',
            'name' => 'Supplier LPB Test',
            'is_active' => true,
        ]);
        $this->productA = Product::create([
            'sku' => 'LPB-PROD-A',
            'name' => 'Produk A',
            'unit' => 'pcs',
            'purchase_price' => 60,
            'sell_price' => 100,
            'is_active' => true,
        ]);
        $this->productB = Product::create([
            'sku' => 'LPB-PROD-B',
            'name' => 'Produk B',
            'unit' => 'pcs',
            'purchase_price' => 80,
            'sell_price' => 150,
            'is_active' => true,
        ]);
        $this->warehouse = Warehouse::create([
            'code' => 'WH-LPB',
            'name' => 'Gudang LPB Test',
            'is_active' => true,
        ]);
    }

    /**
     * Helper: buat PO → 2 LPB (masing-masing menerima qty tertentu)
     */
    private function makePoWithTwoGrns(int $qtyA = 5, int $qtyB = 5, float $headerDiscount = 0, float $taxRate = 0): array
    {
        $totalOrdered = $qtyA + $qtyB;
        $subtotal = $totalOrdered * 60; // unit_price = 60
        $dpp = $subtotal - $headerDiscount;
        $taxAmount = $dpp * ($taxRate / 100);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-LPB-' . uniqid(),
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->admin->id,
            'status' => 'done',
            'order_date' => now()->toDateString(),
            'discount_amount' => $headerDiscount,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_amount' => $dpp + $taxAmount,
        ]);
        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->productA->id,
            'qty_ordered' => $totalOrdered,
            'unit_price' => 60,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => $subtotal,
        ]);

        // LPB A: qtyA unit
        $grnA = GoodsReceipt::create([
            'receipt_number' => 'GRN-A-' . uniqid(),
            'purchase_order_id' => $po->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'qc_status' => 'passed',
            'received_date' => now()->toDateString(),
        ]);
        $grnItemA = GoodsReceiptItem::create([
            'goods_receipt_id' => $grnA->id,
            'purchase_order_item_id' => $poItem->id,
            'warehouse_id' => $this->warehouse->id,
            'qty_received' => $qtyA,
            'qty_rejected' => 0,
            'unit_cost' => 60,
        ]);

        // Stok masuk gudang dari LPB A
        StockMovement::create([
            'product_id' => $this->productA->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'in',
            'quantity' => $qtyA,
            'unit_cost' => 60,
            'movement_date' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        // LPB B: qtyB unit
        $grnB = GoodsReceipt::create([
            'receipt_number' => 'GRN-B-' . uniqid(),
            'purchase_order_id' => $po->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'qc_status' => 'passed',
            'received_date' => now()->toDateString(),
        ]);
        $grnItemB = GoodsReceiptItem::create([
            'goods_receipt_id' => $grnB->id,
            'purchase_order_item_id' => $poItem->id,
            'warehouse_id' => $this->warehouse->id,
            'qty_received' => $qtyB,
            'qty_rejected' => 0,
            'unit_cost' => 60,
        ]);

        // Stok masuk gudang dari LPB B
        StockMovement::create([
            'product_id' => $this->productA->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'in',
            'quantity' => $qtyB,
            'unit_cost' => 60,
            'movement_date' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        return [$po, $poItem, $grnA, $grnItemA, $grnB, $grnItemB];
    }

    /**
     * Test 1: 1 PO → 2 LPB (5+5) → Invoice dari LPB A → cek status flags
     */
    public function test_invoice_from_lpb_a_marks_it_invoiced_and_lpb_b_remains_available(): void
    {
        [$po, $poItem, $grnA, $grnItemA, $grnB, $grnItemB] = $this->makePoWithTwoGrns(5, 5);

        $response = $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grnA->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'tax_rate' => 0,
        ]);

        $response->assertRedirect(route('purchase.invoices.index'));

        // Verifikasi invoice terbit dengan qty penuh dari LPB A
        $invoice = PurchaseInvoice::with('items')->latest()->firstOrFail();
        $this->assertEquals($grnA->id, $invoice->goods_receipt_id);
        $this->assertEquals(5, $invoice->items->sum('qty_invoiced'));
        $this->assertEquals(300, $invoice->total_amount); // 5 × 60 = 300

        // LPB A sudah ditandai diinvoice
        $grnA->refresh();
        $this->assertTrue($grnA->is_invoiced);
        $this->assertEquals($invoice->id, $grnA->purchase_invoice_id);

        // LPB B masih tersedia
        $grnB->refresh();
        $this->assertFalse($grnB->is_invoiced);
        $this->assertNull($grnB->purchase_invoice_id);
    }

    /**
     * Test 2: Cegah pakai LPB yang sudah pernah diinvoice
     */
    public function test_cannot_create_invoice_from_already_invoiced_lpb(): void
    {
        [$po, $poItem, $grnA, $grnItemA, $grnB, $grnItemB] = $this->makePoWithTwoGrns(5, 5);

        // Invoice pertama dari LPB A — berhasil
        $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grnA->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'tax_rate' => 0,
        ]);

        $this->assertEquals(1, PurchaseInvoice::count());

        // Coba lagi dari LPB A yang sama — harus ditolak
        $response = $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grnA->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'tax_rate' => 0,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Masih cuma 1 invoice
        $this->assertEquals(1, PurchaseInvoice::count());
    }

    /**
     * Test 3: Race condition — 2 submit bersamaan untuk LPB yang sama, hanya 1 berhasil
     */
    public function test_race_condition_only_one_invoice_created_for_same_lpb(): void
    {
        [$po, $poItem, $grnA, $grnItemA, $grnB, $grnItemB] = $this->makePoWithTwoGrns(5, 5);

        // Simulasi: pertama berhasil
        $response1 = $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grnA->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'tax_rate' => 0,
        ]);

        $response1->assertRedirect(route('purchase.invoices.index'));

        // Simulasi: kedua ditolak karena LPB sudah ditandai
        $response2 = $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grnA->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'tax_rate' => 0,
        ]);

        $response2->assertRedirect();
        $response2->assertSessionHas('error');

        // Hanya 1 invoice yang tercipta
        $this->assertEquals(1, PurchaseInvoice::count());

        // Tapi LPB B masih bisa dipakai → buat invoice kedua
        $response3 = $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grnB->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'tax_rate' => 0,
        ]);

        $response3->assertRedirect(route('purchase.invoices.index'));
        $this->assertEquals(2, PurchaseInvoice::count());
    }

    /**
     * Test 4: Prorasi diskon header PO proporsional antara 2 LPB
     */
    public function test_header_discount_prorated_correctly_between_two_lpb_invoices(): void
    {
        // PO: 10 unit @ 60 = subtotal 600, header discount = 120, tax = 0%
        // LPB A: 5 unit → subtotal = 300, prorated discount = (300/600)*120 = 60 → DPP = 240
        // LPB B: 5 unit → subtotal = 300, prorated discount = (300/600)*120 = 60 → DPP = 240
        [$po, $poItem, $grnA, $grnItemA, $grnB, $grnItemB] = $this->makePoWithTwoGrns(5, 5, headerDiscount: 120);

        // Invoice dari LPB A
        $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grnA->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'tax_rate' => 0,
        ]);

        $invoiceA = PurchaseInvoice::latest()->firstOrFail();
        $this->assertEquals(240, $invoiceA->amount); // DPP = 300 - 60 = 240
        $this->assertEquals(240, $invoiceA->total_amount);

        // Invoice dari LPB B
        $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grnB->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'tax_rate' => 0,
        ]);

        $invoiceB = PurchaseInvoice::latest()->firstOrFail();
        $this->assertEquals(240, $invoiceB->amount);
        $this->assertEquals(240, $invoiceB->total_amount);

        // Total 2 invoice = 480 = 600 - 120 (diskon header)
        $total = PurchaseInvoice::sum('total_amount');
        $this->assertEquals(480, $total);
    }

    /**
     * Test 5: Data lama (invoice tanpa goods_receipt_id) tetap bisa dibuka normal
     */
    public function test_legacy_invoice_without_grn_reference_still_works(): void
    {
        [$po, $poItem, $grnA, $grnItemA, $grnB, $grnItemB] = $this->makePoWithTwoGrns(5, 5);

        // Simulasi data lama: invoice tanpa goods_receipt_id (pola Opsi A lama)
        $legacyInvoice = PurchaseInvoice::create([
            'invoice_number' => 'PINV-LEGACY-001',
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => null, // data lama tidak punya ini
            'supplier_invoice_number' => null,
            'amount' => 600,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => 600,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addMonth()->toDateString(),
            'status' => 'unpaid',
        ]);

        // Halaman show tetap bisa diakses tanpa error
        $response = $this->actingAs($this->admin)->get(route('purchase.invoices.show', $legacyInvoice));
        $response->assertOk();
        $response->assertSee('PINV-LEGACY-001');

        // Halaman index tetap bisa diakses
        $response = $this->actingAs($this->admin)->get(route('purchase.invoices.index'));
        $response->assertOk();
        $response->assertSee('PINV-LEGACY-001');
    }

    /**
     * Test 6: Retur setelah invoice baru → reversed_qty + outstanding_amount benar
     */
    public function test_return_after_new_invoice_updates_outstanding_correctly(): void
    {
        [$po, $poItem, $grnA, $grnItemA, $grnB, $grnItemB] = $this->makePoWithTwoGrns(10, 5);

        // Buat invoice dari LPB A (10 unit × 60 = 600)
        $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grnA->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'tax_rate' => 0,
        ]);

        $invoice = PurchaseInvoice::with('items')->latest()->firstOrFail();
        $this->assertEquals(600, $invoice->total_amount);
        $this->assertEquals(600, $invoice->outstanding_amount);

        // Retur 4 unit dari LPB A (barang accepted, sudah diinvoice)
        $return = PurchaseReturn::create([
            'return_number' => 'PRET-NEWRULE-001',
            'goods_receipt_id' => $grnA->id,
            'supplier_id' => $this->supplier->id,
            'return_date' => now()->toDateString(),
            'status' => 'sent',
        ]);
        PurchaseReturnItem::create([
            'purchase_return_id' => $return->id,
            'product_id' => $this->productA->id,
            'goods_receipt_item_id' => $grnItemA->id,
            'source_type' => 'accepted',
            'qty' => 4,
            'unit_cost' => 60,
        ]);

        // Complete retur → trigger journal reversal
        $response = $this->actingAs($this->admin)->patch(route('purchase.returns.complete', $return));
        $response->assertRedirect();

        $invoice->refresh()->load(['items', 'payments']);

        // reversed_qty = 4, reversed_amount = 4 × 60 = 240
        $this->assertEquals(240, $invoice->total_reversed_amount);
        $this->assertEquals(360, $invoice->effective_total_amount);
        $this->assertEquals(360, $invoice->outstanding_amount);
        $this->assertEquals('unpaid', $invoice->status);
    }

    /**
     * Test 7: Halaman create memuat seluruh LPB yang belum diinvoice tanpa butuh query param po_id
     */
    public function test_create_view_loads_all_uninvoiced_lpbs_without_query_params(): void
    {
        [$po, $poItem, $grnA, $grnItemA, $grnB, $grnItemB] = $this->makePoWithTwoGrns(5, 5);

        $response = $this->actingAs($this->admin)->get(route('purchase.invoices.create'));

        $response->assertOk();
        $response->assertViewHas('availableReceipts', function ($receipts) use ($grnA, $grnB) {
            return $receipts->contains('id', $grnA->id) && $receipts->contains('id', $grnB->id);
        });
        $response->assertSee($grnA->receipt_number);
        $response->assertSee($grnB->receipt_number);
    }

    /**
     * Test 8: Pembulatan diskon header yang tidak habis dibagi (misal Rp 100 dibagi 3 LPB).
     * Invoice 1: 33.33
     * Invoice 2: 33.33
     * Invoice 3 (terakhir): 33.34 (sisa pembulatan)
     * Total ketiga invoice: tepat Rp 100.00 dan total amount presisi 100%
     */
    public function test_header_discount_rounding_remainder_allocated_to_last_invoice(): void
    {
        $po = PurchaseOrder::create([
            'po_number' => 'PO-DISC-3LPB-' . uniqid(),
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->admin->id,
            'status' => 'done',
            'order_date' => now()->toDateString(),
            'discount_amount' => 100, // Diskon 100 tidak habis dibagi 3
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => 2900, // 3000 - 100
        ]);
        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->productA->id,
            'qty_ordered' => 30,
            'unit_price' => 100,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 3000,
        ]);

        // Buat 3 LPB @ 10 unit = subtotal 1000 masing-masing
        $grns = [];
        for ($i = 1; $i <= 3; $i++) {
            $grn = GoodsReceipt::create([
                'receipt_number' => 'GRN-ROUND-' . $i . '-' . uniqid(),
                'purchase_order_id' => $po->id,
                'warehouse_id' => $this->warehouse->id,
                'user_id' => $this->admin->id,
                'qc_status' => 'passed',
                'received_date' => now()->toDateString(),
            ]);
            GoodsReceiptItem::create([
                'goods_receipt_id' => $grn->id,
                'purchase_order_item_id' => $poItem->id,
                'warehouse_id' => $this->warehouse->id,
                'qty_received' => 10,
                'qty_rejected' => 0,
                'unit_cost' => 100,
            ]);
            $grns[] = $grn;
        }

        // Invoice 1 dari LPB 1
        $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grns[0]->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'tax_rate' => 0,
        ]);
        $inv1 = PurchaseInvoice::where('goods_receipt_id', $grns[0]->id)->firstOrFail();
        $this->assertEquals(967, $inv1->amount); // 1000 - 33 = 967

        // Invoice 2 dari LPB 2
        $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grns[1]->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'tax_rate' => 0,
        ]);
        $inv2 = PurchaseInvoice::where('goods_receipt_id', $grns[1]->id)->firstOrFail();
        $this->assertEquals(967, $inv2->amount); // 1000 - 33 = 967

        // Invoice 3 dari LPB 3 (LPB terakhir dari PO ini)
        $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grns[2]->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'tax_rate' => 0,
        ]);
        $inv3 = PurchaseInvoice::where('goods_receipt_id', $grns[2]->id)->firstOrFail();
        // LPB terakhir mengambil sisa pembulatan: 100 - (33 + 33) = 34
        // DPP = 1000 - 34 = 966
        $this->assertEquals(966, $inv3->amount);

        // Verifikasi total DPP dari ketiga invoice = 967 + 967 + 966 = tepat 2900.00
        $allInvoices = PurchaseInvoice::where('purchase_order_id', $po->id)->get();
        $this->assertEquals(2900, $allInvoices->sum('amount'));
        $this->assertEquals(2900, $allInvoices->sum('total_amount'));

        // Verifikasi total diskon yang diserap = (3000 - 2900) = tepat 100.00 tanpa ada sen yang hilang
        $totalDiscountUsed = $allInvoices->sum(function ($inv) {
            return 1000 - $inv->amount; // subtotal item LPB (1000) - DPP
        });
        $this->assertEquals(100, $totalDiscountUsed);
    }

    /**
     * Test 9: Pembulatan PPN yang terbagi ke 3 LPB (kasus nyata: PO Rp 291.000 + PPN 11% Rp 32.010 = Rp 323.010).
     * LPB 1: DPP 145.500, PPN 16.005
     * LPB 2: DPP 72.750, PPN 8.003 (dari 8002.5 dibulatkan ke atas)
     * LPB 3 (terakhir): DPP 72.750, PPN 8.002 (sisa pembulatan dari total PPN PO 32.010 - 24.008)
     * Total PPN ketiga invoice: tepat Rp 32.010 (bukan 32.011) dan total amount tepat Rp 323.010 sama persis dengan PO.
     */
    public function test_tax_rounding_remainder_allocated_to_last_invoice(): void
    {
        $po = PurchaseOrder::create([
            'po_number' => 'PO-TAX-ROUND-' . uniqid(),
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->admin->id,
            'status' => 'done',
            'order_date' => now()->toDateString(),
            'discount_amount' => 0,
            'tax_rate' => 11,
            'tax_amount' => 32010, // 291000 * 0.11 = 32010
            'total_amount' => 323010, // 291000 + 32010
        ]);
        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->productA->id,
            'qty_ordered' => 4,
            'unit_price' => 72750,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 291000,
        ]);

        // Buat 3 LPB: LPB 1 (2 unit = 145.500), LPB 2 (1 unit = 72.750), LPB 3 (1 unit = 72.750)
        $qtys = [2, 1, 1];
        $grns = [];
        foreach ($qtys as $i => $q) {
            $grn = GoodsReceipt::create([
                'receipt_number' => 'GRN-TAX-' . ($i + 1) . '-' . uniqid(),
                'purchase_order_id' => $po->id,
                'warehouse_id' => $this->warehouse->id,
                'user_id' => $this->admin->id,
                'qc_status' => 'passed',
                'received_date' => now()->toDateString(),
            ]);
            GoodsReceiptItem::create([
                'goods_receipt_id' => $grn->id,
                'purchase_order_item_id' => $poItem->id,
                'warehouse_id' => $this->warehouse->id,
                'qty_received' => $q,
                'qty_rejected' => 0,
                'unit_cost' => 72750,
            ]);
            $grns[] = $grn;
        }

        // Invoice 1 dari LPB 1 (2 unit)
        $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grns[0]->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'tax_rate' => 11,
        ]);
        $inv1 = PurchaseInvoice::where('goods_receipt_id', $grns[0]->id)->firstOrFail();
        $this->assertEquals(145500, $inv1->amount);
        $this->assertEquals(16005, $inv1->tax_amount);
        $this->assertEquals(161505, $inv1->total_amount);

        // Invoice 2 dari LPB 2 (1 unit)
        $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grns[1]->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'tax_rate' => 11,
        ]);
        $inv2 = PurchaseInvoice::where('goods_receipt_id', $grns[1]->id)->firstOrFail();
        $this->assertEquals(72750, $inv2->amount);
        $this->assertEquals(8003, $inv2->tax_amount);
        $this->assertEquals(80753, $inv2->total_amount);

        // Invoice 3 dari LPB 3 (1 unit - LPB terakhir)
        $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grns[2]->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'tax_rate' => 11,
        ]);
        $inv3 = PurchaseInvoice::where('goods_receipt_id', $grns[2]->id)->firstOrFail();
        $this->assertEquals(72750, $inv3->amount);
        // LPB terakhir: sisa PPN = 32.010 - (16.005 + 8.003) = 8.002
        $this->assertEquals(8002, $inv3->tax_amount);
        $this->assertEquals(80752, $inv3->total_amount);

        // Verifikasi total ketiga invoice pas 100% dengan PO
        $allInvoices = PurchaseInvoice::where('purchase_order_id', $po->id)->get();
        $this->assertEquals(291000, $allInvoices->sum('amount'));
        $this->assertEquals(32010, $allInvoices->sum('tax_amount')); // Tepat 32.010
        $this->assertEquals(323010, $allInvoices->sum('total_amount')); // Tepat 323.010 pas sama persis dengan PO!
    }
}
