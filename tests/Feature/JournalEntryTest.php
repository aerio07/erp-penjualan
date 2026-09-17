<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalEntryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ChartOfAccount $cashAcc;
    private ChartOfAccount $expenseAcc;
    private ChartOfAccount $bankAcc;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->cashAcc = ChartOfAccount::create([
            'code' => '1-1001',
            'name' => 'Kas Kecil',
            'type' => 'asset',
            'is_active' => true,
        ]);

        $this->expenseAcc = ChartOfAccount::create([
            'code' => '6-1001',
            'name' => 'Beban Operasional',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $this->bankAcc = ChartOfAccount::create([
            'code' => '1-1002',
            'name' => 'Bank BCA',
            'type' => 'asset',
            'is_active' => true,
        ]);
    }

    public function test_can_view_journal_entries_index(): void
    {
        JournalEntry::create([
            'entry_number' => 'JE-2026-0001',
            'entry_date' => '2026-09-17',
            'description' => 'Test Jurnal',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('accounting.journals.index'));

        $response->assertStatus(200);
        $response->assertSee('JE-2026-0001');
        $response->assertSee('Test Jurnal');
    }

    public function test_can_create_balanced_journal_entry(): void
    {
        $response = $this->actingAs($this->user)->post(route('accounting.journals.store'), [
            'entry_date' => '2026-09-17',
            'description' => 'Pembayaran Beban Kantor',
            'lines' => [
                [
                    'chart_of_account_id' => $this->expenseAcc->id,
                    'debit' => 500000,
                    'credit' => 0,
                    'description' => 'Beban Kantor',
                ],
                [
                    'chart_of_account_id' => $this->cashAcc->id,
                    'debit' => 0,
                    'credit' => 500000,
                    'description' => 'Kas keluar',
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('journal_entries', [
            'description' => 'Pembayaran Beban Kantor',
            'status' => 'draft',
        ]);

        $entry = JournalEntry::where('description', 'Pembayaran Beban Kantor')->first();
        $this->assertNotNull($entry);
        $this->assertEquals(500000, $entry->total_debit);
        $this->assertEquals(500000, $entry->total_credit);
        $this->assertTrue($entry->isBalanced());
    }

    public function test_cannot_create_unbalanced_journal_entry(): void
    {
        $response = $this->actingAs($this->user)->from(route('accounting.journals.create'))->post(route('accounting.journals.store'), [
            'entry_date' => '2026-09-17',
            'description' => 'Jurnal Tidak Balance',
            'lines' => [
                [
                    'chart_of_account_id' => $this->expenseAcc->id,
                    'debit' => 500000,
                    'credit' => 0,
                ],
                [
                    'chart_of_account_id' => $this->cashAcc->id,
                    'debit' => 0,
                    'credit' => 300000,
                ],
            ],
        ]);

        $response->assertRedirect(route('accounting.journals.create'));
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('journal_entries', [
            'description' => 'Jurnal Tidak Balance',
        ]);
    }

    public function test_can_update_draft_journal_entry(): void
    {
        $entry = JournalEntry::create([
            'entry_number' => 'JE-2026-0002',
            'entry_date' => '2026-09-17',
            'description' => 'Original Description',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $entry->lines()->create([
            'chart_of_account_id' => $this->expenseAcc->id,
            'debit' => 200000,
            'credit' => 0,
        ]);
        $entry->lines()->create([
            'chart_of_account_id' => $this->cashAcc->id,
            'debit' => 0,
            'credit' => 200000,
        ]);

        $response = $this->actingAs($this->user)->put(route('accounting.journals.update', $entry), [
            'entry_date' => '2026-09-17',
            'description' => 'Updated Description',
            'lines' => [
                [
                    'chart_of_account_id' => $this->expenseAcc->id,
                    'debit' => 350000,
                    'credit' => 0,
                ],
                [
                    'chart_of_account_id' => $this->bankAcc->id,
                    'debit' => 0,
                    'credit' => 350000,
                ],
            ],
        ]);

        $response->assertRedirect(route('accounting.journals.show', $entry));
        $entry->refresh();
        $this->assertEquals('Updated Description', $entry->description);
        $this->assertEquals(350000, $entry->total_debit);
        $this->assertEquals(350000, $entry->total_credit);
    }

    public function test_can_post_draft_journal_entry(): void
    {
        $entry = JournalEntry::create([
            'entry_number' => 'JE-2026-0003',
            'entry_date' => '2026-09-17',
            'description' => 'Siap Posting',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $entry->lines()->create([
            'chart_of_account_id' => $this->expenseAcc->id,
            'debit' => 100000,
            'credit' => 0,
        ]);
        $entry->lines()->create([
            'chart_of_account_id' => $this->cashAcc->id,
            'debit' => 0,
            'credit' => 100000,
        ]);

        $response = $this->actingAs($this->user)->patch(route('accounting.journals.post', $entry));

        $response->assertRedirect();
        $entry->refresh();
        $this->assertEquals('posted', $entry->status);
        $this->assertNotNull($entry->posted_at);
    }

    public function test_can_delete_draft_journal_entry(): void
    {
        $entry = JournalEntry::create([
            'entry_number' => 'JE-2026-0004',
            'entry_date' => '2026-09-17',
            'description' => 'Hapus Saya',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $entry->lines()->create([
            'chart_of_account_id' => $this->expenseAcc->id,
            'debit' => 50000,
            'credit' => 0,
        ]);
        $entry->lines()->create([
            'chart_of_account_id' => $this->cashAcc->id,
            'debit' => 0,
            'credit' => 50000,
        ]);

        $response = $this->actingAs($this->user)->delete(route('accounting.journals.destroy', $entry));

        $response->assertRedirect(route('accounting.journals.index'));
        $this->assertDatabaseMissing('journal_entries', ['id' => $entry->id]);
    }
}
