<?php

namespace Tests\Feature;

use App\Enums\SalesOrderStatus;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockReservation;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SalesInvoiceService;
use App\Services\StockService;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryWarehouseStockAndSplitInvoicingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Warehouse $warehouseA;
    protected Warehouse $warehouseB;
    protected Customer $customer;
    protected Product $product;
    protected StockService $stockService;
    protected SalesInvoiceService $salesInvoiceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ChartOfAccountSeeder::class);

        $this->stockService = app(StockService::class);
        $this->salesInvoiceService = app(SalesInvoiceService::class);
        $this->admin = User::factory()->create(['role' => 'admin']);

        $this->warehouseA = Warehouse::create([
            'code' => 'WHA',
            'name' => 'Gudang Jakarta',
            'is_active' => true,
        ]);

        $this->warehouseB = Warehouse::create([
            'code' => 'WHB',
            'name' => 'Gudang Surabaya',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'code' => 'CUST-SPLIT',
            'name' => 'PT Pelanggan Nasional',
            'payment_term' => 30,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'sku' => 'LAPTOP-PRO',
            'name' => 'Laptop Pro 15',
            'unit' => 'unit',
            'purchase_price' => 5000000,
            'sell_price' => 8000000,
            'is_active' => true,
        ]);

        // Stok: 40 unit di Gudang A, 30 unit di Gudang B
        $this->stockService->recordMovement([
            'product_id'    => $this->product->id,
            'warehouse_id'  => $this->warehouseA->id,
            'type'          => 'in',
            'quantity'      => 40,
            'unit_cost'     => 5000000,
            'movement_date' => now()->toDateString(),
            'notes'         => 'Stok awal Gudang A',
            'user_id'       => $this->admin->id,
        ]);

        $this->stockService->recordMovement([
            'product_id'    => $this->product->id,
            'warehouse_id'  => $this->warehouseB->id,
            'type'          => 'in',
            'quantity'      => 30,
            'unit_cost'     => 5000000,
            'movement_date' => now()->toDateString(),
            'notes'         => 'Stok awal Gudang B',
            'user_id'       => $this->admin->id,
        ]);
    }

    /**
     * Test: SO 100 unit membagi reservasi 40 di Gudang A, 30 di Gudang B, dan 30 backorder.
     * Matriks stok per gudang pada form Surat Jalan harus menampilkan ketersediaan yang tepat.
     */
    public function test_stock_matrix_shows_correct_quantities_per_warehouse(): void
    {
        $so = SalesOrder::create([
            'so_number'       => 'SO-SPLIT-01',
            'customer_id'     => $this->customer->id,
            'user_id'         => $this->admin->id,
            'status'          => 'confirmed',
            'order_date'      => now()->toDateString(),
            'discount_amount' => 10000000, // Diskon header 10jt
            'tax_rate'        => 11,
            'tax_amount'      => 86900000,
            'total_amount'    => 876900000,
        ]);

        $soItem = SalesOrderItem::create([
            'sales_order_id'   => $so->id,
            'product_id'       => $this->product->id,
            'qty_ordered'      => 100,
            'qty_delivered'    => 0,
            'unit_price'       => 8000000,
            'discount_percent' => 0,
            'discount_amount'  => 0,
            'subtotal'         => 800000000,
        ]);

        // Alokasikan stok SO secara otomatis
        $this->stockService->allocateStockForSalesOrder($so);

        // Ambil matriks stok untuk form Surat Jalan
        $matrix = $this->stockService->getWarehouseStockMatrixForOrder($so);

        $this->assertArrayHasKey($soItem->id, $matrix);
        $whAMatrix = $matrix[$soItem->id]['warehouses'][$this->warehouseA->id];
        $whBMatrix = $matrix[$soItem->id]['warehouses'][$this->warehouseB->id];

        // Gudang A: On Hand 40, Teralokasi 40, Max Deliverable 40
        $this->assertEquals(40, $whAMatrix['on_hand']);
        $this->assertEquals(40, $whAMatrix['reserved_for_so']);
        $this->assertEquals(40, $whAMatrix['max_deliverable']);

        // Gudang B: On Hand 30, Teralokasi 30, Max Deliverable 30
        $this->assertEquals(30, $whBMatrix['on_hand']);
        $this->assertEquals(30, $whBMatrix['reserved_for_so']);
        $this->assertEquals(30, $whBMatrix['max_deliverable']);

        // Response halaman create menyertakan stockMatrixBySo
        $response = $this->actingAs($this->admin)->get(route('sales.deliveries.create', ['so_id' => $so->id]));
        $response->assertStatus(200);
        $response->assertViewHas('stockMatrixBySo');
    }

    /**
     * Test: Validasi backend dengan pessimistic lock menolak pengiriman jika melebihi stok gudang yang dipilih.
     */
    public function test_delivery_creation_is_strictly_validated_against_selected_warehouse_stock(): void
    {
        $so = SalesOrder::create([
            'so_number'       => 'SO-SPLIT-02',
            'customer_id'     => $this->customer->id,
            'user_id'         => $this->admin->id,
            'status'          => 'confirmed',
            'order_date'      => now()->toDateString(),
            'discount_amount' => 0,
            'tax_rate'        => 0,
            'tax_amount'      => 0,
            'total_amount'    => 800000000,
        ]);

        $soItem = SalesOrderItem::create([
            'sales_order_id'   => $so->id,
            'product_id'       => $this->product->id,
            'qty_ordered'      => 100,
            'qty_delivered'    => 0,
            'unit_price'       => 8000000,
            'discount_percent' => 0,
            'discount_amount'  => 0,
            'subtotal'         => 800000000,
        ]);

        $this->stockService->allocateStockForSalesOrder($so);

        // 1. Coba kirim 50 dari Gudang A (Gudang A cuma punya 40) -> Harus DITOLAK
        $responseFail = $this->actingAs($this->admin)->post(route('sales.deliveries.store'), [
            'sales_order_id' => $so->id,
            'warehouse_id'   => $this->warehouseA->id,
            'delivery_date'  => now()->toDateString(),
            'items'          => [
                [
                    'sales_order_item_id' => $soItem->id,
                    'qty_delivered'       => 50,
                ],
            ],
        ]);

        $responseFail->assertSessionHas('error');
        $this->assertDatabaseMissing('deliveries', [
            'sales_order_id' => $so->id,
        ]);

        // 2. Kirim 40 dari Gudang A -> Harus BERHASIL
        $responseSuccess = $this->actingAs($this->admin)->post(route('sales.deliveries.store'), [
            'sales_order_id' => $so->id,
            'warehouse_id'   => $this->warehouseA->id,
            'delivery_date'  => now()->toDateString(),
            'items'          => [
                [
                    'sales_order_item_id' => $soItem->id,
                    'qty_delivered'       => 40,
                ],
            ],
        ]);

        $responseSuccess->assertRedirect();
        $this->assertDatabaseHas('deliveries', [
            'sales_order_id' => $so->id,
            'warehouse_id'   => $this->warehouseA->id,
        ]);

        // Stok fisik Gudang A sekarang harus 0
        $this->assertEquals(0, $this->stockService->getOnHandStock($this->product->id, $this->warehouseA->id));
        // Stok fisik Gudang B tetap utuh 30
        $this->assertEquals(30, $this->stockService->getOnHandStock($this->product->id, $this->warehouseB->id));
    }

    /**
     * Test: Pengiriman parsial dari 2 gudang berbeda dapat ditagihkan secara terpisah dengan diskon & PPN proporsional.
     */
    public function test_split_deliveries_can_be_invoiced_independently(): void
    {
        $so = SalesOrder::create([
            'so_number'       => 'SO-SPLIT-03',
            'customer_id'     => $this->customer->id,
            'user_id'         => $this->admin->id,
            'status'          => 'confirmed',
            'order_date'      => now()->toDateString(),
            'discount_amount' => 7000000, // Diskon header 7jt
            'tax_rate'        => 11,
            'tax_amount'      => 60830000,
            'total_amount'    => 613830000,
        ]);

        $soItem = SalesOrderItem::create([
            'sales_order_id'   => $so->id,
            'product_id'       => $this->product->id,
            'qty_ordered'      => 70, // Total order 70 (40 di A, 30 di B)
            'qty_delivered'    => 0,
            'unit_price'       => 8000000,
            'discount_percent' => 0,
            'discount_amount'  => 0,
            'subtotal'         => 560000000,
        ]);

        $this->stockService->allocateStockForSalesOrder($so);

        // 1. Buat Surat Jalan 1 dari Gudang A (40 unit) dan langsung auto-generate invoice
        $this->actingAs($this->admin)->post(route('sales.deliveries.store'), [
            'sales_order_id'        => $so->id,
            'warehouse_id'          => $this->warehouseA->id,
            'delivery_date'         => now()->toDateString(),
            'auto_generate_invoice' => 1,
            'invoice_date'          => now()->toDateString(),
            'due_date'              => now()->addDays(30)->toDateString(),
            'items'                 => [
                [
                    'sales_order_item_id' => $soItem->id,
                    'qty_delivered'       => 40,
                ],
            ],
        ]);

        $deliveryA = Delivery::where('sales_order_id', $so->id)->where('warehouse_id', $this->warehouseA->id)->first();
        $this->assertNotNull($deliveryA);
        $this->assertTrue((bool) $deliveryA->is_invoiced);

        $invoiceA = SalesInvoice::where('delivery_id', $deliveryA->id)->first();
        $this->assertNotNull($invoiceA);

        // 2. Buat Surat Jalan 2 dari Gudang B (30 unit) dan langsung auto-generate invoice
        $this->actingAs($this->admin)->post(route('sales.deliveries.store'), [
            'sales_order_id'        => $so->id,
            'warehouse_id'          => $this->warehouseB->id,
            'delivery_date'         => now()->toDateString(),
            'auto_generate_invoice' => 1,
            'invoice_date'          => now()->toDateString(),
            'due_date'              => now()->addDays(30)->toDateString(),
            'items'                 => [
                [
                    'sales_order_item_id' => $soItem->id,
                    'qty_delivered'       => 30,
                ],
            ],
        ]);

        $deliveryB = Delivery::where('sales_order_id', $so->id)->where('warehouse_id', $this->warehouseB->id)->first();
        $this->assertNotNull($deliveryB);
        $this->assertTrue((bool) $deliveryB->is_invoiced);

        $invoiceB = SalesInvoice::where('delivery_id', $deliveryB->id)->first();
        $this->assertNotNull($invoiceB);

        // Total akumulasi kedua invoice harus pas persis sama dengan total SO (termasuk diskon dan PPN)
        $totalInvoicedAmount = $invoiceA->total_amount + $invoiceB->total_amount;
        $this->assertEquals((float) $so->total_amount, (float) $totalInvoicedAmount);

        // Status SO harus 'done' karena semua 70 unit telah dikirim
        $so->refresh();
        $this->assertEquals(SalesOrderStatus::Done, $so->status);
    }
}
