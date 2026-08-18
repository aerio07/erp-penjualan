<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\ChartOfAccountSeeder::class);
    }

    public function test_normal_transfer_lifecycle_draft_ship_receive(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $sender = User::factory()->create(['role' => 'gudang']);
        $receiver = User::factory()->create(['role' => 'gudang']);

        $product = Product::create([
            'sku' => 'TRF-PRD-001',
            'name' => 'Produk Transfer Test',
            'unit' => 'pcs',
            'purchase_price' => 20000,
            'sell_price' => 30000,
            'min_stock' => 5,
            'is_active' => true,
        ]);

        $whA = Warehouse::create(['code' => 'WH-A', 'name' => 'Gudang Asal A', 'is_active' => true]);
        $whB = Warehouse::create(['code' => 'WH-B', 'name' => 'Gudang Tujuan B', 'is_active' => true]);

        $stockService = app(StockService::class);

        // Stok awal Gudang A = 50, Gudang B = 0
        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $whA->id,
            'type' => 'in',
            'quantity' => 50,
            'unit_cost' => 20000,
            'movement_date' => now()->toDateString(),
            'user_id' => $user->id,
        ]);

        $this->assertEquals(50, $stockService->getCurrentStock($product->id, $whA->id));
        $this->assertEquals(0, $stockService->getCurrentStock($product->id, $whB->id));

        // 1. Store (Buat Draft)
        $response = $this->actingAs($user)->post(route('inventory.transfers.store'), [
            'from_warehouse_id' => $whA->id,
            'to_warehouse_id' => $whB->id,
            'transfer_date' => now()->toDateString(),
            'notes' => 'Transfer dari A ke B',
            'items' => [
                ['product_id' => $product->id, 'qty' => 20],
            ],
        ]);

        $transfer = WarehouseTransfer::first();
        $this->assertNotNull($transfer);
        $this->assertEquals('draft', $transfer->status);

        // Stok belum berubah
        $this->assertEquals(50, $stockService->getCurrentStock($product->id, $whA->id));
        $this->assertEquals(0, $stockService->getCurrentStock($product->id, $whB->id));

        // 2. Ship (Kirim) oleh sender
        $shipResponse = $this->actingAs($sender)->patch(route('inventory.transfers.ship', $transfer));
        $shipResponse->assertRedirect();

        $transfer->refresh();
        $this->assertEquals('in_transit', $transfer->status);
        $this->assertEquals($sender->id, $transfer->shipped_by);
        $this->assertNotNull($transfer->shipped_at);

        // Stok Gudang A berkurang 20, Gudang B belum bertambah
        $this->assertEquals(30, $stockService->getCurrentStock($product->id, $whA->id));
        $this->assertEquals(0, $stockService->getCurrentStock($product->id, $whB->id));

        // Audit Trail Stock Movement transfer_out
        $outMovement = StockMovement::where('type', 'transfer_out')->first();
        $this->assertNotNull($outMovement);
        $this->assertEquals(WarehouseTransfer::class, $outMovement->reference_type);
        $this->assertEquals($transfer->id, $outMovement->reference_id);

        // 3. Receive (Terima) oleh receiver
        $receiveResponse = $this->actingAs($receiver)->patch(route('inventory.transfers.receive', $transfer));
        $receiveResponse->assertRedirect();

        $transfer->refresh();
        $this->assertEquals('completed', $transfer->status);
        $this->assertEquals($receiver->id, $transfer->received_by);
        $this->assertNotNull($transfer->received_at);

        // Stok Gudang B bertambah 20
        $this->assertEquals(30, $stockService->getCurrentStock($product->id, $whA->id));
        $this->assertEquals(20, $stockService->getCurrentStock($product->id, $whB->id));

        // Audit Trail Stock Movement transfer_in
        $inMovement = StockMovement::where('type', 'transfer_in')->first();
        $this->assertNotNull($inMovement);
        $this->assertEquals(WarehouseTransfer::class, $inMovement->reference_type);
        $this->assertEquals($transfer->id, $inMovement->reference_id);
    }

    public function test_stock_validation_at_ship_time(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::create([
            'sku' => 'TRF-PRD-002',
            'name' => 'Produk Race Condition',
            'unit' => 'pcs',
            'purchase_price' => 10000,
            'sell_price' => 15000,
            'min_stock' => 1,
            'is_active' => true,
        ]);
        $whA = Warehouse::create(['code' => 'WH-A2', 'name' => 'Gudang A2', 'is_active' => true]);
        $whB = Warehouse::create(['code' => 'WH-B2', 'name' => 'Gudang B2', 'is_active' => true]);

        // Stok A2 awal = 15
        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $whA->id,
            'type' => 'in',
            'quantity' => 15,
            'unit_cost' => 10000,
            'movement_date' => now()->toDateString(),
            'user_id' => $user->id,
        ]);

        // Buat Draft transfer 15 unit
        $this->actingAs($user)->post(route('inventory.transfers.store'), [
            'from_warehouse_id' => $whA->id,
            'to_warehouse_id' => $whB->id,
            'transfer_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'qty' => 15]],
        ]);

        $transfer = WarehouseTransfer::first();

        // Lalu ada transaksi lain yang mengurangi stok A2 sebesar 10 unit -> sisa 5
        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $whA->id,
            'type' => 'out',
            'quantity' => 10,
            'unit_cost' => 10000,
            'movement_date' => now()->toDateString(),
            'user_id' => $user->id,
        ]);

        // Coba dipanggil ship() -> harus gagal karena stok sisa 5 (< 15)
        $shipResponse = $this->actingAs($user)->patch(route('inventory.transfers.ship', $transfer));
        $shipResponse->assertSessionHasErrors(['stock']);

        $transfer->refresh();
        $this->assertEquals('draft', $transfer->status);
    }

    public function test_cancel_transfer_flow(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::create([
            'sku' => 'TRF-PRD-003',
            'name' => 'Produk Cancel',
            'unit' => 'pcs',
            'purchase_price' => 10000,
            'sell_price' => 15000,
            'min_stock' => 1,
            'is_active' => true,
        ]);
        $whA = Warehouse::create(['code' => 'WH-A3', 'name' => 'Gudang A3', 'is_active' => true]);
        $whB = Warehouse::create(['code' => 'WH-B3', 'name' => 'Gudang B3', 'is_active' => true]);

        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $whA->id,
            'type' => 'in',
            'quantity' => 10,
            'unit_cost' => 10000,
            'movement_date' => now()->toDateString(),
            'user_id' => $user->id,
        ]);

        // Buat Draft
        $this->actingAs($user)->post(route('inventory.transfers.store'), [
            'from_warehouse_id' => $whA->id,
            'to_warehouse_id' => $whB->id,
            'transfer_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'qty' => 5]],
        ]);

        $transfer = WarehouseTransfer::first();
        $this->assertEquals('draft', $transfer->status);

        // Batalkan
        $response = $this->actingAs($user)->patch(route('inventory.transfers.cancel', $transfer));
        $response->assertRedirect();

        $transfer->refresh();
        $this->assertEquals('cancelled', $transfer->status);
        $this->assertEquals(1, StockMovement::count());
    }

    public function test_illegal_state_transition(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::create([
            'sku' => 'TRF-PRD-004',
            'name' => 'Produk State Test',
            'unit' => 'pcs',
            'purchase_price' => 10000,
            'sell_price' => 15000,
            'min_stock' => 1,
            'is_active' => true,
        ]);
        $whA = Warehouse::create(['code' => 'WH-A4', 'name' => 'Gudang A4', 'is_active' => true]);
        $whB = Warehouse::create(['code' => 'WH-B4', 'name' => 'Gudang B4', 'is_active' => true]);

        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $whA->id,
            'type' => 'in',
            'quantity' => 10,
            'unit_cost' => 10000,
            'movement_date' => now()->toDateString(),
            'user_id' => $user->id,
        ]);

        // Buat Draft
        $this->actingAs($user)->post(route('inventory.transfers.store'), [
            'from_warehouse_id' => $whA->id,
            'to_warehouse_id' => $whB->id,
            'transfer_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'qty' => 5]],
        ]);

        $transfer = WarehouseTransfer::first();

        // Coba receive langsung dari status draft -> abort 403
        $response = $this->actingAs($user)->patch(route('inventory.transfers.receive', $transfer));
        $response->assertStatus(403);
    }
}
