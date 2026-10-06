<?php

namespace Tests\Feature;

use App\Models\CashBankReceipt;
use App\Models\ChartOfAccount;
use App\Models\User;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashBankReceiptFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ChartOfAccountSeeder::class);
    }

    public function test_can_view_cash_bank_receipt_index_with_filter_bar(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('accounting.cash-bank-receipts.index'));
        $response->assertStatus(200);
        $response->assertSee('Cash Bank Receipt');
        $response->assertSee('Cari No. Receipt, Voucher, Company, Catatan...');
        $response->assertSee('Semua Status');
        $response->assertSee('Semua Tipe');
    }

    public function test_can_create_receipt_as_draft_and_redirects_to_show(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $bankAccount = ChartOfAccount::where('type', 'Asset')->first() ?? ChartOfAccount::first();
        $revenueAccount = ChartOfAccount::where('type', 'Revenue')->first() ?? ChartOfAccount::latest()->first();

        $payload = [
            'company_name'        => 'PT Maju Bersama',
            'type'                => 'BANK',
            'transaction_date'    => now()->toDateString(),
            'chart_of_account_id' => $bankAccount->id,
            'voucher_number'      => 'VCH-001',
            'group'               => 'PENJUALAN',
            'notes'               => 'Penerimaan pembayaran piutang klien',
            'items'               => [
                [
                    'chart_of_account_id' => $revenueAccount->id,
                    'description'         => 'Item penerimaan transfer',
                    'amount'              => 5000000,
                    'notes'               => 'Lunas',
                ],
            ],
        ];

        $response = $this->actingAs($user)->post(route('accounting.cash-bank-receipts.store'), $payload);

        $receipt = CashBankReceipt::first();
        $this->assertNotNull($receipt);

        // Harus disimpan sebagai DRAFT dan redirect ke show
        $this->assertEquals('draft', $receipt->status);
        $this->assertEquals(5000000, (float) $receipt->amount);
        $response->assertRedirect(route('accounting.cash-bank-receipts.show', $receipt));
    }

    public function test_can_post_receipt_from_show_view(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $account = ChartOfAccount::first();

        $receipt = CashBankReceipt::create([
            'company_name'        => 'PT Maju Bersama',
            'receipt_number'      => 'CBR-2026-0001',
            'type'                => 'BANK',
            'transaction_date'    => now()->toDateString(),
            'chart_of_account_id' => $account->id,
            'notes'               => 'Test draft receipt',
            'amount'              => 1000000,
            'status'              => 'draft',
            'created_by'          => $user->id,
        ]);

        // Detail view menampilkan tombol posting dan banner draft
        $response = $this->actingAs($user)->get(route('accounting.cash-bank-receipts.show', $receipt));
        $response->assertStatus(200);
        $response->assertSee('Posting Sekarang');
        $response->assertSee('Penerimaan Masih Berstatus DRAFT');

        // Post transaksi
        $postResponse = $this->actingAs($user)->patch(route('accounting.cash-bank-receipts.post', $receipt));
        $postResponse->assertSessionHas('success');

        $receipt->refresh();
        $this->assertEquals('posted', $receipt->status);
        $this->assertNotNull($receipt->posted_at);
        $this->assertEquals($user->id, $receipt->posted_by);

        // Setelah diposting, tidak dapat diedit
        $editResponse = $this->actingAs($user)->get(route('accounting.cash-bank-receipts.edit', $receipt));
        $editResponse->assertStatus(403);
    }
}
