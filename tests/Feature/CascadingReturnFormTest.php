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
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CascadingReturnFormTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Supplier $supplier;
    private Customer $customer;
    private Product $product;
    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->supplier = Supplier::create([
            'code' => 'SUP-TEST-CASC',
            'name' => 'Supplier Cascading',
            'is_active' => true,
        ]);
        $this->customer = Customer::create([
            'code' => 'CUST-TEST-CASC',
            'name' => 'Customer Cascading',
            'payment_term' => 30,
            'is_active' => true,
        ]);
        $this->product = Product::create([
            'sku' => 'PROD-CASC-01',
            'name' => 'Produk Cascading',
            'category' => 'Elektronik',
            'unit' => 'pcs',
            'purchase_price' => 100000,
            'sell_price' => 150000,
            'is_active' => true,
        ]);
        $this->warehouse = Warehouse::create([
            'code' => 'WH-CASC',
            'name' => 'Gudang Cascading',
            'is_active' => true,
        ]);
    }

    public function test_purchase_return_create_page_loads_with_purchase_orders_and_receipts(): void
    {
        // Create 2 POs
        $po1 = PurchaseOrder::create([
            'po_number' => 'PO-CASC-01',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'status' => 'confirmed',
            'order_date' => now()->toDateString(),
        ]);
        $poItem1 = PurchaseOrderItem::create([
            'purchase_order_id' => $po1->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 20,
            'unit_price' => 100000,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 2000000,
        ]);

        // PO1 has 2 GRNs (2 shipments)
        $grn1 = GoodsReceipt::create([
            'receipt_number' => 'GRN-CASC-01',
            'purchase_order_id' => $po1->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'qc_status' => 'passed',
            'received_date' => now()->toDateString(),
        ]);
        GoodsReceiptItem::create([
            'goods_receipt_id' => $grn1->id,
            'purchase_order_item_id' => $poItem1->id,
            'warehouse_id' => $this->warehouse->id,
            'qty_received' => 12,
            'qty_rejected' => 0,
            'unit_cost' => 100000,
            'condition' => 'Good',
        ]);

        $grn2 = GoodsReceipt::create([
            'receipt_number' => 'GRN-CASC-02',
            'purchase_order_id' => $po1->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'qc_status' => 'passed',
            'received_date' => now()->toDateString(),
        ]);
        GoodsReceiptItem::create([
            'goods_receipt_id' => $grn2->id,
            'purchase_order_item_id' => $poItem1->id,
            'warehouse_id' => $this->warehouse->id,
            'qty_received' => 8,
            'qty_rejected' => 0,
            'unit_cost' => 100000,
            'condition' => 'Good',
        ]);

        $response = $this->actingAs($this->admin)->get(route('purchase.returns.create'));
        $response->assertOk();
        $response->assertViewHas('purchaseOrders');
        $response->assertViewHas('receipts');

        $viewOrders = $response->viewData('purchaseOrders');
        $this->assertTrue($viewOrders->contains('id', $po1->id));

        // Test passing po_id query param
        $responseWithPo = $this->actingAs($this->admin)->get(route('purchase.returns.create', ['po_id' => $po1->id]));
        $responseWithPo->assertOk();
        $this->assertEquals($po1->id, $responseWithPo->viewData('selectedPoId'));

        // Test passing grn_id query param auto-resolves selectedPoId
        $responseWithGrn = $this->actingAs($this->admin)->get(route('purchase.returns.create', ['grn_id' => $grn2->id]));
        $responseWithGrn->assertOk();
        $this->assertEquals($grn2->id, $responseWithGrn->viewData('selectedGrnId'));
        $this->assertEquals($po1->id, $responseWithGrn->viewData('selectedPoId'));
    }

    public function test_sales_return_create_page_loads_with_sales_orders_and_deliveries(): void
    {
        $so = SalesOrder::create([
            'so_number' => 'SO-CASC-01',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'status' => 'confirmed',
            'order_date' => now()->toDateString(),
        ]);
        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 15,
            'unit_price' => 150000,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 2250000,
        ]);

        $del = Delivery::create([
            'delivery_number' => 'SJ-CASC-01',
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'condition_status' => 'baik',
            'status' => 'delivered',
            'delivery_date' => now()->toDateString(),
            'received_date' => now()->toDateString(),
        ]);
        DeliveryItem::create([
            'delivery_id' => $del->id,
            'sales_order_item_id' => $soItem->id,
            'qty_delivered' => 15,
        ]);

        $response = $this->actingAs($this->admin)->get(route('sales.returns.create'));
        $response->assertOk();
        $response->assertViewHas('salesOrders');
        $response->assertViewHas('deliveries');

        $viewSos = $response->viewData('salesOrders');
        $this->assertTrue($viewSos->contains('id', $so->id));

        // Test passing so_id query param
        $responseWithSo = $this->actingAs($this->admin)->get(route('sales.returns.create', ['so_id' => $so->id]));
        $responseWithSo->assertOk();
        $this->assertEquals($so->id, $responseWithSo->viewData('selectedSoId'));

        // Test passing delivery_id query param auto-resolves selectedSoId
        $responseWithDel = $this->actingAs($this->admin)->get(route('sales.returns.create', ['delivery_id' => $del->id]));
        $responseWithDel->assertOk();
        $this->assertEquals($del->id, $responseWithDel->viewData('selectedDeliveryId'));
        $this->assertEquals($so->id, $responseWithDel->viewData('selectedSoId'));
    }
}
