<?php

namespace App\Services;

use App\Exceptions\PeriodClosedException;
use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AccountingPeriodService
{
    /**
     * Cek apakah tanggal berada dalam periode akuntansi yang ditutup.
     * ATURAN #1 (Prinsip Non-Negosiabel):
     * Jika tidak ada record periode untuk tanggal ini, transaksi dianggap TERBUKA (return false).
     */
    public function isDateLocked(Carbon|string $date): bool
    {
        $carbonDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        $dateStr = $carbonDate->toDateString();

        $period = AccountingPeriod::where('start_date', '<=', $dateStr)
            ->where('end_date', '>=', $dateStr)
            ->first();

        // ATURAN #1: tidak ada record = TIDAK terkunci, transaksi boleh jalan
        if (!$period) {
            return false;
        }

        return $period->status === 'closed';
    }

    /**
     * Pastikan tanggal transaksi tidak berada di periode yang sudah ditutup.
     * Melempar PeriodClosedException jika terkunci.
     */
    public function assertDateNotLocked(Carbon|string $date): void
    {
        $carbonDate = $date instanceof Carbon ? $date : Carbon::parse($date);

        if ($this->isDateLocked($carbonDate)) {
            throw new PeriodClosedException(
                "Tanggal {$carbonDate->format('m/d/Y')} berada di periode yang sudah ditutup. Hubungi Admin untuk membuka kembali periode ini jika perlu koreksi."
            );
        }
    }

    /**
     * Evaluasi 2 syarat checklist sebelum penutupan periode:
     * 1. Tidak ada jurnal berstatus draft pada rentang tanggal periode
     * 2. Trial Balance periode ini seimbang (Total Debit == Total Kredit)
     */
    public function getClosingChecklist(AccountingPeriod $period): array
    {
        $startDate = $period->start_date->toDateString();
        $endDate   = $period->end_date->toDateString();

        // Syarat 1: Draft journals
        $draftCount = JournalEntry::where('status', 'draft')
            ->whereDate('entry_date', '>=', $startDate)
            ->whereDate('entry_date', '<=', $endDate)
            ->count();

        $draftsValid = ($draftCount === 0);

        // Syarat 2: Trial Balance balance check
        $totals = JournalLine::join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.status', 'posted')
            ->whereDate('journal_entries.entry_date', '>=', $startDate)
            ->whereDate('journal_entries.entry_date', '<=', $endDate)
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        $totalDebit  = (float) ($totals?->total_debit ?? 0);
        $totalCredit = (float) ($totals?->total_credit ?? 0);
        $isBalanced  = abs($totalDebit - $totalCredit) < 0.01;

        $canClose = $draftsValid && $isBalanced;

        return [
            'can_close'    => $canClose,
            'draft_count'  => $draftCount,
            'drafts_valid' => $draftsValid,
            'total_debit'  => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced'  => $isBalanced,
        ];
    }

    /**
     * Tutup periode akuntansi setelah lolos checklist 2-langkah.
     */
    public function close(AccountingPeriod $period, User $user): void
    {
        $checklist = $this->getClosingChecklist($period);

        if (!$checklist['drafts_valid']) {
            throw new \InvalidArgumentException(
                "Periode tidak dapat ditutup karena masih terdapat {$checklist['draft_count']} jurnal berstatus draft yang belum diposting."
            );
        }

        if (!$checklist['is_balanced']) {
            $diff = number_format(abs($checklist['total_debit'] - $checklist['total_credit']), 2, ',', '.');
            throw new \InvalidArgumentException(
                "Periode tidak dapat ditutup karena Trial Balance tidak seimbang (Selisih: Rp {$diff})."
            );
        }

        $period->update([
            'status'    => 'closed',
            'closed_by' => $user->id,
            'closed_at' => now(),
        ]);
    }

    /**
     * Buka kembali periode yang sudah ditutup disertai pencatatan alasan (audit trail).
     */
    public function reopen(AccountingPeriod $period, string $reason, User $user): void
    {
        $period->update([
            'status'        => 'open',
            'reopened_by'   => $user->id,
            'reopened_at'   => now(),
            'reopen_reason' => $reason,
        ]);
    }

    /**
     * Generate 12 bulan periode akuntansi untuk tahun fiskal tertentu.
     */
    public function generateYearPeriods(int $year): Collection
    {
        $periods = collect();

        for ($m = 1; $m <= 12; $m++) {
            $start = Carbon::createFromDate($year, $m, 1)->startOfMonth()->toDateString();
            $end   = Carbon::createFromDate($year, $m, 1)->endOfMonth()->toDateString();

            $period = AccountingPeriod::firstOrCreate(
                [
                    'fiscal_year' => $year,
                    'month'       => $m,
                ],
                [
                    'start_date' => $start,
                    'end_date'   => $end,
                    'status'     => 'open',
                ]
            );

            $periods->push($period);
        }

        return $periods;
    }
}
