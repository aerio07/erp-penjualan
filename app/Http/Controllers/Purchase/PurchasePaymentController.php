<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use App\Models\PurchasePayment;
use App\Services\JournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

use App\Traits\HasListFilters;

class PurchasePaymentController extends Controller
{
    use HasListFilters;

    public function __construct(private JournalService $journalService) {}

    public function index(Request $request): View
    {
        $query = PurchasePayment::with(['purchaseInvoice.purchaseOrder.supplier', 'user']);

        $query = $this->applySearch($query, $request, ['reference_number', 'purchaseInvoice.invoice_number', 'purchaseInvoice.purchaseOrder.supplier.name', 'notes']);
        $query = $this->applyFilter($query, $request, 'method');
        $query = $this->applyFilter($query, $request, 'status');
        $query = $this->applyDateRange($query, $request, 'payment_date');
        $query = $this->applySort($query, $request, ['payment_date', 'amount', 'status', 'method', 'created_at'], 'payment_date', 'desc');

        $perPage = (int) $request->get('per_page', 20);
        $payments = $query->paginate($perPage)->withQueryString();

        return view('purchase.payments.index', compact('payments'));
    }

    public function create(Request $request): View
    {
        $selectedInvoiceId = $request->query('invoice_id');
        $unpaidInvoices = PurchaseInvoice::with(['purchaseOrder.supplier', 'payments', 'items'])
            ->whereIn('status', ['unpaid', 'partial'])
            ->orderByDesc('id')
            ->get()
            ->filter(fn($invoice) => $invoice->outstanding_amount > 0)
            ->values();

        return view('purchase.payments.create', compact('unpaidInvoices', 'selectedInvoiceId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'purchase_invoice_id' => 'required|exists:purchase_invoices,id',
            'amount'              => 'required|numeric|min:1',
            'payment_date'        => 'required|date',
            'method'              => 'required|in:cash,transfer,giro,cek',
            'reference_number'    => 'nullable|string',
            'notes'               => 'nullable|string',
            'action'              => 'nullable|in:draft,post',
        ]);

        $action = $request->input('action', 'post');

        return DB::transaction(function () use ($request, $action) {
            $invoice = PurchaseInvoice::with(['payments', 'items'])->lockForUpdate()->findOrFail($request->purchase_invoice_id);

            if ($invoice->status === 'draft') {
                return back()->with('error', "Invoice masih berstatus Draft dan belum diposting. Pembayaran tidak dapat diproses.")->withInput();
            }

            if ($request->amount > $invoice->outstanding_amount + 0.01) {
                return back()->with('error', "Jumlah pembayaran (Rp " . number_format($request->amount, 0, ',', '.') . ") melebihi sisa hutang (Rp " . number_format($invoice->outstanding_amount, 0, ',', '.') . ").")->withInput();
            }

            $payment = PurchasePayment::create([
                'purchase_invoice_id' => $invoice->id,
                'user_id'             => Auth::id(),
                'amount'              => $request->amount,
                'status'              => ($action === 'post') ? 'posted' : 'draft',
                'payment_date'        => $request->payment_date,
                'method'              => $request->method,
                'reference_number'    => $request->reference_number,
                'notes'               => $request->notes,
            ]);

            if ($action === 'post') {
                // Update status invoice berdasarkan total pembayaran terposting
                $invoice->unsetRelation('payments');
                $newTotalPaid = (float) $invoice->payments()->where('status', '!=', 'draft')->sum('amount');
                $status = ($newTotalPaid >= $invoice->effective_total_amount - 0.01) ? 'paid' : ($newTotalPaid > 0 ? 'partial' : 'unpaid');
                $invoice->update(['status' => $status]);

                // Jurnal Otomatis (Hutang Usaha -> Kas/Bank)
                $entry = $this->journalService->createFromPurchasePayment($payment);
                $this->journalService->postEntry($entry);

                return redirect()->route('purchase.payments.index')
                    ->with('success', 'Pembayaran Hutang berhasil dicatat dan diposting ke Jurnal Akuntansi.');
            }

            return redirect()->route('purchase.payments.index')
                ->with('success', 'Draft Pembayaran Hutang berhasil disimpan. Silakan posting pembayaran jika sudah final.');
        });
    }

    public function show(PurchasePayment $payment): View
    {
        $payment->load(['purchaseInvoice.purchaseOrder.supplier', 'user']);

        return view('purchase.payments.show', compact('payment'));
    }

    public function edit(PurchasePayment $payment): View
    {
        abort_if($payment->isPosted(), 403, 'Pembayaran yang sudah diposting tidak dapat diedit.');

        $payment->load(['purchaseInvoice.purchaseOrder.supplier', 'purchaseInvoice.payments']);
        $invoice = $payment->purchaseInvoice;

        $otherPaid = $invoice->payments->where('id', '!=', $payment->id)->where('status', '!=', 'draft')->sum('amount');
        $maxAllowed = max(0, $invoice->effective_total_amount - $otherPaid);

        return view('purchase.payments.edit', compact('payment', 'invoice', 'maxAllowed', 'otherPaid'));
    }

    public function update(Request $request, PurchasePayment $payment): RedirectResponse
    {
        abort_if($payment->isPosted(), 403, 'Pembayaran yang sudah diposting tidak dapat diubah.');

        $request->validate([
            'amount'           => 'required|numeric|min:1',
            'payment_date'     => 'required|date',
            'method'           => 'required|in:cash,transfer,giro,cek',
            'reference_number' => 'nullable|string',
            'notes'            => 'nullable|string',
            'action'           => 'nullable|in:update,post',
        ]);

        $action = $request->input('action', 'update');

        return DB::transaction(function () use ($request, $payment, $action) {
            $invoice = PurchaseInvoice::with(['payments', 'items'])->lockForUpdate()->findOrFail($payment->purchase_invoice_id);

            $otherPaid = $invoice->payments()->where('id', '!=', $payment->id)->where('status', '!=', 'draft')->sum('amount');
            $maxAllowed = $invoice->effective_total_amount - $otherPaid;

            if ($request->amount > $maxAllowed + 0.01) {
                return back()->with('error', "Jumlah pembayaran (Rp " . number_format($request->amount, 0, ',', '.') . ") melebihi sisa hutang tagihan (Maks: Rp " . number_format($maxAllowed, 0, ',', '.') . ").")->withInput();
            }

            $payment->update([
                'amount'           => $request->amount,
                'status'           => ($action === 'post') ? 'posted' : 'draft',
                'payment_date'     => $request->payment_date,
                'method'           => $request->method,
                'reference_number' => $request->reference_number,
                'notes'            => $request->notes,
            ]);

            if ($action === 'post') {
                // Update status invoice
                $invoice->unsetRelation('payments');
                $newTotalPaid = (float) $invoice->payments()->where('status', '!=', 'draft')->sum('amount');
                $status = ($newTotalPaid >= $invoice->effective_total_amount - 0.01) ? 'paid' : ($newTotalPaid > 0 ? 'partial' : 'unpaid');
                $invoice->update(['status' => $status]);

                // Posting Jurnal Otomatis Akuntansi
                $entry = $this->journalService->createFromPurchasePayment($payment);
                $this->journalService->postEntry($entry);

                return redirect()->route('purchase.payments.show', $payment)
                    ->with('success', 'Pembayaran Hutang berhasil diperbarui dan diposting.');
            }

            return redirect()->route('purchase.payments.show', $payment)
                ->with('success', 'Draft Pembayaran Hutang berhasil diperbarui.');
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

            $payment->update(['status' => 'posted']);

            $invoice->unsetRelation('payments');
            $newTotalPaid = (float) $invoice->payments()->where('status', '!=', 'draft')->sum('amount');
            $status = ($newTotalPaid >= $invoice->effective_total_amount - 0.01) ? 'paid' : ($newTotalPaid > 0 ? 'partial' : 'unpaid');
            $invoice->update(['status' => $status]);

            // Jurnal Otomatis
            $entry = $this->journalService->createFromPurchasePayment($payment);
            $this->journalService->postEntry($entry);

            return back()->with('success', 'Pembayaran Hutang berhasil diposting dan Jurnal Akuntansi otomatis tercatat.');
        });
    }

    public function destroy(PurchasePayment $payment): RedirectResponse
    {
        abort_if($payment->isPosted(), 403, 'Pembayaran yang sudah diposting tidak dapat dihapus.');

        $payment->delete();

        return redirect()->route('purchase.payments.index')
            ->with('success', 'Draft Pembayaran Hutang berhasil dihapus.');
    }
}
