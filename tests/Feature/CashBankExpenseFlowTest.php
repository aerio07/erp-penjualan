<?php

namespace Tests\Feature;

use App\Models\CashBankExpense;
use App\Models\ChartOfAccount;
use App\Models\User;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashBankExpenseFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ChartOfAccountSeeder::class);
    }

    public function test_can_view_cash_bank_expense_index_with_filter_bar(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('accounting.cash-bank-expenses.index'));
        $response->assertStatus(200);
        $response->assertSee('Cash Bank Expense');
        $response->assertSee('Cari No. Pengeluaran, No. Bukti, Company, Catatan...');
        $response->assertSee('Semua Status');
        $response->assertSee('Semua Tipe');
    }

    public function test_can_create_expense_as_draft_and_redirects_to_show(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $bankAccount = ChartOfAccount::where('type', 'Asset')->first() ?? ChartOfAccount::first();
        $expenseAccount = ChartOfAccount::where('type', 'Expense')->first() ?? ChartOfAccount::latest()->first();

        $payload = [
            'company_name'        => 'PT Maju Bersama',
            'type'                => 'KAS',
            'transaction_date'    => now()->toDateString(),
            'chart_of_account_id' => $bankAccount->id,
            'voucher_number'      => 'EXP-001',
            'group'               => 'OPERASIONAL',
            'notes'               => 'Pengeluaran operasional kantor',
            'items'               => [
                [
                    'chart_of_account_id' => $expenseAccount->id,
                    'description'         => 'Biaya perlengkapan kantor',
                    'amount'              => 2500000,
                    'notes'               => 'Nota terlampir',
                ],
            ],
        ];

        $response = $this->actingAs($user)->post(route('accounting.cash-bank-expenses.store'), $payload);

        $expense = CashBankExpense::first();
        $this->assertNotNull($expense);

        // Harus disimpan sebagai DRAFT dan redirect ke show
        $this->assertEquals('draft', $expense->status);
        $this->assertEquals(2500000, (float) $expense->amount);
        $response->assertRedirect(route('accounting.cash-bank-expenses.show', $expense));
    }

    public function test_can_post_expense_from_show_view(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $account = ChartOfAccount::first();

        $expense = CashBankExpense::create([
            'company_name'        => 'PT Maju Bersama',
            'expense_number'      => 'CBE-2026-0001',
            'type'                => 'KAS',
            'transaction_date'    => now()->toDateString(),
            'chart_of_account_id' => $account->id,
            'notes'               => 'Test draft expense',
            'amount'              => 1500000,
            'status'              => 'draft',
            'created_by'          => $user->id,
        ]);

        // Detail view menampilkan tombol posting dan banner draft
        $response = $this->actingAs($user)->get(route('accounting.cash-bank-expenses.show', $expense));
        $response->assertStatus(200);
        $response->assertSee('Posting Sekarang');
        $response->assertSee('Pengeluaran Masih Berstatus DRAFT');

        // Post transaksi
        $postResponse = $this->actingAs($user)->patch(route('accounting.cash-bank-expenses.post', $expense));
        $postResponse->assertSessionHas('success');

        $expense->refresh();
        $this->assertEquals('posted', $expense->status);
        $this->assertNotNull($expense->posted_at);
        $this->assertEquals($user->id, $expense->posted_by);

        // Setelah diposting, tidak dapat diedit
        $editResponse = $this->actingAs($user)->get(route('accounting.cash-bank-expenses.edit', $expense));
        $editResponse->assertStatus(403);
    }
}
