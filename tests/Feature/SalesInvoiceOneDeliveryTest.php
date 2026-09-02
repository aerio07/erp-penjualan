<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalesInvoiceOneDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Customer $customer;
    protected Warehouse $warehouse;
    protected Product $productA;
    protected Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\ChartOfAccountSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->customer = Customer::create([
            'code' => 'CUST-DELIV-' . uniqid(),
            'name' => 'Customer Testing Sales Delivery',
            'is_active' => true,
        ]);
        $this->warehouse = Warehouse::create([
            'code' => 'WH-DELIV-' . uniqid(),
            'name' => 'Gudang Utama Sales Delivery',
            'is_active' => true,
        ]);
        $this->productA = Product::create([
            'sku' => 'PRD-SD-A-' . uniqid(),
            'name' => 'Produk SD Alpha',
            'unit' => 'pcs',
            'purchase_price' => 50000,
            'sell_price' => 100000,
            'is_active' => true,
        ]);
        $this->productB = Product::create([
            'sku' => 'PRD-SD-B-' . uniqid(),
            'name' => 'Produk SD Beta',
            'unit' => 'pcs',
            'purchase_price' => 25000,
            'sell_price' => 50000,
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: 1 SO dengan 2 Surat Jalan (SJ A: 5 unit, SJ B: 5 unit).
     * Menerbitkan invoice dari SJ A menagih penuh 5 unit, menandai SJ A is_invoiced = true,
     * sementara SJ B tetap is_invoiced = false dan siap diinvoice secara terpisah.
     */
    public function test_invoice_from_delivery_a_marks_it_invoiced_and_delivery_b_remains_available(): void
    {
        $so = SalesOrder::create([
            'so_number' => 'SO-TEST-001-' . uniqid(),
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
            'product_id' => $this->productA->id,
            'qty_ordered' => 10,
            'unit_price' => 100000,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 1000000,
        ]);

        // Surat Jalan A: 5 unit
        $delA = Delivery::create([
            'delivery_number' => 'SJ-A-' . uniqid(),
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'condition_status' => 'baik',
            'delivery_date' => now()->toDateString(),
        ]);
        DeliveryItem::create([
            'delivery_id' => $delA->id,
            'sales_order_item_id' => $soItem->id,
            'qty_delivered' => 5,
        ]);

        // Surat Jalan B: 5 unit
        $delB = Delivery::create([
            'delivery_number' => 'SJ-B-' . uniqid(),
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'condition_status' => 'baik',
            'delivery_date' => now()->toDateString(),
        ]);
        DeliveryItem::create([
            'delivery_id' => $delB->id,
            'sales_order_item_id' => $soItem->id,
            'qty_delivered' => 5,
        ]);

        // Terbitkan invoice untuk SJ A
        $response = $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $so->id,
            'delivery_id'    => $delA->id,
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addDays(30)->toDateString(),
            'tax_rate'       => 11,
        ]);

        $response->assertRedirect(route('sales.invoices.index'));

        // Verifikasi status SJ A dan B
        $delA->refresh();
        $delB->refresh();

        $this->assertTrue($delA->is_invoiced);
        $this->assertNotNull($delA->sales_invoice_id);
        $this->assertFalse($delB->is_invoiced);
        $this->assertNull($delB->sales_invoice_id);

        // Verifikasi Invoice A menagih tepat 5 unit penuh
        $invoiceA = SalesInvoice::where('delivery_id', $delA->id)->firstOrFail();
        $this->assertEquals(500000, $invoiceA->amount);
        $this->assertEquals(55000, $invoiceA->tax_amount);
        $this->assertEquals(555000, $invoiceA->total_amount);

        // Terbitkan invoice untuk SJ B
        $responseB = $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $so->id,
            'delivery_id'    => $delB->id,
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addDays(30)->toDateString(),
            'tax_rate'       => 11,
        ]);

        $responseB->assertRedirect(route('sales.invoices.index'));

        $delB->refresh();
        $this->assertTrue($delB->is_invoiced);
        $this->assertNotNull($delB->sales_invoice_id);

        $invoiceB = SalesInvoice::where('delivery_id', $delB->id)->firstOrFail();
        $this->assertEquals(500000, $invoiceB->amount);
        $this->assertEquals(55000, $invoiceB->tax_amount);
        $this->assertEquals(555000, $invoiceB->total_amount);

        // Total kedua invoice tepat sama dengan SO
        $this->assertEquals(1110000, $invoiceA->total_amount + $invoiceB->total_amount);
    }

    /**
     * Test 2: SJ A yang sudah diinvoice tidak boleh di-invoice untuk kedua kalinya.
     */
    public function test_cannot_create_invoice_from_already_invoiced_delivery(): void
    {
        $so = SalesOrder::create([
            'so_number' => 'SO-TEST-002-' . uniqid(),
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'confirmed',
            'order_date' => now()->toDateString(),
            'tax_rate' => 0,
            'total_amount' => 500000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->productA->id,
            'qty_ordered' => 5,
            'unit_price' => 100000,
            'subtotal' => 500000,
        ]);
        $del = Delivery::create([
            'delivery_number' => 'SJ-DOUBLE-' . uniqid(),
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'condition_status' => 'baik',
            'delivery_date' => now()->toDateString(),
        ]);
        DeliveryItem::create([
            'delivery_id' => $del->id,
            'sales_order_item_id' => $soItem->id,
            'qty_delivered' => 5,
        ]);

        // Invoice pertama sukses
        $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $so->id,
            'delivery_id'    => $del->id,
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addDays(30)->toDateString(),
            'tax_rate'       => 0,
        ])->assertRedirect(route('sales.invoices.index'));

        // Invoice kedua untuk SJ yang sama ditolak
        $response = $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $so->id,
            'delivery_id'    => $del->id,
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addDays(30)->toDateString(),
            'tax_rate'       => 0,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(1, SalesInvoice::where('delivery_id', $del->id)->count());
    }

    /**
     * Test 3: Race Condition Guard — lockForUpdate mencegah double invoicing.
     */
    public function test_race_condition_only_one_invoice_created_for_same_delivery(): void
    {
        $so = SalesOrder::create([
            'so_number' => 'SO-RACE-' . uniqid(),
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'confirmed',
            'order_date' => now()->toDateString(),
            'tax_rate' => 0,
            'total_amount' => 200000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->productA->id,
            'qty_ordered' => 2,
            'unit_price' => 100000,
            'subtotal' => 200000,
        ]);
        $del = Delivery::create([
            'delivery_number' => 'SJ-RACE-' . uniqid(),
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'condition_status' => 'baik',
            'delivery_date' => now()->toDateString(),
        ]);
        DeliveryItem::create([
            'delivery_id' => $del->id,
            'sales_order_item_id' => $soItem->id,
            'qty_delivered' => 2,
        ]);

        // Simulasikan request 1 memproses dan menandai delivery
        DB::transaction(function () use ($so, $del) {
            $lockedDel = Delivery::lockForUpdate()->find($del->id);
            $lockedDel->update(['is_invoiced' => true]);
        });

        // Request 2 datang bersamaan dan mendapati is_invoiced sudah true
        $response = $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $so->id,
            'delivery_id'    => $del->id,
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addDays(30)->toDateString(),
            'tax_rate'       => 0,
        ]);

        $response->assertSessionHas('error');
    }

    /**
     * Test 4: Prorasi Diskon Header SO terbagi proporsional ke tiap invoice Surat Jalan.
     */
    public function test_header_discount_prorated_correctly_between_two_delivery_invoices(): void
    {
        // SO: Subtotal 1.000.000, Diskon Header 100.000, Net 900.000
        $so = SalesOrder::create([
            'so_number' => 'SO-DISC-' . uniqid(),
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'confirmed',
            'order_date' => now()->toDateString(),
            'discount_amount' => 100000,
            'tax_rate' => 0,
            'total_amount' => 900000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->productA->id,
            'qty_ordered' => 10,
            'unit_price' => 100000,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 1000000,
        ]);

        // SJ 1: 6 unit (60% = Rp 600.000)
        $del1 = Delivery::create([
            'delivery_number' => 'SJ-DISC-1-' . uniqid(),
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'condition_status' => 'baik',
            'delivery_date' => now()->toDateString(),
        ]);
        DeliveryItem::create([
            'delivery_id' => $del1->id,
            'sales_order_item_id' => $soItem->id,
            'qty_delivered' => 6,
        ]);

        // SJ 2: 4 unit (40% = Rp 400.000)
        $del2 = Delivery::create([
            'delivery_number' => 'SJ-DISC-2-' . uniqid(),
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'condition_status' => 'baik',
            'delivery_date' => now()->toDateString(),
        ]);
        DeliveryItem::create([
            'delivery_id' => $del2->id,
            'sales_order_item_id' => $soItem->id,
            'qty_delivered' => 4,
        ]);

        // Invoice 1: 60% diskon = 60.000, DPP = 600.000 - 60.000 = 540.000
        $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $so->id,
            'delivery_id'    => $del1->id,
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addDays(30)->toDateString(),
            'tax_rate'       => 0,
        ]);
        $inv1 = SalesInvoice::where('delivery_id', $del1->id)->firstOrFail();
        $this->assertEquals(540000, $inv1->amount);

        // Invoice 2: 40% diskon = 40.000, DPP = 400.000 - 40.000 = 360.000
        $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $so->id,
            'delivery_id'    => $del2->id,
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addDays(30)->toDateString(),
            'tax_rate'       => 0,
        ]);
        $inv2 = SalesInvoice::where('delivery_id', $del2->id)->firstOrFail();
        $this->assertEquals(360000, $inv2->amount);

        // Total akumulasi pas 100% dengan SO: 540.000 + 360.000 = 900.000
        $this->assertEquals(900000, $inv1->amount + $inv2->amount);
    }

    /**
     * Test 5: Presisi Pembulatan Diskon & PPN pada Surat Jalan Terakhir.
     * Kasus nyata rupiah bulat: SO Rp 291.000 + PPN 11% Rp 32.010 = Total Rp 323.010 dipecah ke 3 SJ.
     * SJ 1 (1 unit): DPP 72.750, PPN 8.003, Total 80.753
     * SJ 2 (1 unit): DPP 72.750, PPN 8.003, Total 80.753
     * SJ 3 (2 unit - terakhir): DPP 145.500, PPN 16.004, Total 161.504
     * Total ketiga invoice: tepat Rp 323.010 (sama persis dengan SO tanpa selisih 1 rupiah).
     */
    public function test_header_discount_and_tax_rounding_remainder_allocated_to_last_invoice(): void
    {
        $so = SalesOrder::create([
            'so_number' => 'SO-ROUND-TAX-' . uniqid(),
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'done',
            'order_date' => now()->toDateString(),
            'discount_amount' => 0,
            'tax_rate' => 11,
            'tax_amount' => 32010,
            'total_amount' => 323010,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->productA->id,
            'qty_ordered' => 4,
            'unit_price' => 72750,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 291000,
        ]);

        // Buat 3 SJ: SJ 1 (1 unit), SJ 2 (1 unit), SJ 3 (2 unit)
        $deliveries = [];
        foreach ([1, 1, 2] as $idx => $q) {
            $del = Delivery::create([
                'delivery_number' => 'SJ-TAX-' . ($idx + 1) . '-' . uniqid(),
                'sales_order_id' => $so->id,
                'warehouse_id' => $this->warehouse->id,
                'user_id' => $this->admin->id,
                'condition_status' => 'baik',
                'delivery_date' => now()->toDateString(),
            ]);
            DeliveryItem::create([
                'delivery_id' => $del->id,
                'sales_order_item_id' => $soItem->id,
                'qty_delivered' => $q,
            ]);
            $deliveries[] = $del;
        }

        // Invoice 1 (1 unit)
        $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $so->id,
            'delivery_id'    => $deliveries[0]->id,
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addWeek()->toDateString(),
            'tax_rate'       => 11,
        ]);
        $inv1 = SalesInvoice::where('delivery_id', $deliveries[0]->id)->firstOrFail();
        $this->assertEquals(72750, $inv1->amount);
        $this->assertEquals(8003, $inv1->tax_amount);
        $this->assertEquals(80753, $inv1->total_amount);

        // Invoice 2 (1 unit)
        $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $so->id,
            'delivery_id'    => $deliveries[1]->id,
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addWeek()->toDateString(),
            'tax_rate'       => 11,
        ]);
        $inv2 = SalesInvoice::where('delivery_id', $deliveries[1]->id)->firstOrFail();
        $this->assertEquals(72750, $inv2->amount);
        $this->assertEquals(8003, $inv2->tax_amount);
        $this->assertEquals(80753, $inv2->total_amount);

        // Invoice 3 (2 unit - SJ terakhir menyerap sisa PPN)
        $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $so->id,
            'delivery_id'    => $deliveries[2]->id,
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addWeek()->toDateString(),
            'tax_rate'       => 11,
        ]);
        $inv3 = SalesInvoice::where('delivery_id', $deliveries[2]->id)->firstOrFail();
        $this->assertEquals(145500, $inv3->amount);
        // Sisa PPN: 32.010 - (8.003 + 8.003) = 16.004
        $this->assertEquals(16004, $inv3->tax_amount);
        $this->assertEquals(161504, $inv3->total_amount);

        // Verifikasi total akumulasi sama 100% pas dengan SO
        $allInvoices = SalesInvoice::where('sales_order_id', $so->id)->get();
        $this->assertEquals(291000, $allInvoices->sum('amount'));
        $this->assertEquals(32010, $allInvoices->sum('tax_amount'));
        $this->assertEquals(323010, $allInvoices->sum('total_amount'));
    }

    /**
     * Test 6: Invoice lama tanpa delivery_id tetap aman dan bisa dibuka normal.
     */
    public function test_legacy_invoice_without_delivery_reference_still_works(): void
    {
        $so = SalesOrder::create([
            'so_number' => 'SO-LEGACY-' . uniqid(),
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'done',
            'order_date' => now()->toDateString(),
            'tax_rate' => 0,
            'total_amount' => 100000,
        ]);
        $legacyInvoice = SalesInvoice::create([
            'invoice_number' => 'SINV-LEGACY-' . uniqid(),
            'sales_order_id' => $so->id,
            'delivery_id'    => null, // data lama
            'amount'         => 100000,
            'tax_rate'       => 0,
            'tax_amount'     => 0,
            'total_amount'   => 100000,
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addDays(30)->toDateString(),
            'status'         => 'unpaid',
        ]);

        $response = $this->actingAs($this->admin)->get(route('sales.invoices.show', $legacyInvoice));
        $response->assertOk();
        $response->assertSee($legacyInvoice->invoice_number);

        $indexResponse = $this->actingAs($this->admin)->get(route('sales.invoices.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee($legacyInvoice->invoice_number);
    }

    /**
     * Test 7: Form create memuat seluruh SJ yang belum di-invoice tanpa perlu query param ?so_id=...
     */
    public function test_create_view_loads_all_uninvoiced_deliveries_without_query_params(): void
    {
        $so = SalesOrder::create([
            'so_number' => 'SO-VIEW-' . uniqid(),
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'confirmed',
            'order_date' => now()->toDateString(),
            'tax_rate' => 0,
            'total_amount' => 100000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->productA->id,
            'qty_ordered' => 1,
            'unit_price' => 100000,
            'subtotal' => 100000,
        ]);
        $del = Delivery::create([
            'delivery_number' => 'SJ-VIEW-TEST-' . uniqid(),
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

        // Request form create tanpa query param
        $response = $this->actingAs($this->admin)->get(route('sales.invoices.create'));
        $response->assertOk();
        $response->assertSee($so->so_number);
        $response->assertSee($del->delivery_number);
    }

    /**
     * Test 8: Konsistensi Retur Penjualan Parsial dari Surat Jalan yang Full-Invoiced.
     * Skenario yang disorot user:
     * - 1 SO & SJ berisi 10 unit.
     * - Diterbitkan invoice penuh (10 unit, invoiced_qty = 10, SalesInvoiceItem->qty_invoiced = 10).
     * - Customer meretur sebagian (4 unit dari 10 unit).
     * - Verifikasi formula JournalService::createFromSalesReturn():
     *   qty_to_reverse = min(4, 10 - 0) = 4.
     * - Verifikasi reversed_qty pada SalesInvoiceItem bertambah menjadi 4.
     * - Verifikasi Jurnal Retur membalik piutang proporsional untuk 4 unit.
     * - Verifikasi effective_total_amount & outstanding_amount invoice berkurang tepat untuk 4 unit.
     */
    public function test_partial_sales_return_after_full_invoice_reduces_outstanding_correctly(): void
    {
        $so = SalesOrder::create([
            'so_number' => 'SO-RET-PARTIAL-' . uniqid(),
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'confirmed',
            'order_date' => now()->toDateString(),
            'tax_rate' => 0,
            'total_amount' => 1000000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->productA->id,
            'qty_ordered' => 10,
            'unit_price' => 100000,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 1000000,
        ]);

        $del = Delivery::create([
            'delivery_number' => 'SJ-RET-PARTIAL-' . uniqid(),
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

        // Terbitkan invoice penuh dari SJ ini (10 unit)
        $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $so->id,
            'delivery_id'    => $del->id,
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addMonth()->toDateString(),
            'tax_rate'       => 0,
        ]);

        $invoice = SalesInvoice::where('delivery_id', $del->id)->firstOrFail();
        $this->assertEquals(1000000, $invoice->total_amount);
        $this->assertEquals(1000000, $invoice->outstanding_amount);

        $invItem = $invoice->items->first();
        $this->assertEquals(10, $invItem->qty_invoiced);
        $this->assertEquals(0, $invItem->reversed_qty);

        // Buat Retur Penjualan SEBAGIAN (4 unit dari 10 unit)
        $return = SalesReturn::create([
            'return_number' => 'SRET-PARTIAL-' . uniqid(),
            'delivery_id'   => $del->id,
            'customer_id'   => $this->customer->id,
            'return_date'   => now()->toDateString(),
            'status'        => 'draft',
        ]);
        SalesReturnItem::create([
            'sales_return_id'  => $return->id,
            'product_id'       => $this->productA->id,
            'delivery_item_id' => $delItem->id,
            'qty'              => 4,
            'condition'        => 'baik',
        ]);

        // Konfirmasi penerimaan fisik retur di gudang (PATCH route)
        $this->actingAs($this->admin)->patch(route('sales.returns.receive', $return));
        $return->refresh();
        $this->assertEquals('received', $return->status);

        // Selesaikan retur (complete) -> memicu createFromSalesReturn() & refreshAffectedInvoiceStatuses()
        $this->actingAs($this->admin)->patch(route('sales.returns.complete', $return));
        $return->refresh();
        $this->assertEquals('completed', $return->status);

        // 1. Verifikasi reversed_qty pada SalesInvoiceItem bertambah tepat menjadi 4
        $invItem->refresh();
        $this->assertEquals(4, $invItem->reversed_qty);

        // 2. Verifikasi total_reversed_amount pada invoice = 4 * 100.000 = 400.000
        $invoice->refresh();
        $this->assertEquals(400000, $invoice->total_reversed_amount);

        // 3. Verifikasi effective_total_amount & outstanding_amount berkurang tepat menjadi 600.000 (sisa 6 unit)
        $this->assertEquals(600000, $invoice->effective_total_amount);
        $this->assertEquals(600000, $invoice->outstanding_amount);

        // 4. Verifikasi Jurnal Retur terbuat dan seimbang (Debit Retur Penjualan 400.000, Kredit Piutang 400.000)
        $journalEntry = \App\Models\JournalEntry::where('reference_type', SalesReturn::class)
            ->where('reference_id', $return->id)
            ->firstOrFail();

        $piutangCredit = $journalEntry->lines->where('chartOfAccount.code', '1-1200')->sum('credit');
        $this->assertEquals(400000, $piutangCredit);
    }
}
