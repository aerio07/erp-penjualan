<?php

namespace Tests\Feature;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Audit konsistensi keuangan alur PR → PO → LPB → IP:
 * - diskon item & diskon header
 * - penerimaan tidak lengkap (parsial, beberapa LPB)
 * - PPN & pembulatan rupiah (0 vs 2 desimal) harus sama antara PO, preview form, dan IP tersimpan.
 */
class PurchaseDiscountFlowConsistencyTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private Supplier $supplier;
    private Warehouse $warehouse;
    private Product $productA;
    private Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->supplier = Supplier::create([
            'code' => 'SUP-FLOW-CONSISTENT',
            'name' => 'Supplier Flow Consistency',
            'is_active' => true,
        ]);
        $this->warehouse = Warehouse::create([
            'code' => 'WH-FLOW',
            'name' => 'Gudang Flow',
            'is_active' => true,
        ]);
        $this->productA = Product::create([
            'sku' => 'FLOW-A',
            'name' => 'Produk Flow A',
            'unit' => 'pcs',
            'purchase_price' => 125000,
            'sell_price' => 150000,
            'is_active' => true,
        ]);
        $this->productB = Product::create([
            'sku' => 'FLOW-B',
            'name' => 'Produk Flow B',
            'unit' => 'pcs',
            'purchase_price' => 250000,
            'sell_price' => 300000,
            'is_active' => true,
        ]);

        $this->setupAccounts();
    }

    private function setupAccounts(): void
    {
        $accounts = [
            ['code' => '1-1400', 'name' => 'Persediaan Barang Dagang', 'type' => 'asset'],
            ['code' => '1-1500', 'name' => 'PPN Masukan', 'type' => 'asset'],
            ['code' => '2-1100', 'name' => 'Hutang Usaha', 'type' => 'liability'],
        ];

        foreach ($accounts as $acc) {
            \App\Models\ChartOfAccount::firstOrCreate(['code' => $acc['code']], [
                'name' => $acc['name'],
                'type' => $acc['type'],
                'is_active' => true,
            ]);
        }
    }

    private function createPr(array $items): PurchaseRequest
    {
        $pr = PurchaseRequest::create([
            'pr_number' => 'PR-FLOW-' . uniqid(),
            'user_id' => $this->admin->id,
            'warehouse_id' => $this->warehouse->id,
            'request_date' => now()->toDateString(),
            'required_date' => now()->addDays(7)->toDateString(),
            'status' => 'confirmed',
        ]);
        foreach ($items as $it) {
            PurchaseRequestItem::create([
                'purchase_request_id' => $pr->id,
                'product_id' => $it['product_id'],
                'qty' => $it['qty'],
            ]);
        }
        return $pr;
    }

    private function createPoViaRoute(array $items, float $discountHeader, float $taxRate): PurchaseOrder
    {
        $pr = $this->createPr(array_map(fn ($i) => ['product_id' => $i['product_id'], 'qty' => $i['qty_ordered']], $items));

        $this->actingAs($this->admin)->post(route('purchase.orders.store'), [
            'purchase_request_id' => $pr->id,
            'supplier_id' => $this->supplier->id,
            'order_date' => now()->toDateString(),
            'expected_date' => now()->addDays(5)->toDateString(),
            'tax_rate' => $taxRate,
            'discount_amount' => $discountHeader,
            'items' => $items,
        ]);

        return PurchaseOrder::where('purchase_request_id', $pr->id)->firstOrFail();
    }

    private function createLpb(PurchaseOrder $po, array $qtyByPoItemId, int $seq): GoodsReceipt
    {
        $grn = GoodsReceipt::create([
            'receipt_number' => 'LPB-FLOW-' . $seq . '-' . uniqid(),
            'purchase_order_id' => $po->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'qc_status' => 'passed',
            'received_date' => now()->toDateString(),
        ]);
        foreach ($qtyByPoItemId as $poItemId => $qty) {
            if ($qty <= 0) continue;
            GoodsReceiptItem::create([
                'goods_receipt_id' => $grn->id,
                'purchase_order_item_id' => $poItemId,
                'warehouse_id' => $this->warehouse->id,
                'qty_received' => $qty,
                'qty_rejected' => 0,
                'unit_cost' => $po->items->firstWhere('id', $poItemId)->unit_price,
            ]);
        }
        return $grn;
    }

    private function invoiceLpb(PurchaseOrder $po, GoodsReceipt $grn, float $taxRate): PurchaseInvoice
    {
        $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $grn->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'tax_rate' => $taxRate,
        ]);

        return PurchaseInvoice::where('goods_receipt_id', $grn->id)->firstOrFail();
    }

    /**
     * Skenario lengkap: 2 item (diskon item 5% di A), diskon header 250.000, PPN 11%,
     * penerimaan PARSIAL lewat 2 LPB. Setiap IP harus kembali bulat & total akhir
     * persis sama dengan total PO (tanpa sen hilang/lebih).
     */
    public function test_partial_receipt_chain_reconciles_exactly_to_po_totals(): void
    {
        $items = [
            [
                'product_id' => $this->productA->id,
                'qty_ordered' => 100,
                'unit_price' => 125000,
                'discount_percent' => 5,
            ],
            [
                'product_id' => $this->productB->id,
                'qty_ordered' => 20,
                'unit_price' => 250000,
                'discount_percent' => 0,
            ],
        ];

        // PO (lewat route/controller, mengikuti rumus backend):
        // A: 100 × 125.000 × 95% = 11.875.000
        // B: 20 × 250.000 = 5.000.000
        // Subtotal net = 16.875.000; Diskon header 250.000 → DPP 16.625.000
        // PPN 11% = 1.828.750; Total = 18.453.750
        $po = $this->createPoViaRoute($items, 250000, 11);

        $this->assertEquals(1828750, (float) $po->tax_amount);
        $this->assertEquals(18453750, (float) $po->total_amount);

        $poItemA = $po->items->where('product_id', $this->productA->id)->first();
        $poItemB = $po->items->where('product_id', $this->productB->id)->first();

        // LPB 1: A 60 pcs + B 20 pcs (parsial; B sudah lengkap, A belum)
        $grn1 = $this->createLpb($po, [$poItemA->id => 60, $poItemB->id => 20], 1);
        // LPB 2: sisa A 40 pcs (LPB terakhir dari sisi kuantitas)
        $grn2 = $this->createLpb($po, [$poItemA->id => 40, $poItemB->id => 0], 2);

        // Invoice LPB 1 (bukan terakhir → prorata diskon header)
        // subtotal grn = 7.125.000 + 5.000.000 = 12.125.000
        // prorasi diskon = 12.125.000/16.875.000 × 250.000 = 179.629,63 → 179.630
        // DPP = 11.945.370; PPN = 11.945.370 × 11% = 1.313.990,7 → 1.313.991
        $inv1 = $this->invoiceLpb($po, $grn1, 11);
        $this->assertEquals(11945370, (float) $inv1->amount);
        $this->assertEquals(1313991, (float) $inv1->tax_amount);
        $this->assertEquals(13259361, (float) $inv1->total_amount);

        // Invoice LPB 2 (terakhir → menyerap sisa diskon & PPN)
        // subtotal grn = 4.750.000; diskon sisa = 250.000 - 179.630 = 70.370
        // DPP = 4.679.630; PPN = 1.828.750 - 1.313.991 = 514.759
        $inv2 = $this->invoiceLpb($po, $grn2, 11);
        $this->assertEquals(4679630, (float) $inv2->amount);
        $this->assertEquals(514759, (float) $inv2->tax_amount);
        $this->assertEquals(5194389, (float) $inv2->total_amount);

        // Invariant: jumlah seluruh IP = total PO persis
        $invoices = PurchaseInvoice::where('purchase_order_id', $po->id)->get();
        $this->assertEquals(16625000, $invoices->sum('amount'));
        $this->assertEquals(1828750, $invoices->sum('tax_amount'));
        $this->assertEquals(18453750, $invoices->sum('total_amount'));
    }

    /**
     * Kasus angka "berantakan": DPP 12.333.333 × PPN 11% = 1.356.666,63.
     * PO harus membulatkan PPN ke rupiah penuh (1.356.667 / total 13.690.000),
     * lalu seluruh IP (penerimaan parsial 1 + 2 pcs) jumlahnya harus sama persis.
     */
    public function test_fractional_vat_is_rounded_to_full_rupiah_and_invoices_reconcile(): void
    {
        $items = [[
            'product_id' => $this->productA->id,
            'qty_ordered' => 3,
            'unit_price' => 4111111,
            'discount_percent' => 0,
        ]];

        // Subtotal 12.333.333; PPN 11% = 1.356.666,63 → disimpan 1.356.667; total 13.690.000
        $po = $this->createPoViaRoute($items, 0, 11);
        $this->assertEquals(1356667, (float) $po->tax_amount);
        $this->assertEquals(13690000, (float) $po->total_amount);

        $poItem = $po->items->first();

        // Penerimaan parsial: 1 pcs lalu 2 pcs
        $grn1 = $this->createLpb($po, [$poItem->id => 1], 1);
        $grn2 = $this->createLpb($po, [$poItem->id => 2], 2);

        // IP 1: DPP 4.111.111; PPN = 452.222,21 → 452.222; total 4.563.333
        $inv1 = $this->invoiceLpb($po, $grn1, 11);
        $this->assertEquals(4111111, (float) $inv1->amount);
        $this->assertEquals(452222, (float) $inv1->tax_amount);
        $this->assertEquals(4563333, (float) $inv1->total_amount);

        // IP 2 (terakhir): DPP 8.222.222; PPN menyerap sisa = 1.356.667 - 452.222 = 904.445
        $inv2 = $this->invoiceLpb($po, $grn2, 11);
        $this->assertEquals(8222222, (float) $inv2->amount);
        $this->assertEquals(904445, (float) $inv2->tax_amount);
        $this->assertEquals(9126667, (float) $inv2->total_amount);

        // Invariant
        $invoices = PurchaseInvoice::where('purchase_order_id', $po->id)->get();
        $this->assertEquals(12333333, $invoices->sum('amount'));
        $this->assertEquals(1356667, $invoices->sum('tax_amount'));
        $this->assertEquals(13690000, $invoices->sum('total_amount'));
    }

    /**
     * Regresi UI: subtotal per baris di halaman detail/PDF harus menampilkan nilai
     * murni qty × harga × (1 − diskon item), bukan nilai tersimpan yang sudah
     * terpotong sisa diskon header (diskon header tampil sebagai baris terpisah).
     * Contoh user: 2 × 6.500.000 − 5% = 12.350.000 (bukan 12.333.333).
     */
    public function test_show_and_pdf_display_pure_line_subtotal_with_separate_header_discount(): void
    {
        // 2 × 6.500.000 = 13.000.000; diskon item 5% = 650.000 → subtotal murni 12.350.000
        // Diskon header 16.667 → DPP 12.333.333; invoice TUNGGAL menyerap semua → item.subtotal tersimpan = 12.333.333
        $items = [[
            'product_id' => $this->productA->id,
            'qty_ordered' => 2,
            'unit_price' => 6500000,
            'discount_percent' => 5,
        ]];

        $po = $this->createPoViaRoute($items, 16667, 11);
        $poItem = $po->items->first();

        $grn = $this->createLpb($po, [$poItem->id => 2], 1);
        $inv = $this->invoiceLpb($po, $grn, 11);

        // Nilai tersimpan memang tetap terpotong (biar jumlah pas), ini yang dulu
        // ikut tampil di view sehingga terlihat "berubah".
        $storedItem = $inv->items->first();
        $this->assertEquals(12333333, (float) $storedItem->subtotal);
        $this->assertEquals(12333333, (float) $inv->amount);

        // Halaman detail: subtotal baris murni 12.350.000 + baris Diskon Header terpisah
        $response = $this->actingAs($this->admin)->get(route('purchase.invoices.show', $inv));
        $response->assertOk();
        $response->assertSee('Rp 12.350.000');
        $response->assertSee('Diskon Header');
        $response->assertSee('Rp 16.667');

        // PDF hanya untuk invoice ter-posting → posting draft dulu
        $this->actingAs($this->admin)->post(route('purchase.invoices.post', $inv));
        $inv->refresh();
        $this->assertTrue($inv->isPosted());

        $pdf = $this->actingAs($this->admin)->get(route('pdf.purchase-invoice', $inv));
        $pdf->assertOk();
    }
}