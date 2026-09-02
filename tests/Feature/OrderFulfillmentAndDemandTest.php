<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\ProcurementDemand;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFulfillmentAndDemandTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Warehouse $warehouse;
    private Product $product;
    private Customer $customer;
    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->warehouse = Warehouse::create([
            'code' => 'WH-MAIN',
            'name' => 'Gudang Utama',
            'is_active' => true,
        ]);
        $this->product = Product::create([
            'sku' => 'PROD-FULFILL-001',
            'name' => 'Produk Fulfillment Test',
            'category' => 'Testing',
            'unit' => 'pcs',
            'purchase_price' => 50000,
            'sell_price' => 75000,
            'min_stock' => 5,
            'is_active' => true,
        ]);
        $this->customer = Customer::create([
            'code' => 'CUST-FULFILL',
            'name' => 'PT Customer Fulfillment',
            'is_active' => true,
        ]);
        $this->supplier = Supplier::create([
            'code' => 'SUPP-FULFILL',
            'name' => 'PT Supplier Pengadaan',
            'is_active' => true,
        ]);
    }

    public function test_sales_order_confirmation_with_zero_stock_sets_backorder_and_creates_procurement_demand(): void
    {
        $so = SalesOrder::create([
            'so_number' => 'SO-ZERO-001',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'total_amount' => 750000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'unit_price' => 75000,
            'subtotal' => 750000,
        ]);

        $this->actingAs($this->admin)->patch(route('sales.orders.confirm', $so));

        $so->refresh();
        $this->assertEquals('confirmed', $so->status);
        $this->assertEquals('backorder', $so->fulfillment_status);
        $this->assertFalse($so->canCreateDelivery());

        // Assert Procurement Demand created
        $demand = ProcurementDemand::where('sales_order_id', $so->id)->first();
        $this->assertNotNull($demand);
        $this->assertEquals(10, $demand->qty_demanded);
        $this->assertEquals('pending', $demand->status);
        $this->assertEquals(0, $demand->qty_fulfilled);

        // Assert 5 Dimensions
        $this->assertEquals(0, $this->product->onHandStock());
        $this->assertEquals(0, $this->product->reservedStock());
        $this->assertEquals(0, $this->product->availableStock());
        $this->assertEquals(10, $this->product->backorderStock());
    }

    public function test_sales_order_confirmation_with_partial_stock_allocates_reservation_and_demand(): void
    {
        // Add 4 pcs on-hand stock
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'in',
            'quantity' => 4,
            'unit_cost' => 50000,
            'movement_date' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        $so = SalesOrder::create([
            'so_number' => 'SO-PARTIAL-001',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'total_amount' => 750000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'unit_price' => 75000,
            'subtotal' => 750000,
        ]);

        $this->actingAs($this->admin)->patch(route('sales.orders.confirm', $so));

        $so->refresh();
        $this->assertEquals('confirmed', $so->status);
        $this->assertEquals('partially_available', $so->fulfillment_status);
        $this->assertTrue($so->canCreateDelivery());
        $this->assertTrue($so->canCreatePartialDelivery());

        // Assert 4 pcs reserved, 6 pcs demanded
        $reservation = StockReservation::where('sales_order_item_id', $soItem->id)->first();
        $this->assertNotNull($reservation);
        $this->assertEquals(4, $reservation->qty_reserved);
        $this->assertEquals('active', $reservation->status);

        $demand = ProcurementDemand::where('sales_order_item_id', $soItem->id)->first();
        $this->assertNotNull($demand);
        $this->assertEquals(6, $demand->qty_demanded);

        // Assert 5 Dimensions
        $this->assertEquals(4, $this->product->onHandStock());
        $this->assertEquals(4, $this->product->reservedStock());
        $this->assertEquals(0, $this->product->availableStock());
        $this->assertEquals(6, $this->product->backorderStock());
    }

    public function test_goods_receipt_targeted_allocation_fulfills_demand_and_upgrades_so_to_ready_to_ship(): void
    {
        // 1. Customer orders 10 pcs when stock is 0
        $so = SalesOrder::create([
            'so_number' => 'SO-TARGET-001',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'total_amount' => 750000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'unit_price' => 75000,
            'subtotal' => 750000,
        ]);
        $this->actingAs($this->admin)->patch(route('sales.orders.confirm', $so));
        $this->assertEquals('backorder', $so->refresh()->fulfillment_status);

        // 2. Purchasing issues PO for 10 pcs to Supplier
        $po = PurchaseOrder::create([
            'po_number' => 'PO-TARGET-001',
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->admin->id,
            'status' => 'confirmed',
            'order_date' => now()->toDateString(),
            'total_amount' => 500000,
        ]);
        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'unit_price' => 50000,
            'subtotal' => 500000,
        ]);

        // Assert Incoming stock
        $this->assertEquals(10, $this->product->incomingStock());

        // 3. Goods Receipt arrives at warehouse
        $this->actingAs($this->admin)->post(route('purchase.goods-receipts.store'), [
            'purchase_order_id' => $po->id,
            'received_date' => now()->toDateString(),
            'items' => [
                [
                    'purchase_order_item_id' => $poItem->id,
                    'warehouse_id' => $this->warehouse->id,
                    'qty_physical' => 10,
                    'qty_rejected' => 0,
                ],
            ],
        ]);

        // 4. Verify targeted fulfillment
        $demand = ProcurementDemand::where('sales_order_id', $so->id)->first();
        $this->assertEquals('fulfilled', $demand->status);
        $this->assertEquals(10, $demand->qty_fulfilled);

        $so->refresh();
        $this->assertEquals('ready_to_ship', $so->fulfillment_status);
        $this->assertTrue($so->canCreateDelivery());

        $this->assertEquals(10, $this->product->onHandStock());
        $this->assertEquals(10, $this->product->reservedStock());
        $this->assertEquals(0, $this->product->availableStock());
        $this->assertEquals(0, $this->product->backorderStock());
    }

    public function test_delivery_creation_consumes_reservation_and_updates_status_to_delivered(): void
    {
        // 1. Initial 10 pcs in stock
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'in',
            'quantity' => 10,
            'unit_cost' => 50000,
            'movement_date' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        $so = SalesOrder::create([
            'so_number' => 'SO-DELIV-001',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'total_amount' => 750000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'unit_price' => 75000,
            'subtotal' => 750000,
        ]);
        $this->actingAs($this->admin)->patch(route('sales.orders.confirm', $so));
        $this->assertEquals('ready_to_ship', $so->refresh()->fulfillment_status);

        // 2. Create Delivery for all 10 pcs
        $this->actingAs($this->admin)->post(route('sales.deliveries.store'), [
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'items' => [
                [
                    'sales_order_item_id' => $soItem->id,
                    'qty_delivered' => 10,
                ],
            ],
        ]);

        $so->refresh();
        $this->assertEquals('done', $so->status);
        $this->assertEquals('delivered', $so->fulfillment_status);

        // Reservation is fulfilled
        $res = StockReservation::where('sales_order_item_id', $soItem->id)->first();
        $this->assertEquals('fulfilled', $res->status);
        $this->assertEquals(10, $res->qty_delivered);

        // Stock On-hand is now 0
        $this->assertEquals(0, $this->product->onHandStock());
        $this->assertEquals(0, $this->product->reservedStock());
        $this->assertEquals(0, $this->product->availableStock());
    }

    public function test_so_cancellation_releases_reservation_and_allocates_to_next_pending_so(): void
    {
        // 1. Initial 10 pcs in stock
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'in',
            'quantity' => 10,
            'unit_cost' => 50000,
            'movement_date' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        // SO-1 claims all 10 pcs
        $so1 = SalesOrder::create([
            'so_number' => 'SO-CLAIM-001',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'draft',
            'order_date' => now()->subDay()->toDateString(),
            'total_amount' => 750000,
        ]);
        SalesOrderItem::create([
            'sales_order_id' => $so1->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'unit_price' => 75000,
            'subtotal' => 750000,
        ]);
        $this->actingAs($this->admin)->patch(route('sales.orders.confirm', $so1));
        $this->assertEquals('ready_to_ship', $so1->refresh()->fulfillment_status);

        // SO-2 comes in, stock is now fully reserved (Available = 0) -> Backorder
        $so2 = SalesOrder::create([
            'so_number' => 'SO-CLAIM-002',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'total_amount' => 750000,
        ]);
        SalesOrderItem::create([
            'sales_order_id' => $so2->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'unit_price' => 75000,
            'subtotal' => 750000,
        ]);
        $this->actingAs($this->admin)->patch(route('sales.orders.confirm', $so2));
        $this->assertEquals('backorder', $so2->refresh()->fulfillment_status);

        // 2. Customer 1 cancels SO-1
        $this->actingAs($this->admin)->patch(route('sales.orders.cancel', $so1));
        $this->assertEquals('cancelled', $so1->refresh()->status);

        // 3. SO-2 automatically receives the released 10 pcs reservation and becomes ready_to_ship!
        $so2->refresh();
        $this->assertEquals('ready_to_ship', $so2->fulfillment_status);
        $this->assertTrue($so2->canCreateDelivery());
    }

    public function test_partial_deliveries_continuously_update_procurement_demand_qty_fulfilled(): void
    {
        // 1. Zero stock SO confirmed -> Demand for 10 pcs
        $so = SalesOrder::create([
            'so_number' => 'SO-PARTIAL-DELIV-001',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'total_amount' => 750000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'unit_price' => 75000,
            'subtotal' => 750000,
        ]);
        $this->actingAs($this->admin)->patch(route('sales.orders.confirm', $so));

        $demand = ProcurementDemand::where('sales_order_id', $so->id)->first();
        $this->assertEquals(10, $demand->qty_demanded);
        $this->assertEquals(0, $demand->qty_fulfilled);
        $this->assertEquals('pending', $demand->status);

        // 2. Add 4 units to warehouse and deliver 4 units
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'in',
            'quantity' => 4,
            'unit_cost' => 50000,
            'movement_date' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post(route('sales.deliveries.store'), [
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'items' => [
                [
                    'sales_order_item_id' => $soItem->id,
                    'qty_delivered' => 4,
                ],
            ],
        ]);

        // Demand should now be fulfilled 4 pcs out of 10
        $demand->refresh();
        $this->assertEquals(4, $demand->qty_fulfilled);
        $this->assertEquals('pending', $demand->status);
        $this->assertEquals(6, $demand->qty_unfulfilled);

        // SO should be partially_delivered
        $so->refresh();
        $this->assertEquals('partially_delivered', $so->status);
        $this->assertEquals('partially_delivered', $so->fulfillment_status);

        // 3. Add remaining 6 units and deliver remaining 6 units
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'in',
            'quantity' => 6,
            'unit_cost' => 50000,
            'movement_date' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post(route('sales.deliveries.store'), [
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'items' => [
                [
                    'sales_order_item_id' => $soItem->id,
                    'qty_delivered' => 6,
                ],
            ],
        ]);

        // Demand should now be completely fulfilled (10 of 10)
        $demand->refresh();
        $this->assertEquals(10, $demand->qty_fulfilled);
        $this->assertEquals('fulfilled', $demand->status);
        $this->assertEquals(0, $demand->qty_unfulfilled);

        // SO should be done & delivered
        $so->refresh();
        $this->assertEquals('done', $so->status);
        $this->assertEquals('delivered', $so->fulfillment_status);
    }

    public function test_partial_initial_stock_and_multiple_deliveries_track_demand_fulfillment_accurately(): void
    {
        // 1. Initial 3 pcs in stock
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'in',
            'quantity' => 3,
            'unit_cost' => 50000,
            'movement_date' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        $so = SalesOrder::create([
            'so_number' => 'SO-PARTIAL-INIT-001',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'total_amount' => 750000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'unit_price' => 75000,
            'subtotal' => 750000,
        ]);
        $this->actingAs($this->admin)->patch(route('sales.orders.confirm', $so));

        // Demand is 7 pcs, 3 pcs reserved
        $demand = ProcurementDemand::where('sales_order_id', $so->id)->first();
        $this->assertEquals(7, $demand->qty_demanded);
        $this->assertEquals(0, $demand->qty_fulfilled);

        // 2. Deliver the 3 initial pcs
        $this->actingAs($this->admin)->post(route('sales.deliveries.store'), [
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'items' => [
                [
                    'sales_order_item_id' => $soItem->id,
                    'qty_delivered' => 3,
                ],
            ],
        ]);

        // Demand is still 0 fulfilled because initial stock fulfilled the first 3 pcs
        $demand->refresh();
        $this->assertEquals(0, $demand->qty_fulfilled);

        // 3. Add 4 pcs and deliver 4 pcs
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'in',
            'quantity' => 4,
            'unit_cost' => 50000,
            'movement_date' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post(route('sales.deliveries.store'), [
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'items' => [
                [
                    'sales_order_item_id' => $soItem->id,
                    'qty_delivered' => 4,
                ],
            ],
        ]);

        // Demand now has 4 fulfilled out of 7
        $demand->refresh();
        $this->assertEquals(4, $demand->qty_fulfilled);
        $this->assertEquals('pending', $demand->status);

        // 4. Add 3 pcs and deliver remaining 3 pcs
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'in',
            'quantity' => 3,
            'unit_cost' => 50000,
            'movement_date' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post(route('sales.deliveries.store'), [
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'items' => [
                [
                    'sales_order_item_id' => $soItem->id,
                    'qty_delivered' => 3,
                ],
            ],
        ]);

        // Demand is now fully fulfilled (7 of 7)
        $demand->refresh();
        $this->assertEquals(7, $demand->qty_fulfilled);
        $this->assertEquals('fulfilled', $demand->status);
    }

    public function test_warehouse_transfer_allocates_stock_to_pending_demands(): void
    {
        $warehouse2 = Warehouse::create([
            'code' => 'WH-BRANCH',
            'name' => 'Gudang Cabang',
            'is_active' => true,
        ]);

        // 1. SO when stock is 0 -> Demand for 10 pcs (backorder)
        $so = SalesOrder::create([
            'so_number' => 'SO-TRANSFER-DEMAND-001',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'total_amount' => 750000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'unit_price' => 75000,
            'subtotal' => 750000,
        ]);
        $this->actingAs($this->admin)->patch(route('sales.orders.confirm', $so));
        $this->assertEquals('backorder', $so->refresh()->fulfillment_status);

        // 2. Initial 10 pcs in Warehouse 2
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $warehouse2->id,
            'type' => 'in',
            'quantity' => 10,
            'unit_cost' => 50000,
            'movement_date' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        // 3. Create, ship, and receive transfer from WH2 to WH1
        $transfer = \App\Models\WarehouseTransfer::create([
            'transfer_number' => 'TRF-TEST-001',
            'from_warehouse_id' => $warehouse2->id,
            'to_warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'transfer_date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        \App\Models\WarehouseTransferItem::create([
            'warehouse_transfer_id' => $transfer->id,
            'product_id' => $this->product->id,
            'qty' => 10,
        ]);

        // Ship
        $this->actingAs($this->admin)->patch(route('inventory.transfers.ship', $transfer));

        // Receive
        $this->actingAs($this->admin)->patch(route('inventory.transfers.receive', $transfer));

        // 4. Verify that the received transfer automatically allocated stock to the pending demand
        $demand = ProcurementDemand::where('sales_order_id', $so->id)->first();
        $this->assertEquals(10, $demand->qty_fulfilled);
        $this->assertEquals('fulfilled', $demand->status);

        $so->refresh();
        $this->assertEquals('ready_to_ship', $so->fulfillment_status);
        $this->assertTrue($so->canCreateDelivery());
    }

    /**
     * Test: Kebutuhan pengadaan (Procurement Demand) tidak boleh terakumulasi ganda saat syncAllPendingSalesOrders()
     * dipanggil berulang kali, dan otomatis menghubungkan PO aktif jika ada PO terbit untuk produk tersebut.
     */
    public function test_procurement_demand_does_not_accumulate_qty_demanded_on_repeated_sync_and_auto_links_po(): void
    {
        // 1. Buat Sales Order 15 pcs saat stok gudang = 0
        $so = SalesOrder::create([
            'so_number' => 'SO-DEM-TEST-' . uniqid(),
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'tax_rate' => 0,
            'total_amount' => 1500000,
        ]);
        SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 15,
            'unit_price' => 100000,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 1500000,
        ]);

        // Konfirmasi SO -> buat demand 15 pcs
        $this->actingAs($this->admin)->patch(route('sales.orders.confirm', $so));

        $demand = ProcurementDemand::where('sales_order_id', $so->id)->firstOrFail();
        $this->assertEquals(15, $demand->qty_demanded);
        $this->assertEquals('pending', $demand->status);

        // 2. Simulasikan refresh halaman berkali-kali (syncAllPendingSalesOrders dipanggil 5 kali)
        $stockService = app(\App\Services\StockService::class);
        for ($i = 0; $i < 5; $i++) {
            $stockService->syncAllPendingSalesOrders();
            $stockService->syncAllProcurementDemands();
        }

        // Pastikan qty_demanded TETAP 15, tidak menggelembung menjadi 75
        $demand->refresh();
        $this->assertEquals(15, $demand->qty_demanded, 'qty_demanded tidak boleh menggelembung pada sync berulang.');

        // 3. Buat PO aktif untuk produk ini (20 pcs)
        $po = \App\Models\PurchaseOrder::create([
            'po_number' => 'PO-DEM-MATCH-' . uniqid(),
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'status' => 'confirmed',
            'order_date' => now()->toDateString(),
            'tax_rate' => 0,
            'total_amount' => 1000000,
        ]);
        \App\Models\PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 20,
            'unit_price' => 50000,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 1000000,
        ]);

        // Trigger sync
        $stockService->syncAllProcurementDemands();

        // 4. Verifikasi bahwa demand sekarang otomatis terhubung ke PO dan berstatus 'ordered'
        $demand->refresh();
        $this->assertEquals($po->id, $demand->purchase_order_id);
        $this->assertEquals('ordered', $demand->status);
    }
}
