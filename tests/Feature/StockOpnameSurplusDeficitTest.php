<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockOpnameSurplusDeficitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ChartOfAccountSeeder::class);
    }

    private function createProduct(string $sku, string $name, float $price = 50000): Product
    {
        return Product::create([
            'sku' => $sku,
            'name' => $name,
            'category' => 'General',
            'unit' => 'pcs',
            'purchase_price' => $price,
            'sell_price' => $price * 1.5,
            'min_stock' => 5,
            'is_active' => true,
        ]);
    }

    private function createWarehouse(): Warehouse
    {
        return Warehouse::create([
            'code' => 'WH-TEST',
            'name' => 'Gudang Utama Test',
            'is_active' => true,
        ]);
    }

    /**
     * 1. Test surplus murni: sistem 50, fisik 60 -> adjustment +10, stok akhir 60.
     */
    public function test_pure_surplus_adjustment(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $warehouse = $this->createWarehouse();
        $product = $this->createProduct('SKU-SURPLUS', 'Produk Surplus Test');
        $stockService = app(StockService::class);

        // Initial stock = 50 via standard 'in' movement
        $stockService->recordMovement([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'in',
            'quantity' => 50,
            'unit_cost' => $product->purchase_price,
            'movement_date' => now()->toDateString(),
            'notes' => 'Stok awal',
            'user_id' => $user->id,
        ]);

        $this->assertEquals(50, $stockService->getCurrentStock($product->id, $warehouse->id));

        // Submit Stock Opname draft: physical 60
        $response = $this->actingAs($user)->post(route('inventory.opname.store'), [
            'warehouse_id' => $warehouse->id,
            'opname_date' => now()->toDateString(),
            'notes' => 'Opname Surplus Test',
            'items' => [
                [
                    'product_id' => $product->id,
                    'system_qty' => 50,
                    'physical_qty' => 60,
                    'notes' => 'Ditemukan kelebihan 10 pcs di rak belakang',
                ]
            ],
        ]);

        $response->assertRedirect(route('inventory.opname.index'));

        $opname = StockOpname::with('items')->first();
        $this->assertNotNull($opname);
        $this->assertEquals('draft', $opname->status);
        $this->assertCount(1, $opname->items);

        $item = $opname->items->first();
        $this->assertEquals(50, $item->system_qty);
        $this->assertEquals(60, $item->physical_qty);
        $this->assertEquals(10, $item->difference); // Difference is +10

        // Complete the Opname
        $completeResponse = $this->actingAs($user)->patch(route('inventory.opname.complete', $opname));
        $completeResponse->assertSessionHas('success');

        $opname->refresh();
        $this->assertEquals('completed', $opname->status);

        // Verify stock movement
        $movement = StockMovement::where('reference_type', StockOpname::class)
            ->where('reference_id', $opname->id)
            ->where('product_id', $product->id)
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals('adjustment', $movement->type);
        $this->assertEquals(10, $movement->quantity); // Signed integer +10

        // Verify final stock is 60
        $finalStock = $stockService->getCurrentStock($product->id, $warehouse->id);
        $this->assertEquals(60, $finalStock);
    }

    /**
     * 2. Test defisit murni: sistem 50, fisik 40 -> adjustment -10, stok akhir 40.
     */
    public function test_pure_deficit_adjustment(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $warehouse = $this->createWarehouse();
        $product = $this->createProduct('SKU-DEFICIT', 'Produk Defisit Test');
        $stockService = app(StockService::class);

        // Initial stock = 50
        $stockService->recordMovement([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'in',
            'quantity' => 50,
            'unit_cost' => $product->purchase_price,
            'movement_date' => now()->toDateString(),
            'notes' => 'Stok awal',
            'user_id' => $user->id,
        ]);

        // Submit Stock Opname draft: physical 40
        $this->actingAs($user)->post(route('inventory.opname.store'), [
            'warehouse_id' => $warehouse->id,
            'opname_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $product->id,
                    'system_qty' => 50,
                    'physical_qty' => 40,
                    'notes' => 'Hilang / rusak 10 pcs',
                ]
            ],
        ]);

        $opname = StockOpname::first();
        $item = $opname->items->first();
        $this->assertEquals(-10, $item->difference);

        // Complete the Opname
        $this->actingAs($user)->patch(route('inventory.opname.complete', $opname));

        // Verify movement
        $movement = StockMovement::where('reference_type', StockOpname::class)
            ->where('reference_id', $opname->id)
            ->where('product_id', $product->id)
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals('adjustment', $movement->type);
        $this->assertEquals(-10, $movement->quantity);

        // Final stock is 40
        $this->assertEquals(40, $stockService->getCurrentStock($product->id, $warehouse->id));
    }

    /**
     * 3. Test tidak ada selisih: sistem 50, fisik 50 -> TIDAK ADA movement adjustment yang dibuat.
     */
    public function test_no_difference_creates_no_movement(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $warehouse = $this->createWarehouse();
        $product = $this->createProduct('SKU-MATCH', 'Produk Cocok Test');
        $stockService = app(StockService::class);

        $stockService->recordMovement([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'in',
            'quantity' => 50,
            'unit_cost' => $product->purchase_price,
            'movement_date' => now()->toDateString(),
            'notes' => 'Stok awal',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)->post(route('inventory.opname.store'), [
            'warehouse_id' => $warehouse->id,
            'opname_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $product->id,
                    'system_qty' => 50,
                    'physical_qty' => 50,
                    'notes' => 'Sesuai fisik',
                ]
            ],
        ]);

        $opname = StockOpname::first();
        $this->assertEquals(0, $opname->items->first()->difference);

        $this->actingAs($user)->patch(route('inventory.opname.complete', $opname));

        $opname->refresh();
        $this->assertEquals('completed', $opname->status);

        // Assert NO adjustment movements were created
        $movementsCount = StockMovement::where('reference_type', StockOpname::class)
            ->where('reference_id', $opname->id)
            ->count();

        $this->assertEquals(0, $movementsCount);
        $this->assertEquals(50, $stockService->getCurrentStock($product->id, $warehouse->id));
    }

    /**
     * 4. Test satu Opname banyak produk sekaligus: campur surplus, defisit, dan tidak ada selisih.
     */
    public function test_multi_product_mixed_opname(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $warehouse = $this->createWarehouse();
        $stockService = app(StockService::class);

        $prodSurplus = $this->createProduct('SKU-MIX-A', 'Produk Surplus');
        $prodDeficit = $this->createProduct('SKU-MIX-B', 'Produk Defisit');
        $prodExact   = $this->createProduct('SKU-MIX-C', 'Produk Cocok');

        foreach ([$prodSurplus, $prodDeficit, $prodExact] as $p) {
            $stockService->recordMovement([
                'product_id' => $p->id,
                'warehouse_id' => $warehouse->id,
                'type' => 'in',
                'quantity' => 50,
                'unit_cost' => $p->purchase_price,
                'movement_date' => now()->toDateString(),
                'notes' => 'Stok awal 50',
                'user_id' => $user->id,
            ]);
        }

        // 1 Opname document with 3 products:
        // A: 50 -> 60 (+10)
        // B: 50 -> 40 (-10)
        // C: 50 -> 50 (0)
        $this->actingAs($user)->post(route('inventory.opname.store'), [
            'warehouse_id' => $warehouse->id,
            'opname_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $prodSurplus->id, 'system_qty' => 50, 'physical_qty' => 60],
                ['product_id' => $prodDeficit->id, 'system_qty' => 50, 'physical_qty' => 40],
                ['product_id' => $prodExact->id,   'system_qty' => 50, 'physical_qty' => 50],
            ],
        ]);

        $opname = StockOpname::first();
        $this->actingAs($user)->patch(route('inventory.opname.complete', $opname));

        // Verify movements
        $opnameMovements = StockMovement::where('reference_type', StockOpname::class)
            ->where('reference_id', $opname->id)
            ->get();

        // Exactly 2 movements (Surplus and Deficit, Exact produces none)
        $this->assertCount(2, $opnameMovements);

        $movSurplus = $opnameMovements->firstWhere('product_id', $prodSurplus->id);
        $this->assertNotNull($movSurplus);
        $this->assertEquals(10, $movSurplus->quantity);

        $movDeficit = $opnameMovements->firstWhere('product_id', $prodDeficit->id);
        $this->assertNotNull($movDeficit);
        $this->assertEquals(-10, $movDeficit->quantity);

        $this->assertNull($opnameMovements->firstWhere('product_id', $prodExact->id));

        // Check ending stocks
        $this->assertEquals(60, $stockService->getCurrentStock($prodSurplus->id, $warehouse->id));
        $this->assertEquals(40, $stockService->getCurrentStock($prodDeficit->id, $warehouse->id));
        $this->assertEquals(50, $stockService->getCurrentStock($prodExact->id, $warehouse->id));
    }

    /**
     * 5. Test traceability & visual representation in Stock Card & Movements.
     */
    public function test_traceability_and_stock_card_views(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $warehouse = $this->createWarehouse();
        $product = $this->createProduct('SKU-TRACE', 'Produk Trace Test');
        $stockService = app(StockService::class);

        $stockService->recordMovement([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'in',
            'quantity' => 50,
            'unit_cost' => $product->purchase_price,
            'movement_date' => now()->toDateString(),
            'notes' => 'Stok awal',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)->post(route('inventory.opname.store'), [
            'warehouse_id' => $warehouse->id,
            'opname_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'system_qty' => 50, 'physical_qty' => 60],
            ],
        ]);

        $opname = StockOpname::first();
        $this->actingAs($user)->patch(route('inventory.opname.complete', $opname));

        $movement = StockMovement::where('reference_type', StockOpname::class)->first();

        // 1. Check accessor source_url
        $expectedUrl = route('inventory.opname.show', $opname->id);
        $this->assertEquals($expectedUrl, $movement->source_url);

        // 2. Check Stock Card response renders OPNAME (SURPLUS), +10, and source link
        $cardResponse = $this->actingAs($user)->get(route('inventory.stock-card', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
        ]));

        $cardResponse->assertOk();
        $cardResponse->assertSee('OPNAME (SURPLUS)');
        $cardResponse->assertSee('+10');
        $cardResponse->assertSee($expectedUrl);

        // 3. Check Movements index page renders OPNAME (SURPLUS), +10, and source link
        $movementsResponse = $this->actingAs($user)->get(route('inventory.movements.index'));
        $movementsResponse->assertOk();
        $movementsResponse->assertSee('OPNAME (SURPLUS)');
        $movementsResponse->assertSee('+10');
        $movementsResponse->assertSee($expectedUrl);
    }
}
