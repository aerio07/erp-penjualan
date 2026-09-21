<?php

namespace App\Http\Controllers\Purchase;

use App\Enums\PaymentStatus;
use App\Enums\PurchaseInvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\PurchaseInvoice;
use App\Models\PurchasePayment;
use App\Services\JournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

use App\Traits\HasListFilters;
use App\Traits\ExportsPdfDocument;

class PurchasePaymentController extends Controller
{
    use HasListFilters, ExportsPdfDocument;

    public function __construct(private JournalService $journalService) {}

    public function index(Request $request): View
    {
        $query = PurchasePayment::with(['purchaseInvoice.purchaseOrder.supplier', 'user', 'chartOfAccount']);

        $query = $this->applySearch($query, $request, ['payment_number', 'purchaseInvoice.invoice_number', 'purchaseInvoice.purchaseOrder.supplier.name', 'notes']);
        $query = $this->applyFilter($query, $request, 'method');
        $query = $this->applyFilter($query, $request, 'status');
        $query = $this->applyFilter($query, $request, 'payment_type');
        $query = $this->applyFilter($query, $request, 'chart_of_account_id');
        $query = $this->applyDateRange($query, $request, 'payment_date');
        $query = $this->applySort($query, $request, ['payment_number', 'payment_date', 'amount', 'status', 'method', 'created_at'], 'payment_date', 'desc');

        $perPage = (int) $request->get('per_page', 10);
        $payments = $query->paginate($perPage)->withQueryString();

        $coas = ChartOfAccount::where('is_active', true)
            ->where(function ($q) {
                $q->where('is_cash_bank', true)
                  ->orWhere('code', 'like', '1-11%');
            })
            ->orderBy('code')
            ->get();

        return view('purchase.payments.index', compact('payments', 'coas'));
    }

    public function create(Request $request): View
    {
        $selectedInvoiceId = $request->query('invoice_id');
        $unpaidInvoices = PurchaseInvoice::with(['purchaseOrder.supplier', 'payments', 'items'])
            ->whereIn('status', [PurchaseInvoiceStatus::Unpaid->value, PurchaseInvoiceStatus::Partial->value])
            ->orderByDesc('id')
            ->get();

        $coas = ChartOfAccount::where('is_active', true)
            ->orderByDesc('is_cash_bank')
            ->orderBy('code')
            ->get();

        return view('purchase.payments.create', compact('unpaidInvoices', 'selectedInvoiceId', 'coas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'purchase_invoice_id' => 'required|exists:purchase_invoices,id',
            'chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'payment_type'        => 'required|in:kas,bank',
            'amount'              => 'required|numeric|min:1',
            'payment_date'        => 'required|date',
            'method'              => 'required|in:cash,transfer,cek',
            'reference_number'    => 'nullable|string',
            'notes'               => 'nullable|string',
            'action'              => 'nullable|in:draft,post',
        ]);

        $action = $request->input('action', 'draft');

        return DB::transaction(function () use ($request, $action) {
            $invoice = PurchaseInvoice::with(['payments', 'items'])->lockForUpdate()->findOrFail($request->purchase_invoice_id);

            if ($invoice->isDraft()) {
                return back()->with('error', "Invoice masih berstatus Draft dan belum diposting. Pembayaran tidak dapat diproses.")->withInput();
            }

            if ($request->amount > $invoice->outstanding_amount + 0.01) {
                return back()->with('error', "Jumlah pembayaran (Rp " . number_format($request->amount, 0, ',', '.') . ") melebihi sisa hutang (Rp " . number_format($invoice->outstanding_amount, 0, ',', '.') . ").")->withInput();
            }

            $paymentNumber = PurchasePayment::generatePaymentNumber($request->method, $request->payment_date, $request->payment_type);

            $payment = PurchasePayment::create([
                'payment_number'      => $paymentNumber,
                'purchase_invoice_id' => $invoice->id,
                'chart_of_account_id' => $request->chart_of_account_id,
                'user_id'             => Auth::id(),
                'amount'              => $request->amount,
                'status'              => ($action === 'post') ? PaymentStatus::Posted : PaymentStatus::Draft,
                'payment_date'        => $request->payment_date,
                'payment_type'        => $request->payment_type,
                'method'              => $request->method,
                'reference_number'    => $request->reference_number,
                'notes'               => $request->notes,
            ]);

            if ($action === 'post') {
                $invoice->unsetRelation('payments');
                $newTotalPaid = (float) $invoice->payments()->where('status', '!=', PaymentStatus::Draft->value)->sum('amount');
                $status = ($newTotalPaid >= $invoice->effective_total_amount - 0.01) ? PurchaseInvoiceStatus::Paid : ($newTotalPaid > 0 ? PurchaseInvoiceStatus::Partial : PurchaseInvoiceStatus::Unpaid);
                $invoice->update(['status' => $status]);

                $entry = $this->journalService->createFromPurchasePayment($payment);
                $this->journalService->postEntry($entry);

                return redirect()->route('purchase.payments.index')
                    ->with('success', 'Pembayaran Hutang berhasil dicatat dan diposting ke Jurnal Akuntansi.');
            }

            return redirect()->route('purchase.payments.index')
                ->with('success', 'Draft Pembayaran Hutang berhasil disimpan. Silakan buka detail pembayaran untuk memposting.');
        });
    }

    public function show(PurchasePayment $payment): View
    {
        $payment->load(['purchaseInvoice.purchaseOrder.supplier', 'user', 'chartOfAccount']);

        return view('purchase.payments.show', compact('payment'));
    }

    public function edit(PurchasePayment $payment): View
    {
        abort_if($payment->isPosted(), 403, 'Pembayaran yang sudah diposting tidak dapat diedit.');

        $payment->load(['purchaseInvoice.purchaseOrder.supplier', 'purchaseInvoice.payments', 'chartOfAccount']);
        $invoice = $payment->purchaseInvoice;

        $otherPaid = (float) $invoice->payments->filter(fn($p) => $p->id !== $payment->id && $p->status !== PaymentStatus::Draft)->sum('amount');
        $maxAllowed = max(0, $invoice->effective_total_amount - $otherPaid);

        $coas = ChartOfAccount::where('is_active', true)
            ->orderByDesc('is_cash_bank')
            ->orderBy('code')
            ->get();

        return view('purchase.payments.edit', compact('payment', 'invoice', 'maxAllowed', 'otherPaid', 'coas'));
    }

    public function update(Request $request, PurchasePayment $payment): RedirectResponse
    {
        abort_if($payment->isPosted(), 403, 'Pembayaran yang sudah diposting tidak dapat diubah.');

        $request->validate([
            'chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'payment_type'        => 'required|in:kas,bank',
            'amount'              => 'required|numeric|min:1',
            'payment_date'        => 'required|date',
            'method'              => 'required|in:cash,transfer,cek',
            'reference_number'    => 'nullable|string',
            'notes'               => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $payment) {
            $invoice = PurchaseInvoice::with(['payments', 'items'])->lockForUpdate()->findOrFail($payment->purchase_invoice_id);

            $otherPaid = $invoice->payments()->where('id', '!=', $payment->id)->where('status', '!=', PaymentStatus::Draft->value)->sum('amount');
            $maxAllowed = $invoice->effective_total_amount - $otherPaid;

            if ($request->amount > $maxAllowed + 0.01) {
                return back()->with('error', "Jumlah pembayaran (Rp " . number_format($request->amount, 0, ',', '.') . ") melebihi sisa hutang tagihan (Maks: Rp " . number_format($maxAllowed, 0, ',', '.') . ").")->withInput();
            }

            $payment->update([
                'chart_of_account_id' => $request->chart_of_account_id,
                'payment_type'        => $request->payment_type,
                'amount'              => $request->amount,
                'payment_date'        => $request->payment_date,
                'method'              => $request->method,
                'reference_number'    => $request->reference_number,
                'notes'               => $request->notes,
            ]);

            return redirect()->route('purchase.payments.index')
                ->with('success', 'Draft Pembayaran Hutang #' . $payment->payment_number . ' berhasil diperbarui.');
        });
    }

    public function post(PurchasePayment $payment): RedirectResponse
    {
        abort_if($payment->isPosted(), 422, 'Pembayaran ini sudah berstatus posted.');

        return DB::transaction(function () use ($payment) {
            $invoice = PurchaseInvoice::with(['payments', 'items'])->lockForUpdate()->findOrFail($payment->purchase_invoice_id);

            if ($payment->amount > $invoice->outstanding_amount + 0.01) {
                return back()->with('error', "Jumlah pembayaran (Rp " . number_format($payment->amount, 0, ',', '.') . ") melebihi sisa hutang (Rp " . number_format($invoice->outstanding_amount, 0, ',', '.') . ").");
            }

            $payment->update(['status' => PaymentStatus::Posted]);

            $invoice->unsetRelation('payments');
            $newTotalPaid = (float) $invoice->payments()->where('status', '!=', PaymentStatus::Draft->value)->sum('amount');
            $status = ($newTotalPaid >= $invoice->effective_total_amount - 0.01) ? PurchaseInvoiceStatus::Paid : ($newTotalPaid > 0 ? PurchaseInvoiceStatus::Partial : PurchaseInvoiceStatus::Unpaid);
            $invoice->update(['status' => $status]);

            // Jurnal Otomatis
            $entry = $this->journalService->createFromPurchasePayment($payment);
            $this->journalService->postEntry($entry);

            return redirect()->route('purchase.payments.show', $payment)
                ->with('success', 'Pembayaran Hutang berhasil diposting dan Jurnal Akuntansi otomatis tercatat.');
        });
    }

    public function destroy(PurchasePayment $payment): RedirectResponse
    {
        abort_if($payment->isPosted(), 403, 'Pembayaran yang sudah diposting tidak dapat dihapus.');

        $payment->delete();

        return redirect()->route('purchase.payments.index')
            ->with('success', 'Draft Pembayaran Hutang berhasil dihapus.');
    }

    public function exportPdf(PurchasePayment $payment)
    {
        abort_if(!$payment->isPosted(), 403, 'PDF bukti pembayaran hanya dapat dicetak setelah status Posted.');

        $payment->load(['purchaseInvoice.purchaseOrder.supplier', 'user']);
        $safeNumber = $payment->voucher_number;

        return $this->streamPdf('pdf.purchase-payment', compact('payment'), $safeNumber);
    }
}
