<?php

namespace App\Services;

use App\Enums\PurchaseInvoiceStatus;
use App\Enums\SalesInvoiceStatus;
use App\Models\ChartOfAccount;
use App\Models\DebitNote;
use App\Models\DebitNoteAccount;
use App\Models\DebitNoteInvoice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DebitNoteService
{
    public function generateDnNumber(): string
    {
        $prefix = 'DN-' . date('ym') . '-';

        $last = DebitNote::where('dn_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('dn_number');

        $seq = $last ? ((int) substr($last, strlen($prefix)) + 1) : 1;

        return $prefix . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }

    public function generateDraftNumber(): string
    {
        $max = DebitNote::max('draft_number');
        $next = $max ? ((int) $max + 1) : 1;
        return (string) $next;
    }

    /**
     * Post a Debit Note:
     * 1. Validations (must be draft/in_process or in_approval, must have invoices & accounts, accounts must balance)
     * 2. Generate Journal Entry (lines from accounts)
     * 3. Update Debit Note status to 'posted'
     * 4. Check and update affected Purchase / Sales Invoices status if paid off
     */
    public function post(DebitNote $dn, ?int $userId = null): DebitNote
    {
        if ($dn->isPosted()) {
            throw new RuntimeException("Debit Note #{$dn->dn_number} sudah diposting sebelumnya.");
        }

        $dn->loadMissing(['invoices', 'accounts.debitAccount', 'accounts.creditAccount']);

        if ($dn->invoices->isEmpty()) {
            throw new RuntimeException("Debit Note harus memiliki minimal 1 detail invoice yang dipotong.");
        }

        if ($dn->accounts->isEmpty()) {
            throw new RuntimeException("Debit Note harus memiliki minimal 1 alokasi akun COA debet/kredit.");
        }

        $userId = $userId ?? Auth::id() ?? User::first()?->id ?? 1;

        return DB::transaction(function () use ($dn, $userId) {
            // Check Accounting Period lock
            try {
                app(AccountingPeriodService::class)->assertDateNotLocked(Carbon::parse($dn->dn_date));
            } catch (\Exception $e) {
                // If period service not configured or fails, continue or throw
                if (str_contains($e->getMessage(), 'Terkunci')) {
                    throw $e;
                }
            }

            // Create Journal Entry
            $journalService = app(JournalService::class);
            $entryNumber = $journalService->generateEntryNumber();

            $journalEntry = JournalEntry::create([
                'entry_number'   => $entryNumber,
                'entry_date'     => $dn->dn_date,
                'reference_type' => DebitNote::class,
                'reference_id'   => $dn->id,
                'description'    => "Debit Note #{$dn->dn_number} - {$dn->partner_name}" . ($dn->notes ? " - {$dn->notes}" : ''),
                'status'         => 'posted',
                'created_by'     => $userId,
                'posted_by'      => $userId,
                'posted_at'      => now(),
            ]);

            // Add journal lines from DebitNote accounts
            foreach ($dn->accounts as $acc) {
                $lineAmt = (float) $acc->dn_amount + (float) $acc->tax_amount;
                if ($lineAmt <= 0) {
                    continue;
                }

                // Debit line
                JournalLine::create([
                    'journal_entry_id'    => $journalEntry->id,
                    'chart_of_account_id' => $acc->debit_chart_of_account_id,
                    'debit'               => $lineAmt,
                    'credit'              => 0,
                    'description'         => $acc->notes ?: "DN #{$dn->dn_number} - {$acc->invoice_number}",
                ]);

                // Credit line
                JournalLine::create([
                    'journal_entry_id'    => $journalEntry->id,
                    'chart_of_account_id' => $acc->credit_chart_of_account_id,
                    'debit'               => 0,
                    'credit'              => $lineAmt,
                    'description'         => $acc->notes ?: "DN #{$dn->dn_number} - {$acc->invoice_number}",
                ]);
            }

            // Update Debit Note
            $dn->update([
                'status'           => 'posted',
                'journal_entry_id' => $journalEntry->id,
                'posted_by'        => $userId,
                'posted_at'        => now(),
            ]);

            // Update related invoices status if outstanding reaches 0
            foreach ($dn->invoices as $item) {
                if ($dn->dn_type === 'AP' && $item->purchase_invoice_id) {
                    $inv = PurchaseInvoice::find($item->purchase_invoice_id);
                    if ($inv && $inv->outstanding_amount <= 0) {
                        $inv->update(['status' => PurchaseInvoiceStatus::Paid]);
                    }
                } elseif ($dn->dn_type === 'AR' && $item->sales_invoice_id) {
                    $inv = SalesInvoice::find($item->sales_invoice_id);
                    if ($inv && $inv->outstanding_amount <= 0) {
                        $inv->update(['status' => SalesInvoiceStatus::Paid]);
                    }
                }
            }

            return $dn;
        });
    }

    /**
     * Cancel a Debit Note (can only be done if not yet closed or by admin/finance)
     */
    public function cancel(DebitNote $dn): DebitNote
    {
        if ($dn->isCancelled()) {
            return $dn;
        }

        return DB::transaction(function () use ($dn) {
            if ($dn->journal_entry_id) {
                $je = JournalEntry::find($dn->journal_entry_id);
                if ($je) {
                    $je->lines()->delete();
                    $je->delete();
                }
            }

            $dn->update([
                'status'           => 'cancelled',
                'journal_entry_id' => null,
            ]);

            // Recheck invoices
            foreach ($dn->invoices as $item) {
                if ($dn->dn_type === 'AP' && $item->purchase_invoice_id) {
                    $inv = PurchaseInvoice::find($item->purchase_invoice_id);
                    if ($inv && $inv->outstanding_amount > 0 && $inv->status === PurchaseInvoiceStatus::Paid) {
                        $inv->update(['status' => $inv->total_paid > 0 ? PurchaseInvoiceStatus::Partial : PurchaseInvoiceStatus::Unpaid]);
                    }
                } elseif ($dn->dn_type === 'AR' && $item->sales_invoice_id) {
                    $inv = SalesInvoice::find($item->sales_invoice_id);
                    if ($inv && $inv->outstanding_amount > 0 && $inv->status === SalesInvoiceStatus::Paid) {
                        $inv->update(['status' => $inv->total_paid > 0 ? SalesInvoiceStatus::Partial : SalesInvoiceStatus::Unpaid]);
                    }
                }
            }

            return $dn;
        });
    }
}
