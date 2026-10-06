<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\CashBankReceipt;
use App\Models\CashBankReceiptItem;
use App\Models\ChartOfAccount;
use App\Traits\HasListFilters;
use App\Traits\GeneratesDocumentNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CashBankReceiptController extends Controller
{
    use HasListFilters, GeneratesDocumentNumber;

    public function index(Request $request): View
    {
        $query = CashBankReceipt::with(['account', 'creator']);

        $query = $this->applySearch($query, $request, ['receipt_number', 'voucher_number', 'company_name', 'notes']);
        $query = $this->applyFilter($query, $request, 'status');
        $query = $this->applyFilter($query, $request, 'type');
        $query = $this->applyDateRange($query, $request, 'transaction_date');
        $query = $this->applySort($query, $request, ['receipt_number', 'transaction_date', 'amount', 'status', 'created_at'], 'transaction_date', 'desc');

        $perPage = (int) $request->get('per_page', 10);
        $receipts = $query->paginate($perPage)->withQueryString();

        return view('accounting.cash-bank-receipts.index', compact('receipts'));
    }

    public function create(): View
    {
        $accounts = ChartOfAccount::where('is_active', true)->orderBy('code')->get();
        return view('accounting.cash-bank-receipts.create', compact('accounts'));
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
                $docFile = $request->file('document_file')->store('cash-bank-receipts', 'public');
            }

            $totalAmount = collect($request->items ?? [])->sum(fn($i) => (float)($i['amount'] ?? 0));

            $receipt = CashBankReceipt::create([
                'company_name'        => $request->company_name,
                'receipt_number'      => $this->generateDocumentNumber(CashBankReceipt::class, 'receipt_number', 'CBM'),
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
                    $receipt->items()->create([
                        'chart_of_account_id' => $item['chart_of_account_id'],
                        'description'         => $item['description'] ?? null,
                        'amount'              => (float) $item['amount'],
                        'notes'               => $item['notes'] ?? null,
                    ]);
                }
            }

            return redirect()->route('accounting.cash-bank-receipts.show', $receipt)
                ->with('success', "Cash Bank Receipt {$receipt->receipt_number} berhasil disimpan sebagai Draft.");
        });
    }

    public function show(CashBankReceipt $cashBankReceipt): View
    {
        $cashBankReceipt->load(['account', 'items.account', 'creator', 'poster']);
        return view('accounting.cash-bank-receipts.show', compact('cashBankReceipt'));
    }

    public function edit(CashBankReceipt $cashBankReceipt): View
    {
        abort_if($cashBankReceipt->isPosted(), 403, 'Dokumen yang sudah diposting tidak dapat diedit.');
        $cashBankReceipt->load(['items.account']);
        $accounts = ChartOfAccount::where('is_active', true)->orderBy('code')->get();
        return view('accounting.cash-bank-receipts.edit', compact('cashBankReceipt', 'accounts'));
    }

    public function update(Request $request, CashBankReceipt $cashBankReceipt): RedirectResponse
    {
        abort_if($cashBankReceipt->isPosted(), 403, 'Dokumen yang sudah diposting tidak dapat diubah.');

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

        return DB::transaction(function () use ($request, $cashBankReceipt) {
            $docFile = $cashBankReceipt->document_file;
            if ($request->hasFile('document_file')) {
                if ($docFile) Storage::disk('public')->delete($docFile);
                $docFile = $request->file('document_file')->store('cash-bank-receipts', 'public');
            }

            $totalAmount = collect($request->items ?? [])->sum(fn($i) => (float)($i['amount'] ?? 0));

            $cashBankReceipt->update([
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

            $cashBankReceipt->items()->delete();
            foreach ($request->items ?? [] as $item) {
                if (!empty($item['chart_of_account_id']) && (float)($item['amount'] ?? 0) > 0) {
                    $cashBankReceipt->items()->create([
                        'chart_of_account_id' => $item['chart_of_account_id'],
                        'description'         => $item['description'] ?? null,
                        'amount'              => (float) $item['amount'],
                        'notes'               => $item['notes'] ?? null,
                    ]);
                }
            }

            return redirect()->route('accounting.cash-bank-receipts.show', $cashBankReceipt)
                ->with('success', "Cash Bank Receipt {$cashBankReceipt->receipt_number} berhasil diperbarui.");
        });
    }

    public function destroy(CashBankReceipt $cashBankReceipt): RedirectResponse
    {
        abort_if($cashBankReceipt->isPosted(), 403, 'Dokumen yang sudah diposting tidak dapat dihapus.');
        $cashBankReceipt->items()->delete();
        if ($cashBankReceipt->document_file) {
            Storage::disk('public')->delete($cashBankReceipt->document_file);
        }
        $cashBankReceipt->delete();
        return redirect()->route('accounting.cash-bank-receipts.index')
            ->with('success', 'Cash Bank Receipt berhasil dihapus.');
    }

    public function post(CashBankReceipt $cashBankReceipt): RedirectResponse
    {
        abort_if($cashBankReceipt->isPosted(), 403, 'Dokumen sudah diposting.');
        $cashBankReceipt->update([
            'status'    => 'posted',
            'posted_by' => Auth::id(),
            'posted_at' => now(),
        ]);
        return back()->with('success', "Cash Bank Receipt {$cashBankReceipt->receipt_number} berhasil diposting.");
    }
}
