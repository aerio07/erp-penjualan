<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoiceDraftAndPostingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private Warehouse $warehouse;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->customer = Customer::create([
            'code' => 'CUST-TEST',
            'name' => 'PT Customer Retail',
            'is_active' => true,
            'payment_terms' => 30,
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
            'name' => 'Produk Penjualan Tes',
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
            ['code' => '1-1200', 'name' => 'Piutang Usaha', 'type' => 'asset'],
            ['code' => '1-1400', 'name' => 'Persediaan Barang Dagang', 'type' => 'asset'],
            ['code' => '2-1400', 'name' => 'PPN Keluaran', 'type' => 'liability'],
            ['code' => '4-1100', 'name' => 'Penjualan', 'type' => 'revenue'],
            ['code' => '5-1100', 'name' => 'Beban Pokok Penjualan', 'type' => 'expense'],
        ];

        foreach ($accounts as $acc) {
            ChartOfAccount::firstOrCreate(['code' => $acc['code']], [
                'name' => $acc['name'],
                'type' => $acc['type'],
                'is_active' => true,
            ]);
        }
    }

    private function createSoAndDelivery(int $qty = 10, float $price = 150000): array
    {
        $so = SalesOrder::create([
            'so_number' => 'SO-' . uniqid(),
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'subtotal' => $qty * $price,
            'tax_rate' => 11,
            'tax_amount' => ($qty * $price) * 0.11,
            'total_amount' => ($qty * $price) * 1.11,
        ]);

        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->product->id,
            'qty_ordered' => $qty,
            'qty_delivered' => $qty,
            'unit_price' => $price,
            'discount_percent' => 0,
            'subtotal' => $qty * $price,
        ]);

        $delivery = Delivery::create([
            'delivery_number' => 'SJ-' . uniqid(),
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'delivery_date' => now()->toDateString(),
            'status' => 'delivered',
            'is_invoiced' => false,
        ]);

        $deliveryItem = DeliveryItem::create([
            'delivery_id' => $delivery->id,
            'sales_order_item_id' => $soItem->id,
            'qty_delivered' => $qty,
            'invoiced_qty' => 0,
        ]);

        return [$so, $delivery, $soItem, $deliveryItem];
    }

    public function test_create_invoice_stores_as_draft_without_journal(): void
    {
        [$so, $delivery] = $this->createSoAndDelivery(10, 150000);

        $response = $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $so->id,
            'delivery_id' => $delivery->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'tax_rate' => 11,
            'notes' => 'Draft invoice awal',
        ]);

        $invoice = SalesInvoice::where('sales_order_id', $so->id)->first();
        $this->assertNotNull($invoice);
        $response->assertRedirect(route('sales.invoices.index'));

        // Verifikasi status draft
        $this->assertEquals('draft', $invoice->status);
        $this->assertTrue($invoice->isDraft());
        $this->assertFalse($invoice->isPosted());

        // Verifikasi Surat Jalan terkait telah ditandai is_invoiced
        $delivery->refresh();
        $this->assertTrue((bool) $delivery->is_invoiced);
        $this->assertEquals($invoice->id, $delivery->sales_invoice_id);

        // Verifikasi TIDAK ADA jurnal akuntansi yang terbit
        $journal = JournalEntry::where('reference_type', SalesInvoice::class)
            ->where('reference_id', $invoice->id)
            ->first();
        $this->assertNull($journal);
    }

    public function test_draft_invoice_is_excluded_from_receivables_and_reports(): void
    {
        [$so, $delivery] = $this->createSoAndDelivery(10, 150000);

        $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $so->id,
            'delivery_id' => $delivery->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'tax_rate' => 11,
        ]);

        $invoice = SalesInvoice::where('sales_order_id', $so->id)->first();
        $this->assertEquals('draft', $invoice->status);

        // Cek Laporan Piutang & AR Aging
        $arResponse = $this->actingAs($this->admin)->get(route('accounting.reports.receivables'));
        $arResponse->assertOk();
        $openInvoices = $arResponse->viewData('openInvoices');
        $this->assertFalse($openInvoices->contains('id', $invoice->id));

        // Cek Laporan Tagihan Akan Jatuh Tempo
        $upcomingResponse = $this->actingAs($this->admin)->get(route('accounting.reports.receivables-upcoming'));
        $upcomingResponse->assertOk();
        $upcomingInvoices = $upcomingResponse->viewData('invoices');
        $this->assertFalse($upcomingInvoices->contains('id', $invoice->id));

        // Cek Laporan Rekap Piutang Customer
        $custReportResponse = $this->actingAs($this->admin)->get(route('accounting.reports.receivables-by-customer'));
        $custReportResponse->assertOk();
        $customers = $custReportResponse->viewData('customers');
        $testCust = $customers->firstWhere('id', $this->customer->id);
        $this->assertTrue($testCust === null || $testCust->total_receivable == 0);

        // Cek Dashboard total piutang
        $dashResponse = $this->actingAs($this->admin)->get(route('dashboard'));
        $dashResponse->assertOk();
        $totalPiutang = $dashResponse->viewData('totalPiutang');
        $this->assertEquals(0, $totalPiutang);
    }

    public function test_cannot_record_payment_for_draft_invoice(): void
    {
        [$so, $delivery] = $this->createSoAndDelivery(10, 150000);

        $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $so->id,
            'delivery_id' => $delivery->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'tax_rate' => 11,
        ]);

        $invoice = SalesInvoice::where('sales_order_id', $so->id)->first();

        // Form create payment tidak menampilkan invoice draft
        $createPayResponse = $this->actingAs($this->admin)->get(route('sales.payments.create'));
        $createPayResponse->assertOk();
        $unpaidInvoices = $createPayResponse->viewData('unpaidInvoices');
        $this->assertFalse($unpaidInvoices->contains('id', $invoice->id));

        // Submit pembayaran untuk draft invoice harus ditolak
        $payResponse = $this->actingAs($this->admin)->post(route('sales.payments.store'), [
            'sales_invoice_id' => $invoice->id,
            'amount' => 500000,
            'payment_date' => now()->toDateString(),
            'method' => 'transfer',
        ]);

        $payResponse->assertSessionHas('error');
        $this->assertEquals('draft', $invoice->fresh()->status);
    }

    public function test_cannot_return_goods_if_delivery_has_draft_invoice(): void
    {
        [$so, $delivery, $soItem, $deliveryItem] = $this->createSoAndDelivery(10, 150000);

        $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $so->id,
            'delivery_id' => $delivery->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'tax_rate' => 11,
        ]);

        $returnResponse = $this->actingAs($this->admin)->post(route('sales.returns.store'), [
            'delivery_id' => $delivery->id,
            'return_date' => now()->toDateString(),
            'reason' => 'Barang rusak dalam perjalanan',
            'items' => [
                [
                    'delivery_item_id' => $deliveryItem->id,
                    'product_id' => $this->product->id,
                    'qty' => 2,
                    'notes' => 'Pecah',
                ]
            ],
        ]);

        $returnResponse->assertSessionHas('error');
    }

    public function test_edit_and_update_draft_invoice(): void
    {
        [$so, $delivery] = $this->createSoAndDelivery(10, 150000);

        $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $so->id,
            'delivery_id' => $delivery->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'tax_rate' => 11,
        ]);

        $invoice = SalesInvoice::where('sales_order_id', $so->id)->first();

        // Edit page loads
        $editResponse = $this->actingAs($this->admin)->get(route('sales.invoices.edit', $invoice));
        $editResponse->assertOk();

        // Update draft invoice
        $updateResponse = $this->actingAs($this->admin)->put(route('sales.invoices.update', $invoice), [
            'tax_rate' => 12,
            'invoice_date' => now()->addDay()->toDateString(),
            'due_date' => now()->addDays(45)->toDateString(),
            'tax_invoice_number' => '010.001-26.00000001',
            'notes' => 'Catatan penjualan diubah',
        ]);

        $updateResponse->assertRedirect(route('sales.invoices.show', $invoice));
        $invoice->refresh();

        $this->assertEquals(12, (float) $invoice->tax_rate);
        $this->assertEquals(180000, (float) $invoice->tax_amount);
        $this->assertEquals(1680000, (float) $invoice->total_amount);
        $this->assertEquals('010.001-26.00000001', $invoice->tax_invoice_number);
        $this->assertEquals('Catatan penjualan diubah', $invoice->notes);
    }

    public function test_destroy_draft_invoice_releases_delivery(): void
    {
        [$so, $delivery] = $this->createSoAndDelivery(10, 150000);

        $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $so->id,
            'delivery_id' => $delivery->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'tax_rate' => 11,
        ]);

        $invoice = SalesInvoice::where('sales_order_id', $so->id)->first();
        $this->assertNotNull($invoice);

        $delivery->refresh();
        $this->assertTrue((bool) $delivery->is_invoiced);

        // Delete draft invoice
        $deleteResponse = $this->actingAs($this->admin)->delete(route('sales.invoices.destroy', $invoice));
        $deleteResponse->assertRedirect(route('sales.invoices.index'));

        // Invoice harus terhapus
        $this->assertNull(SalesInvoice::find($invoice->id));

        // Surat Jalan harus dilepaskan (is_invoiced = false, sales_invoice_id = null)
        $delivery->refresh();
        $this->assertFalse((bool) $delivery->is_invoiced);
        $this->assertNull($delivery->sales_invoice_id);
    }

    public function test_post_draft_invoice_publishes_journal_and_sets_unpaid(): void
    {
        [$so, $delivery] = $this->createSoAndDelivery(10, 150000);

        $this->actingAs($this->admin)->post(route('sales.invoices.store'), [
            'sales_order_id' => $so->id,
            'delivery_id' => $delivery->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'tax_rate' => 11,
        ]);

        $invoice = SalesInvoice::where('sales_order_id', $so->id)->first();
        $this->assertEquals('draft', $invoice->status);

        // Klik Posting Jurnal
        $postResponse = $this->actingAs($this->admin)->post(route('sales.invoices.post', $invoice));
        $postResponse->assertRedirect(route('sales.invoices.show', $invoice));

        $invoice->refresh();
        $this->assertEquals('unpaid', $invoice->status);
        $this->assertTrue($invoice->isPosted());
        $this->assertFalse($invoice->isDraft());

        // Jurnal Akuntansi otomatis terbentuk dan berstatus posted
        $journal = JournalEntry::where('reference_type', SalesInvoice::class)
            ->where('reference_id', $invoice->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertEquals('posted', $journal->status);
        // Total debit = Total Tagihan (Piutang) + COGS
        // Total credit = Penjualan + PPN Keluaran + Persediaan (COGS)
        $this->assertEquals((float) $journal->total_debit, (float) $journal->total_credit);

        // Setelah diposting, invoice tidak dapat diedit atau dihapus
        $deleteResponse = $this->actingAs($this->admin)->delete(route('sales.invoices.destroy', $invoice));
        $deleteResponse->assertRedirect(route('sales.invoices.show', $invoice));
        $this->assertNotNull(SalesInvoice::find($invoice->id));

        $editResponse = $this->actingAs($this->admin)->get(route('sales.invoices.edit', $invoice));
        $editResponse->assertRedirect(route('sales.invoices.show', $invoice));
    }
}
