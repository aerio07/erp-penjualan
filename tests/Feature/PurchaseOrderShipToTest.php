<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderShipToTest extends TestCase
{
    use RefreshDatabase;

    private User $purchasing;
    private Supplier $supplier;
    private Product $product;
    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->purchasing = User::factory()->create(['role' => 'purchasing']);
        $this->supplier = Supplier::create([
            'code' => 'SUP-TEST',
            'name' => 'Supplier Test',
            'is_active' => true,
        ]);
        $this->product = Product::create([
            'sku' => 'PROD-PO-01',
            'name' => 'Produk Test PO',
            'category' => 'General',
            'unit' => 'pcs',
            'purchase_price' => 20000,
            'sell_price' => 30000,
            'is_active' => true,
        ]);
        $this->warehouse = Warehouse::create([
            'code' => 'WH-01',
            'name' => 'Gudang Pusat',
            'address' => 'Jl. Merdeka No. 10, Jakarta',
            'is_active' => true,
        ]);
    }

    public function test_can_create_purchase_order_with_ship_to(): void
    {
        $response = $this->actingAs($this->purchasing)->post(route('purchase.orders.store'), [
            'supplier_id' => $this->supplier->id,
            'order_date' => now()->toDateString(),
            'expected_date' => now()->addDays(5)->toDateString(),
            'tax_rate' => 11,
            'discount_amount' => 0,
            'notes' => 'Catatan PO',
            'ship_to' => 'Gudang Pusat - Jl. Merdeka No. 10, Jakarta (UP: Pak Budi)',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'qty_ordered' => 10,
                    'unit_price' => 20000,
                    'discount_percent' => 0,
                ],
            ],
        ]);

        $response->assertRedirect(route('purchase.orders.index'));

        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $this->supplier->id,
            'ship_to' => 'Gudang Pusat - Jl. Merdeka No. 10, Jakarta (UP: Pak Budi)',
        ]);
    }

    public function test_can_update_purchase_order_ship_to(): void
    {
        $po = PurchaseOrder::create([
            'po_number' => 'PO-202608-0001',
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->purchasing->id,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'expected_date' => now()->addDays(5)->toDateString(),
            'tax_rate' => 11,
            'tax_amount' => 22000,
            'total_amount' => 222000,
            'ship_to' => 'Alamat Awal',
        ]);

        $response = $this->actingAs($this->purchasing)->put(route('purchase.orders.update', $po), [
            'supplier_id' => $this->supplier->id,
            'order_date' => now()->toDateString(),
            'expected_date' => now()->addDays(5)->toDateString(),
            'tax_rate' => 11,
            'discount_amount' => 0,
            'notes' => 'Updated notes',
            'ship_to' => 'Gudang Cabang Baru - Jl. Pahlawan No. 5',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'qty_ordered' => 10,
                    'unit_price' => 20000,
                    'discount_percent' => 0,
                ],
            ],
        ]);

        $response->assertRedirect(route('purchase.orders.show', $po));

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $po->id,
            'ship_to' => 'Gudang Cabang Baru - Jl. Pahlawan No. 5',
        ]);
    }
}
