<?php

namespace Tests\Feature;

use App\Exceptions\PeriodClosedException;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Services\AccountingPeriodService;
use App\Services\JournalService;
use Carbon\Carbon;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingPeriodClosingTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected JournalService $journalService;
    protected AccountingPeriodService $periodService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountSeeder::class);

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->journalService = app(JournalService::class);
        $this->periodService = app(AccountingPeriodService::class);
    }

    /**
     * ATURAN #1 (Prinsip Non-Negosiabel):
     * Tanggal tanpa record periode HARUS tetap boleh transaksi seperti biasa (terbuka).
     */
    public function test_date_without_period_record_allows_transactions_normally(): void
    {
        // Pastikan tabel accounting_periods benar-benar kosong
        $this->assertEquals(0, AccountingPeriod::count());

        $dateWithoutPeriod = '2024-05-15';

        $this->assertFalse($this->periodService->isDateLocked(Carbon::parse($dateWithoutPeriod)));

        // Tidak boleh throw exception
        $this->periodService->assertDateNotLocked(Carbon::parse($dateWithoutPeriod));

        // Buat manual journal entry bertanggal 2024-05-15
        $kas   = ChartOfAccount::where('code', '1-1100')->first();
        $modal = ChartOfAccount::where('code', '3-1100')->first();

        $response = $this->actingAs($this->adminUser)
            ->post(route('accounting.journals.store'), [
                'entry_date'  => $dateWithoutPeriod,
                'description' => 'Transaksi tanpa periode',
                'lines'       => [
                    ['chart_of_account_id' => $kas->id, 'debit' => 1000000, 'credit' => 0],
                    ['chart_of_account_id' => $modal->id, 'debit' => 0, 'credit' => 1000000],
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('journal_entries', [
            'entry_date'  => $dateWithoutPeriod,
            'description' => 'Transaksi tanpa periode',
        ]);
    }

    /**
     * Periode yang ada tetapi berstatus 'open' juga mengizinkan transaksi berjalan normal.
     */
    public function test_date_with_open_period_allows_transactions_normally(): void
    {
        AccountingPeriod::create([
            'fiscal_year' => 2026,
            'month'       => 10,
            'start_date'  => '2026-10-01',
            'end_date'    => '2026-10-31',
            'status'      => 'open',
        ]);

        $this->assertFalse($this->periodService->isDateLocked(Carbon::parse('2026-10-15')));

        $kas   = ChartOfAccount::where('code', '1-1100')->first();
        $modal = ChartOfAccount::where('code', '3-1100')->first();

        $response = $this->actingAs($this->adminUser)
            ->post(route('accounting.journals.store'), [
                'entry_date'  => '2026-10-15',
                'description' => 'Transaksi periode open',
                'lines'       => [
                    ['chart_of_account_id' => $kas->id, 'debit' => 500000, 'credit' => 0],
                    ['chart_of_account_id' => $modal->id, 'debit' => 0, 'credit' => 500000],
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
    }

    /**
     * Periode yang berstatus 'closed' menolak pembuatan transaksi baru pada tanggal tersebut.
     */
    public function test_closed_period_blocks_journal_creation(): void
    {
        AccountingPeriod::create([
            'fiscal_year' => 2026,
            'month'       => 9,
            'start_date'  => '2026-09-01',
            'end_date'    => '2026-09-30',
            'status'      => 'closed',
            'closed_by'   => $this->adminUser->id,
            'closed_at'   => now(),
        ]);

        $this->assertTrue($this->periodService->isDateLocked(Carbon::parse('2026-09-15')));

        // Uji direct assertion melempar PeriodClosedException
        $this->expectException(PeriodClosedException::class);
        $this->periodService->assertDateNotLocked(Carbon::parse('2026-09-15'));
    }

    /**
     * Percobaan input jurnal manual di periode closed melalui UI menghasilkan pesan error yang bersahabat.
     */
    public function test_ui_blocks_manual_journal_on_closed_period(): void
    {
        AccountingPeriod::create([
            'fiscal_year' => 2026,
            'month'       => 9,
            'start_date'  => '2026-09-01',
            'end_date'    => '2026-09-30',
            'status'      => 'closed',
            'closed_by'   => $this->adminUser->id,
            'closed_at'   => now(),
        ]);

        $kas   = ChartOfAccount::where('code', '1-1100')->first();
        $modal = ChartOfAccount::where('code', '3-1100')->first();

        $response = $this->actingAs($this->adminUser)
            ->post(route('accounting.journals.store'), [
                'entry_date'  => '2026-09-15',
                'description' => 'Coba tembus periode closed',
                'lines'       => [
                    ['chart_of_account_id' => $kas->id, 'debit' => 500000, 'credit' => 0],
                    ['chart_of_account_id' => $modal->id, 'debit' => 0, 'credit' => 500000],
                ],
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('journal_entries', [
            'entry_date'  => '2026-09-15',
            'description' => 'Coba tembus periode closed',
        ]);
    }

    /**
     * Admin dapat melakukan generate 12 periode untuk tahun tertentu.
     */
    public function test_admin_can_generate_twelve_periods_for_year(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('accounting.periods.generate'), [
                'year' => 2027,
            ]);

        $response->assertRedirect(route('accounting.periods.index', ['year' => 2027]));
        $response->assertSessionHas('success');

        $this->assertEquals(12, AccountingPeriod::where('fiscal_year', 2027)->count());
        $jan = AccountingPeriod::where('fiscal_year', 2027)->where('month', 1)->first();
        $this->assertEquals('2027-01-01', $jan->start_date->toDateString());
        $this->assertEquals('2027-01-31', $jan->end_date->toDateString());
        $this->assertEquals('open', $jan->status);
    }

    /**
     * Penutupan periode ditolak jika masih ada jurnal draft pada periode tersebut.
     */
    public function test_cannot_close_period_if_draft_journals_exist(): void
    {
        $period = AccountingPeriod::create([
            'fiscal_year' => 2026,
            'month'       => 8,
            'start_date'  => '2026-08-01',
            'end_date'    => '2026-08-31',
            'status'      => 'open',
        ]);

        $kas = ChartOfAccount::where('code', '1-1100')->first();

        // Buat jurnal berstatus draft di bulan Agustus 2026
        $entry = JournalEntry::create([
            'entry_number'   => 'JE-DRAFT-01',
            'entry_date'     => '2026-08-10',
            'description'    => 'Draft Journal',
            'status'         => 'draft',
            'created_by'     => $this->adminUser->id,
        ]);

        JournalLine::create([
            'journal_entry_id'    => $entry->id,
            'chart_of_account_id' => $kas->id,
            'debit'               => 1000,
            'credit'              => 1000,
        ]);

        $checklist = $this->periodService->getClosingChecklist($period);
        $this->assertFalse($checklist['can_close']);
        $this->assertEquals(1, $checklist['draft_count']);

        $response = $this->actingAs($this->adminUser)
            ->post(route('accounting.periods.close', $period));

        $response->assertSessionHas('error');
        $this->assertEquals('open', $period->fresh()->status);
    }

    /**
     * Penutupan periode berhasil jika checklist terpenuhi.
     */
    public function test_can_close_period_when_checklist_passed(): void
    {
        $period = AccountingPeriod::create([
            'fiscal_year' => 2026,
            'month'       => 7,
            'start_date'  => '2026-07-01',
            'end_date'    => '2026-07-31',
            'status'      => 'open',
        ]);

        $checklist = $this->periodService->getClosingChecklist($period);
        $this->assertTrue($checklist['can_close']);

        $response = $this->actingAs($this->adminUser)
            ->post(route('accounting.periods.close', $period));

        $response->assertRedirect(route('accounting.periods.show', $period));
        $response->assertSessionHas('success');

        $fresh = $period->fresh();
        $this->assertEquals('closed', $fresh->status);
        $this->assertEquals($this->adminUser->id, $fresh->closed_by);
        $this->assertNotNull($fresh->closed_at);
    }

    /**
     * Admin dapat membuka kembali (reopen) periode tertutup dengan mencantumkan alasan.
     */
    public function test_can_reopen_closed_period_with_reason(): void
    {
        $period = AccountingPeriod::create([
            'fiscal_year' => 2026,
            'month'       => 6,
            'start_date'  => '2026-06-01',
            'end_date'    => '2026-06-30',
            'status'      => 'closed',
            'closed_by'   => $this->adminUser->id,
            'closed_at'   => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('accounting.periods.reopen', $period), [
                'reopen_reason' => 'Perlu koreksi tagihan retur vendor yang tertunda',
            ]);

        $response->assertRedirect(route('accounting.periods.show', $period));
        $response->assertSessionHas('success');

        $fresh = $period->fresh();
        $this->assertEquals('open', $fresh->status);
        $this->assertEquals($this->adminUser->id, $fresh->reopened_by);
        $this->assertEquals('Perlu koreksi tagihan retur vendor yang tertunda', $fresh->reopen_reason);
        $this->assertNotNull($fresh->reopened_at);

        // Setelah reopen, transaksi di tanggal tersebut kembali diizinkan
        $this->assertFalse($this->periodService->isDateLocked(Carbon::parse('2026-06-15')));
    }
}
