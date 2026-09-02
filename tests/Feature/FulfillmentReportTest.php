<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchasePayment;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesPayment;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FulfillmentReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Customer $customer;
    protected Supplier $supplier;
    protected Warehouse $warehouse;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\ChartOfAccountSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->customer = Customer::create([
            'code' => 'CUST-FULFILL-' . uniqid(),
            'name' => 'PT Pelanggan Monitoring Sejati',
            'is_active' => true,
        ]);
        $this->supplier = Supplier::create([
            'code' => 'SUPP-FULFILL-' . uniqid(),
            'name' => 'PT Pemasok Mitra Utama',
            'is_active' => true,
        ]);
        $this->warehouse = Warehouse::create([
            'code' => 'WH-FULFILL-' . uniqid(),
            'name' => 'Gudang Utama Monitoring',
            'is_active' => true,
        ]);
        $this->product = Product::create([
            'sku' => 'SKU-FULFILL-' . uniqid(),
            'name' => 'Barang Monitoring Komputer',
            'unit' => 'unit',
            'purchase_price' => 50000,
            'sell_price' => 100000,
            'is_active' => true,
        ]);
    }

    /**
     * Skenario 1: Verifikasi konsistensi data Sales Order Monitoring (1 SO, 2 SJ, 1 Invoice).
     * SO: 10 unit.
     * SJ 1: 5 unit, SJ 2: 5 unit. Total SJ = 10 unit.
     * Invoice baru terbit dari SJ 1 (5 unit).
     * Laporan harus menampilkan: Qty Pesan = 10, Qty SJ = 10, Qty Invoice = 5, Total Tagihan = Rp 555.000.
     */
    public function test_sales_fulfillment_report_tracks_deliveries_and_invoices_accurately(): void
    {
        $so = SalesOrder::create([
            'so_number' => 'SO-MONITOR-01',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'confirmed',
            'order_date' => now()->toDateString(),
            'tax_rate' => 11,
            'tax_amount' => 110000,
            'total_amount' => 1110000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'unit_price' => 100000,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 1000000,
        ]);

        // SJ 1 (5 unit)
        $del1 = Delivery::create([
            'delivery_number' => 'SJ-MONITOR-01A',
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'condition_status' => 'baik',
            'delivery_date' => now()->toDateString(),
        ]);
        DeliveryItem::create([
            'delivery_id' => $del1->id,
            'sales_order_item_id' => $soItem->id,
            'qty_delivered' => 5,
        ]);

        // SJ 2 (5 unit)
        $del2 = Delivery::create([
            'delivery_number' => 'SJ-MONITOR-01B',
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'condition_status' => 'baik',
            'delivery_date' => now()->toDateString(),
        ]);
        DeliveryItem::create([
            'delivery_id' => $del2->id,
            'sales_order_item_id' => $soItem->id,
            'qty_delivered' => 5,
        ]);

        // Terbitkan invoice HANYA dari SJ 1 (5 unit)
        $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $so->id,
            'delivery_id'    => $del1->id,
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addMonth()->toDateString(),
            'tax_rate'       => 11,
        ]);

        $response = $this->actingAs($this->admin)->get(route('sales.reports.fulfillment'));
        $response->assertOk();
        $response->assertSee('SO-MONITOR-01');
        $response->assertSee($this->customer->name);

        // Ambil data order dari view
        $orders = $response->viewData('orders');
        $this->assertEquals(1, $orders->total());
        $reportedOrder = $orders->first();

        // 1. Qty Pesan = 10
        $this->assertEquals(10, $reportedOrder->qty_ordered_sum);
        // 2. Qty SJ = 10 (dari SJ 1 + SJ 2)
        $this->assertEquals(10, $reportedOrder->qty_delivered_sum);
        // 3. Qty Invoice = 5 (hanya dari SJ 1)
        $this->assertEquals(5, $reportedOrder->qty_invoiced_sum);
        // 4. Total Invoice = 555.000 (5 * 100.000 + 11%)
        $this->assertEquals(555000, $reportedOrder->total_invoice_sum);
        // 5. Belum Dibayar = 0
        $this->assertEquals(0, $reportedOrder->total_paid_sum);
        // 6. Sisa Tagihan = 555.000
        $this->assertEquals(555000, $reportedOrder->remaining_balance);
        // 7. Status Bayar = Belum Dibayar
        $this->assertEquals('Belum Dibayar', $reportedOrder->payment_status_label);
    }

    /**
     * Skenario 2: Verifikasi siklus status bayar: Belum Dibayar -> Sebagian -> Lunas.
     */
    public function test_payment_status_transitions_automatically_on_payments(): void
    {
        $so = SalesOrder::create([
            'so_number' => 'SO-PAY-CYCLE-01',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'confirmed',
            'order_date' => now()->toDateString(),
            'tax_rate' => 0,
            'total_amount' => 1000000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'unit_price' => 100000,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 1000000,
        ]);
        $del = Delivery::create([
            'delivery_number' => 'SJ-PAY-CYCLE-01',
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'condition_status' => 'baik',
            'delivery_date' => now()->toDateString(),
        ]);
        DeliveryItem::create([
            'delivery_id' => $del->id,
            'sales_order_item_id' => $soItem->id,
            'qty_delivered' => 10,
        ]);

        // 1. Sebelum ada invoice -> status "Belum Ada Invoice"
        $res1 = $this->actingAs($this->admin)->get(route('sales.reports.fulfillment'));
        $order1 = $res1->viewData('orders')->first();
        $this->assertEquals('Belum Ada Invoice', $order1->payment_status_label);

        // 2. Terbitkan invoice Rp 1.000.000 -> status "Belum Dibayar"
        $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $so->id,
            'delivery_id'    => $del->id,
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addMonth()->toDateString(),
            'tax_rate'       => 0,
        ]);
        $res2 = $this->actingAs($this->admin)->get(route('sales.reports.fulfillment'));
        $order2 = $res2->viewData('orders')->first();
        $this->assertEquals('Belum Dibayar', $order2->payment_status_label);
        $this->assertEquals(1000000, $order2->remaining_balance);

        $invoice = SalesInvoice::where('sales_order_id', $so->id)->firstOrFail();

        // 3. Bayar sebagian (Rp 400.000) -> status "Sebagian"
        SalesPayment::create([
            'sales_invoice_id' => $invoice->id,
            'customer_id'      => $this->customer->id,
            'user_id'          => $this->admin->id,
            'payment_date'     => now()->toDateString(),
            'amount'           => 400000,
            'payment_method'   => 'bank_transfer',
            'reference_number' => 'PAY-PARTIAL-01',
        ]);
        $res3 = $this->actingAs($this->admin)->get(route('sales.reports.fulfillment'));
        $order3 = $res3->viewData('orders')->first();
        $this->assertEquals('Sebagian', $order3->payment_status_label);
        $this->assertEquals(400000, $order3->total_paid_sum);
        $this->assertEquals(600000, $order3->remaining_balance);

        // 4. Bayar sisa pelunasan (Rp 600.000) -> status "Lunas"
        SalesPayment::create([
            'sales_invoice_id' => $invoice->id,
            'customer_id'      => $this->customer->id,
            'user_id'          => $this->admin->id,
            'payment_date'     => now()->toDateString(),
            'amount'           => 600000,
            'payment_method'   => 'bank_transfer',
            'reference_number' => 'PAY-SETTLE-01',
        ]);
        $res4 = $this->actingAs($this->admin)->get(route('sales.reports.fulfillment'));
        $order4 = $res4->viewData('orders')->first();
        $this->assertEquals('Lunas', $order4->payment_status_label);
        $this->assertEquals(1000000, $order4->total_paid_sum);
        $this->assertEquals(0, $order4->remaining_balance);
    }

    /**
     * Skenario 3: Verifikasi konsistensi data Purchase Order Monitoring (1 PO, 2 LPB, 1 Invoice).
     * PO: 10 unit.
     * LPB 1: 5 unit, LPB 2: 5 unit. Total LPB = 10 unit.
     * Invoice baru terbit dari LPB 1 (5 unit).
     * Laporan harus menampilkan: Qty Pesan = 10, Qty LPB = 10, Qty Invoice = 5, Total Tagihan = Rp 277.500.
     */
    public function test_purchase_fulfillment_report_tracks_receipts_and_invoices_accurately(): void
    {
        $po = PurchaseOrder::create([
            'po_number' => 'PO-MONITOR-01',
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

        // LPB 1 (5 unit)
        $grn1 = GoodsReceipt::create([
            'receipt_number' => 'LPB-MONITOR-01A',
            'purchase_order_id' => $po->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'received_date' => now()->toDateString(),
        ]);
        GoodsReceiptItem::create([
            'goods_receipt_id' => $grn1->id,
            'purchase_order_item_id' => $poItem->id,
            'qty_received' => 5,
            'qty_rejected' => 0,
            'unit_cost' => 50000,
        ]);

        // LPB 2 (5 unit)
        $grn2 = GoodsReceipt::create([
            'receipt_number' => 'LPB-MONITOR-01B',
            'purchase_order_id' => $po->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'received_date' => now()->toDateString(),
        ]);
        GoodsReceiptItem::create([
            'goods_receipt_id' => $grn2->id,
            'purchase_order_item_id' => $poItem->id,
            'qty_received' => 5,
            'qty_rejected' => 0,
            'unit_cost' => 50000,
        ]);

        // Terbitkan invoice HANYA dari LPB 1 (5 unit)
        $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id'  => $grn1->id,
            'invoice_date'      => now()->toDateString(),
            'due_date'          => now()->addMonth()->toDateString(),
            'tax_rate'          => 11,
        ]);

        $response = $this->actingAs($this->admin)->get(route('purchase.reports.fulfillment'));
        $response->assertOk();
        $response->assertSee('PO-MONITOR-01');
        $response->assertSee($this->supplier->name);

        $orders = $response->viewData('orders');
        $this->assertEquals(1, $orders->total());
        $reportedPo = $orders->first();

        // 1. Qty Pesan = 10
        $this->assertEquals(10, $reportedPo->qty_ordered_sum);
        // 2. Qty LPB = 10 (dari LPB 1 + LPB 2)
        $this->assertEquals(10, $reportedPo->qty_received_sum);
        // 3. Qty Invoice = 5 (hanya dari LPB 1)
        $this->assertEquals(5, $reportedPo->qty_invoiced_sum);
        // 4. Total Invoice = 277.500 (5 * 50.000 + 11%)
        $this->assertEquals(277500, $reportedPo->total_invoice_sum);
        // 5. Sisa Hutang = 277.500
        $this->assertEquals(277500, $reportedPo->remaining_balance);
        // 6. Status Bayar = Belum Dibayar
        $this->assertEquals('Belum Dibayar', $reportedPo->payment_status_label);
    }

    /**
     * Skenario 4: Kinerja & Proteksi N+1 Query.
     * Query count harus konstan (tidak bertambah secara linear dengan jumlah SO di halaman).
     */
    public function test_reports_have_constant_query_count_without_n_plus_one(): void
    {
        // Buat 5 Sales Order dengan relasi lengkap
        for ($i = 1; $i <= 5; $i++) {
            $so = SalesOrder::create([
                'so_number' => 'SO-N1-TEST-' . $i,
                'customer_id' => $this->customer->id,
                'user_id' => $this->admin->id,
                'status' => 'confirmed',
                'order_date' => now()->toDateString(),
                'tax_rate' => 0,
                'total_amount' => 100000,
            ]);
            $soItem = SalesOrderItem::create([
                'sales_order_id' => $so->id,
                'product_id' => $this->product->id,
                'qty_ordered' => 1,
                'unit_price' => 100000,
                'subtotal' => 100000,
            ]);
            $del = Delivery::create([
                'delivery_number' => 'SJ-N1-' . $i,
                'sales_order_id' => $so->id,
                'warehouse_id' => $this->warehouse->id,
                'user_id' => $this->admin->id,
                'condition_status' => 'baik',
                'delivery_date' => now()->toDateString(),
            ]);
            DeliveryItem::create([
                'delivery_id' => $del->id,
                'sales_order_item_id' => $soItem->id,
                'qty_delivered' => 1,
            ]);
        }

        // Render dengan 1 SO
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->admin)->get(route('sales.reports.fulfillment', ['per_page' => 1]));
        $queryCount1 = count(DB::getQueryLog());

        // Render dengan 5 SO
        DB::flushQueryLog();
        $this->actingAs($this->admin)->get(route('sales.reports.fulfillment', ['per_page' => 5]));
        $queryCount5 = count(DB::getQueryLog());

        // Jumlah query harus identik antara 1 SO dan 5 SO (bukti pasti ZERO N+1)
        $this->assertEquals($queryCount1, $queryCount5, "Query count with 1 SO ({$queryCount1}) and 5 SOs ({$queryCount5}) must be identical, proving zero N+1.");
    }

    /**
     * Skenario 5: Filter dan Pencarian Berjalan Akurat.
     */
    public function test_filtering_and_search_in_fulfillment_reports(): void
    {
        $soA = SalesOrder::create([
            'so_number' => 'SO-FILTER-AAA',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'confirmed',
            'order_date' => '2026-08-01',
            'tax_rate' => 0,
            'total_amount' => 100000,
        ]);
        SalesOrderItem::create([
            'sales_order_id' => $soA->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 1,
            'unit_price' => 100000,
            'subtotal' => 100000,
        ]);

        $soB = SalesOrder::create([
            'so_number' => 'SO-FILTER-BBB',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'done',
            'order_date' => '2026-09-01',
            'tax_rate' => 0,
            'total_amount' => 200000,
        ]);
        SalesOrderItem::create([
            'sales_order_id' => $soB->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 2,
            'unit_price' => 100000,
            'subtotal' => 200000,
        ]);

        // 1. Filter status = done
        $resDone = $this->actingAs($this->admin)->get(route('sales.reports.fulfillment', ['status' => 'done']));
        $resDone->assertSee('SO-FILTER-BBB');
        $resDone->assertDontSee('SO-FILTER-AAA');

        // 2. Search keyword = AAA
        $resSearch = $this->actingAs($this->admin)->get(route('sales.reports.fulfillment', ['q' => 'AAA']));
        $resSearch->assertSee('SO-FILTER-AAA');
        $resSearch->assertDontSee('SO-FILTER-BBB');

        // 3. Date range filter
        $resDate = $this->actingAs($this->admin)->get(route('sales.reports.fulfillment', [
            'date_from' => '2026-08-20',
            'date_to'   => '2026-09-05',
        ]));
        $resDate->assertSee('SO-FILTER-BBB');
        $resDate->assertDontSee('SO-FILTER-AAA');
    }

    /**
     * Skenario 6: Verifikasi Retur Pembelian otomatis memperbarui monitoring PO.
     * PO 10 unit @ 50.000 (Total 500.000).
     * LPB 10 unit, Invoice 10 unit (Total 500.000).
     * Retur Pembelian 2 unit completed (Nilai retur 100.000).
     * Laporan harus update:
     * - Net Qty Received = 8 (Retur = 2)
     * - Net Qty Invoiced = 8 (Reversed = 2)
     * - Effective Total Invoice = Rp 400.000
     * - Sisa Hutang = Rp 400.000
     * - Bayar Rp 400.000 -> Status Lunas.
     */
    public function test_purchase_fulfillment_report_adjusts_for_returns_and_reversed_invoices(): void
    {
        // Beri stok awal agar bisa diretur
        \App\Models\StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'in',
            'quantity' => 10,
            'unit_cost' => 50000,
            'movement_date' => now()->toDateString(),
            'notes' => 'Initial Stock',
            'user_id' => $this->admin->id,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-RET-TEST-01',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'status' => 'confirmed',
            'order_date' => now()->toDateString(),
            'tax_rate' => 0,
            'total_amount' => 500000,
        ]);
        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'unit_price' => 50000,
            'subtotal' => 500000,
        ]);
        $grn = GoodsReceipt::create([
            'receipt_number' => 'LPB-RET-TEST-01',
            'purchase_order_id' => $po->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'received_date' => now()->toDateString(),
        ]);
        $grnItem = GoodsReceiptItem::create([
            'goods_receipt_id' => $grn->id,
            'purchase_order_item_id' => $poItem->id,
            'qty_received' => 10,
            'qty_rejected' => 0,
            'unit_cost' => 50000,
        ]);

        // Invoice diterbitkan
        $this->actingAs($this->admin)->post(route('purchase.invoices.store'), [
            'purchase_order_id' => $po->id,
            'goods_receipt_id'  => $grn->id,
            'invoice_date'      => now()->toDateString(),
            'due_date'          => now()->addMonth()->toDateString(),
            'tax_rate'          => 0,
        ]);

        // Buat dan selesaikan Retur Pembelian 2 unit
        $pRet = \App\Models\PurchaseReturn::create([
            'return_number' => 'RET-P-TEST-01',
            'goods_receipt_id' => $grn->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'draft',
            'return_date' => now()->toDateString(),
        ]);
        \App\Models\PurchaseReturnItem::create([
            'purchase_return_id' => $pRet->id,
            'goods_receipt_item_id' => $grnItem->id,
            'product_id' => $this->product->id,
            'source_type' => 'accepted',
            'qty' => 2,
            'unit_cost' => 50000,
        ]);

        $this->actingAs($this->admin)->patch(route('purchase.returns.complete', $pRet));

        // Cek Laporan Monitoring PO
        $response = $this->actingAs($this->admin)->get(route('purchase.reports.fulfillment', ['q' => 'PO-RET-TEST-01']));
        $response->assertOk();

        $reportedPo = $response->viewData('orders')->first();
        $this->assertEquals(10, $reportedPo->qty_ordered_sum);
        $this->assertEquals(10, $reportedPo->qty_received_sum);
        $this->assertEquals(2, $reportedPo->qty_returned_sum);
        $this->assertEquals(8, $reportedPo->net_qty_received);
        $this->assertEquals(10, $reportedPo->qty_invoiced_sum);
        $this->assertEquals(2, $reportedPo->qty_reversed_sum);
        $this->assertEquals(8, $reportedPo->net_qty_invoiced);
        $this->assertEquals(500000, $reportedPo->total_invoice_sum);
        $this->assertEquals(100000, $reportedPo->total_reversed_amount_sum);
        $this->assertEquals(400000, $reportedPo->effective_total_invoice);
        $this->assertEquals(400000, $reportedPo->remaining_balance);
        $this->assertEquals('Belum Dibayar', $reportedPo->payment_status_label);

        // Lakukan pembayaran lunas Rp 400.000
        $invoice = PurchaseInvoice::where('purchase_order_id', $po->id)->firstOrFail();
        PurchasePayment::create([
            'purchase_invoice_id' => $invoice->id,
            'supplier_id'         => $this->supplier->id,
            'user_id'             => $this->admin->id,
            'payment_date'        => now()->toDateString(),
            'amount'              => 400000,
            'payment_method'      => 'bank_transfer',
            'reference_number'    => 'PAY-PO-RET-01',
        ]);

        $resPaid = $this->actingAs($this->admin)->get(route('purchase.reports.fulfillment', ['q' => 'PO-RET-TEST-01']));
        $reportedPaid = $resPaid->viewData('orders')->first();
        $this->assertEquals(400000, $reportedPaid->total_paid_sum);
        $this->assertEquals(0, $reportedPaid->remaining_balance);
        $this->assertEquals('Lunas', $reportedPaid->payment_status_label);
    }

    /**
     * Skenario 7: Verifikasi Retur Penjualan otomatis memperbarui monitoring SO.
     * SO 10 unit @ 100.000 (Total 1.000.000).
     * SJ 10 unit, Invoice 10 unit (Total 1.000.000).
     * Retur Penjualan 2 unit completed (Nilai retur 200.000).
     * Laporan SO harus update:
     * - Net Qty Delivered = 8 (Retur = 2)
     * - Net Qty Invoiced = 8 (Reversed = 2)
     * - Effective Total Invoice = Rp 800.000
     * - Sisa Piutang = Rp 800.000
     * - Bayar Rp 800.000 -> Status Lunas.
     */
    public function test_sales_fulfillment_report_adjusts_for_returns_and_reversed_invoices(): void
    {
        $so = SalesOrder::create([
            'so_number' => 'SO-RET-TEST-01',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'confirmed',
            'order_date' => now()->toDateString(),
            'tax_rate' => 0,
            'total_amount' => 1000000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'unit_price' => 100000,
            'subtotal' => 1000000,
        ]);
        $del = Delivery::create([
            'delivery_number' => 'SJ-RET-TEST-01',
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'condition_status' => 'baik',
            'delivery_date' => now()->toDateString(),
        ]);
        $delItem = DeliveryItem::create([
            'delivery_id' => $del->id,
            'sales_order_item_id' => $soItem->id,
            'qty_delivered' => 10,
        ]);

        // Invoice diterbitkan
        $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $so->id,
            'delivery_id'    => $del->id,
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addMonth()->toDateString(),
            'tax_rate'       => 0,
        ]);

        // Buat dan selesaikan Retur Penjualan 2 unit
        $sRet = \App\Models\SalesReturn::create([
            'return_number' => 'RET-S-TEST-01',
            'delivery_id'   => $del->id,
            'customer_id'   => $this->customer->id,
            'status'        => 'draft',
            'return_date'   => now()->toDateString(),
        ]);
        \App\Models\SalesReturnItem::create([
            'sales_return_id'  => $sRet->id,
            'delivery_item_id' => $delItem->id,
            'product_id'       => $this->product->id,
            'qty'              => 2,
            'condition'        => 'baik',
        ]);

        $this->actingAs($this->admin)->patch(route('sales.returns.receive', $sRet));
        $this->actingAs($this->admin)->patch(route('sales.returns.complete', $sRet));

        // Cek Laporan Monitoring SO
        $response = $this->actingAs($this->admin)->get(route('sales.reports.fulfillment', ['q' => 'SO-RET-TEST-01']));
        $response->assertOk();

        $reportedSo = $response->viewData('orders')->first();
        $this->assertEquals(10, $reportedSo->qty_ordered_sum);
        $this->assertEquals(10, $reportedSo->qty_delivered_sum);
        $this->assertEquals(2, $reportedSo->qty_returned_sum);
        $this->assertEquals(8, $reportedSo->net_qty_delivered);
        $this->assertEquals(10, $reportedSo->qty_invoiced_sum);
        $this->assertEquals(2, $reportedSo->qty_reversed_sum);
        $this->assertEquals(8, $reportedSo->net_qty_invoiced);
        $this->assertEquals(1000000, $reportedSo->total_invoice_sum);
        $this->assertEquals(200000, $reportedSo->total_reversed_amount_sum);
        $this->assertEquals(800000, $reportedSo->effective_total_invoice);
        $this->assertEquals(800000, $reportedSo->remaining_balance);
        $this->assertEquals('Belum Dibayar', $reportedSo->payment_status_label);

        // Lakukan pembayaran lunas Rp 800.000
        $invoice = SalesInvoice::where('sales_order_id', $so->id)->firstOrFail();
        SalesPayment::create([
            'sales_invoice_id' => $invoice->id,
            'customer_id'      => $this->customer->id,
            'user_id'          => $this->admin->id,
            'payment_date'     => now()->toDateString(),
            'amount'           => 800000,
            'payment_method'   => 'bank_transfer',
            'reference_number' => 'PAY-SO-RET-01',
        ]);

        $resPaid = $this->actingAs($this->admin)->get(route('sales.reports.fulfillment', ['q' => 'SO-RET-TEST-01']));
        $reportedPaid = $resPaid->viewData('orders')->first();
        $this->assertEquals(800000, $reportedPaid->total_paid_sum);
        $this->assertEquals(0, $reportedPaid->remaining_balance);
        $this->assertEquals('Lunas', $reportedPaid->payment_status_label);
    }
}
