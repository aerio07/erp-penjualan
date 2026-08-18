<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ChartOfAccountSeeder::class);
    }

    /**
     * Verifikasi Jurnal Penyesuaian Modal Pemilik (Opening Balance)
     * Debit Kas (1-1100) Rp 100.000.000
     * Kredit Modal Pemilik (3-1100) Rp 100.000.000
     */
    public function test_opening_balance_journal_entry_creates_balance_sheet_equity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $kasAcc   = ChartOfAccount::where('code', '1-1100')->firstOrFail();
        $modalAcc = ChartOfAccount::where('code', '3-1100')->firstOrFail();

        // 1. Buat & Post Jurnal Pembuka Modal
        $entry = JournalEntry::create([
            'entry_number' => 'JE-INIT-001',
            'entry_date'   => '2026-01-01',
            'description'  => 'Setor Modal Awal Pemilik',
            'status'       => 'posted',
            'created_by'   => $admin->id,
            'posted_by'    => $admin->id,
            'posted_at'    => now(),
        ]);

        $entry->lines()->createMany([
            ['chart_of_account_id' => $kasAcc->id, 'debit' => 100000000, 'credit' => 0, 'description' => 'Kas Modal Awal'],
            ['chart_of_account_id' => $modalAcc->id, 'debit' => 0, 'credit' => 100000000, 'description' => 'Modal Pemilik'],
        ]);

        // 2. Akses Laporan Neraca (Balance Sheet)
        $response = $this->actingAs($admin)->get(route('accounting.reports.balance-sheet'));
        $response->assertOk();

        $response->assertViewHas('totalAssets', 100000000);
        $response->assertViewHas('ownerCapital', 100000000);
        $response->assertViewHas('totalLiabilitiesAndEquity', 100000000);
        $response->assertViewHas('isBalanced', true);
    }

    public function test_trial_balance_debit_equals_credit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $kasAcc   = ChartOfAccount::where('code', '1-1100')->firstOrFail();
        $modalAcc = ChartOfAccount::where('code', '3-1100')->firstOrFail();
        $sewaAcc  = ChartOfAccount::where('code', '5-2200')->firstOrFail();

        // Entry 1: Modal Awal Rp 50.000.000
        $entry1 = JournalEntry::create([
            'entry_number' => 'JE-001',
            'entry_date'   => now()->toDateString(),
            'status'       => 'posted',
            'created_by'   => $admin->id,
        ]);
        $entry1->lines()->createMany([
            ['chart_of_account_id' => $kasAcc->id, 'debit' => 50000000, 'credit' => 0],
            ['chart_of_account_id' => $modalAcc->id, 'debit' => 0, 'credit' => 50000000],
        ]);

        // Entry 2: Bayar Sewa Gedung Rp 5.000.000
        $entry2 = JournalEntry::create([
            'entry_number' => 'JE-002',
            'entry_date'   => now()->toDateString(),
            'status'       => 'posted',
            'created_by'   => $admin->id,
        ]);
        $entry2->lines()->createMany([
            ['chart_of_account_id' => $sewaAcc->id, 'debit' => 5000000, 'credit' => 0],
            ['chart_of_account_id' => $kasAcc->id, 'debit' => 0, 'credit' => 5000000],
        ]);

        $response = $this->actingAs($admin)->get(route('accounting.reports.trial-balance'));
        $response->assertOk();

        $response->assertViewHas('grandTotalDebit', 55000000);
        $response->assertViewHas('grandTotalCredit', 55000000);
        $response->assertViewHas('isBalanced', true);
    }

    public function test_ledger_running_balance_calculation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kasAcc = ChartOfAccount::where('code', '1-1100')->firstOrFail();

        $entry1 = JournalEntry::create([
            'entry_number' => 'JE-101',
            'entry_date'   => '2026-02-01',
            'status'       => 'posted',
            'created_by'   => $admin->id,
        ]);
        $entry1->lines()->create(['chart_of_account_id' => $kasAcc->id, 'debit' => 10000000, 'credit' => 0]);

        $entry2 = JournalEntry::create([
            'entry_number' => 'JE-102',
            'entry_date'   => '2026-02-05',
            'status'       => 'posted',
            'created_by'   => $admin->id,
        ]);
        $entry2->lines()->create(['chart_of_account_id' => $kasAcc->id, 'debit' => 0, 'credit' => 2000000]);

        $response = $this->actingAs($admin)->get(route('accounting.reports.ledger', [
            'chart_of_account_id' => $kasAcc->id,
            'date_from'           => '2026-02-01',
            'date_to'             => '2026-02-28',
        ]));

        $response->assertOk();
        $lines = $response->viewData('lines');

        $this->assertCount(2, $lines);
        $this->assertEquals(10000000, $lines[0]->running_balance);
        $this->assertEquals(8000000, $lines[1]->running_balance);
    }

    public function test_receivables_aging_buckets(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['code' => 'CUST-01', 'name' => 'PT Customer Test', 'is_active' => true]);
        $so       = SalesOrder::create([
            'so_number'    => 'SO-001',
            'customer_id'  => $customer->id,
            'order_date'   => now()->toDateString(),
            'total_amount' => 1000000,
            'status'       => 'confirmed',
            'user_id'      => $admin->id,
        ]);

        // Invoice 1: Current (due in 5 days)
        SalesInvoice::create([
            'invoice_number' => 'INV-CURR',
            'sales_order_id' => $so->id,
            'invoice_date'   => now()->toDateString(),
            'due_date'       => Carbon::today()->addDays(5)->toDateString(),
            'amount'         => 1000000,
            'tax_amount'     => 0,
            'total_amount'   => 1000000,
            'status'         => 'unpaid',
        ]);

        // Invoice 2: 1-30 days overdue (due 15 days ago)
        SalesInvoice::create([
            'invoice_number' => 'INV-15D',
            'sales_order_id' => $so->id,
            'invoice_date'   => Carbon::today()->subDays(20)->toDateString(),
            'due_date'       => Carbon::today()->subDays(15)->toDateString(),
            'amount'         => 2000000,
            'tax_amount'     => 0,
            'total_amount'   => 2000000,
            'status'         => 'unpaid',
        ]);

        $response = $this->actingAs($admin)->get(route('accounting.reports.receivables'));
        $response->assertOk();

        $response->assertViewHas('bucketCurrent', 1000000);
        $response->assertViewHas('bucket1to30', 2000000);
        $response->assertViewHas('totalOutstanding', 3000000);
    }
}
