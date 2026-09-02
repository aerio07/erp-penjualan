<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesPayment;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerAndRecapReportsTest extends TestCase
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

        $this->seed(\Database\Seeders\ChartOfAccountSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);

        $this->supplier = Supplier::create([
            'name' => 'PT Mitra Supplier',
            'code' => 'SUP-001',
            'phone' => '08123456789',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'name' => 'PT Klien Sukses',
            'code' => 'CUST-001',
            'phone' => '08987654321',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Gudang Utama',
            'code' => 'WH-01',
            'is_active' => true,
        ]);

        $this->category = ProductCategory::create([
            'name' => 'Elektronik',
            'code' => 'ELK',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Laptop Pro 14',
            'sku' => 'LAP-001',
            'category_id' => $this->category->id,
            'purchase_price' => 10000000,
            'selling_price' => 12500000,
            'unit' => 'unit',
            'min_stock' => 5,
            'is_active' => true,
        ]);
    }

    /**
     * Test Khusus Saldo Awal Kartu Hutang:
     * Buat transaksi sebelum tanggal filter dan sesudah tanggal filter.
     * Verifikasi Saldo Awal + Mutasi Kredit - Mutasi Debit = Saldo Akhir.
     */
    public function test_kartu_hutang_saldo_awal_dan_mutasi_berjalan_cocok(): void
    {
        // 1. Transaksi SEBELUM periode filter (Agustus 2026)
        $po1 = PurchaseOrder::create([
            'po_number' => 'PO-AUG-001',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'order_date' => '2026-08-01',
            'status' => 'confirmed',
            'tax_rate' => 0,
            'total_amount' => 10000000,
        ]);

        $inv1 = PurchaseInvoice::create([
            'invoice_number' => 'INV-AUG-001',
            'purchase_order_id' => $po1->id,
            'amount' => 10000000,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => 10000000,
            'invoice_date' => '2026-08-05',
            'due_date' => '2026-09-05',
            'status' => 'partial',
        ]);

        // Bayar sebagian sebelum filter (Agustus)
        PurchasePayment::create([
            'purchase_invoice_id' => $inv1->id,
            'user_id' => $this->admin->id,
            'payment_date' => '2026-08-15',
            'amount' => 3000000,
            'method' => 'transfer',
            'reference_number' => 'PAY-AUG-01',
        ]);
        // Sisa saldo hutang sebelum September = 10.000.000 - 3.000.000 = 7.000.000

        // 2. Transaksi DALAM periode filter (September 2026)
        $po2 = PurchaseOrder::create([
            'po_number' => 'PO-SEP-001',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'order_date' => '2026-09-02',
            'status' => 'confirmed',
            'tax_rate' => 0,
            'total_amount' => 5000000,
        ]);

        $inv2 = PurchaseInvoice::create([
            'invoice_number' => 'INV-SEP-001',
            'purchase_order_id' => $po2->id,
            'amount' => 5000000,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => 5000000,
            'invoice_date' => '2026-09-05',
            'due_date' => '2026-10-05',
            'status' => 'partial',
        ]);

        // Bayar lagi di September
        PurchasePayment::create([
            'purchase_invoice_id' => $inv2->id,
            'user_id' => $this->admin->id,
            'payment_date' => '2026-09-12',
            'amount' => 2000000,
            'method' => 'transfer',
            'reference_number' => 'PAY-SEP-01',
        ]);

        // Request Kartu Hutang dengan filter 1 Sept - 30 Sept 2026
        $response = $this->actingAs($this->admin)
            ->get(route('accounting.reports.ledger-payable', [
                'supplier' => $this->supplier->id,
                'date_from' => '2026-09-01',
                'date_to' => '2026-09-30',
            ]));

        $response->assertStatus(200);

        // Verifikasi Nilai Saldo Awal dan Mutasi
        $beginningBalance = $response->viewData('beginningBalance');
        $totalCredit      = $response->viewData('totalCredit');
        $totalDebit       = $response->viewData('totalDebit');
        $endingBalance    = $response->viewData('endingBalance');
        $transactions     = $response->viewData('filteredTransactions');

        // Saldo Awal harus persis 7.000.000 (10jt invoice - 3jt bayar di bulan Agustus)
        $this->assertEquals(7000000, $beginningBalance, 'Saldo awal hutang harus menghitung akumulasi transaksi sebelum date_from');

        // Mutasi kredit periode Sept harus 5.000.000 (invoice baru)
        $this->assertEquals(5000000, $totalCredit, 'Mutasi kredit harus mencatat penambahan hutang invoice');

        // Mutasi debit periode Sept harus 2.000.000 (pembayaran baru)
        $this->assertEquals(2000000, $totalDebit, 'Mutasi debit harus mencatat pelunasan hutang');

        // Saldo Akhir harus persis 7jt + 5jt - 2jt = 10.000.000
        $this->assertEquals(10000000, $endingBalance, 'Saldo akhir = Saldo Awal + Kredit - Debit');
        $this->assertEquals($beginningBalance + $totalCredit - $totalDebit, $endingBalance);

        // Hanya ada 2 transaksi dalam periode Sept (INV-SEP-001 dan PAY-SEP-01)
        $this->assertCount(2, $transactions);
        $this->assertEquals(12000000, $transactions[0]->running_balance); // 7jt + 5jt = 12jt
        $this->assertEquals(10000000, $transactions[1]->running_balance); // 12jt - 2jt = 10jt
    }

    /**
     * Test Khusus Saldo Awal Kartu Piutang:
     * Verifikasi Saldo Awal + Mutasi Debit - Mutasi Kredit = Saldo Akhir.
     */
    public function test_kartu_piutang_saldo_awal_dan_mutasi_berjalan_cocok(): void
    {
        // 1. Transaksi SEBELUM periode filter (Agustus 2026)
        $so1 = SalesOrder::create([
            'so_number' => 'SO-AUG-001',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'order_date' => '2026-08-01',
            'status' => 'confirmed',
            'tax_rate' => 0,
            'total_amount' => 20000000,
        ]);

        $sinv1 = SalesInvoice::create([
            'invoice_number' => 'SINV-AUG-001',
            'sales_order_id' => $so1->id,
            'amount' => 20000000,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => 20000000,
            'invoice_date' => '2026-08-05',
            'due_date' => '2026-09-05',
            'status' => 'partial',
        ]);

        SalesPayment::create([
            'sales_invoice_id' => $sinv1->id,
            'user_id' => $this->admin->id,
            'payment_date' => '2026-08-15',
            'amount' => 8000000,
            'method' => 'transfer',
            'reference_number' => 'SPAY-AUG-01',
        ]);
        // Saldo piutang sebelum September = 20jt - 8jt = 12jt

        // 2. Transaksi DALAM periode filter (September 2026)
        $so2 = SalesOrder::create([
            'so_number' => 'SO-SEP-001',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'order_date' => '2026-09-02',
            'status' => 'confirmed',
            'tax_rate' => 0,
            'total_amount' => 6000000,
        ]);

        $sinv2 = SalesInvoice::create([
            'invoice_number' => 'SINV-SEP-001',
            'sales_order_id' => $so2->id,
            'amount' => 6000000,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => 6000000,
            'invoice_date' => '2026-09-05',
            'due_date' => '2026-10-05',
            'status' => 'partial',
        ]);

        SalesPayment::create([
            'sales_invoice_id' => $sinv2->id,
            'user_id' => $this->admin->id,
            'payment_date' => '2026-09-12',
            'amount' => 3000000,
            'method' => 'transfer',
            'reference_number' => 'SPAY-SEP-01',
        ]);

        // Request Kartu Piutang filter 1 Sept - 30 Sept 2026
        $response = $this->actingAs($this->admin)
            ->get(route('accounting.reports.ledger-receivable', [
                'customer' => $this->customer->id,
                'date_from' => '2026-09-01',
                'date_to' => '2026-09-30',
            ]));

        $response->assertStatus(200);

        $beginningBalance = $response->viewData('beginningBalance');
        $totalDebit       = $response->viewData('totalDebit');
        $totalCredit      = $response->viewData('totalCredit');
        $endingBalance    = $response->viewData('endingBalance');
        $transactions     = $response->viewData('filteredTransactions');

        // Saldo Awal harus persis 12.000.000 (20jt - 8jt)
        $this->assertEquals(12000000, $beginningBalance);

        // Mutasi debit (tambah piutang) = 6.000.000
        $this->assertEquals(6000000, $totalDebit);

        // Mutasi kredit (kurang piutang) = 3.000.000
        $this->assertEquals(3000000, $totalCredit);

        // Saldo Akhir = 12jt + 6jt - 3jt = 15.000.000
        $this->assertEquals(15000000, $endingBalance);
        $this->assertEquals($beginningBalance + $totalDebit - $totalCredit, $endingBalance);

        $this->assertCount(2, $transactions);
        $this->assertEquals(18000000, $transactions[0]->running_balance); // 12jt + 6jt = 18jt
        $this->assertEquals(15000000, $transactions[1]->running_balance); // 18jt - 3jt = 15jt
    }

    /**
     * Test Rekap Hutang by Vendor
     */
    public function test_rekap_hutang_by_vendor(): void
    {
        $po = PurchaseOrder::create([
            'po_number' => 'PO-REKAP-001',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'order_date' => '2026-09-01',
            'status' => 'confirmed',
            'tax_rate' => 0,
            'total_amount' => 15000000,
        ]);

        $inv = PurchaseInvoice::create([
            'invoice_number' => 'INV-REKAP-001',
            'purchase_order_id' => $po->id,
            'amount' => 15000000,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => 15000000,
            'invoice_date' => '2026-09-02',
            'due_date' => '2026-09-10',
            'status' => 'partial',
        ]);

        PurchasePayment::create([
            'purchase_invoice_id' => $inv->id,
            'user_id' => $this->admin->id,
            'payment_date' => '2026-09-03',
            'amount' => 5000000,
            'method' => 'cash',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('accounting.reports.payables-by-vendor'));

        $response->assertStatus(200);
        $suppliers = $response->viewData('suppliers');
        $this->assertCount(1, $suppliers);

        $sup = $suppliers->first();
        $this->assertEquals(10000000, $sup->total_payable);
        $this->assertEquals(1, $sup->open_invoices_count);
    }

    /**
     * Test Rekap Piutang by Customer
     */
    public function test_rekap_piutang_by_customer(): void
    {
        $so = SalesOrder::create([
            'so_number' => 'SO-REKAP-001',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'order_date' => '2026-09-01',
            'status' => 'confirmed',
            'tax_rate' => 0,
            'total_amount' => 25000000,
        ]);

        $inv = SalesInvoice::create([
            'invoice_number' => 'SINV-REKAP-001',
            'sales_order_id' => $so->id,
            'amount' => 25000000,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => 25000000,
            'invoice_date' => '2026-09-02',
            'due_date' => '2026-09-15',
            'status' => 'partial',
        ]);

        SalesPayment::create([
            'sales_invoice_id' => $inv->id,
            'user_id' => $this->admin->id,
            'payment_date' => '2026-09-04',
            'amount' => 10000000,
            'method' => 'transfer',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('accounting.reports.receivables-by-customer'));

        $response->assertStatus(200);
        $customers = $response->viewData('customers');
        $this->assertCount(1, $customers);

        $cust = $customers->first();
        $this->assertEquals(15000000, $cust->total_receivable);
        $this->assertEquals(1, $cust->open_invoices_count);
    }

    /**
     * Test Rekap Pembelian per Barang
     */
    public function test_rekap_pembelian_per_barang(): void
    {
        $po = PurchaseOrder::create([
            'po_number' => 'PO-RECAP-PROD-1',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'order_date' => '2026-09-01',
            'status' => 'confirmed',
            'tax_rate' => 0,
            'total_amount' => 20000000,
        ]);

        $inv = PurchaseInvoice::create([
            'invoice_number' => 'INV-RECAP-PROD-1',
            'purchase_order_id' => $po->id,
            'amount' => 20000000,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => 20000000,
            'invoice_date' => '2026-09-03',
            'due_date' => '2026-09-20',
            'status' => 'unpaid',
        ]);

        PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $inv->id,
            'product_id' => $this->product->id,
            'qty_invoiced' => 2,
            'unit_price' => 10000000,
            'subtotal' => 20000000,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('purchase.reports.recap-by-product'));

        $response->assertStatus(200);
        $products = $response->viewData('products');
        $item = $products->firstWhere('id', $this->product->id);

        $this->assertNotNull($item);
        $this->assertEquals(2, $item->total_qty);
        $this->assertEquals(20000000, $item->total_amount);
        $this->assertEquals(10000000, $item->avg_price);
    }

    /**
     * Test Rekap Penjualan per Barang (dengan Margin Kotor & Margin %)
     */
    public function test_rekap_penjualan_per_barang(): void
    {
        $so = SalesOrder::create([
            'so_number' => 'SO-RECAP-PROD-1',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'order_date' => '2026-09-01',
            'status' => 'confirmed',
            'tax_rate' => 0,
            'total_amount' => 25000000,
        ]);

        $inv = SalesInvoice::create([
            'invoice_number' => 'SINV-RECAP-PROD-1',
            'sales_order_id' => $so->id,
            'amount' => 25000000,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => 25000000,
            'invoice_date' => '2026-09-04',
            'due_date' => '2026-09-25',
            'status' => 'unpaid',
        ]);

        SalesInvoiceItem::create([
            'sales_invoice_id' => $inv->id,
            'product_id' => $this->product->id,
            'qty_invoiced' => 2,
            'unit_price' => 12500000,
            'subtotal' => 25000000,
            'cogs_amount' => 20000000, // 2 unit x 10jt
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('sales.reports.recap-by-product'));

        $response->assertStatus(200);
        $products = $response->viewData('products');
        $item = $products->firstWhere('id', $this->product->id);

        $this->assertNotNull($item);
        $this->assertEquals(2, $item->total_qty);
        $this->assertEquals(25000000, $item->total_amount);
        $this->assertEquals(20000000, $item->calculated_cogs);
        $this->assertEquals(5000000, $item->gross_margin); // 25jt - 20jt = 5jt
        $this->assertEquals(20.0, $item->margin_percentage); // 5jt / 25jt = 20%
    }

    /**
     * Test Rekap Retur per Barang (Retur Rate %)
     */
    public function test_rekap_retur_per_barang(): void
    {
        // 1. Catat penjualan 10 unit
        $so = SalesOrder::create([
            'so_number' => 'SO-RET-PROD-1',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'order_date' => '2026-09-01',
            'status' => 'confirmed',
            'tax_rate' => 0,
            'total_amount' => 125000000,
        ]);

        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'unit_price' => 12500000,
            'subtotal' => 125000000,
        ]);

        $inv = SalesInvoice::create([
            'invoice_number' => 'SINV-RET-PROD-1',
            'sales_order_id' => $so->id,
            'amount' => 125000000,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => 125000000,
            'invoice_date' => '2026-09-02',
            'due_date' => '2026-09-20',
            'status' => 'unpaid',
        ]);

        SalesInvoiceItem::create([
            'sales_invoice_id' => $inv->id,
            'product_id' => $this->product->id,
            'qty_invoiced' => 10,
            'unit_price' => 12500000,
            'subtotal' => 125000000,
            'cogs_amount' => 100000000,
        ]);

        // 2. Retur Penjualan 1 unit rusak
        $delivery = Delivery::create([
            'delivery_number' => 'SJ-RET-001',
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'condition_status' => 'baik',
            'delivery_date' => '2026-09-02',
            'status' => 'delivered',
        ]);

        $delItem = DeliveryItem::create([
            'delivery_id' => $delivery->id,
            'sales_order_item_id' => $soItem->id,
            'qty_delivered' => 10,
        ]);

        $sReturn = SalesReturn::create([
            'return_number' => 'SR-001',
            'customer_id' => $this->customer->id,
            'delivery_id' => $delivery->id,
            'return_date' => '2026-09-05',
            'status' => 'completed',
        ]);

        SalesReturnItem::create([
            'sales_return_id' => $sReturn->id,
            'delivery_item_id' => $delItem->id,
            'product_id' => $this->product->id,
            'qty' => 1,
            'condition' => 'rusak',
            'action' => 'karantina',
            'reason' => 'cacat pabrik',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('inventory.reports.returns-by-product'));

        $response->assertStatus(200);
        $paginatedProducts = $response->viewData('paginatedProducts');
        $item = collect($paginatedProducts->items())->firstWhere('id', $this->product->id);

        $this->assertNotNull($item);
        $this->assertEquals(1, $item->sales_return_damaged_qty);
        $this->assertEquals(1, $item->total_sales_return_qty);
        $this->assertEquals(10, $item->total_sold_qty);
        $this->assertEquals(10.0, $item->return_rate); // 1 / 10 = 10%
    }
}
