<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashFlowForecastAndUpcomingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Supplier $supplier;
    protected Customer $customer;
    protected Warehouse $warehouse;
    protected ProductCategory $category;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);

        // Akun Kas & Bank
        ChartOfAccount::firstOrCreate(
            ['code' => '1-1100'],
            ['name' => 'Kas', 'type' => 'asset', 'normal_balance' => 'debit']
        );

        $this->supplier = Supplier::create([
            'code' => 'SUP-001',
            'name' => 'PT Supplier Utama',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'code' => 'CUST-001',
            'name' => 'PT Customer Prioritas',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'code' => 'WH-01',
            'name' => 'Gudang Utama',
            'is_active' => true,
        ]);

        $this->category = ProductCategory::create([
            'code' => 'CAT-01',
            'name' => 'Elektronik',
        ]);

        $this->product = Product::create([
            'sku' => 'PROD-001',
            'name' => 'Laptop Pro',
            'category_id' => $this->category->id,
            'unit' => 'unit',
            'purchase_price' => 5000000,
            'sell_price' => 8000000,
            'min_stock' => 5,
            'is_active' => true,
        ]);
    }

    public function test_cash_flow_forecast_reflects_effective_total_amount_after_returns(): void
    {
        $today = Carbon::today();

        // 1. Buat Sales Invoice jatuh tempo 5 hari lagi (Minggu ke-1)
        $so = SalesOrder::create([
            'so_number' => 'SO-FC-01',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'order_date' => $today->toDateString(),
            'status' => 'confirmed',
            'tax_rate' => 0,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'qty_delivered' => 10,
            'unit_price' => 8000000,
            'subtotal' => 80000000,
        ]);
        $delivery = Delivery::create([
            'delivery_number' => 'DO-FC-01',
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'delivery_date' => $today->toDateString(),
            'status' => 'delivered',
            'condition_status' => 'baik',
        ]);
        $deliveryItem = DeliveryItem::create([
            'delivery_id' => $delivery->id,
            'sales_order_item_id' => $soItem->id,
            'product_id' => $this->product->id,
            'qty_delivered' => 10,
            'invoiced_qty' => 10,
        ]);

        // Invoice Rp 80.000.000 jatuh tempo 5 hari lagi
        $si = SalesInvoice::create([
            'invoice_number' => 'INV-FC-01',
            'sales_order_id' => $so->id,
            'delivery_id' => $delivery->id,
            'invoice_date' => $today->toDateString(),
            'due_date' => $today->copy()->addDays(5)->toDateString(),
            'amount' => 80000000,
            'tax_amount' => 0,
            'total_amount' => 80000000,
            'status' => 'unpaid',
        ]);
        $siItem = SalesInvoiceItem::create([
            'sales_invoice_id' => $si->id,
            'product_id' => $this->product->id,
            'delivery_item_id' => $deliveryItem->id,
            'qty_invoiced' => 10,
            'unit_price' => 8000000,
            'subtotal' => 80000000,
            'cogs_amount' => 50000000,
            'reversed_qty' => 2, // 2 unit diretur = Rp 16.000.000 berkurang!
        ]);

        // 2. Buat Purchase Invoice jatuh tempo 10 hari lagi (Minggu ke-2)
        $po = PurchaseOrder::create([
            'po_number' => 'PO-FC-01',
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->admin->id,
            'order_date' => $today->toDateString(),
            'status' => 'confirmed',
            'tax_rate' => 0,
        ]);
        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'qty_received' => 10,
            'unit_price' => 5000000,
            'subtotal' => 50000000,
        ]);
        $gr = GoodsReceipt::create([
            'receipt_number' => 'GRN-FC-01',
            'purchase_order_id' => $po->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'received_date' => $today->toDateString(),
            'qc_status' => 'passed',
        ]);
        $grItem = GoodsReceiptItem::create([
            'goods_receipt_id' => $gr->id,
            'purchase_order_item_id' => $poItem->id,
            'product_id' => $this->product->id,
            'qty_received' => 10,
            'unit_cost' => 5000000,
            'invoiced_qty' => 10,
        ]);

        // Invoice Pembelian Rp 50.000.000 jatuh tempo 10 hari lagi
        $pi = PurchaseInvoice::create([
            'invoice_number' => 'PI-FC-01',
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $gr->id,
            'invoice_date' => $today->toDateString(),
            'due_date' => $today->copy()->addDays(10)->toDateString(),
            'amount' => 50000000,
            'tax_amount' => 0,
            'total_amount' => 50000000,
            'status' => 'unpaid',
        ]);
        $piItem = PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $pi->id,
            'goods_receipt_item_id' => $grItem->id,
            'product_id' => $this->product->id,
            'qty_invoiced' => 10,
            'unit_price' => 5000000,
            'subtotal' => 50000000,
            'reversed_qty' => 2, // 2 unit diretur = Rp 10.000.000 berkurang!
        ]);

        // Cek pemanggilan route Cash Flow
        $response = $this->actingAs($this->admin)->get(route('accounting.reports.cash-flow'));
        $response->assertStatus(200);

        $forecastWeeks = $response->viewData('forecastWeeks');
        $totalProjectedInflow = $response->viewData('totalProjectedInflow');
        $totalProjectedOutflow = $response->viewData('totalProjectedOutflow');

        // SI: 80jt - (2/10 * 80jt = 16jt) = 64jt di Minggu ke-1
        $this->assertEquals(64000000, $forecastWeeks[1]['inflow']);
        $this->assertEquals(64000000, $totalProjectedInflow);

        // PI: 50jt - (2/10 * 50jt = 10jt) = 40jt di Minggu ke-2
        $this->assertEquals(40000000, $forecastWeeks[2]['outflow']);
        $this->assertEquals(40000000, $totalProjectedOutflow);
    }

    public function test_receivables_upcoming_excludes_overdue_and_applies_return_deduction(): void
    {
        $today = Carbon::today();

        $so = SalesOrder::create([
            'so_number' => 'SO-UP-01',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'order_date' => $today->toDateString(),
            'status' => 'confirmed',
            'tax_rate' => 0,
        ]);

        // Invoice 1: Sudah jatuh tempo kemarin (Harus TIDAK MUNCUL di upcoming)
        SalesInvoice::create([
            'invoice_number' => 'INV-OVERDUE',
            'sales_order_id' => $so->id,
            'invoice_date' => $today->copy()->subDays(10)->toDateString(),
            'due_date' => $today->copy()->subDay()->toDateString(),
            'amount' => 10000000,
            'tax_amount' => 0,
            'total_amount' => 10000000,
            'status' => 'unpaid',
        ]);

        // Invoice 2: Jatuh tempo 4 hari ke depan (Harus MUNCUL) dengan retur
        $invUpcoming = SalesInvoice::create([
            'invoice_number' => 'INV-UPCOMING-4D',
            'sales_order_id' => $so->id,
            'invoice_date' => $today->toDateString(),
            'due_date' => $today->copy()->addDays(4)->toDateString(),
            'amount' => 20000000,
            'tax_amount' => 0,
            'total_amount' => 20000000,
            'status' => 'unpaid',
        ]);
        SalesInvoiceItem::create([
            'sales_invoice_id' => $invUpcoming->id,
            'product_id' => $this->product->id,
            'qty_invoiced' => 10,
            'unit_price' => 2000000,
            'subtotal' => 20000000,
            'cogs_amount' => 12000000,
            'reversed_qty' => 3, // Retur 3 unit = 6.000.000 berkurang! Sisa 14.000.000
        ]);

        // Invoice 3: Jatuh tempo 20 hari ke depan (Harus TIDAK MUNCUL jika filter ?days=7)
        SalesInvoice::create([
            'invoice_number' => 'INV-UPCOMING-20D',
            'sales_order_id' => $so->id,
            'invoice_date' => $today->toDateString(),
            'due_date' => $today->copy()->addDays(20)->toDateString(),
            'amount' => 30000000,
            'tax_amount' => 0,
            'total_amount' => 30000000,
            'status' => 'unpaid',
        ]);

        // Filter default 7 hari
        $response = $this->actingAs($this->admin)->get(route('accounting.reports.receivables-upcoming', ['days' => 7]));
        $response->assertStatus(200);

        $invoices = $response->viewData('invoices');
        $this->assertCount(1, $invoices);
        $this->assertEquals('INV-UPCOMING-4D', $invoices->first()->invoice_number);
        // Sisa piutang setelah retur harus 14.000.000
        $this->assertEquals(14000000, $invoices->first()->outstanding_amount);
        $this->assertEquals(4, $invoices->first()->days_remaining);

        // Filter 30 hari -> Invoice 20 hari ke depan harus ikut muncul
        $response30 = $this->actingAs($this->admin)->get(route('accounting.reports.receivables-upcoming', ['days' => 30]));
        $response30->assertStatus(200);
        $invoices30 = $response30->viewData('invoices');
        $this->assertCount(2, $invoices30);
    }

    public function test_payables_upcoming_excludes_overdue_and_applies_return_deduction(): void
    {
        $today = Carbon::today();

        $po = PurchaseOrder::create([
            'po_number' => 'PO-UP-01',
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->admin->id,
            'order_date' => $today->toDateString(),
            'status' => 'confirmed',
            'tax_rate' => 0,
        ]);

        // Overdue invoice (Tidak boleh muncul)
        PurchaseInvoice::create([
            'invoice_number' => 'PI-OVERDUE',
            'purchase_order_id' => $po->id,
            'invoice_date' => $today->copy()->subDays(10)->toDateString(),
            'due_date' => $today->copy()->subDay()->toDateString(),
            'amount' => 15000000,
            'tax_amount' => 0,
            'total_amount' => 15000000,
            'status' => 'unpaid',
        ]);

        // Upcoming 6 hari dengan retur (Harus muncul)
        $piUpcoming = PurchaseInvoice::create([
            'invoice_number' => 'PI-UPCOMING-6D',
            'purchase_order_id' => $po->id,
            'invoice_date' => $today->toDateString(),
            'due_date' => $today->copy()->addDays(6)->toDateString(),
            'amount' => 25000000,
            'tax_amount' => 0,
            'total_amount' => 25000000,
            'status' => 'unpaid',
        ]);
        PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $piUpcoming->id,
            'product_id' => $this->product->id,
            'qty_invoiced' => 10,
            'unit_price' => 2500000,
            'subtotal' => 25000000,
            'reversed_qty' => 4, // Retur 4 unit = 10.000.000 berkurang! Sisa 15.000.000
        ]);

        $response = $this->actingAs($this->admin)->get(route('accounting.reports.payables-upcoming', ['days' => 7]));
        $response->assertStatus(200);

        $invoices = $response->viewData('invoices');
        $this->assertCount(1, $invoices);
        $this->assertEquals('PI-UPCOMING-6D', $invoices->first()->invoice_number);
        // Sisa hutang setelah retur harus 15.000.000
        $this->assertEquals(15000000, $invoices->first()->outstanding_amount);
        $this->assertEquals(6, $invoices->first()->days_remaining);
    }

    public function test_gross_profit_trend_and_category_breakdown(): void
    {
        $today = Carbon::today();

        $so = SalesOrder::create([
            'so_number' => 'SO-GP-01',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'order_date' => $today->toDateString(),
            'status' => 'confirmed',
            'tax_rate' => 0,
        ]);

        $si = SalesInvoice::create([
            'invoice_number' => 'INV-GP-01',
            'sales_order_id' => $so->id,
            'invoice_date' => $today->toDateString(),
            'due_date' => $today->toDateString(),
            'amount' => 100000000,
            'tax_amount' => 0,
            'total_amount' => 100000000,
            'status' => 'paid',
        ]);

        // 10 unit @ 10jt = 100jt omset, HPP 6jt/unit = 60jt COGS.
        // Diretur 2 unit -> omset bersih 80jt, HPP bersih 48jt, Laba kotor 32jt (Margin 40%).
        SalesInvoiceItem::create([
            'sales_invoice_id' => $si->id,
            'product_id' => $this->product->id,
            'qty_invoiced' => 10,
            'unit_price' => 10000000,
            'subtotal' => 100000000,
            'cogs_amount' => 60000000,
            'reversed_qty' => 2,
        ]);

        $response = $this->actingAs($this->admin)->get(route('accounting.reports.gross-profit', ['period_months' => 12]));
        $response->assertStatus(200);

        $totalRevenue = $response->viewData('totalRevenue');
        $totalCogs = $response->viewData('totalCogs');
        $totalGrossProfit = $response->viewData('totalGrossProfit');
        $avgMarginPct = $response->viewData('avgMarginPct');
        $categoryBreakdown = $response->viewData('categoryBreakdown');

        $this->assertEquals(80000000, $totalRevenue);
        $this->assertEquals(48000000, $totalCogs);
        $this->assertEquals(32000000, $totalGrossProfit);
        $this->assertEquals(40.0, $avgMarginPct);

        $this->assertNotEmpty($categoryBreakdown);
        $elektronik = $categoryBreakdown->firstWhere('name', 'Elektronik');
        $this->assertNotNull($elektronik);
        $this->assertEquals(32000000, $elektronik->gross_profit);
        $this->assertEquals(40.0, $elektronik->margin_pct);
    }
}
