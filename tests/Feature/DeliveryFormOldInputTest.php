<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DeliveryFormOldInputTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private Customer $customer;
    private Warehouse $warehouse;
    private Product $product;
    private SalesOrder $salesOrder;
    private SalesOrderItem $soItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->customer = Customer::create([
            'code' => 'CUST-OLD-INP',
            'name' => 'PT Customer Preserved',
            'tax_type' => 'non_pkp',
            'is_active' => true,
        ]);
        $this->warehouse = Warehouse::create([
            'code' => 'WH-OLD-INP',
            'name' => 'Gudang Input Terjaga',
            'is_active' => true,
        ]);
        $this->product = Product::create([
            'sku' => 'PROD-OLD-INP',
            'name' => 'Produk Anti Reset',
            'category' => 'Testing',
            'unit' => 'pcs',
            'purchase_price' => 50000,
            'sell_price' => 75000,
            'is_active' => true,
        ]);

        $this->salesOrder = SalesOrder::create([
            'so_number' => 'SO-OLD-INP-001',
            'customer_id' => $this->customer->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'fulfillment_status' => 'backorder',
            'total_amount' => 750000,
            'subtotal' => 750000,
            'tax_amount' => 0,
            'user_id' => $this->admin->id,
        ]);

        $this->soItem = SalesOrderItem::create([
            'sales_order_id' => $this->salesOrder->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'unit_price' => 75000,
            'subtotal' => 750000,
        ]);
    }

    public function test_qty_exceeding_so_remaining_preserves_old_inputs(): void
    {
        $payload = [
            'sales_order_id' => $this->salesOrder->id,
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => '2026-09-04',
            'recipient_name' => 'Budi Tester Lapangan',
            'shipping_address' => 'Jl. Gatot Subroto Kav. 50, Jakarta',
            'notes' => 'Truk Engkel No Pol B 9999 XYZ - Driver Pak Joko',
            'items' => [
                [
                    'sales_order_item_id' => $this->soItem->id,
                    'qty_delivered' => 25, // Melebihi sisa SO (10)
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->from(route('sales.deliveries.create'))
            ->post(route('sales.deliveries.store'), $payload);

        $response->assertRedirect(route('sales.deliveries.create'));
        $response->assertSessionHas('error');
        $response->assertSessionHasInput('sales_order_id', $this->salesOrder->id);
        $response->assertSessionHasInput('warehouse_id', $this->warehouse->id);
        $response->assertSessionHasInput('recipient_name', 'Budi Tester Lapangan');
        $response->assertSessionHasInput('shipping_address', 'Jl. Gatot Subroto Kav. 50, Jakarta');
        $response->assertSessionHasInput('notes', 'Truk Engkel No Pol B 9999 XYZ - Driver Pak Joko');
        $response->assertSessionHasInput('items.0.qty_delivered', 25);
    }

    public function test_insufficient_stock_preserves_old_inputs(): void
    {
        // Stok di gudang 0, mencoba kirim 5
        $payload = [
            'sales_order_id' => $this->salesOrder->id,
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => '2026-09-04',
            'recipient_name' => 'Siti Logistik',
            'shipping_address' => 'Komplek Pergudangan Cikupa',
            'notes' => 'Pengiriman Batch 1',
            'items' => [
                [
                    'sales_order_item_id' => $this->soItem->id,
                    'qty_delivered' => 5,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->from(route('sales.deliveries.create'))
            ->post(route('sales.deliveries.store'), $payload);

        $response->assertRedirect(route('sales.deliveries.create'));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('tidak mencukupi untuk dikirim', session('error'));
        $response->assertSessionHasInput('sales_order_id', $this->salesOrder->id);
        $response->assertSessionHasInput('warehouse_id', $this->warehouse->id);
        $response->assertSessionHasInput('items.0.qty_delivered', 5);
    }

    public function test_view_renders_preserved_inputs_correctly(): void
    {
        $oldInput = [
            'sales_order_id' => (string) $this->salesOrder->id,
            'warehouse_id' => (string) $this->warehouse->id,
            'delivery_date' => '2026-09-04',
            'recipient_name' => 'Budi Tester Lapangan',
            'shipping_address' => 'Jl. Gatot Subroto Kav. 50, Jakarta',
            'notes' => 'Truk Engkel No Pol B 9999 XYZ - Driver Pak Joko',
            'items' => [
                [
                    'sales_order_item_id' => (string) $this->soItem->id,
                    'qty_delivered' => '25',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->withSession(['_old_input' => $oldInput])
            ->get(route('sales.deliveries.create'));

        $response->assertOk();
        $response->assertSee('Budi Tester Lapangan');
        $response->assertSee('Jl. Gatot Subroto Kav. 50, Jakarta');
        $response->assertSee('Truk Engkel No Pol B 9999 XYZ - Driver Pak Joko');
        $response->assertSee('selected', false); // Warehouse selected
    }
}
