<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\CreditNote;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\Supplier;
use App\Services\CreditNoteService;
use App\Traits\HasListFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CreditNoteController extends Controller
{
    use HasListFilters;

    protected CreditNoteService $cnService;

    public function __construct(CreditNoteService $cnService)
    {
        $this->cnService = $cnService;
    }

    public function index(Request $request): View
    {
        $query = CreditNote::with(['supplier', 'customer', 'invoices', 'accounts.creditAccount']);

        // Search (support both 'q' from x-list-filter-bar and 'search')
        $search = $request->get('q', $request->get('search'));
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('cn_number', 'like', "%{$search}%")
                  ->orWhere('draft_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('invoices', fn($iq) => $iq->where('invoice_number', 'like', "%{$search}%"));
            });
        }

        // Status Filter (draft, posted, cancelled)
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Type Filter (AP or AR)
        if ($cnType = $request->get('cn_type')) {
            $query->where('cn_type', $cnType);
        }

        $query = $this->applyDateRange($query, $request, 'cn_date');
        $query = $this->applySort($query, $request, ['cn_number', 'draft_number', 'cn_date', 'total_amount', 'status', 'created_at'], 'id', 'desc');

        $perPage = (int) $request->get('per_page', 10);
        $creditNotes = $query->paginate($perPage)->withQueryString();

        return view('accounting.credit-notes.index', compact('creditNotes'));
    }

    public function create(): View
    {
        $nextCnNumber = $this->cnService->generateCnNumber();
        $suppliers = Supplier::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        $accounts = ChartOfAccount::where('is_active', true)->orderBy('code')->get();

        return view('accounting.credit-notes.create', compact('nextCnNumber', 'suppliers', 'customers', 'accounts'));
    }

    /**
     * AJAX endpoint to fetch outstanding invoices for given partner
     */
    public function getInvoices(Request $request): JsonResponse
    {
        $type = $request->get('type', 'AR');
        $partnerId = (int) $request->get('partner_id');

        if (!$partnerId) {
            return response()->json(['invoices' => []]);
        }

        $invoices = [];

        if ($type === 'AP') {
            // Purchase Invoices for Supplier
            $records = PurchaseInvoice::with(['purchaseOrder.supplier', 'goodsReceipt'])
                ->whereHas('purchaseOrder', fn($q) => $q->where('supplier_id', $partnerId))
                ->whereNotIn('status', ['draft', 'cancelled'])
                ->get()
                ->filter(fn($inv) => $inv->outstanding_amount > 0)
                ->values();

            foreach ($records as $inv) {
                $invoices[] = [
                    'id'               => $inv->id,
                    'invoice_number'   => $inv->invoice_number,
                    'sj_number'        => $inv->goodsReceipt ? $inv->goodsReceipt->receipt_number : ($inv->supplier_invoice_number ?: '-'),
                    'invoice_date'     => $inv->invoice_date?->format('Y-m-d'),
                    'invoice_date_formatted' => $inv->invoice_date?->format('d/m/Y'),
                    'total_amount'     => (float) $inv->effective_total_amount,
                    'paid_amount'      => (float) $inv->total_paid,
                    'remaining_amount' => (float) $inv->outstanding_amount,
                ];
            }
        } else {
            // Sales Invoices for Customer
            $records = SalesInvoice::with(['salesOrder.customer', 'delivery'])
                ->whereHas('salesOrder', fn($q) => $q->where('customer_id', $partnerId))
                ->whereNotIn('status', ['draft', 'cancelled'])
                ->get()
                ->filter(fn($inv) => $inv->outstanding_amount > 0)
                ->values();

            foreach ($records as $inv) {
                $invoices[] = [
                    'id'               => $inv->id,
                    'invoice_number'   => $inv->invoice_number,
                    'sj_number'        => $inv->delivery ? $inv->delivery->delivery_number : ($inv->tax_invoice_number ?: '-'),
                    'invoice_date'     => $inv->invoice_date?->format('Y-m-d'),
                    'invoice_date_formatted' => $inv->invoice_date?->format('d/m/Y'),
                    'total_amount'     => (float) $inv->effective_total_amount,
                    'paid_amount'      => (float) $inv->total_paid,
                    'remaining_amount' => (float) $inv->outstanding_amount,
                ];
            }
        }

        return response()->json(['invoices' => $invoices]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'cn_type'       => 'required|in:AP,AR',
            'business_unit' => 'nullable|string',
            'cn_date'       => 'required|date',
            'notes'         => 'nullable|string',
            'supplier_id'   => 'required_if:cn_type,AP|nullable|exists:suppliers,id',
            'customer_id'   => 'required_if:cn_type,AR|nullable|exists:customers,id',
            'invoices'      => 'required|array|min:1',
            'invoices.*.invoice_number' => 'required|string',
            'invoices.*.cn_amount'      => 'required|numeric|min:0.01',
            'accounts'      => 'required|array|min:1',
            'accounts.*.debit_chart_of_account_id'  => 'required|exists:chart_of_accounts,id',
            'accounts.*.credit_chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'accounts.*.cn_amount'                  => 'required|numeric|min:0.01',
        ]);

        return DB::transaction(function () use ($request) {
            $cnNumber = $this->cnService->generateCnNumber();
            $draftNumber = $this->cnService->generateDraftNumber();

            $totalAmt = collect($request->invoices)->sum(fn($i) => (float)($i['cn_amount'] ?? 0));
            $taxAmt = (float)($request->tax_amount ?? 0);

            $creditNote = CreditNote::create([
                'draft_number'  => $draftNumber,
                'cn_number'     => $cnNumber,
                'cn_type'       => $request->cn_type,
                'business_unit' => $request->business_unit ?? 'BECCA',
                'supplier_id'   => $request->cn_type === 'AP' ? $request->supplier_id : null,
                'customer_id'   => $request->cn_type === 'AR' ? $request->customer_id : null,
                'cn_date'       => $request->cn_date,
                'tax_type'      => $request->tax_type ?? 'NON TAX',
                'tax_rate'      => (float)($request->tax_rate ?? 0),
                'tax_amount'    => $taxAmt,
                'total_amount'  => $totalAmt,
                'status'        => 'draft',
                'notes'         => $request->notes,
                'created_by'    => Auth::id() ?? 1,
            ]);

            // Save Invoices
            foreach ($request->invoices as $inv) {
                $cnAmount = (float)($inv['cn_amount'] ?? 0);
                if ($cnAmount <= 0) continue;

                $remAmount = (float)($inv['remaining_amount'] ?? $cnAmount);
                $balAmount = max(0, $remAmount - $cnAmount);

                $creditNote->invoices()->create([
                    'purchase_invoice_id'   => $request->cn_type === 'AP' ? ($inv['invoice_id'] ?? null) : null,
                    'sales_invoice_id'      => $request->cn_type === 'AR' ? ($inv['invoice_id'] ?? null) : null,
                    'invoice_number'        => $inv['invoice_number'],
                    'delivery_order_number' => $inv['delivery_order_number'] ?? null,
                    'invoice_date'          => $inv['invoice_date'] ?? null,
                    'remaining_amount'      => $remAmount,
                    'paid_amount'           => (float)($inv['paid_amount'] ?? 0),
                    'cn_amount'             => $cnAmount,
                    'balance_amount'        => $balAmount,
                ]);
            }

            // Save Accounts
            foreach ($request->accounts as $acc) {
                $accCnAmt = (float)($acc['cn_amount'] ?? 0);
                if ($accCnAmt <= 0) continue;

                $creditNote->accounts()->create([
                    'invoice_number'             => $acc['invoice_number'] ?? null,
                    'debit_chart_of_account_id'  => $acc['debit_chart_of_account_id'],
                    'credit_chart_of_account_id' => $acc['credit_chart_of_account_id'],
                    'outstanding_amount'         => (float)($acc['outstanding_amount'] ?? 0),
                    'cn_amount'                  => $accCnAmt,
                    'tax_amount'                 => (float)($acc['tax_amount'] ?? 0),
                    'notes'                      => $acc['notes'] ?? null,
                ]);
            }

            return redirect()->route('accounting.credit-notes.index')
                ->with('success', "Kredit Note #{$creditNote->cn_number} berhasil disimpan sebagai Draft.");
        });
    }

    public function edit(CreditNote $creditNote): View|RedirectResponse
    {
        if (!$creditNote->isDraft()) {
            return redirect()->route('accounting.credit-notes.show', $creditNote)
                ->with('error', 'Hanya dokumen berstatus Draft yang dapat diedit.');
        }

        $creditNote->load(['supplier', 'customer', 'invoices', 'accounts.debitAccount', 'accounts.creditAccount']);
        $suppliers = Supplier::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        $accounts = ChartOfAccount::where('is_active', true)->orderBy('code')->get();

        $existingInvoices = $creditNote->invoices->map(function ($inv) {
            return [
                'id'                     => $inv->purchase_invoice_id ?: $inv->sales_invoice_id ?: $inv->id,
                'invoice_id'             => $inv->purchase_invoice_id ?: $inv->sales_invoice_id,
                'invoice_number'         => $inv->invoice_number,
                'sj_number'              => $inv->delivery_order_number ?: '-',
                'invoice_date'           => $inv->invoice_date?->format('Y-m-d'),
                'invoice_date_formatted' => $inv->invoice_date?->format('d/m/Y'),
                'remaining_amount'       => (float) $inv->remaining_amount,
                'paid_amount'            => (float) $inv->paid_amount,
                'cn_amount'              => (float) $inv->cn_amount,
                'formatted_cn_amount'    => number_format($inv->cn_amount, 0, ',', '.'),
                'balance_amount'         => (float) $inv->balance_amount,
            ];
        })->values();

        $existingAccounts = $creditNote->accounts->map(function ($acc) {
            return [
                'invoice_number'             => $acc->invoice_number,
                'debit_chart_of_account_id'  => $acc->debit_chart_of_account_id,
                'credit_chart_of_account_id' => $acc->credit_chart_of_account_id,
                'outstanding_amount'         => (float) $acc->outstanding_amount,
                'cn_amount'                  => (float) $acc->cn_amount,
                'formatted_cn_amount'        => number_format($acc->cn_amount, 0, ',', '.'),
                'tax_amount'                 => (float) $acc->tax_amount,
                'formatted_tax_amount'       => number_format($acc->tax_amount, 0, ',', '.'),
                'notes'                      => $acc->notes ?? '',
            ];
        })->values();

        return view('accounting.credit-notes.edit', compact(
            'creditNote', 'suppliers', 'customers', 'accounts', 'existingInvoices', 'existingAccounts'
        ));
    }

    public function update(Request $request, CreditNote $creditNote): RedirectResponse
    {
        if (!$creditNote->isDraft()) {
            return redirect()->route('accounting.credit-notes.show', $creditNote)
                ->with('error', 'Hanya dokumen berstatus Draft yang dapat diedit.');
        }

        $request->validate([
            'cn_date'       => 'required|date',
            'business_unit' => 'nullable|string',
            'notes'         => 'nullable|string',
            'invoices'      => 'required|array|min:1',
            'invoices.*.invoice_number' => 'required|string',
            'invoices.*.cn_amount'      => 'required|numeric|min:0.01',
            'accounts'      => 'required|array|min:1',
            'accounts.*.debit_chart_of_account_id'  => 'required|exists:chart_of_accounts,id',
            'accounts.*.credit_chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'accounts.*.cn_amount'                  => 'required|numeric|min:0.01',
        ]);

        return DB::transaction(function () use ($request, $creditNote) {
            $totalAmt = collect($request->invoices)->sum(fn($i) => (float)($i['cn_amount'] ?? 0));
            $taxAmt = (float)($request->tax_amount ?? 0);

            $creditNote->update([
                'cn_date'       => $request->cn_date,
                'business_unit' => $request->business_unit ?? $creditNote->business_unit,
                'tax_type'      => $request->tax_type ?? 'NON TAX',
                'tax_rate'      => (float)($request->tax_rate ?? 0),
                'tax_amount'    => $taxAmt,
                'total_amount'  => $totalAmt,
                'notes'         => $request->notes,
            ]);

            // Recreate Invoices
            $creditNote->invoices()->delete();
            foreach ($request->invoices as $inv) {
                $cnAmount = (float)($inv['cn_amount'] ?? 0);
                if ($cnAmount <= 0) continue;

                $remAmount = (float)($inv['remaining_amount'] ?? $cnAmount);
                $balAmount = max(0, $remAmount - $cnAmount);

                $creditNote->invoices()->create([
                    'purchase_invoice_id'   => $creditNote->cn_type === 'AP' ? ($inv['invoice_id'] ?? null) : null,
                    'sales_invoice_id'      => $creditNote->cn_type === 'AR' ? ($inv['invoice_id'] ?? null) : null,
                    'invoice_number'        => $inv['invoice_number'],
                    'delivery_order_number' => $inv['delivery_order_number'] ?? null,
                    'invoice_date'          => $inv['invoice_date'] ?? null,
                    'remaining_amount'      => $remAmount,
                    'paid_amount'           => (float)($inv['paid_amount'] ?? 0),
                    'cn_amount'             => $cnAmount,
                    'balance_amount'        => $balAmount,
                ]);
            }

            // Recreate Accounts
            $creditNote->accounts()->delete();
            foreach ($request->accounts as $acc) {
                $accCnAmt = (float)($acc['cn_amount'] ?? 0);
                if ($accCnAmt <= 0) continue;

                $creditNote->accounts()->create([
                    'invoice_number'             => $acc['invoice_number'] ?? null,
                    'debit_chart_of_account_id'  => $acc['debit_chart_of_account_id'],
                    'credit_chart_of_account_id' => $acc['credit_chart_of_account_id'],
                    'outstanding_amount'         => (float)($acc['outstanding_amount'] ?? 0),
                    'cn_amount'                  => $accCnAmt,
                    'tax_amount'                 => (float)($acc['tax_amount'] ?? 0),
                    'notes'                      => $acc['notes'] ?? null,
                ]);
            }

            return redirect()->route('accounting.credit-notes.index')
                ->with('success', "Kredit Note #{$creditNote->cn_number} berhasil diperbarui.");
        });
    }

    public function show(CreditNote $creditNote): View
    {
        $creditNote->load(['supplier', 'customer', 'invoices', 'accounts.debitAccount', 'accounts.creditAccount', 'journalEntry.lines.chartOfAccount', 'creator', 'poster']);
        return view('accounting.credit-notes.show', compact('creditNote'));
    }

    public function post(CreditNote $creditNote): RedirectResponse
    {
        try {
            $this->cnService->post($creditNote);
            return redirect()->back()->with('success', "Kredit Note #{$creditNote->cn_number} berhasil diposting ke jurnal akuntansi dan memotong saldo faktur terkait.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Gagal posting: " . $e->getMessage());
        }
    }

    public function destroy(CreditNote $creditNote): RedirectResponse
    {
        if (!$creditNote->isDraft()) {
            return redirect()->back()->with('error', 'Hanya dokumen berstatus Draft yang dapat dihapus.');
        }

        $creditNote->invoices()->delete();
        $creditNote->accounts()->delete();
        $creditNote->delete();

        return redirect()->route('accounting.credit-notes.index')
            ->with('success', "Kredit Note #{$creditNote->cn_number} berhasil dihapus.");
    }

    public function cancel(CreditNote $creditNote): RedirectResponse
    {
        try {
            $this->cnService->cancel($creditNote);
            return redirect()->back()->with('success', "Kredit Note #{$creditNote->cn_number} berhasil dibatalkan.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Gagal membatalkan: " . $e->getMessage());
        }
    }

    public function print(CreditNote $creditNote): View|RedirectResponse
    {
        if (!$creditNote->isPosted()) {
            return redirect()->route('accounting.credit-notes.show', $creditNote)
                ->with('error', 'Kredit Note belum disetujui / diposting, tidak dapat mencetak atau mengekspor dokumen.');
        }

        $creditNote->load(['supplier', 'customer', 'invoices', 'accounts.debitAccount', 'accounts.creditAccount', 'creator', 'poster']);
        return view('accounting.credit-notes.print', compact('creditNote'));
    }
}
