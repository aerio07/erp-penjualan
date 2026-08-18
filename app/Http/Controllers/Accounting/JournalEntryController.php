<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use App\Services\JournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JournalEntryController extends Controller
{
    public function __construct(private JournalService $journalService) {}

    public function index(): View
    {
        $entries = JournalEntry::with(['creator', 'poster'])
            ->latest('entry_date')
            ->paginate(20);

        return view('accounting.journals.index', compact('entries'));
    }

    public function create(): View
    {
        $accounts = ChartOfAccount::where('is_active', true)->orderBy('code')->get();
        return view('accounting.journals.create', compact('accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'entry_date'            => 'required|date',
            'description'           => 'nullable|string',
            'lines'                 => 'required|array|min:2',
            'lines.*.chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.debit'         => 'required|numeric|min:0',
            'lines.*.credit'        => 'required|numeric|min:0',
        ]);

        $entry = JournalEntry::create([
            'entry_number' => $this->generateNumber(),
            'entry_date'   => $request->entry_date,
            'description'  => $request->description,
            'status'       => 'draft',
            'created_by'   => auth()->id(),
        ]);

        foreach ($request->lines as $line) {
            $entry->lines()->create([
                'chart_of_account_id' => $line['chart_of_account_id'],
                'debit'               => $line['debit'] ?? 0,
                'credit'              => $line['credit'] ?? 0,
                'description'         => $line['description'] ?? null,
            ]);
        }

        return redirect()->route('accounting.journals.show', $entry)
            ->with('success', 'Journal Entry berhasil dibuat.');
    }

    public function show(JournalEntry $journal): View
    {
        $journal->load(['lines.chartOfAccount', 'creator', 'poster']);
        return view('accounting.journals.show', compact('journal'));
    }

    public function edit(JournalEntry $journal): View
    {
        abort_if($journal->status === 'posted', 403, 'Journal yang sudah diposting tidak dapat diedit.');
        $accounts = ChartOfAccount::where('is_active', true)->orderBy('code')->get();
        return view('accounting.journals.edit', compact('journal', 'accounts'));
    }

    public function update(Request $request, JournalEntry $journal): RedirectResponse
    {
        abort_if($journal->status === 'posted', 403);

        $request->validate([
            'entry_date'  => 'required|date',
            'description' => 'nullable|string',
            'lines'       => 'required|array|min:2',
        ]);

        $journal->update([
            'entry_date'  => $request->entry_date,
            'description' => $request->description,
        ]);

        $journal->lines()->delete();
        foreach ($request->lines as $line) {
            $journal->lines()->create([
                'chart_of_account_id' => $line['chart_of_account_id'],
                'debit'               => $line['debit'] ?? 0,
                'credit'              => $line['credit'] ?? 0,
                'description'         => $line['description'] ?? null,
            ]);
        }

        return redirect()->route('accounting.journals.show', $journal)
            ->with('success', 'Journal Entry berhasil diperbarui.');
    }

    public function destroy(JournalEntry $journal): RedirectResponse
    {
        abort_if($journal->status === 'posted', 403);
        $journal->delete();
        return redirect()->route('accounting.journals.index')
            ->with('success', 'Journal Entry berhasil dihapus.');
    }

    public function post(JournalEntry $journal): RedirectResponse
    {
        try {
            $this->journalService->postEntry($journal);
            return back()->with('success', "Journal #{$journal->entry_number} berhasil diposting.");
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function generateNumber(): string
    {
        $prefix = 'JE-' . date('Ym') . '-';
        $last   = \App\Models\JournalEntry::where('entry_number', 'like', $prefix . '%')
            ->orderByDesc('id')->value('entry_number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
