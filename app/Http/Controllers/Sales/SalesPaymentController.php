<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use App\Models\SalesPayment;
use App\Services\JournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

use App\Traits\HasListFilters;
use App\Traits\ExportsPdfDocument;

class SalesPaymentController extends Controller
{
    use HasListFilters, ExportsPdfDocument;

    public function __construct(private JournalService $journalService) {}

    public function index(Request $request): View
    {
        $query = SalesPayment::with(['salesInvoice.salesOrder.customer', 'user']);

        $query = $this->applySearch($query, $request, ['reference_number', 'salesInvoice.invoice_number', 'salesInvoice.salesOrder.customer.name', 'notes']);
        $query = $this->applyFilter($query, $request, 'method');
        $query = $this->applyFilter($query, $request, 'status');
        $query = $this->applyDateRange($query, $request, 'payment_date');
        $query = $this->applySort($query, $request, ['payment_date', 'amount', 'status', 'method', 'created_at'], 'payment_date', 'desc');

        $perPage = (int) $request->get('per_page', 20);
        $payments = $query->paginate($perPage)->withQueryString();

        return view('sales.payments.index', compact('payments'));
    }

    public function create(Request $request): View
    {
        $selectedInvoiceId = $request->query('invoice_id');
        $unpaidInvoices = SalesInvoice::with(['salesOrder.customer', 'payments', 'items'])
            ->whereIn('status', ['unpaid', 'partial'])
            ->orderByDesc('id')
            ->get()
            ->filter(fn($invoice) => $invoice->outstanding_amount > 0)
            ->values();

        return view('sales.payments.create', compact('unpaidInvoices', 'selectedInvoiceId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'sales_invoice_id' => 'required|exists:sales_invoices,id',
            'amount'           => 'required|numeric|min:1',
            'payment_date'     => 'required|date',
            'method'           => 'required|in:cash,transfer,giro,cek',
            'reference_number' => 'nullable|string',
            'notes'            => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            $invoice = SalesInvoice::with(['payments', 'items'])->lockForUpdate()->findOrFail($request->sales_invoice_id);

            if ($invoice->status === 'draft') {
                return back()->with('error', 'Invoice masih berstatus Draft dan belum diposting. Pembayaran piutang tidak dapat dicatat.')->withInput();
            }

            if ($request->amount > $invoice->outstanding_amount + 0.01) {
                return back()->with('error', "Jumlah penerimaan (Rp " . number_format($request->amount, 0, ',', '.') . ") melebihi sisa piutang (Rp " . number_format($invoice->outstanding_amount, 0, ',', '.') . ").")->withInput();
            }

            SalesPayment::create([
                'sales_invoice_id' => $invoice->id,
                'user_id'          => Auth::id(),
                'amount'           => $request->amount,
                'status'           => 'draft',
                'payment_date'     => $request->payment_date,
                'method'           => $request->method,
                'reference_number' => $request->reference_number,
                'notes'            => $request->notes,
            ]);

            return redirect()->route('sales.payments.index')
                ->with('success', 'Draft Penerimaan Piutang berhasil disimpan. Silakan buka detail pembayaran untuk memposting.');
        });
    }

    public function show(SalesPayment $payment): View
    {
        $payment->load(['salesInvoice.salesOrder.customer', 'user']);

        return view('sales.payments.show', compact('payment'));
    }

    public function edit(SalesPayment $payment): View
    {
        abort_if($payment->isPosted(), 403, 'Penerimaan piutang yang sudah diposting tidak dapat diedit.');

        $payment->load(['salesInvoice.salesOrder.customer', 'salesInvoice.payments']);
        $invoice = $payment->salesInvoice;

        $otherPaid = $invoice->payments->where('id', '!=', $payment->id)->where('status', '!=', 'draft')->sum('amount');
        $maxAllowed = max(0, $invoice->effective_total_amount - $otherPaid);

        return view('sales.payments.edit', compact('payment', 'invoice', 'maxAllowed', 'otherPaid'));
    }

    public function update(Request $request, SalesPayment $payment): RedirectResponse
    {
        abort_if($payment->isPosted(), 403, 'Penerimaan piutang yang sudah diposting tidak dapat diubah.');

        $request->validate([
            'amount'           => 'required|numeric|min:1',
            'payment_date'     => 'required|date',
            'method'           => 'required|in:cash,transfer,giro,cek',
            'reference_number' => 'nullable|string',
            'notes'            => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $payment) {
            $invoice = SalesInvoice::with(['payments', 'items'])->lockForUpdate()->findOrFail($payment->sales_invoice_id);

            $otherPaid = $invoice->payments()->where('id', '!=', $payment->id)->where('status', '!=', 'draft')->sum('amount');
            $maxAllowed = $invoice->effective_total_amount - $otherPaid;

            if ($request->amount > $maxAllowed + 0.01) {
                return back()->with('error', "Jumlah penerimaan (Rp " . number_format($request->amount, 0, ',', '.') . ") melebihi sisa piutang tagihan (Maks: Rp " . number_format($maxAllowed, 0, ',', '.') . ").")->withInput();
            }

            $payment->update([
                'amount'           => $request->amount,
                'payment_date'     => $request->payment_date,
                'method'           => $request->method,
                'reference_number' => $request->reference_number,
                'notes'            => $request->notes,
            ]);

            return redirect()->route('sales.payments.show', $payment)
                ->with('success', 'Draft Penerimaan Piutang berhasil diperbarui.');
        });
    }

    public function post(SalesPayment $payment): RedirectResponse
    {
        abort_if($payment->isPosted(), 422, 'Penerimaan piutang ini sudah berstatus posted.');

        return DB::transaction(function () use ($payment) {
            $invoice = SalesInvoice::with(['payments', 'items'])->lockForUpdate()->findOrFail($payment->sales_invoice_id);

            if ($payment->amount > $invoice->outstanding_amount + 0.01) {
                return back()->with('error', "Jumlah penerimaan (Rp " . number_format($payment->amount, 0, ',', '.') . ") melebihi sisa piutang (Rp " . number_format($invoice->outstanding_amount, 0, ',', '.') . ").");
            }

            $payment->update(['status' => 'posted']);

            $invoice->unsetRelation('payments');
            $newTotalPaid = (float) $invoice->payments()->where('status', '!=', 'draft')->sum('amount');
            $status = ($newTotalPaid >= $invoice->effective_total_amount - 0.01) ? 'paid' : ($newTotalPaid > 0 ? 'partial' : 'unpaid');
            $invoice->update(['status' => $status]);

            // Jurnal Otomatis
            $entry = $this->journalService->createFromSalesPayment($payment);
            $this->journalService->postEntry($entry);

            return redirect()->route('sales.payments.show', $payment)
                ->with('success', 'Penerimaan Piutang berhasil diposting dan Jurnal Akuntansi otomatis tercatat.');
        });
    }

    public function destroy(SalesPayment $payment): RedirectResponse
    {
        abort_if($payment->isPosted(), 403, 'Penerimaan piutang yang sudah diposting tidak dapat dihapus.');

        $payment->delete();

        return redirect()->route('sales.payments.index')
            ->with('success', 'Draft Penerimaan Piutang berhasil dihapus.');
    }

    public function exportPdf(SalesPayment $payment)
    {
        abort_if(!$payment->isPosted(), 403, 'PDF bukti penerimaan pembayaran hanya dapat dicetak setelah status Posted.');

        $payment->load(['salesInvoice.salesOrder.customer', 'user']);
        $safeNumber = $payment->voucher_number;

        return $this->streamPdf('pdf.sales-payment', compact('payment'), $safeNumber);
    }
}
