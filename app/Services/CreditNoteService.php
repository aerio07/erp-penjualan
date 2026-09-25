<?php

namespace App\Services;

use App\Enums\PurchaseInvoiceStatus;
use App\Enums\SalesInvoiceStatus;
use App\Models\ChartOfAccount;
use App\Models\CreditNote;
use App\Models\CreditNoteAccount;
use App\Models\CreditNoteInvoice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreditNoteService
{
    public function generateCnNumber(): string
    {
        $prefix = 'CN-B-' . date('ym') . '-';

        $last = CreditNote::where('cn_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('cn_number');

        $seq = $last ? ((int) substr($last, strlen($prefix)) + 1) : 1;

        return $prefix . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }

    public function generateDraftNumber(): string
    {
        $max = CreditNote::max('draft_number');
        $next = $max ? ((int) $max + 1) : 1;
        return (string) $next;
    }

    /**
     * Post a Credit Note:
     * 1. Validations (must be draft, must have invoices & accounts)
     * 2. Generate Journal Entry (lines from accounts)
     * 3. Update Credit Note status to 'posted'
     * 4. Check and update affected Purchase / Sales Invoices status if paid off
     */
    public function post(CreditNote $cn, ?int $userId = null): CreditNote
    {
        if ($cn->isPosted()) {
            throw new RuntimeException("Credit Note #{$cn->cn_number} sudah diposting sebelumnya.");
        }

        $cn->loadMissing(['invoices', 'accounts.debitAccount', 'accounts.creditAccount']);

        if ($cn->invoices->isEmpty()) {
            throw new RuntimeException("Credit Note harus memiliki minimal 1 detail invoice yang dipotong.");
        }

        if ($cn->accounts->isEmpty()) {
            throw new RuntimeException("Credit Note harus memiliki minimal 1 alokasi akun COA debet/kredit.");
        }

        $userId = $userId ?? Auth::id() ?? User::first()?->id ?? 1;

        return DB::transaction(function () use ($cn, $userId) {
            // Check Accounting Period lock
            try {
                app(AccountingPeriodService::class)->assertDateNotLocked(Carbon::parse($cn->cn_date));
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), 'Terkunci')) {
                    throw $e;
                }
            }

            // Create Journal Entry
            $journalService = app(JournalService::class);
            $entryNumber = $journalService->generateEntryNumber();

            $journalEntry = JournalEntry::create([
                'entry_number'   => $entryNumber,
                'entry_date'     => $cn->cn_date,
                'reference_type' => CreditNote::class,
                'reference_id'   => $cn->id,
                'description'    => "Credit Note #{$cn->cn_number} - {$cn->partner_name}" . ($cn->notes ? " - {$cn->notes}" : ''),
                'status'         => 'posted',
                'created_by'     => $userId,
                'posted_by'      => $userId,
                'posted_at'      => now(),
            ]);

            // Add journal lines from CreditNote accounts
            foreach ($cn->accounts as $acc) {
                $lineAmt = (float) $acc->cn_amount + (float) $acc->tax_amount;
                if ($lineAmt <= 0) {
                    continue;
                }

                // Debit line
                JournalLine::create([
                    'journal_entry_id'    => $journalEntry->id,
                    'chart_of_account_id' => $acc->debit_chart_of_account_id,
                    'debit'               => $lineAmt,
                    'credit'              => 0,
                    'description'         => $acc->notes ?: "CN #{$cn->cn_number} - {$acc->invoice_number}",
                ]);

                // Credit line
                JournalLine::create([
                    'journal_entry_id'    => $journalEntry->id,
                    'chart_of_account_id' => $acc->credit_chart_of_account_id,
                    'debit'               => 0,
                    'credit'              => $lineAmt,
                    'description'         => $acc->notes ?: "CN #{$cn->cn_number} - {$acc->invoice_number}",
                ]);
            }

            // Update Credit Note
            $cn->update([
                'status'           => 'posted',
                'journal_entry_id' => $journalEntry->id,
                'posted_by'        => $userId,
                'posted_at'        => now(),
            ]);

            // Update related invoices status if outstanding reaches 0
            foreach ($cn->invoices as $item) {
                if ($cn->cn_type === 'AP' && $item->purchase_invoice_id) {
                    $inv = PurchaseInvoice::find($item->purchase_invoice_id);
                    if ($inv && $inv->outstanding_amount <= 0) {
                        $inv->update(['status' => PurchaseInvoiceStatus::Paid]);
                    }
                } elseif ($cn->cn_type === 'AR' && $item->sales_invoice_id) {
                    $inv = SalesInvoice::find($item->sales_invoice_id);
                    if ($inv && $inv->outstanding_amount <= 0) {
                        $inv->update(['status' => SalesInvoiceStatus::Paid]);
                    }
                }
            }

            return $cn;
        });
    }

    /**
     * Cancel a Credit Note
     */
    public function cancel(CreditNote $cn): CreditNote
    {
        if ($cn->isCancelled()) {
            return $cn;
        }

        return DB::transaction(function () use ($cn) {
            if ($cn->journal_entry_id) {
                $je = JournalEntry::find($cn->journal_entry_id);
                if ($je) {
                    $je->lines()->delete();
                    $je->delete();
                }
            }

            $cn->update([
                'status'           => 'cancelled',
                'journal_entry_id' => null,
            ]);

            // Recheck invoices
            foreach ($cn->invoices as $item) {
                if ($cn->cn_type === 'AP' && $item->purchase_invoice_id) {
                    $inv = PurchaseInvoice::find($item->purchase_invoice_id);
                    if ($inv && $inv->outstanding_amount > 0 && $inv->status === PurchaseInvoiceStatus::Paid) {
                        $inv->update(['status' => $inv->total_paid > 0 ? PurchaseInvoiceStatus::Partial : PurchaseInvoiceStatus::Unpaid]);
                    }
                } elseif ($cn->cn_type === 'AR' && $item->sales_invoice_id) {
                    $inv = SalesInvoice::find($item->sales_invoice_id);
                    if ($inv && $inv->outstanding_amount > 0 && $inv->status === SalesInvoiceStatus::Paid) {
                        $inv->update(['status' => $inv->total_paid > 0 ? SalesInvoiceStatus::Partial : SalesInvoiceStatus::Unpaid]);
                    }
                }
            }

            return $cn;
        });
    }
}
