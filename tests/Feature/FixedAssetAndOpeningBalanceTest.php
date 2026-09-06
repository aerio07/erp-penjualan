<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use App\Models\JournalEntry;
use App\Models\User;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\FixedAssetCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixedAssetAndOpeningBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountSeeder::class);
        $this->seed(FixedAssetCategorySeeder::class);

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    public function test_opening_balance_page_is_accessible()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('accounting.opening-balance.index'));

        $response->assertStatus(200);
        $response->assertSee('Saldo Awal Neraca');
        $response->assertSee('1-1100');
        $response->assertSee('3-1100');
    }

    public function test_opening_balance_can_be_saved_when_balanced()
    {
        $kas = ChartOfAccount::where('code', '1-1100')->first();
        $modal = ChartOfAccount::where('code', '3-1100')->first();

        $response = $this->actingAs($this->adminUser)
            ->post(route('accounting.opening-balance.store'), [
                'cut_off_date' => '2026-01-01',
                'notes' => 'Saldo Awal Tahun 2026',
                'lines' => [
                    ['account_id' => $kas->id, 'debit' => 50000000, 'credit' => 0],
                    ['account_id' => $modal->id, 'debit' => 0, 'credit' => 50000000],
                ],
            ]);

        $response->assertRedirect(route('accounting.opening-balance.index'));
        $response->assertSessionHas('success');

        $journal = JournalEntry::where('reference_type', 'OpeningBalance')->first();
        $this->assertNotNull($journal);
        $this->assertEquals('posted', $journal->status);
        $this->assertEquals(2, $journal->lines()->count());
        $this->assertTrue($journal->isBalanced());
    }

    public function test_opening_balance_rejects_unbalanced_input()
    {
        $kas = ChartOfAccount::where('code', '1-1100')->first();
        $modal = ChartOfAccount::where('code', '3-1100')->first();

        $response = $this->actingAs($this->adminUser)
            ->post(route('accounting.opening-balance.store'), [
                'cut_off_date' => '2026-01-01',
                'notes' => 'Saldo Awal Belum Seimbang',
                'lines' => [
                    ['account_id' => $kas->id, 'debit' => 50000000, 'credit' => 0],
                    ['account_id' => $modal->id, 'debit' => 0, 'credit' => 40000000],
                ],
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('journal_entries', [
            'reference_type' => 'OpeningBalance',
        ]);
    }

    public function test_fixed_asset_index_is_accessible()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('accounting.fixed-assets.index'));

        $response->assertStatus(200);
        $response->assertSee('Aset Tetap &amp; Inventaris', false);
        $response->assertSee('Tambah / Beli Aset');
    }

    public function test_fixed_asset_cash_purchase_creates_n_rows_and_posts_cash_journal()
    {
        $category = FixedAssetCategory::where('code', 'KND')->first(); // Kendaraan (1-2030)
        $kas = ChartOfAccount::where('code', '1-1100')->first();

        $response = $this->actingAs($this->adminUser)
            ->post(route('accounting.fixed-assets.store'), [
                'category_id' => $category->id,
                'name' => 'Honda Vario 160',
                'acquisition_type' => 'purchase',
                'purchase_date' => '2026-09-06',
                'quantity' => 3, // Beli 3 unit
                'unit_cost' => 25000000, // 25 jt / unit -> total 75 jt
                'payment_account_id' => $kas->id,
                'location' => 'Kantor Cabang',
                'serial_numbers' => ['SN-VARIO-01', 'SN-VARIO-02', 'SN-VARIO-03'],
                'notes' => 'Armada operasional kurir',
            ]);

        $response->assertRedirect(route('accounting.fixed-assets.index'));
        $response->assertSessionHas('success');

        // Pastikan terbuat 3 baris detail aset terpisah
        $assets = FixedAsset::where('name', 'Honda Vario 160')->orderBy('id')->get();
        $this->assertCount(3, $assets);

        $this->assertEquals('AST-KND-00001', $assets[0]->asset_code);
        $this->assertEquals('SN-VARIO-01', $assets[0]->serial_number);
        $this->assertEquals(25000000, $assets[0]->purchase_cost);

        $this->assertEquals('AST-KND-00002', $assets[1]->asset_code);
        $this->assertEquals('SN-VARIO-02', $assets[1]->serial_number);

        $this->assertEquals('AST-KND-00003', $assets[2]->asset_code);
        $this->assertEquals('SN-VARIO-03', $assets[2]->serial_number);

        // Pastikan 1 Jurnal Kas terbuat dengan total 75.000.000
        $journalId = $assets[0]->journal_entry_id;
        $this->assertNotNull($journalId);
        $this->assertEquals($journalId, $assets[1]->journal_entry_id);
        $this->assertEquals($journalId, $assets[2]->journal_entry_id);

        $journal = JournalEntry::with('lines.chartOfAccount')->find($journalId);
        $this->assertNotNull($journal);
        $this->assertEquals('posted', $journal->status);
        $this->assertTrue($journal->isBalanced());

        // Baris Debit: 1-2030 (Kendaraan) = 75.000.000
        $debitLine = $journal->lines->where('debit', '>', 0)->first();
        $this->assertEquals('1-2030', $debitLine->chartOfAccount->code);
        $this->assertEquals(75000000, (float) $debitLine->debit);

        // Baris Kredit: 1-1100 (Kas & Bank) = 75.000.000
        $creditLine = $journal->lines->where('credit', '>', 0)->first();
        $this->assertEquals('1-1100', $creditLine->chartOfAccount->code);
        $this->assertEquals(75000000, (float) $creditLine->credit);
    }

    public function test_fixed_asset_opening_balance_creates_n_rows_without_cash_journal()
    {
        $category = FixedAssetCategory::where('code', 'PRL')->first(); // Peralatan Kantor (1-2100)

        $response = $this->actingAs($this->adminUser)
            ->post(route('accounting.fixed-assets.store'), [
                'category_id' => $category->id,
                'name' => 'MacBook Pro M1',
                'acquisition_type' => 'opening_balance',
                'purchase_date' => '2025-01-15',
                'quantity' => 2,
                'unit_cost' => 18000000,
                'location' => 'Divisi IT',
                'notes' => 'Aset lama bawaan',
            ]);

        $response->assertRedirect(route('accounting.fixed-assets.index'));
        $response->assertSessionHas('success');

        // Pastikan terbuat 2 baris detail aset
        $assets = FixedAsset::where('name', 'MacBook Pro M1')->get();
        $this->assertCount(2, $assets);

        // Tidak boleh ada jurnal kas!
        $this->assertNull($assets[0]->journal_entry_id);
        $this->assertNull($assets[1]->journal_entry_id);
        $this->assertEquals('opening_balance', $assets[0]->acquisition_type);
    }

    public function test_balance_sheet_includes_fixed_asset_accounts_dynamically()
    {
        // 1. Post opening balance
        $kas = ChartOfAccount::where('code', '1-1100')->first();
        $modal = ChartOfAccount::where('code', '3-1100')->first();
        $tanah = ChartOfAccount::where('code', '1-2010')->first();

        // Kas 100jt, Tanah 200jt vs Modal 300jt
        $this->actingAs($this->adminUser)
            ->post(route('accounting.opening-balance.store'), [
                'cut_off_date' => '2026-01-01',
                'lines' => [
                    ['account_id' => $kas->id, 'debit' => 100000000, 'credit' => 0],
                    ['account_id' => $tanah->id, 'debit' => 200000000, 'credit' => 0],
                    ['account_id' => $modal->id, 'debit' => 0, 'credit' => 300000000],
                ],
            ]);

        // 2. Akses neraca keuangan
        $response = $this->actingAs($this->adminUser)
            ->get(route('accounting.reports.balance-sheet', ['as_of_date' => '2026-09-06']));

        $response->assertStatus(200);
        $response->assertSee('1-2010');
        $response->assertSee('Tanah');
        $response->assertSee('BALANCED', false);
    }
}
