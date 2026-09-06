<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Services\JournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpeningBalanceController extends Controller
{
    public function __construct(
        protected JournalService $journalService
    ) {}

    /**
     * Tampilkan form pengisian saldo awal neraca
     */
    public function index(): View
    {
        // Ambil seluruh akun neraca (Aset, Kewajiban, Ekuitas)
        $accounts = ChartOfAccount::whereIn('type', ['asset', 'liability', 'equity'])
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        // Cek apakah sudah pernah ada jurnal saldo awal
        $existingEntry = JournalEntry::with('lines')
            ->where('reference_type', 'OpeningBalance')
            ->first();

        $existingLines = [];
        if ($existingEntry) {
            foreach ($existingEntry->lines as $line) {
                $existingLines[$line->chart_of_account_id] = [
                    'debit'  => (float) $line->debit,
                    'credit' => (float) $line->credit,
                ];
            }
        }

        $cutOffDate = $existingEntry?->entry_date?->toDateString() ?? date('Y-01-01');
        $notes = $existingEntry?->description ?? 'Saldo Awal Neraca (Opening Balance)';

        return view('accounting.opening-balance.index', compact(
            'accounts',
            'existingEntry',
            'existingLines',
            'cutOffDate',
            'notes'
        ));
    }

    /**
     * Simpan / Perbarui saldo awal neraca
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'cut_off_date' => 'required|date',
            'lines'        => 'required|array',
            'lines.*.account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.debit'      => 'nullable|numeric|min:0',
            'lines.*.credit'     => 'nullable|numeric|min:0',
            'notes'              => 'nullable|string|max:255',
        ]);

        try {
            $this->journalService->postOpeningBalance(
                lines: $request->input('lines', []),
                date: $request->input('cut_off_date'),
                notes: $request->input('notes')
            );

            return redirect()->route('accounting.opening-balance.index')
                ->with('success', 'Saldo awal neraca berhasil disimpan dan jurnal pembuka telah terposting!');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
