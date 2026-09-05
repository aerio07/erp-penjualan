<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\JournalService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockOpnameReasonAndJournalTest extends TestCase
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
     * 1. Test validasi: adjustment_reason wajib jika selisih != 0.
     */
    public function test_validation_requires_adjustment_reason_when_difference_exists(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $warehouse = $this->createWarehouse();
        $product = $this->createProduct('SKU-VAL-1', 'Produk Validasi Test');

        // A. Ada selisih (sistem 50, fisik 45) tapi alasan kosong -> harus gagal validasi
        $responseFail = $this->actingAs($user)->post(route('inventory.opname.store'), [
            'warehouse_id' => $warehouse->id,
            'opname_date'  => now()->toDateString(),
            'items' => [
                [
                    'product_id'   => $product->id,
                    'system_qty'   => 50,
                    'physical_qty' => 45,
                    'adjustment_reason' => '',
                ]
            ],
        ]);

        $responseFail->assertSessionHasErrors('items.0.adjustment_reason');

        // B. Tidak ada selisih (sistem 50, fisik 50) tanpa alasan -> harus lolos
        $responsePassZero = $this->actingAs($user)->post(route('inventory.opname.store'), [
            'warehouse_id' => $warehouse->id,
            'opname_date'  => now()->toDateString(),
            'items' => [
                [
                    'product_id'   => $product->id,
                    'system_qty'   => 50,
                    'physical_qty' => 50,
                    'adjustment_reason' => '',
                ]
            ],
        ]);

        $responsePassZero->assertSessionHasNoErrors();

        // C. Ada selisih surplus (sistem 50, fisik 60) dengan alasan valid -> harus lolos
        $responsePassSurplus = $this->actingAs($user)->post(route('inventory.opname.store'), [
            'warehouse_id' => $warehouse->id,
            'opname_date'  => now()->toDateString(),
            'items' => [
                [
                    'product_id'   => $product->id,
                    'system_qty'   => 50,
                    'physical_qty' => 60,
                    'adjustment_reason' => 'found_extra',
                    'adjustment_note'   => 'Ditemukan di dus cadangan',
                ]
            ],
        ]);

        $responsePassSurplus->assertSessionHasNoErrors();

        $savedItem = StockOpnameItem::where('physical_qty', 60)->first();
        $this->assertNotNull($savedItem);
        $this->assertEquals('found_extra', $savedItem->adjustment_reason);
        $this->assertEquals('Ditemukan Lebih', $savedItem->reason_label);
        $this->assertEquals('Ditemukan di dus cadangan', $savedItem->adjustment_note);
    }

    /**
     * 2. Test Safe Journal di level dokumen:
     *    - Teragregasi di reference_type = StockOpname::class
     *    - Tidak ada nominal negatif di debit/credit
     *    - Defisit masuk ke Kerugian 5-1600 & Persediaan 1-1400
     *    - Surplus masuk ke Persediaan 1-1400 & Pendapatan 4-1500
     */
    public function test_safe_journal_at_document_level_with_no_negative_amounts(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $warehouse = $this->createWarehouse();
        $stockService = app(StockService::class);

        $prodDeficit = $this->createProduct('SKU-DEF-1', 'Produk Defisit', 50000); // 50.000 / pcs
        $prodSurplus = $this->createProduct('SKU-SUR-1', 'Produk Surplus', 20000); // 20.000 / pcs

        // Stok awal
        $stockService->recordMovement([
            'product_id' => $prodDeficit->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'in',
            'quantity' => 50,
            'unit_cost' => 50000,
            'movement_date' => now()->toDateString(),
            'notes' => 'Stok awal',
            'user_id' => $user->id,
        ]);
        $stockService->recordMovement([
            'product_id' => $prodSurplus->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'in',
            'quantity' => 30,
            'unit_cost' => 20000,
            'movement_date' => now()->toDateString(),
            'notes' => 'Stok awal',
            'user_id' => $user->id,
        ]);

        // Submit Opname
        // Item 1: 50 -> 40 (Defisit 10 x 50.000 = Rp 500.000)
        // Item 2: 30 -> 35 (Surplus 5 x 20.000 = Rp 100.000)
        $this->actingAs($user)->post(route('inventory.opname.store'), [
            'warehouse_id' => $warehouse->id,
            'opname_date'  => now()->toDateString(),
            'items' => [
                [
                    'product_id'        => $prodDeficit->id,
                    'system_qty'        => 50,
                    'physical_qty'      => 40,
                    'adjustment_reason' => 'lost',
                ],
                [
                    'product_id'        => $prodSurplus->id,
                    'system_qty'        => 30,
                    'physical_qty'      => 35,
                    'adjustment_reason' => 'found_extra',
                ],
            ],
        ]);

        $opname = StockOpname::first();
        $this->actingAs($user)->patch(route('inventory.opname.complete', $opname));

        // 1 Jurnal teragregasi pada level StockOpname
        $journal = JournalEntry::where('reference_type', StockOpname::class)
            ->where('reference_id', $opname->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertEquals('posted', $journal->status);
        $this->assertTrue($journal->isBalanced());

        $lines = $journal->lines;
        $this->assertCount(4, $lines);

        // WAJIB: Pastikan TIDAK ADA nilai negatif di debit maupun kredit
        foreach ($lines as $line) {
            $this->assertGreaterThanOrEqual(0, $line->debit, "Nilai debit tidak boleh negatif");
            $this->assertGreaterThanOrEqual(0, $line->credit, "Nilai credit tidak boleh negatif");
        }

        $coaPersediaan = ChartOfAccount::where('code', '1-1400')->first();
        $coaKerugian   = ChartOfAccount::where('code', '5-1600')->first();
        $coaPendapatan = ChartOfAccount::where('code', '4-1500')->first();

        // Cek baris Kerugian (Defisit 10 unit x 50.000 = 500.000)
        $lineKerugian = $lines->firstWhere('chart_of_account_id', $coaKerugian->id);
        $this->assertNotNull($lineKerugian);
        $this->assertEquals(500000, (float) $lineKerugian->debit);
        $this->assertEquals(0, (float) $lineKerugian->credit);

        // Cek baris Pendapatan (Surplus 5 unit x 20.000 = 100.000)
        $linePendapatan = $lines->firstWhere('chart_of_account_id', $coaPendapatan->id);
        $this->assertNotNull($linePendapatan);
        $this->assertEquals(0, (float) $linePendapatan->debit);
        $this->assertEquals(100000, (float) $linePendapatan->credit);

        // Cek total persediaan
        $persediaanDebit  = $lines->where('chart_of_account_id', $coaPersediaan->id)->sum('debit');
        $persediaanCredit = $lines->where('chart_of_account_id', $coaPersediaan->id)->sum('credit');

        $this->assertEquals(100000, (float) $persediaanDebit);
        $this->assertEquals(500000, (float) $persediaanCredit);
    }

    /**
     * 3. Test handling barang bonus / unit_cost = 0 tidak membuat jurnal bernilai Rp0.
     */
    public function test_zero_cost_product_does_not_create_empty_or_zero_journal(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $warehouse = $this->createWarehouse();
        $productZero = $this->createProduct('SKU-ZERO', 'Produk Bonus Sample', 0); // Rp0

        $this->actingAs($user)->post(route('inventory.opname.store'), [
            'warehouse_id' => $warehouse->id,
            'opname_date'  => now()->toDateString(),
            'items' => [
                [
                    'product_id'        => $productZero->id,
                    'system_qty'        => 0,
                    'physical_qty'      => 5,
                    'adjustment_reason' => 'found_extra',
                ],
            ],
        ]);

        $opname = StockOpname::first();
        $this->actingAs($user)->patch(route('inventory.opname.complete', $opname));

        // Karena unit cost = 0, tidak ada jurnal kosong/Rp0 yang terbentuk
        $journal = JournalEntry::where('reference_type', StockOpname::class)
            ->where('reference_id', $opname->id)
            ->first();

        $this->assertNull($journal);
    }

    /**
     * 4. Test Idempotency Guard: pemanggilan createFromStockOpname berulang tidak menduplikasi jurnal.
     */
    public function test_idempotency_guard_prevents_duplicate_journal(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $warehouse = $this->createWarehouse();
        $product = $this->createProduct('SKU-IDEM', 'Produk Idempotent', 10000);

        $opname = StockOpname::create([
            'opname_number' => 'SOP-IDEM-001',
            'warehouse_id'  => $warehouse->id,
            'user_id'       => $user->id,
            'status'        => 'draft',
            'opname_date'   => now(),
        ]);

        StockOpnameItem::create([
            'stock_opname_id'   => $opname->id,
            'product_id'        => $product->id,
            'system_qty'        => 10,
            'physical_qty'      => 15,
            'difference'        => 5,
            'adjustment_reason' => 'found_extra',
        ]);

        $journalService = app(JournalService::class);

        $entry1 = $journalService->createFromStockOpname($opname);
        $this->assertNotNull($entry1);

        $entry2 = $journalService->createFromStockOpname($opname);
        $this->assertEquals($entry1->id, $entry2->id);

        $totalCount = JournalEntry::where('reference_type', StockOpname::class)
            ->where('reference_id', $opname->id)
            ->count();

        $this->assertEquals(1, $totalCount);
    }

    /**
     * 5. Test Saldo Awal (Opening Balance):
     *    - Mutasi tipe opening_balance
     *    - Jurnal masuk ke Persediaan 1-1400 (Debit) dan Modal Pemilik 3-1100 (Kredit)
     *    - Tidak menyentuh akun pendapatan atau beban di Laba/Rugi
     *    - Stok sistem & kartu stok bertambah
     */
    public function test_opening_balance_journal_and_balance_sheet_isolation(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $warehouse = $this->createWarehouse();
        $product = $this->createProduct('SKU-OPENING', 'Produk Saldo Awal', 75000);
        $stockService = app(StockService::class);
        $journalService = app(JournalService::class);

        // Catat mutasi Saldo Awal 100 pcs @ Rp 75.000 = Rp 7.500.000
        $movement = $stockService->recordMovement([
            'product_id'    => $product->id,
            'warehouse_id'  => $warehouse->id,
            'type'          => 'opening_balance',
            'quantity'      => 100,
            'unit_cost'     => 75000,
            'movement_date' => now()->toDateString(),
            'notes'         => 'Migrasi saldo awal dari sistem lama',
            'user_id'       => $user->id,
        ]);

        $this->assertEquals('opening_balance', $movement->type);
        $this->assertEquals(100, $stockService->getCurrentStock($product->id, $warehouse->id));

        // Buat jurnal saldo awal
        $journal = $journalService->createFromOpeningBalance($movement);

        $this->assertNotNull($journal);
        $this->assertEquals('posted', $journal->status);
        $this->assertTrue($journal->isBalanced());

        $coaPersediaan = ChartOfAccount::where('code', '1-1400')->first();
        $coaModal      = ChartOfAccount::where('code', '3-1100')->first();

        $linePersediaan = $journal->lines->firstWhere('chart_of_account_id', $coaPersediaan->id);
        $lineModal      = $journal->lines->firstWhere('chart_of_account_id', $coaModal->id);

        $this->assertNotNull($linePersediaan);
        $this->assertEquals(7500000, (float) $linePersediaan->debit);
        $this->assertEquals(0, (float) $linePersediaan->credit);

        $this->assertNotNull($lineModal);
        $this->assertEquals(0, (float) $lineModal->debit);
        $this->assertEquals(7500000, (float) $lineModal->credit);

        // Pastikan TIDAK ADA akun Revenue atau Expense yang tersentuh
        $affectedAccountTypes = $journal->lines->map(fn($l) => $l->account->type)->unique();
        $this->assertFalse($affectedAccountTypes->contains('revenue'), "Saldo awal tidak boleh menyentuh revenue");
        $this->assertFalse($affectedAccountTypes->contains('expense'), "Saldo awal tidak boleh menyentuh expense");
    }
}
