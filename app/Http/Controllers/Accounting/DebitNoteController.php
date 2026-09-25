<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\DebitNote;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\Supplier;
use App\Services\DebitNoteService;
use App\Traits\HasListFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DebitNoteController extends Controller
{
    use HasListFilters;

    protected DebitNoteService $dnService;

    public function __construct(DebitNoteService $dnService)
    {
        $this->dnService = $dnService;
    }

    public function index(Request $request): View
    {
        $query = DebitNote::with(['supplier', 'customer', 'invoices', 'accounts.creditAccount']);

        // Search (support both 'q' from x-list-filter-bar and 'search')
        $search = $request->get('q', $request->get('search'));
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('dn_number', 'like', "%{$search}%")
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
        if ($dnType = $request->get('dn_type')) {
            $query->where('dn_type', $dnType);
        }

        $query = $this->applyDateRange($query, $request, 'dn_date');
        $query = $this->applySort($query, $request, ['dn_number', 'draft_number', 'dn_date', 'total_amount', 'status', 'created_at'], 'id', 'desc');

        $perPage = (int) $request->get('per_page', 10);
        $debitNotes = $query->paginate($perPage)->withQueryString();

        return view('accounting.debit-notes.index', compact('debitNotes'));
    }

    public function create(): View
    {
        $nextDnNumber = $this->dnService->generateDnNumber();
        $suppliers = Supplier::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        $accounts = ChartOfAccount::where('is_active', true)->orderBy('code')->get();

        return view('accounting.debit-notes.create', compact('nextDnNumber', 'suppliers', 'customers', 'accounts'));
    }

    /**
     * AJAX endpoint to fetch outstanding invoices for given partner
     */
    public function getInvoices(Request $request): JsonResponse
    {
        $type = $request->get('type', 'AP');
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
            'dn_type'       => 'required|in:AP,AR',
            'dn_date'       => 'required|date',
            'notes'         => 'nullable|string',
            'supplier_id'   => 'required_if:dn_type,AP|nullable|exists:suppliers,id',
            'customer_id'   => 'required_if:dn_type,AR|nullable|exists:customers,id',
            'invoices'      => 'required|array|min:1',
            'invoices.*.invoice_number' => 'required|string',
            'invoices.*.dn_amount'      => 'required|numeric|min:0.01',
            'accounts'      => 'required|array|min:1',
            'accounts.*.debit_chart_of_account_id'  => 'required|exists:chart_of_accounts,id',
            'accounts.*.credit_chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'accounts.*.dn_amount'                  => 'required|numeric|min:0.01',
        ]);

        return DB::transaction(function () use ($request) {
            $dnNumber = $this->dnService->generateDnNumber();
            $draftNumber = $this->dnService->generateDraftNumber();

            $totalAmt = collect($request->invoices)->sum(fn($i) => (float)($i['dn_amount'] ?? 0));
            $taxAmt = (float)($request->tax_amount ?? 0);

            $debitNote = DebitNote::create([
                'draft_number'  => $draftNumber,
                'dn_number'     => $dnNumber,
                'dn_type'       => $request->dn_type,
                'supplier_id'   => $request->dn_type === 'AP' ? $request->supplier_id : null,
                'customer_id'   => $request->dn_type === 'AR' ? $request->customer_id : null,
                'dn_date'       => $request->dn_date,
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
                $dnAmount = (float)($inv['dn_amount'] ?? 0);
                if ($dnAmount <= 0) continue;

                $remAmount = (float)($inv['remaining_amount'] ?? $dnAmount);
                $balAmount = max(0, $remAmount - $dnAmount);

                $debitNote->invoices()->create([
                    'purchase_invoice_id'   => $request->dn_type === 'AP' ? ($inv['invoice_id'] ?? null) : null,
                    'sales_invoice_id'      => $request->dn_type === 'AR' ? ($inv['invoice_id'] ?? null) : null,
                    'invoice_number'        => $inv['invoice_number'],
                    'delivery_order_number' => $inv['delivery_order_number'] ?? null,
                    'invoice_date'          => $inv['invoice_date'] ?? null,
                    'remaining_amount'      => $remAmount,
                    'paid_amount'           => (float)($inv['paid_amount'] ?? 0),
                    'dn_amount'             => $dnAmount,
                    'balance_amount'        => $balAmount,
                ]);
            }

            // Save Accounts
            foreach ($request->accounts as $acc) {
                $accDnAmt = (float)($acc['dn_amount'] ?? 0);
                if ($accDnAmt <= 0) continue;

                $debitNote->accounts()->create([
                    'invoice_number'             => $acc['invoice_number'] ?? null,
                    'debit_chart_of_account_id'  => $acc['debit_chart_of_account_id'],
                    'credit_chart_of_account_id' => $acc['credit_chart_of_account_id'],
                    'outstanding_amount'         => (float)($acc['outstanding_amount'] ?? 0),
                    'dn_amount'                  => $accDnAmt,
                    'tax_amount'                 => (float)($acc['tax_amount'] ?? 0),
                    'notes'                      => $acc['notes'] ?? null,
                ]);
            }

            return redirect()->route('accounting.debit-notes.index')
                ->with('success', "Debet Note #{$debitNote->dn_number} berhasil disimpan sebagai Draft.");
        });
    }

    public function edit(DebitNote $debitNote): View|RedirectResponse
    {
        if (!$debitNote->isDraft()) {
            return redirect()->route('accounting.debit-notes.show', $debitNote)
                ->with('error', 'Hanya dokumen berstatus Draft yang dapat diedit.');
        }

        $debitNote->load(['supplier', 'customer', 'invoices', 'accounts.debitAccount', 'accounts.creditAccount']);
        $suppliers = Supplier::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        $accounts = ChartOfAccount::where('is_active', true)->orderBy('code')->get();

        $existingInvoices = $debitNote->invoices->map(function ($inv) {
            return [
                'id'                     => $inv->purchase_invoice_id ?: $inv->sales_invoice_id ?: $inv->id,
                'invoice_id'             => $inv->purchase_invoice_id ?: $inv->sales_invoice_id,
                'invoice_number'         => $inv->invoice_number,
                'sj_number'              => $inv->delivery_order_number ?: '-',
                'invoice_date'           => $inv->invoice_date?->format('Y-m-d'),
                'invoice_date_formatted' => $inv->invoice_date?->format('d/m/Y'),
                'remaining_amount'       => (float) $inv->remaining_amount,
                'paid_amount'            => (float) $inv->paid_amount,
                'dn_amount'              => (float) $inv->dn_amount,
                'formatted_dn_amount'    => number_format($inv->dn_amount, 0, ',', '.'),
                'balance_amount'         => (float) $inv->balance_amount,
            ];
        })->values();

        $existingAccounts = $debitNote->accounts->map(function ($acc) {
            return [
                'invoice_number'             => $acc->invoice_number,
                'debit_chart_of_account_id'  => $acc->debit_chart_of_account_id,
                'credit_chart_of_account_id' => $acc->credit_chart_of_account_id,
                'outstanding_amount'         => (float) $acc->outstanding_amount,
                'dn_amount'                  => (float) $acc->dn_amount,
                'formatted_dn_amount'        => number_format($acc->dn_amount, 0, ',', '.'),
                'tax_amount'                 => (float) $acc->tax_amount,
                'formatted_tax_amount'       => number_format($acc->tax_amount, 0, ',', '.'),
                'notes'                      => $acc->notes ?? '',
            ];
        })->values();

        return view('accounting.debit-notes.edit', compact(
            'debitNote', 'suppliers', 'customers', 'accounts', 'existingInvoices', 'existingAccounts'
        ));
    }

    public function update(Request $request, DebitNote $debitNote): RedirectResponse
    {
        if (!$debitNote->isDraft()) {
            return redirect()->route('accounting.debit-notes.show', $debitNote)
                ->with('error', 'Hanya dokumen berstatus Draft yang dapat diedit.');
        }

        $request->validate([
            'dn_date'       => 'required|date',
            'notes'         => 'nullable|string',
            'invoices'      => 'required|array|min:1',
            'invoices.*.invoice_number' => 'required|string',
            'invoices.*.dn_amount'      => 'required|numeric|min:0.01',
            'accounts'      => 'required|array|min:1',
            'accounts.*.debit_chart_of_account_id'  => 'required|exists:chart_of_accounts,id',
            'accounts.*.credit_chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'accounts.*.dn_amount'                  => 'required|numeric|min:0.01',
        ]);

        return DB::transaction(function () use ($request, $debitNote) {
            $totalAmt = collect($request->invoices)->sum(fn($i) => (float)($i['dn_amount'] ?? 0));
            $taxAmt = (float)($request->tax_amount ?? 0);

            $debitNote->update([
                'dn_date'       => $request->dn_date,
                'tax_type'      => $request->tax_type ?? 'NON TAX',
                'tax_rate'      => (float)($request->tax_rate ?? 0),
                'tax_amount'    => $taxAmt,
                'total_amount'  => $totalAmt,
                'notes'         => $request->notes,
            ]);

            // Recreate Invoices
            $debitNote->invoices()->delete();
            foreach ($request->invoices as $inv) {
                $dnAmount = (float)($inv['dn_amount'] ?? 0);
                if ($dnAmount <= 0) continue;

                $remAmount = (float)($inv['remaining_amount'] ?? $dnAmount);
                $balAmount = max(0, $remAmount - $dnAmount);

                $debitNote->invoices()->create([
                    'purchase_invoice_id'   => $debitNote->dn_type === 'AP' ? ($inv['invoice_id'] ?? null) : null,
                    'sales_invoice_id'      => $debitNote->dn_type === 'AR' ? ($inv['invoice_id'] ?? null) : null,
                    'invoice_number'        => $inv['invoice_number'],
                    'delivery_order_number' => $inv['delivery_order_number'] ?? null,
                    'invoice_date'          => $inv['invoice_date'] ?? null,
                    'remaining_amount'      => $remAmount,
                    'paid_amount'           => (float)($inv['paid_amount'] ?? 0),
                    'dn_amount'             => $dnAmount,
                    'balance_amount'        => $balAmount,
                ]);
            }

            // Recreate Accounts
            $debitNote->accounts()->delete();
            foreach ($request->accounts as $acc) {
                $accDnAmt = (float)($acc['dn_amount'] ?? 0);
                if ($accDnAmt <= 0) continue;

                $debitNote->accounts()->create([
                    'invoice_number'             => $acc['invoice_number'] ?? null,
                    'debit_chart_of_account_id'  => $acc['debit_chart_of_account_id'],
                    'credit_chart_of_account_id' => $acc['credit_chart_of_account_id'],
                    'outstanding_amount'         => (float)($acc['outstanding_amount'] ?? 0),
                    'dn_amount'                  => $accDnAmt,
                    'tax_amount'                 => (float)($acc['tax_amount'] ?? 0),
                    'notes'                      => $acc['notes'] ?? null,
                ]);
            }

            return redirect()->route('accounting.debit-notes.index')
                ->with('success', "Debet Note #{$debitNote->dn_number} berhasil diperbarui.");
        });
    }

    public function show(DebitNote $debitNote): View
    {
        $debitNote->load(['supplier', 'customer', 'invoices', 'accounts.debitAccount', 'accounts.creditAccount', 'journalEntry.lines.chartOfAccount', 'creator', 'poster']);
        return view('accounting.debit-notes.show', compact('debitNote'));
    }

    public function post(DebitNote $debitNote): RedirectResponse
    {
        try {
            $this->dnService->post($debitNote);
            return redirect()->back()->with('success', "Debet Note #{$debitNote->dn_number} berhasil diposting ke jurnal akuntansi dan memotong saldo faktur terkait.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Gagal posting: " . $e->getMessage());
        }
    }

    public function destroy(DebitNote $debitNote): RedirectResponse
    {
        if (!$debitNote->isDraft()) {
            return redirect()->back()->with('error', 'Hanya dokumen berstatus Draft yang dapat dihapus.');
        }

        $debitNote->invoices()->delete();
        $debitNote->accounts()->delete();
        $debitNote->delete();

        return redirect()->route('accounting.debit-notes.index')
            ->with('success', "Debet Note #{$debitNote->dn_number} berhasil dihapus.");
    }

    public function cancel(DebitNote $debitNote): RedirectResponse
    {
        try {
            $this->dnService->cancel($debitNote);
            return redirect()->back()->with('success', "Debet Note #{$debitNote->dn_number} berhasil dibatalkan.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Gagal membatalkan: " . $e->getMessage());
        }
    }

    public function print(DebitNote $debitNote): View|RedirectResponse
    {
        if (!$debitNote->isPosted()) {
            return redirect()->route('accounting.debit-notes.show', $debitNote)
                ->with('error', 'Debet Note belum disetujui / diposting, tidak dapat mencetak atau mengekspor dokumen.');
        }

        $debitNote->load(['supplier', 'customer', 'invoices', 'accounts.debitAccount', 'accounts.creditAccount', 'creator', 'poster']);
        return view('accounting.debit-notes.print', compact('debitNote'));
    }
}
