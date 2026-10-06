<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\CashBankExpense;
use App\Models\CashBankExpenseItem;
use App\Models\ChartOfAccount;
use App\Traits\HasListFilters;
use App\Traits\GeneratesDocumentNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CashBankExpenseController extends Controller
{
    use HasListFilters, GeneratesDocumentNumber;

    public function index(Request $request): View
    {
        $query = CashBankExpense::with(['account', 'creator']);

        $query = $this->applySearch($query, $request, ['expense_number', 'voucher_number', 'company_name', 'notes']);
        $query = $this->applyFilter($query, $request, 'status');
        $query = $this->applyFilter($query, $request, 'type');
        $query = $this->applyDateRange($query, $request, 'transaction_date');
        $query = $this->applySort($query, $request, ['expense_number', 'transaction_date', 'amount', 'status', 'created_at'], 'transaction_date', 'desc');

        $perPage = (int) $request->get('per_page', 10);
        $expenses = $query->paginate($perPage)->withQueryString();

        return view('accounting.cash-bank-expenses.index', compact('expenses'));
    }

    public function create(): View
    {
        $accounts = ChartOfAccount::where('is_active', true)->orderBy('code')->get();
        return view('accounting.cash-bank-expenses.create', compact('accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'company_name'        => 'required|string|max:255',
            'type'                => 'required|in:KAS,BANK,DP',
            'transaction_date'    => 'required|date',
            'chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'notes'               => 'required|string',
            'document_file'       => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'items'               => 'nullable|array',
            'items.*.chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'items.*.amount'      => 'required|numeric|min:0',
            'items.*.notes'       => 'required|string',
        ]);

        return DB::transaction(function () use ($request) {
            $docFile = null;
            if ($request->hasFile('document_file')) {
                $docFile = $request->file('document_file')->store('cash-bank-expenses', 'public');
            }

            $totalAmount = collect($request->items ?? [])->sum(fn($i) => (float)($i['amount'] ?? 0));

            $expense = CashBankExpense::create([
                'company_name'        => $request->company_name,
                'expense_number'      => $this->generateDocumentNumber(CashBankExpense::class, 'expense_number', 'CBK'),
                'voucher_number'      => $request->voucher_number,
                'type'                => $request->type,
                'transaction_date'    => $request->transaction_date,
                'group'               => $request->group,
                'chart_of_account_id' => $request->chart_of_account_id,
                'reference_number'    => $request->reference_number,
                'notes'               => $request->notes,
                'document_file'       => $docFile,
                'amount'              => $totalAmount,
                'status'              => 'draft',
                'created_by'          => Auth::id(),
            ]);

            foreach ($request->items ?? [] as $item) {
                if (!empty($item['chart_of_account_id']) && (float)($item['amount'] ?? 0) > 0) {
                    $expense->items()->create([
                        'chart_of_account_id' => $item['chart_of_account_id'],
                        'description'         => $item['description'] ?? null,
                        'amount'              => (float) $item['amount'],
                        'notes'               => $item['notes'] ?? null,
                    ]);
                }
            }

            return redirect()->route('accounting.cash-bank-expenses.show', $expense)
                ->with('success', "Cash Bank Expense {$expense->expense_number} berhasil disimpan sebagai Draft.");
        });
    }

    public function show(CashBankExpense $cashBankExpense): View
    {
        $cashBankExpense->load(['account', 'items.account', 'creator', 'poster']);
        return view('accounting.cash-bank-expenses.show', compact('cashBankExpense'));
    }

    public function edit(CashBankExpense $cashBankExpense): View
    {
        abort_if($cashBankExpense->isPosted(), 403, 'Dokumen yang sudah diposting tidak dapat diedit.');
        $cashBankExpense->load(['items.account']);
        $accounts = ChartOfAccount::where('is_active', true)->orderBy('code')->get();
        return view('accounting.cash-bank-expenses.edit', compact('cashBankExpense', 'accounts'));
    }

    public function update(Request $request, CashBankExpense $cashBankExpense): RedirectResponse
    {
        abort_if($cashBankExpense->isPosted(), 403, 'Dokumen yang sudah diposting tidak dapat diubah.');

        $request->validate([
            'company_name'        => 'required|string|max:255',
            'type'                => 'required|in:KAS,BANK,DP',
            'transaction_date'    => 'required|date',
            'chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'notes'               => 'required|string',
            'document_file'       => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'items'               => 'nullable|array',
            'items.*.chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'items.*.amount'      => 'required|numeric|min:0',
            'items.*.notes'       => 'required|string',
        ]);

        return DB::transaction(function () use ($request, $cashBankExpense) {
            $docFile = $cashBankExpense->document_file;
            if ($request->hasFile('document_file')) {
                if ($docFile) Storage::disk('public')->delete($docFile);
                $docFile = $request->file('document_file')->store('cash-bank-expenses', 'public');
            }

            $totalAmount = collect($request->items ?? [])->sum(fn($i) => (float)($i['amount'] ?? 0));

            $cashBankExpense->update([
                'company_name'        => $request->company_name,
                'voucher_number'      => $request->voucher_number,
                'type'                => $request->type,
                'transaction_date'    => $request->transaction_date,
                'group'               => $request->group,
                'chart_of_account_id' => $request->chart_of_account_id,
                'reference_number'    => $request->reference_number,
                'notes'               => $request->notes,
                'document_file'       => $docFile,
                'amount'              => $totalAmount,
            ]);

            $cashBankExpense->items()->delete();
            foreach ($request->items ?? [] as $item) {
                if (!empty($item['chart_of_account_id']) && (float)($item['amount'] ?? 0) > 0) {
                    $cashBankExpense->items()->create([
                        'chart_of_account_id' => $item['chart_of_account_id'],
                        'description'         => $item['description'] ?? null,
                        'amount'              => (float) $item['amount'],
                        'notes'               => $item['notes'] ?? null,
                    ]);
                }
            }

            return redirect()->route('accounting.cash-bank-expenses.show', $cashBankExpense)
                ->with('success', "Cash Bank Expense {$cashBankExpense->expense_number} berhasil diperbarui.");
        });
    }

    public function destroy(CashBankExpense $cashBankExpense): RedirectResponse
    {
        abort_if($cashBankExpense->isPosted(), 403, 'Dokumen yang sudah diposting tidak dapat dihapus.');
        $cashBankExpense->items()->delete();
        if ($cashBankExpense->document_file) {
            Storage::disk('public')->delete($cashBankExpense->document_file);
        }
        $cashBankExpense->delete();
        return redirect()->route('accounting.cash-bank-expenses.index')
            ->with('success', 'Cash Bank Expense berhasil dihapus.');
    }

    public function post(CashBankExpense $cashBankExpense): RedirectResponse
    {
        abort_if($cashBankExpense->isPosted(), 403, 'Dokumen sudah diposting.');
        $cashBankExpense->update([
            'status'    => 'posted',
            'posted_by' => Auth::id(),
            'posted_at' => now(),
        ]);
        return back()->with('success', "Cash Bank Expense {$cashBankExpense->expense_number} berhasil diposting.");
    }
}
