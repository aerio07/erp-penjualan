<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Services\AccountingPeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PeriodController extends Controller
{
    public function __construct(
        protected AccountingPeriodService $periodService
    ) {}

    /**
     * Tampilkan daftar periode akuntansi per tahun fiskal
     */
    public function index(Request $request): View
    {
        $years = AccountingPeriod::distinct()->pluck('fiscal_year')->sortDesc()->values();

        if ($years->isEmpty()) {
            $years = collect([(int) date('Y')]);
        }

        $selectedYear = (int) $request->input('year', $years->first());
        $periods = AccountingPeriod::where('fiscal_year', $selectedYear)
            ->with(['closedBy', 'reopenedBy'])
            ->orderBy('month')
            ->get();

        return view('accounting.periods.index', compact('periods', 'years', 'selectedYear'));
    }

    /**
     * Generate 12 bulan periode untuk tahun yang dipilih
     */
    public function generate(Request $request): RedirectResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2000|max:2099',
        ]);

        $year = (int) $request->year;
        $this->periodService->generateYearPeriods($year);

        return redirect()->route('accounting.periods.index', ['year' => $year])
            ->with('success', "12 periode akuntansi untuk tahun fiskal {$year} berhasil dibuat / diperbarui.");
    }

    /**
     * Tampilkan detail periode akuntansi dan evaluasi checklist penutupan
     */
    public function show(AccountingPeriod $period): View
    {
        $period->load(['closedBy', 'reopenedBy']);
        $checklist = $this->periodService->getClosingChecklist($period);

        return view('accounting.periods.show', compact('period', 'checklist'));
    }

    /**
     * Tutup periode akuntansi
     */
    public function close(Request $request, AccountingPeriod $period): RedirectResponse
    {
        try {
            $this->periodService->close($period, auth()->user());

            return redirect()->route('accounting.periods.show', $period)
                ->with('success', "Periode akuntansi {$period->period_name} berhasil ditutup.");
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Buka kembali periode akuntansi yang ditutup
     */
    public function reopen(Request $request, AccountingPeriod $period): RedirectResponse
    {
        $request->validate([
            'reopen_reason' => 'required|string|min:5|max:500',
        ]);

        $this->periodService->reopen($period, $request->reopen_reason, auth()->user());

        return redirect()->route('accounting.periods.show', $period)
            ->with('success', "Periode akuntansi {$period->period_name} berhasil dibuka kembali.");
    }
}
