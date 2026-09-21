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
        $this->assertDatabaseHas('journal_entries', [
            'description' => 'Transaksi tanpa periode',
        ]);
        $entry = JournalEntry::where('description', 'Transaksi tanpa periode')->first();
        $this->assertNotNull($entry);
        $this->assertEquals($dateWithoutPeriod, $entry->entry_date->toDateString());
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

    /**
     * Saat periode ditutup, percobaan pembuatan invoice tidak boleh crash 500
     * melainkan harus redirect back dengan flash alert session 'error'.
     */
    public function test_closed_period_returns_friendly_session_error_alert_on_invoice_creation(): void
    {
        // Tutup periode September 2026
        AccountingPeriod::create([
            'fiscal_year' => 2026,
            'month'       => 9,
            'start_date'  => '2026-09-01',
            'end_date'    => '2026-09-30',
            'status'      => 'closed',
            'closed_by'   => $this->adminUser->id,
            'closed_at'   => now(),
        ]);

        $supplier = Supplier::create([
            'code'         => 'SUPP-PERIOD-TEST',
            'name'         => 'Vendor Uji Periode',
            'payment_term' => 30,
            'is_active'    => true,
        ]);

        $warehouse = \App\Models\Warehouse::create([
            'code'      => 'WH-PERIOD-TEST',
            'name'      => 'Gudang Periode',
            'is_active' => true,
        ]);

        $product = \App\Models\Product::create([
            'sku'            => 'PROD-PERIOD-1',
            'name'           => 'Barang Uji',
            'unit'           => 'pcs',
            'purchase_price' => 100000,
            'sell_price'     => 150000,
            'is_active'      => true,
        ]);

        $po = PurchaseOrder::create([
            'po_number'    => 'PO-TEST-' . uniqid(),
            'supplier_id'  => $supplier->id,
            'user_id'      => $this->adminUser->id,
            'warehouse_id' => $warehouse->id,
            'order_date'   => '2026-09-02',
            'status'       => 'confirmed',
            'subtotal'     => 500000,
            'tax_rate'     => 0,
            'tax_amount'   => 0,
            'total_amount' => 500000,
        ]);

        \App\Models\PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id'        => $product->id,
            'qty_ordered'       => 5,
            'qty_received'      => 5,
            'unit_price'        => 100000,
            'subtotal'          => 500000,
        ]);

        $grn = \App\Models\GoodsReceipt::create([
            'receipt_number'    => 'LPB-TEST-' . uniqid(),
            'purchase_order_id' => $po->id,
            'warehouse_id'      => $warehouse->id,
            'user_id'           => $this->adminUser->id,
            'received_by'       => $this->adminUser->id,
            'received_date'     => '2026-09-05',
            'qc_status'         => 'passed',
            'is_invoiced'       => false,
        ]);

        \App\Models\GoodsReceiptItem::create([
            'goods_receipt_id'       => $grn->id,
            'purchase_order_item_id' => $po->items->first()->id,
            'warehouse_id'           => $warehouse->id,
            'qty_received'           => 5,
            'qty_accepted'           => 5,
            'qty_rejected'           => 0,
            'unit_cost'              => 100000,
        ]);

        // Coba terbitkan invoice pada tanggal yang berada di periode tertutup (07/09/2026)
        $response = $this->actingAs($this->adminUser)
            ->from(route('purchase.invoices.create', ['grn_id' => $grn->id]))
            ->post(route('purchase.invoices.store'), [
                'purchase_order_id' => $po->id,
                'goods_receipt_id'  => $grn->id,
                'invoice_date'      => '2026-09-07',
                'due_date'          => '2026-10-07',
                'tax_rate'          => 0,
            ]);

        // Harus redirect back dan memiliki session error bersahabat, BUKAN 500 error screen
        $response->assertRedirect(route('purchase.invoices.create', ['grn_id' => $grn->id]));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('periode yang sudah ditutup', session('error'));
        $this->assertEquals(0, PurchaseInvoice::count());
    }
}
