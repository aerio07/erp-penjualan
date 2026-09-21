<?php

namespace Tests\Feature;

use App\Enums\DeliveryStatus;
use App\Enums\GoodsReceiptStatus;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoodsReceiptViewApprovalAndDeliveryFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Warehouse $warehouse;
    protected Supplier $supplier;
    protected Customer $customer;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ChartOfAccountSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->warehouse = Warehouse::create([
            'code' => 'WH-TEST',
            'name' => 'Gudang Utama',
            'is_active' => true,
        ]);
        $this->supplier = Supplier::create([
            'code' => 'SUP-TEST',
            'name' => 'PT Supplier Utama',
            'is_active' => true,
        ]);
        $this->customer = Customer::create([
            'code' => 'CUST-TEST',
            'name' => 'PT Customer Utama',
            'is_active' => true,
        ]);
        $this->product = Product::create([
            'sku' => 'PROD-TEST-1',
            'name' => 'Produk Test',
            'unit' => 'pcs',
            'purchase_price' => 10000,
            'sell_price' => 15000,
            'is_active' => true,
        ]);
    }

    public function test_goods_receipt_in_draft_shows_ajukan_approval_button(): void
    {
        $po = PurchaseOrder::create([
            'po_number'    => 'PO-TEST-' . uniqid(),
            'supplier_id'  => $this->supplier->id,
            'user_id'      => $this->admin->id,
            'warehouse_id' => $this->warehouse->id,
            'status'       => 'confirmed',
            'order_date'   => now()->toDateString(),
            'total_amount' => 100000,
        ]);

        $grn = GoodsReceipt::create([
            'receipt_number'    => 'LPB-TEST-' . uniqid(),
            'purchase_order_id' => $po->id,
            'warehouse_id'      => $this->warehouse->id,
            'user_id'           => $this->admin->id,
            'status'            => GoodsReceiptStatus::Draft,
            'received_date'     => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('purchase.goods-receipts.show', $grn));

        $response->assertStatus(200);
        $response->assertSee('Ajukan Approval');
        $response->assertSee('Status Dokumen: Draft');
    }

    public function test_delivery_in_transit_shows_confirm_receipt_button(): void
    {
        $so = SalesOrder::create([
            'so_number'    => 'SO-TEST-' . uniqid(),
            'customer_id'  => $this->customer->id,
            'user_id'      => $this->admin->id,
            'status'       => 'confirmed',
            'order_date'   => now()->toDateString(),
            'total_amount' => 150000,
        ]);

        $soItem = SalesOrderItem::create([
            'sales_order_id'   => $so->id,
            'product_id'       => $this->product->id,
            'qty_ordered'      => 10,
            'qty_delivered'    => 10,
            'unit_price'       => 15000,
            'subtotal'         => 150000,
        ]);

        $delivery = Delivery::create([
            'delivery_number'  => 'SJ-TEST-' . uniqid(),
            'sales_order_id'   => $so->id,
            'warehouse_id'     => $this->warehouse->id,
            'user_id'          => $this->admin->id,
            'condition_status' => 'baik',
            'status'           => DeliveryStatus::InTransit,
            'delivery_date'    => now()->toDateString(),
            'shipping_address' => 'Jl. Test No. 1',
            'recipient_name'   => 'Budi Test',
        ]);

        DeliveryItem::create([
            'delivery_id'         => $delivery->id,
            'sales_order_item_id' => $soItem->id,
            'qty_delivered'       => 10,
            'condition'           => 'Good',
        ]);

        $response = $this->actingAs($this->admin)->get(route('sales.deliveries.show', $delivery));

        $response->assertStatus(200);
        $response->assertSee('Konfirmasi Penerimaan Barang');
        $response->assertSee('Dalam Pengiriman (In Transit)');
        $response->assertSee('Cetak Surat Jalan PDF');
    }

    public function test_delivery_delivered_shows_invoice_button(): void
    {
        $so = SalesOrder::create([
            'so_number'    => 'SO-TEST-' . uniqid(),
            'customer_id'  => $this->customer->id,
            'user_id'      => $this->admin->id,
            'status'       => 'confirmed',
            'order_date'   => now()->toDateString(),
            'total_amount' => 150000,
        ]);

        $soItem = SalesOrderItem::create([
            'sales_order_id'   => $so->id,
            'product_id'       => $this->product->id,
            'qty_ordered'      => 10,
            'qty_delivered'    => 10,
            'unit_price'       => 15000,
            'subtotal'         => 150000,
        ]);

        $delivery = Delivery::create([
            'delivery_number'  => 'SJ-TEST-' . uniqid(),
            'sales_order_id'   => $so->id,
            'warehouse_id'     => $this->warehouse->id,
            'user_id'          => $this->admin->id,
            'condition_status' => 'baik',
            'status'           => DeliveryStatus::Delivered,
            'is_invoiced'      => false,
            'delivery_date'    => now()->toDateString(),
            'received_date'    => now()->toDateString(),
            'shipping_address' => 'Jl. Test No. 1',
            'recipient_name'   => 'Budi Test',
        ]);

        DeliveryItem::create([
            'delivery_id'         => $delivery->id,
            'sales_order_item_id' => $soItem->id,
            'qty_delivered'       => 10,
            'condition'           => 'Good',
        ]);

        $response = $this->actingAs($this->admin)->get(route('sales.deliveries.show', $delivery));

        $response->assertStatus(200);
        $response->assertSee('Terbitkan Invoice Penjualan');
        $response->assertSee('Diterima (Delivered)');
    }
}
