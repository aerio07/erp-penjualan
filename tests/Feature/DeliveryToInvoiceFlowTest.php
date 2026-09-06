<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryToInvoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Warehouse $warehouse;
    protected Customer $customer;
    protected Product $product;
    protected StockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ChartOfAccountSeeder::class);

        $this->stockService = app(StockService::class);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->warehouse = Warehouse::create([
            'code' => 'WH-DELIV',
            'name' => 'Gudang Pengiriman',
            'is_active' => true,
        ]);
        $this->customer = Customer::create([
            'code' => 'CUST-DELIV',
            'name' => 'PT Mitra Distribusi',
            'payment_term' => 14,
            'is_active' => true,
        ]);
        $this->product = Product::create([
            'sku' => 'PROD-DELIV-1',
            'name' => 'Komoditas Unggulan',
            'unit' => 'pcs',
            'purchase_price' => 50000,
            'sell_price' => 75000,
            'is_active' => true,
        ]);

        // Berikan stok awal di gudang
        $this->stockService->recordMovement([
            'product_id'     => $this->product->id,
            'warehouse_id'   => $this->warehouse->id,
            'type'           => 'in',
            'quantity'       => 100,
            'unit_cost'      => 50000,
            'movement_date'  => now()->subDays(5)->toDateString(),
            'notes'          => 'Stok awal untuk test pengiriman',
            'user_id'        => $this->admin->id,
        ]);
    }

    private function createSalesOrder(int $qty = 10, float $taxRate = 11): SalesOrder
    {
        $unitPrice = 75000;
        $subtotal = $qty * $unitPrice;
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $totalAmount = $subtotal + $taxAmount;

        $so = SalesOrder::create([
            'so_number'    => 'SO-DELIV-' . uniqid(),
            'customer_id'  => $this->customer->id,
            'user_id'      => $this->admin->id,
            'status'       => 'confirmed',
            'order_date'   => now()->subDays(2)->toDateString(),
            'discount_amount' => 0,
            'tax_rate'     => $taxRate,
            'tax_amount'   => $taxAmount,
            'total_amount' => $totalAmount,
        ]);

        SalesOrderItem::create([
            'sales_order_id'   => $so->id,
            'product_id'       => $this->product->id,
            'qty_ordered'      => $qty,
            'qty_delivered'    => 0,
            'unit_price'       => $unitPrice,
            'discount_percent' => 0,
            'discount_amount'  => 0,
            'subtotal'         => $subtotal,
        ]);

        return $so;
    }

    public function test_instant_delivery_creation_auto_generates_invoice_and_posts_journal(): void
    {
        $so = $this->createSalesOrder(10);
        $soItem = $so->items->first();

        $response = $this->actingAs($this->admin)
            ->post(route('sales.deliveries.store'), [
                'sales_order_id'         => $so->id,
                'warehouse_id'           => $this->warehouse->id,
                'delivery_date'          => '2026-09-01',
                'auto_generate_invoice'  => '1',
                'received_date'          => '2026-09-02',
                'invoice_date'           => '2026-09-03',
                'due_date'               => '2026-09-17',
                'invoice_tax_rate'       => 11,
                'receipt_notes'          => 'Barang diserahterimakan langsung',
                'items' => [
                    [
                        'sales_order_item_id' => $soItem->id,
                        'qty_delivered'       => 10,
                    ]
                ]
            ]);

        $response->assertRedirect(route('sales.deliveries.index'));
        $response->assertSessionHas('success');

        // Pastikan Delivery tercatat dengan status delivered dan received_date tersimpan
        $delivery = Delivery::where('sales_order_id', $so->id)->first();
        $this->assertNotNull($delivery);
        $this->assertEquals('delivered', $delivery->status);
        $this->assertEquals('2026-09-01', $delivery->delivery_date->toDateString());
        $this->assertEquals('2026-09-02', $delivery->received_date->toDateString());
        $this->assertTrue((bool) $delivery->is_invoiced);
        $this->assertNotNull($delivery->sales_invoice_id);

        // Pastikan Sales Invoice terbit otomatis dengan tanggal custom
        $invoice = SalesInvoice::find($delivery->sales_invoice_id);
        $this->assertNotNull($invoice);
        $this->assertEquals('2026-09-03', $invoice->invoice_date->toDateString());
        $this->assertEquals('2026-09-17', $invoice->due_date->toDateString());
        $this->assertEquals(750000, (float) $invoice->amount);
        $this->assertEquals(82500, (float) $invoice->tax_amount);
        $this->assertEquals(832500, (float) $invoice->total_amount);
        $this->assertEquals('unpaid', $invoice->status);

        // Pastikan Jurnal otomatis terposting
        $journal = JournalEntry::where('reference_type', SalesInvoice::class)
            ->where('reference_id', $invoice->id)
            ->first();
        $this->assertNotNull($journal);
        $this->assertEquals('posted', $journal->status);
    }

    public function test_staged_delivery_creation_is_in_transit_and_blocks_premature_invoicing(): void
    {
        $so = $this->createSalesOrder(5);
        $soItem = $so->items->first();

        // Buat Surat Jalan tanpa opsi auto-invoice (bertahap)
        $response = $this->actingAs($this->admin)
            ->post(route('sales.deliveries.store'), [
                'sales_order_id'         => $so->id,
                'warehouse_id'           => $this->warehouse->id,
                'delivery_date'          => '2026-09-01',
                'auto_generate_invoice'  => '0',
                'items' => [
                    [
                        'sales_order_item_id' => $soItem->id,
                        'qty_delivered'       => 5,
                    ]
                ]
            ]);

        $response->assertRedirect(route('sales.deliveries.index'));
        $delivery = Delivery::where('sales_order_id', $so->id)->first();
        $this->assertNotNull($delivery);
        $this->assertEquals('in_transit', $delivery->status);
        $this->assertNull($delivery->received_date);
        $this->assertFalse((bool) $delivery->is_invoiced);
        $this->assertNull($delivery->sales_invoice_id);

        // Percobaan menerbitkan invoice saat status masih in_transit harus ditolak
        $invResponse = $this->actingAs($this->admin)
            ->post(route('sales.invoices.store'), [
                'sales_order_id' => $so->id,
                'delivery_id'    => $delivery->id,
                'invoice_date'   => '2026-09-01',
                'due_date'       => '2026-09-15',
                'tax_rate'       => 11,
            ]);

        $invResponse->assertSessionHas('error');
        $this->assertDatabaseMissing('sales_invoices', [
            'delivery_id' => $delivery->id,
        ]);
    }

    public function test_confirm_receipt_transitions_to_delivered_and_permits_subsequent_invoicing(): void
    {
        $so = $this->createSalesOrder(8);
        $soItem = $so->items->first();

        // Buat pengiriman in_transit
        $this->actingAs($this->admin)
            ->post(route('sales.deliveries.store'), [
                'sales_order_id' => $so->id,
                'warehouse_id'   => $this->warehouse->id,
                'delivery_date'  => '2026-09-01',
                'items' => [
                    ['sales_order_item_id' => $soItem->id, 'qty_delivered' => 8]
                ]
            ]);

        $delivery = Delivery::where('sales_order_id', $so->id)->first();
        $this->assertEquals('in_transit', $delivery->status);

        // Konfirmasi penerimaan tanpa auto-invoice
        $confirmResponse = $this->actingAs($this->admin)
            ->patch(route('sales.deliveries.confirm-receipt', $delivery), [
                'received_date'  => '2026-09-04',
                'recipient_name' => 'Bpk. Ahmad Gudang',
                'receipt_notes'  => 'Diterima dalam kondisi baik',
            ]);

        $confirmResponse->assertSessionHas('success');
        $delivery->refresh();
        $this->assertEquals('delivered', $delivery->status);
        $this->assertEquals('2026-09-04', $delivery->received_date->toDateString());
        $this->assertEquals('Bpk. Ahmad Gudang', $delivery->recipient_name);
        $this->assertFalse((bool) $delivery->is_invoiced);

        // Sekarang terbitkan invoice manual lewat modul Invoice Penjualan
        $invResponse = $this->actingAs($this->admin)
            ->post(route('sales.invoices.store'), [
                'sales_order_id' => $so->id,
                'delivery_id'    => $delivery->id,
                'invoice_date'   => '2026-09-04',
                'due_date'       => '2026-09-18',
                'tax_rate'       => 11,
            ]);

        $invResponse->assertRedirect(route('sales.invoices.index'));
        $delivery->refresh();
        $this->assertTrue((bool) $delivery->is_invoiced);
        $this->assertNotNull($delivery->sales_invoice_id);
    }

    public function test_confirm_receipt_with_auto_generate_invoice_immediately_creates_invoice(): void
    {
        $so = $this->createSalesOrder(6);
        $soItem = $so->items->first();

        // Buat pengiriman in_transit
        $this->actingAs($this->admin)
            ->post(route('sales.deliveries.store'), [
                'sales_order_id' => $so->id,
                'warehouse_id'   => $this->warehouse->id,
                'delivery_date'  => '2026-09-01',
                'items' => [
                    ['sales_order_item_id' => $soItem->id, 'qty_delivered' => 6]
                ]
            ]);

        $delivery = Delivery::where('sales_order_id', $so->id)->first();

        // Konfirmasi penerimaan DENGAN centang auto_generate_invoice
        $confirmResponse = $this->actingAs($this->admin)
            ->patch(route('sales.deliveries.confirm-receipt', $delivery), [
                'received_date'          => '2026-09-05',
                'recipient_name'         => 'Ibu Siti Purchasing',
                'auto_generate_invoice'  => '1',
                'invoice_date'           => '2026-09-06',
                'due_date'               => '2026-09-20',
            ]);

        $confirmResponse->assertSessionHas('success');
        $delivery->refresh();
        $this->assertEquals('delivered', $delivery->status);
        $this->assertEquals('2026-09-05', $delivery->received_date->toDateString());
        $this->assertTrue((bool) $delivery->is_invoiced);
        $this->assertNotNull($delivery->sales_invoice_id);

        $invoice = SalesInvoice::find($delivery->sales_invoice_id);
        $this->assertNotNull($invoice);
        $this->assertEquals('2026-09-06', $invoice->invoice_date->toDateString());
        $this->assertEquals('2026-09-20', $invoice->due_date->toDateString());
    }

    public function test_anti_double_invoicing_protection(): void
    {
        $so = $this->createSalesOrder(4);
        $soItem = $so->items->first();

        // Buat pengiriman instant yang sudah langsung mengenerate invoice
        $this->actingAs($this->admin)
            ->post(route('sales.deliveries.store'), [
                'sales_order_id'         => $so->id,
                'warehouse_id'           => $this->warehouse->id,
                'delivery_date'          => '2026-09-01',
                'auto_generate_invoice'  => '1',
                'received_date'          => '2026-09-01',
                'items' => [
                    ['sales_order_item_id' => $soItem->id, 'qty_delivered' => 4]
                ]
            ]);

        $delivery = Delivery::where('sales_order_id', $so->id)->first();
        $this->assertTrue((bool) $delivery->is_invoiced);
        $invoiceCountBefore = SalesInvoice::count();

        // Coba invoice ulang Surat Jalan yang sama
        $dupResponse = $this->actingAs($this->admin)
            ->post(route('sales.invoices.store'), [
                'sales_order_id' => $so->id,
                'delivery_id'    => $delivery->id,
                'invoice_date'   => '2026-09-02',
                'due_date'       => '2026-09-16',
                'tax_rate'       => 11,
            ]);

        $dupResponse->assertSessionHas('error');
        $this->assertEquals($invoiceCountBefore, SalesInvoice::count());
    }
}
