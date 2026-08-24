<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseReturnStockValidationTest extends TestCase
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
            'code' => 'WH-TEST',
            'name' => 'Gudang Uji Retur',
            'is_active' => true,
        ]);
        $this->product = Product::create([
            'sku' => 'PROD-RET-001',
            'name' => 'Produk Uji Retur',
            'category' => 'Testing',
            'unit' => 'pcs',
            'purchase_price' => 50000,
            'sell_price' => 75000,
            'min_stock' => 5,
            'is_active' => true,
        ]);
        $this->customer = Customer::create([
            'code' => 'CUST-RET',
            'name' => 'Customer Retur Test',
            'is_active' => true,
        ]);
        $this->supplier = Supplier::create([
            'code' => 'SUPP-RET',
            'name' => 'Supplier Retur Test',
            'is_active' => true,
        ]);
    }

    public function test_cannot_return_goods_when_stock_has_already_been_delivered_via_sales_order(): void
    {
        // 1. PO created and received (10 pcs)
        $po = PurchaseOrder::create([
            'po_number' => 'PO-RET-001',
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

        $grn = GoodsReceipt::create([
            'receipt_number' => 'GRN-RET-001',
            'purchase_order_id' => $po->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'received_date' => now()->toDateString(),
            'status' => 'received',
        ]);
        $grnItem = GoodsReceiptItem::create([
            'goods_receipt_id' => $grn->id,
            'purchase_order_item_id' => $poItem->id,
            'warehouse_id' => $this->warehouse->id,
            'qty_received' => 10,
            'qty_rejected' => 0,
            'unit_cost' => 50000,
            'condition' => 'Good',
        ]);

        // Stock in: 10 pcs
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'in',
            'quantity' => 10,
            'unit_cost' => 50000,
            'movement_date' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        $stockService = app(StockService::class);
        $this->assertEquals(10, $stockService->getOnHandStock($this->product->id, $this->warehouse->id));

        // 2. Customer buys all 10 pcs (SO -> Delivery)
        $so = SalesOrder::create([
            'so_number' => 'SO-RET-001',
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

        // Deliver all 10 pcs
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

        // Stock On-Hand is now 0
        $this->assertEquals(0, $stockService->getOnHandStock($this->product->id, $this->warehouse->id));
        $this->assertEquals(0, $stockService->getAvailableStock($this->product->id, $this->warehouse->id));

        // 3. Attempt to create a Purchase Return for that GRN (10 pcs)
        $response = $this->actingAs($this->admin)->post(route('purchase.returns.store'), [
            'goods_receipt_id' => $grn->id,
            'return_date' => now()->toDateString(),
            'reason' => 'Cacat pabrik',
            'items' => [
                [
                    'goods_receipt_item_id' => $grnItem->id,
                    'product_id' => $this->product->id,
                    'source_type' => 'accepted',
                    'qty' => 10,
                    'unit_cost' => 50000,
                ],
            ],
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(0, PurchaseReturn::count());
        $this->assertEquals(0, $stockService->getOnHandStock($this->product->id, $this->warehouse->id));
    }

    public function test_can_return_goods_up_to_actual_available_stock_when_partial_stock_is_sold(): void
    {
        // 1. Initial 10 pcs received
        $po = PurchaseOrder::create([
            'po_number' => 'PO-PART-001',
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
        $grn = GoodsReceipt::create([
            'receipt_number' => 'GRN-PART-001',
            'purchase_order_id' => $po->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'received_date' => now()->toDateString(),
            'status' => 'received',
        ]);
        $grnItem = GoodsReceiptItem::create([
            'goods_receipt_id' => $grn->id,
            'purchase_order_item_id' => $poItem->id,
            'warehouse_id' => $this->warehouse->id,
            'qty_received' => 10,
            'qty_rejected' => 0,
            'unit_cost' => 50000,
            'condition' => 'Good',
        ]);
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'in',
            'quantity' => 10,
            'unit_cost' => 50000,
            'movement_date' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        // 2. Deliver 6 pcs to customer (4 pcs remaining in stock)
        $so = SalesOrder::create([
            'so_number' => 'SO-PART-001',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'total_amount' => 450000,
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 6,
            'unit_price' => 75000,
            'subtotal' => 450000,
        ]);
        $this->actingAs($this->admin)->patch(route('sales.orders.confirm', $so));
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

        $stockService = app(StockService::class);
        $this->assertEquals(4, $stockService->getOnHandStock($this->product->id, $this->warehouse->id));

        // 3. Attempt to return 5 pcs -> Should Fail
        $res5 = $this->actingAs($this->admin)->post(route('purchase.returns.store'), [
            'goods_receipt_id' => $grn->id,
            'return_date' => now()->toDateString(),
            'reason' => 'Cacat',
            'items' => [
                [
                    'goods_receipt_item_id' => $grnItem->id,
                    'product_id' => $this->product->id,
                    'source_type' => 'accepted',
                    'qty' => 5,
                    'unit_cost' => 50000,
                ],
            ],
        ]);
        $res5->assertSessionHas('error');
        $this->assertEquals(0, PurchaseReturn::count());

        // 4. Attempt to return 4 pcs -> Should Succeed
        $res4 = $this->actingAs($this->admin)->post(route('purchase.returns.store'), [
            'goods_receipt_id' => $grn->id,
            'return_date' => now()->toDateString(),
            'reason' => 'Cacat',
            'items' => [
                [
                    'goods_receipt_item_id' => $grnItem->id,
                    'product_id' => $this->product->id,
                    'source_type' => 'accepted',
                    'qty' => 4,
                    'unit_cost' => 50000,
                ],
            ],
        ]);
        $res4->assertSessionHas('success');
        $this->assertEquals(1, PurchaseReturn::count());

        $return = PurchaseReturn::first();
        $this->assertEquals('draft', $return->status);

        // 5. Complete the return -> Stock drops from 4 to 0 (never negative)
        $this->actingAs($this->admin)->patch(route('purchase.returns.complete', $return));
        $this->assertEquals('completed', $return->refresh()->status);
        $this->assertEquals(0, $stockService->getOnHandStock($this->product->id, $this->warehouse->id));
    }
}
