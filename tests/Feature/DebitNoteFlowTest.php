<?php

namespace Tests\Feature;

use App\Enums\PurchaseInvoiceStatus;
use App\Models\ChartOfAccount;
use App\Models\DebitNote;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebitNoteFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ChartOfAccountSeeder::class);
    }

    public function test_can_view_debit_note_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('accounting.debit-notes.index'));
        $response->assertStatus(200);
        $response->assertSee('Debet Note');
    }

    public function test_can_create_and_post_debit_note_for_purchase_invoice(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::create([
            'code'         => 'SUP-0001',
            'name'         => 'ALAM LESTARI UNGGUL',
            'phone'        => '08123456789',
            'payment_term' => 'net_30',
        ]);

        $po = PurchaseOrder::create([
            'po_number'    => 'PO-001',
            'supplier_id'  => $supplier->id,
            'user_id'      => $admin->id,
            'order_date'   => now()->toDateString(),
            'status'       => \App\Enums\PurchaseOrderStatus::Confirmed,
            'total_amount' => 10000000,
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number' => 'AP-B-SBY-2607-01072',
            'purchase_order_id' => $po->id,
            'amount' => 10000000,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => 10000000,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => PurchaseInvoiceStatus::Unpaid,
        ]);

        $this->assertEquals(10000000, $invoice->fresh()->outstanding_amount);

        $debitCoa = ChartOfAccount::first();
        $creditCoa = ChartOfAccount::skip(1)->first();

        // 1. Submit store as draft
        $payload = [
            'dn_type'       => 'AP',
            'dn_date'       => now()->toDateString(),
            'supplier_id'   => $supplier->id,
            'notes'         => 'POTONG SELISIH 2% BIAYA KIRIM',
            'tax_type'      => 'NON TAX',
            'tax_rate'      => 0,
            'tax_amount'    => 0,
            'invoices'      => [
                [
                    'invoice_id'       => $invoice->id,
                    'invoice_number'   => $invoice->invoice_number,
                    'remaining_amount' => 10000000,
                    'paid_amount'      => 0,
                    'dn_amount'        => 200000,
                ],
            ],
            'accounts'      => [
                [
                    'invoice_number'             => $invoice->invoice_number,
                    'debit_chart_of_account_id'  => $debitCoa->id,
                    'credit_chart_of_account_id' => $creditCoa->id,
                    'outstanding_amount'         => 10000000,
                    'dn_amount'                  => 200000,
                    'tax_amount'                 => 0,
                    'notes'                      => 'POTONG SELISIH 2% BIAYA KIRIM',
                ],
            ],
        ];

        $response = $this->actingAs($admin)->post(route('accounting.debit-notes.store'), $payload);
        $dn = DebitNote::first();
        $this->assertNotNull($dn);
        $response->assertRedirect(route('accounting.debit-notes.show', $dn));

        // Assert Debit Note created as DRAFT
        $this->assertEquals('draft', $dn->status);
        $this->assertEquals(200000, (float)$dn->total_amount);
        $this->assertNull($dn->journal_entry_id);

        // While draft, invoice outstanding is not yet deducted
        $this->assertEquals(10000000, $invoice->fresh()->outstanding_amount);

        // 2. Post from View (Show)
        $postRes = $this->actingAs($admin)->patch(route('accounting.debit-notes.post', $dn));
        $postRes->assertRedirect();

        $dn->refresh();
        $this->assertEquals('posted', $dn->status);
        $this->assertNotNull($dn->journal_entry_id);

        // After posting, invoice outstanding is reduced
        $this->assertEquals(9800000, $invoice->fresh()->outstanding_amount);

        // Test Print View
        $printRes = $this->actingAs($admin)->get(route('accounting.debit-notes.print', $dn));
        $printRes->assertStatus(200);
        $printRes->assertSee('BUKTI DEBET NOTE');

        // Test Cancel
        $cancelRes = $this->actingAs($admin)->patch(route('accounting.debit-notes.cancel', $dn));
        $cancelRes->assertRedirect();
        $this->assertEquals('cancelled', $dn->fresh()->status);
        $this->assertEquals(10000000, $invoice->fresh()->outstanding_amount);
    }
}
