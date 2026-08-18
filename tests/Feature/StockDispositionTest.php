<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Product;
use App\Models\StockDisposition;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\JournalService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockDispositionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\ChartOfAccountSeeder::class);
    }

    public function test_stock_disposition_write_off_flow(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::create([
            'sku' => 'PRD-TEST-001',
            'name' => 'Produk Test Rusak',
            'category' => 'Elektronik',
            'unit' => 'pcs',
            'purchase_price' => 50000,
            'sell_price' => 75000,
            'min_stock' => 5,
            'is_active' => true,
        ]);
        $warehouse = Warehouse::create([
            'code' => 'WH-TEST',
            'name' => 'Gudang Utama Test',
            'is_active' => true,
        ]);

        $stockService = app(StockService::class);

        // 1. Simulasi Retur Penjualan Rusak -> Karantina 10 unit
        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'return_in_damaged',
            'quantity' => 10,
            'unit_cost' => 50000,
            'movement_date' => now()->toDateString(),
            'user_id' => $user->id,
        ]);

        // Sisa karantina awal = 10
        $this->assertEquals(10, $stockService->getQuarantineStockAvailable($product->id, $warehouse->id));
        $this->assertEquals(0, $stockService->getCurrentStock($product->id, $warehouse->id));

        // 2. Submit Disposition Write-Off 4 unit
        $response = $this->actingAs($user)->post(route('inventory.dispositions.store'), [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'qty' => 4,
            'resolution_type' => 'write_off',
            'disposed_at' => now()->toDateString(),
            'notes' => 'Barang hancur tidak bisa dipakai',
        ]);

        $response->assertRedirect(route('inventory.dispositions.index'));
        $response->assertSessionHas('success');

        // Sisa karantina berkurang jadi 6
        $this->assertEquals(6, $stockService->getQuarantineStockAvailable($product->id, $warehouse->id));
        // Stok sellable tetap 0
        $this->assertEquals(0, $stockService->getCurrentStock($product->id, $warehouse->id));

        $disposition = StockDisposition::first();
        $this->assertNotNull($disposition);
        $this->assertEquals('write_off', $disposition->resolution_type);
        $this->assertEquals(4, $disposition->qty);
        $this->assertEquals(50000, $disposition->unit_cost);
        $this->assertNotNull($disposition->journal_entry_id);

        // Cek Stock Movement tercatat dengan audit trail
        $movement = StockMovement::where('reference_type', StockDisposition::class)->first();
        $this->assertNotNull($movement);
        $this->assertEquals('write_off', $movement->type);
        $this->assertEquals(4, $movement->quantity);

        // Cek Journal Entry
        $journal = $disposition->journalEntry;
        $this->assertNotNull($journal);
        $this->assertTrue($journal->isBalanced());
        $this->assertEquals(200000, $journal->total_debit);

        // Debet 5-1300 Kerugian Persediaan Rusak = 200,000, Kredit 1-1400 = 200,000
        $kerugianAccount = ChartOfAccount::where('code', '5-1300')->first();
        $persediaanAccount = ChartOfAccount::where('code', '1-1400')->first();

        $debitLine = $journal->lines->where('chart_of_account_id', $kerugianAccount->id)->first();
        $creditLine = $journal->lines->where('chart_of_account_id', $persediaanAccount->id)->first();

        $this->assertEquals(200000, $debitLine->debit);
        $this->assertEquals(200000, $creditLine->credit);
    }

    public function test_stock_disposition_sold_as_reject_flow(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::create([
            'sku' => 'PRD-TEST-002',
            'name' => 'Produk Test Reject',
            'unit' => 'pcs',
            'purchase_price' => 40000,
            'sell_price' => 60000,
            'min_stock' => 2,
            'is_active' => true,
        ]);
        $warehouse = Warehouse::create([
            'code' => 'WH-TEST2',
            'name' => 'Gudang Cabang Test',
            'is_active' => true,
        ]);

        $stockService = app(StockService::class);

        // Karantina 5 unit
        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'return_in_damaged',
            'quantity' => 5,
            'unit_cost' => 40000,
            'movement_date' => now()->toDateString(),
            'user_id' => $user->id,
        ]);

        // Submit Sold As Reject 5 unit @ Rp 15.000
        $response = $this->actingAs($user)->post(route('inventory.dispositions.store'), [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'qty' => 5,
            'resolution_type' => 'sold_as_reject',
            'sale_price' => 15000,
            'disposed_at' => now()->toDateString(),
            'notes' => 'Dijual ke pengepul',
        ]);

        $response->assertRedirect(route('inventory.dispositions.index'));

        // Sisa karantina habis (0)
        $this->assertEquals(0, $stockService->getQuarantineStockAvailable($product->id, $warehouse->id));

        $disposition = StockDisposition::first();
        $this->assertEquals('sold_as_reject', $disposition->resolution_type);
        $this->assertEquals(15000, $disposition->sale_price);

        // Cek Journal Entry
        $journal = $disposition->journalEntry;
        $this->assertNotNull($journal);
        $this->assertTrue($journal->isBalanced());

        $kasAccount = ChartOfAccount::where('code', '1-1100')->first();
        $pendapatanAccount = ChartOfAccount::where('code', '4-1400')->first();
        $hppRejectAccount = ChartOfAccount::where('code', '5-1400')->first();
        $persediaanAccount = ChartOfAccount::where('code', '1-1400')->first();

        // 5 * 15.000 = 75.000 (Kas & Pendapatan Reject)
        // 5 * 40.000 = 200.000 (HPP Reject & Persediaan)
        $this->assertEquals(75000, $journal->lines->where('chart_of_account_id', $kasAccount->id)->first()->debit);
        $this->assertEquals(75000, $journal->lines->where('chart_of_account_id', $pendapatanAccount->id)->first()->credit);
        $this->assertEquals(200000, $journal->lines->where('chart_of_account_id', $hppRejectAccount->id)->first()->debit);
        $this->assertEquals(200000, $journal->lines->where('chart_of_account_id', $persediaanAccount->id)->first()->credit);
    }

    public function test_over_disposal_validation(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::create([
            'sku' => 'PRD-TEST-003',
            'name' => 'Produk Test Over',
            'unit' => 'pcs',
            'purchase_price' => 10000,
            'sell_price' => 15000,
            'min_stock' => 1,
            'is_active' => true,
        ]);
        $warehouse = Warehouse::create([
            'code' => 'WH-TEST3',
            'name' => 'Gudang 3',
            'is_active' => true,
        ]);

        // Karantina hanya 3 unit
        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'return_in_damaged',
            'quantity' => 3,
            'unit_cost' => 10000,
            'movement_date' => now()->toDateString(),
            'user_id' => $user->id,
        ]);

        // Coba disposition 5 unit -> ditolak
        $response = $this->actingAs($user)->post(route('inventory.dispositions.store'), [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'qty' => 5,
            'resolution_type' => 'write_off',
            'disposed_at' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['qty']);
    }
}
