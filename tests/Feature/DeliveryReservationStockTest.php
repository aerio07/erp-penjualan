<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Delivery;
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

class DeliveryReservationStockTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Warehouse $warehouse;
    private Product $product;
    private Customer $customer;
    private Supplier $supplier;
    private StockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->warehouse = Warehouse::create([
            'code' => 'WH-TEST',
            'name' => 'Gudang Testing Reservasi',
            'is_active' => true,
        ]);
        $this->product = Product::create([
            'sku' => 'PROD-RES-001',
            'name' => 'Produk Tes Reservasi',
            'category' => 'Testing',
            'unit' => 'pcs',
            'purchase_price' => 100000,
            'sell_price' => 150000,
            'min_stock' => 5,
            'is_active' => true,
        ]);
        $this->customer = Customer::create([
            'code' => 'CUST-RES-01',
            'name' => 'PT Mitra Konsumen',
            'tax_type' => 'non_pkp',
            'nik' => '3201123456780001',
            'is_active' => true,
        ]);
        $this->supplier = Supplier::create([
            'code' => 'SUPP-RES-01',
            'name' => 'PT Mitra Pemasok',
            'is_active' => true,
        ]);
        $this->stockService = app(StockService::class);
    }

    /**
     * Skenario P0.1 Utama:
     * On Hand = 100.
     * SO A (70 pcs) di-confirm -> reserved 70.
     * SO B (30 pcs) di-confirm -> reserved 30.
     * Free stock = 0.
     * SO C (50 pcs) coba dikirim -> DITOLAK karena stok ter-reserve untuk SO A & B.
     * SO A (70 pcs) dikirim -> BERHASIL.
     */
    public function test_p01_delivery_cannot_steal_reserved_stock_of_other_sales_orders(): void
    {
        // 1. Initial stock On-Hand = 100 pcs
        StockMovement::create([
            'product_id'    => $this->product->id,
            'warehouse_id'  => $this->warehouse->id,
            'type'          => 'in',
            'quantity'      => 100,
            'unit_cost'     => 100000,
            'movement_date' => now()->toDateString(),
            'user_id'       => $this->admin->id,
        ]);
        $this->assertEquals(100, $this->stockService->getOnHandStock($this->product->id, $this->warehouse->id));

        // 2. Create and Confirm SO A (70 pcs)
        $soA = SalesOrder::create([
            'so_number' => 'SO-A-001',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'total_amount' => 70 * 150000,
        ]);
        $soItemA = SalesOrderItem::create([
            'sales_order_id' => $soA->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 70,
            'unit_price' => 150000,
            'subtotal' => 70 * 150000,
        ]);
        $this->actingAs($this->admin)->patch(route('sales.orders.confirm', $soA));
        $soA->refresh();
        $this->assertEquals('ready_to_ship', $soA->fulfillment_status);

        // 3. Create and Confirm SO B (30 pcs)
        $soB = SalesOrder::create([
            'so_number' => 'SO-B-001',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'total_amount' => 30 * 150000,
        ]);
        $soItemB = SalesOrderItem::create([
            'sales_order_id' => $soB->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 30,
            'unit_price' => 150000,
            'subtotal' => 30 * 150000,
        ]);
        $this->actingAs($this->admin)->patch(route('sales.orders.confirm', $soB));
        $soB->refresh();
        $this->assertEquals('ready_to_ship', $soB->fulfillment_status);

        // Check 4 Stock Dimensions:
        $this->assertEquals(100, $this->stockService->getOnHandStock($this->product->id, $this->warehouse->id));
        $this->assertEquals(100, $this->stockService->getReservedStock($this->product->id, $this->warehouse->id));
        $this->assertEquals(0, $this->stockService->getAvailableStock($this->product->id, $this->warehouse->id));

        // 4. Create SO C (50 pcs) - will be backordered with 0 reserved
        $soC = SalesOrder::create([
            'so_number' => 'SO-C-001',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'total_amount' => 50 * 150000,
        ]);
        $soItemC = SalesOrderItem::create([
            'sales_order_id' => $soC->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 50,
            'unit_price' => 150000,
            'subtotal' => 50 * 150000,
        ]);
        $this->actingAs($this->admin)->patch(route('sales.orders.confirm', $soC));
        $soC->refresh();
        $this->assertEquals('backorder', $soC->fulfillment_status);

        // 5. Attempt Delivery for SO C (50 pcs) -> MUST BE REJECTED!
        $responseDeliveryC = $this->actingAs($this->admin)->post(route('sales.deliveries.store'), [
            'sales_order_id' => $soC->id,
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'items' => [
                [
                    'sales_order_item_id' => $soItemC->id,
                    'qty_delivered' => 50,
                ],
            ],
        ]);

        $responseDeliveryC->assertSessionHas('error');
        $this->assertDatabaseMissing('deliveries', [
            'sales_order_id' => $soC->id,
        ]);

        // 6. Delivery for SO A (70 pcs) -> MUST SUCCEED!
        $responseDeliveryA = $this->actingAs($this->admin)->post(route('sales.deliveries.store'), [
            'sales_order_id' => $soA->id,
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'items' => [
                [
                    'sales_order_item_id' => $soItemA->id,
                    'qty_delivered' => 70,
                ],
            ],
        ]);

        $responseDeliveryA->assertRedirect(route('sales.deliveries.index'));
        $this->assertDatabaseHas('deliveries', [
            'sales_order_id' => $soA->id,
        ]);
        // Reservation A should be fulfilled
        $resA = StockReservation::where('sales_order_item_id', $soItemA->id)->first();
        $this->assertEquals('fulfilled', $resA->status);
        $this->assertEquals(70, $resA->qty_delivered);

        // On-Hand is now 30, and still reserved for SO B (30 pcs)
        $this->assertEquals(30, $this->stockService->getOnHandStock($this->product->id, $this->warehouse->id));
        $this->assertEquals(30, $this->stockService->getReservedStock($this->product->id, $this->warehouse->id));
    }

    /**
     * Skenario 2: Partial delivery mengurangi reserved_qty dengan benar.
     */
    public function test_partial_delivery_reduces_reservation_correctly(): void
    {
        StockMovement::create([
            'product_id'    => $this->product->id,
            'warehouse_id'  => $this->warehouse->id,
            'type'          => 'in',
            'quantity'      => 70,
            'unit_cost'     => 100000,
            'movement_date' => now()->toDateString(),
            'user_id'       => $this->admin->id,
        ]);

        $so = SalesOrder::create([
            'so_number' => 'SO-PARTIAL-001',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'total_amount' => 70 * 150000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 70,
            'unit_price' => 150000,
            'subtotal' => 70 * 150000,
        ]);
        $this->actingAs($this->admin)->patch(route('sales.orders.confirm', $so));

        // Step 1: Send partial delivery 30 pcs
        $this->actingAs($this->admin)->post(route('sales.deliveries.store'), [
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'items' => [
                [
                    'sales_order_item_id' => $soItem->id,
                    'qty_delivered' => 30,
                ],
            ],
        ]);

        $res = StockReservation::where('sales_order_item_id', $soItem->id)->first();
        $this->assertEquals('active', $res->status);
        $this->assertEquals(30, $res->qty_delivered);
        $this->assertEquals(40, $res->qty_active); // Remaining unconsumed active reservation = 40
        $this->assertEquals(40, $this->stockService->getOnHandStock($this->product->id, $this->warehouse->id));

        // Step 2: Send remaining 40 pcs
        $this->actingAs($this->admin)->post(route('sales.deliveries.store'), [
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'items' => [
                [
                    'sales_order_item_id' => $soItem->id,
                    'qty_delivered' => 40,
                ],
            ],
        ]);

        $res->refresh();
        $this->assertEquals('fulfilled', $res->status);
        $this->assertEquals(70, $res->qty_delivered);
        $this->assertEquals(0, $res->qty_active);
        $this->assertEquals(0, $this->stockService->getOnHandStock($this->product->id, $this->warehouse->id));

        $so->refresh();
        $this->assertEquals('delivered', $so->fulfillment_status);
    }

    /**
     * Skenario 3: SO dibatalkan -> reservasinya lepas, stok itu jadi available lagi untuk SO lain.
     */
    public function test_cancelled_sales_order_releases_reservation_for_other_orders(): void
    {
        StockMovement::create([
            'product_id'    => $this->product->id,
            'warehouse_id'  => $this->warehouse->id,
            'type'          => 'in',
            'quantity'      => 50,
            'unit_cost'     => 100000,
            'movement_date' => now()->toDateString(),
            'user_id'       => $this->admin->id,
        ]);

        // SO 1 reserves all 50 pcs
        $so1 = SalesOrder::create([
            'so_number' => 'SO-CANCEL-001',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'total_amount' => 50 * 150000,
        ]);
        $soItem1 = SalesOrderItem::create([
            'sales_order_id' => $so1->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 50,
            'unit_price' => 150000,
            'subtotal' => 50 * 150000,
        ]);
        $this->actingAs($this->admin)->patch(route('sales.orders.confirm', $so1));

        // SO 2 needs 50 pcs, gets 0 reservation (backorder)
        $so2 = SalesOrder::create([
            'so_number' => 'SO-CANCEL-002',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'total_amount' => 50 * 150000,
        ]);
        $soItem2 = SalesOrderItem::create([
            'sales_order_id' => $so2->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 50,
            'unit_price' => 150000,
            'subtotal' => 50 * 150000,
        ]);
        $this->actingAs($this->admin)->patch(route('sales.orders.confirm', $so2));

        // Cancel SO 1
        $this->actingAs($this->admin)->patch(route('sales.orders.cancel', $so1));
        $so1->refresh();
        $this->assertEquals('cancelled', $so1->status);

        // Released stock automatically fulfills SO 2's demand and creates reservation
        $so2->refresh();
        $this->assertEquals('ready_to_ship', $so2->fulfillment_status);

        // Now SO 2 can deliver 50 pcs successfully
        $responseDelivery = $this->actingAs($this->admin)->post(route('sales.deliveries.store'), [
            'sales_order_id' => $so2->id,
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'items' => [
                [
                    'sales_order_item_id' => $soItem2->id,
                    'qty_delivered' => 50,
                ],
            ],
        ]);

        $responseDelivery->assertRedirect(route('sales.deliveries.index'));
        $this->assertDatabaseHas('deliveries', [
            'sales_order_id' => $so2->id,
        ]);
    }

    /**
     * Skenario 4: Double delivery attempt for same SO cannot exceed reservation or remaining SO qty.
     */
    public function test_cannot_over_consume_reservation_or_deliver_more_than_ordered(): void
    {
        StockMovement::create([
            'product_id'    => $this->product->id,
            'warehouse_id'  => $this->warehouse->id,
            'type'          => 'in',
            'quantity'      => 100,
            'unit_cost'     => 100000,
            'movement_date' => now()->toDateString(),
            'user_id'       => $this->admin->id,
        ]);

        $so = SalesOrder::create([
            'so_number' => 'SO-DOUBLE-001',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'total_amount' => 50 * 150000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 50,
            'unit_price' => 150000,
            'subtotal' => 50 * 150000,
        ]);
        $this->actingAs($this->admin)->patch(route('sales.orders.confirm', $so));

        // Delivery 1: Deliver full 50 pcs
        $this->actingAs($this->admin)->post(route('sales.deliveries.store'), [
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'items' => [
                [
                    'sales_order_item_id' => $soItem->id,
                    'qty_delivered' => 50,
                ],
            ],
        ]);

        // Attempt Delivery 2 for another 50 pcs on the same SO -> MUST BE REJECTED
        $responseSecond = $this->actingAs($this->admin)->post(route('sales.deliveries.store'), [
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'items' => [
                [
                    'sales_order_item_id' => $soItem->id,
                    'qty_delivered' => 50,
                ],
            ],
        ]);

        $responseSecond->assertSessionHas('error');
        $this->assertEquals(1, Delivery::where('sales_order_id', $so->id)->count());
    }

    /**
     * Skenario 5: Backorder SO (0 reserved) receives stock from GRN via allocateProcurementDemands, then can be delivered.
     */
    public function test_backorder_so_gets_stock_from_grn_and_can_be_delivered(): void
    {
        // 1. Initial stock is 0. Create and confirm SO for 25 pcs
        $so = SalesOrder::create([
            'so_number' => 'SO-BACKORDER-001',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'total_amount' => 25 * 150000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 25,
            'unit_price' => 150000,
            'subtotal' => 25 * 150000,
        ]);
        $this->actingAs($this->admin)->patch(route('sales.orders.confirm', $so));
        $so->refresh();
        $this->assertEquals('backorder', $so->fulfillment_status);

        // 2. Delivery attempt fails when still in backorder
        $responseFail = $this->actingAs($this->admin)->post(route('sales.deliveries.store'), [
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'items' => [
                [
                    'sales_order_item_id' => $soItem->id,
                    'qty_delivered' => 25,
                ],
            ],
        ]);
        $responseFail->assertSessionHas('error');

        // 3. Procurement: PO and GRN for 25 pcs
        $po = PurchaseOrder::create([
            'po_number' => 'PO-DEMAND-001',
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->admin->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'total_amount' => 25 * 100000,
        ]);
        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id'        => $this->product->id,
            'qty_ordered'       => 25,
            'unit_price'        => 100000,
            'subtotal'          => 25 * 100000,
        ]);

        // Receive via Goods Receipt
        $this->actingAs($this->admin)->post(route('purchase.goods-receipts.store'), [
            'purchase_order_id' => $po->id,
            'received_date'     => now()->toDateString(),
            'items' => [
                [
                    'purchase_order_item_id' => $poItem->id,
                    'warehouse_id'           => $this->warehouse->id,
                    'qty_physical'           => 25,
                    'qty_rejected'           => 0,
                ],
            ],
        ]);

        // 4. Verify SO upgraded to ready_to_ship and active reservation created
        $so->refresh();
        $this->assertEquals('ready_to_ship', $so->fulfillment_status);
        $res = StockReservation::where('sales_order_item_id', $soItem->id)->first();
        $this->assertNotNull($res);
        $this->assertEquals(25, $res->qty_reserved);
        $this->assertEquals('active', $res->status);

        // 5. Now Delivery for SO can be created successfully!
        $responseSuccess = $this->actingAs($this->admin)->post(route('sales.deliveries.store'), [
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'items' => [
                [
                    'sales_order_item_id' => $soItem->id,
                    'qty_delivered' => 25,
                ],
            ],
        ]);

        $responseSuccess->assertRedirect(route('sales.deliveries.index'));
        $this->assertDatabaseHas('deliveries', [
            'sales_order_id' => $so->id,
        ]);
    }
}
