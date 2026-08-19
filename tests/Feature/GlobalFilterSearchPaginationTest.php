<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GlobalFilterSearchPaginationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    #[Test]
    public function it_can_search_products_by_sku_and_name()
    {
        Product::create([
            'sku'            => 'PROD-ALPHA-01',
            'name'           => 'Barang Alpha Super',
            'unit'           => 'pcs',
            'purchase_price' => 10000,
            'sell_price'     => 15000,
            'is_active'      => true,
        ]);

        Product::create([
            'sku'            => 'PROD-BETA-02',
            'name'           => 'Barang Beta Standard',
            'unit'           => 'pcs',
            'purchase_price' => 20000,
            'sell_price'     => 25000,
            'is_active'      => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('master.products.index', ['q' => 'ALPHA']));

        $response->assertOk();
        $response->assertSee('Barang Alpha Super');
        $response->assertDontSee('Barang Beta Standard');
    }

    #[Test]
    public function it_can_filter_sales_orders_by_customer_and_status()
    {
        $customerA = Customer::create(['code' => 'CUST-A', 'name' => 'Customer Alpha', 'is_active' => true]);
        $customerB = Customer::create(['code' => 'CUST-B', 'name' => 'Customer Beta', 'is_active' => true]);

        $soDraft = SalesOrder::create([
            'so_number'    => 'SO-202608-0001',
            'customer_id'  => $customerA->id,
            'user_id'      => $this->adminUser->id,
            'status'       => 'draft',
            'order_date'   => now()->toDateString(),
            'total_amount' => 500000,
        ]);

        $soConfirmed = SalesOrder::create([
            'so_number'    => 'SO-202608-0002',
            'customer_id'  => $customerB->id,
            'user_id'      => $this->adminUser->id,
            'status'       => 'confirmed',
            'order_date'   => now()->toDateString(),
            'total_amount' => 1000000,
        ]);

        // Filter status = confirmed
        $response = $this->actingAs($this->adminUser)
            ->get(route('sales.orders.index', ['status' => 'confirmed']));

        $response->assertOk();
        $response->assertSee('SO-202608-0002');
        $response->assertDontSee('SO-202608-0001');

        // Filter customer_id = Customer Alpha
        $responseCust = $this->actingAs($this->adminUser)
            ->get(route('sales.orders.index', ['customer_id' => $customerA->id]));

        $responseCust->assertOk();
        $responseCust->assertSee('SO-202608-0001');
        $responseCust->assertDontSee('SO-202608-0002');
    }

    #[Test]
    public function it_can_filter_stock_movements_by_warehouse_and_valid_enum_type()
    {
        $wh1 = Warehouse::create(['code' => 'TWH-01-' . uniqid(), 'name' => 'Gudang Utama Test', 'is_active' => true]);
        $wh2 = Warehouse::create(['code' => 'TWH-02-' . uniqid(), 'name' => 'Gudang Karantina Test', 'is_active' => true]);

        $product = Product::create([
            'sku' => 'TSKU-' . uniqid(), 'name' => 'Kipas Angin Test', 'unit' => 'pcs', 'purchase_price' => 50000, 'sell_price' => 75000, 'is_active' => true,
        ]);

        StockMovement::create([
            'product_id'    => $product->id,
            'warehouse_id'  => $wh1->id,
            'type'          => 'in',
            'quantity'      => 10,
            'movement_date' => now()->toDateString(),
            'user_id'       => $this->adminUser->id,
            'notes'         => 'Stok Awal Masa',
        ]);

        StockMovement::create([
            'product_id'    => $product->id,
            'warehouse_id'  => $wh2->id,
            'type'          => 'return_in_damaged',
            'quantity'      => 2,
            'movement_date' => now()->toDateString(),
            'user_id'       => $this->adminUser->id,
            'notes'         => 'Retur Karantina Customer',
        ]);

        $responseType = $this->actingAs($this->adminUser)
            ->get(route('inventory.movements.index', ['type' => 'return_in_damaged']));

        $responseType->assertOk();
        $responseType->assertSee('RETUR KARANTINA');
        $responseType->assertSee('Gudang Karantina Test');

        $responseWh = $this->actingAs($this->adminUser)
            ->get(route('inventory.movements.index', ['warehouse_id' => $wh1->id]));

        $responseWh->assertOk();
        $responseWh->assertSee('Gudang Utama Test');
    }

    #[Test]
    public function it_preserves_query_string_parameters_in_pagination_and_sorting()
    {
        $supplier = Supplier::create(['code' => 'SUP-01', 'name' => 'Supplier Utama', 'is_active' => true]);

        for ($i = 1; $i <= 25; $i++) {
            Product::create([
                'sku'            => sprintf('SKU-%03d', $i),
                'name'           => 'Item Test ' . $i,
                'unit'           => 'pcs',
                'purchase_price' => 1000 * $i,
                'sell_price'     => 1500 * $i,
                'is_active'      => true,
            ]);
        }

        $response = $this->actingAs($this->adminUser)
            ->get(route('master.products.index', [
                'q'        => 'Item',
                'sort_by'  => 'purchase_price',
                'sort_dir' => 'asc',
                'per_page' => 10,
                'page'     => 2,
            ]));

        $response->assertOk();
        $response->assertSee('q=Item');
        $response->assertSee('sort_by=purchase_price');
        $response->assertSee('sort_dir=asc');
        $response->assertSee('per_page=10');
    }
}
