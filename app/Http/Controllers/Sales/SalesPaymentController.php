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

class SalesPaymentController extends Controller
{
    use HasListFilters;

    public function __construct(private JournalService $journalService) {}

    public function index(Request $request): View
    {
        $query = SalesPayment::with(['salesInvoice.salesOrder.customer', 'user']);

        $query = $this->applySearch($query, $request, ['reference_number', 'salesInvoice.invoice_number', 'salesInvoice.salesOrder.customer.name', 'notes']);
        $query = $this->applyFilter($query, $request, 'method');
        $query = $this->applyDateRange($query, $request, 'payment_date');
        $query = $this->applySort($query, $request, ['payment_date', 'amount', 'method', 'created_at'], 'payment_date', 'desc');

        $perPage = (int) $request->get('per_page', 20);
        $payments = $query->paginate($perPage)->withQueryString();

        return view('sales.payments.index', compact('payments'));
    }

    public function create(Request $request): View
    {
        $selectedInvoiceId = $request->query('invoice_id');
        $unpaidInvoices = SalesInvoice::with(['salesOrder.customer', 'payments', 'items'])
            ->where('status', '!=', 'paid')
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

        $invoice = SalesInvoice::findOrFail($request->sales_invoice_id);

        if ($request->amount > $invoice->outstanding_amount + 0.01) {
            return back()->with('error', "Jumlah penerimaan (Rp " . number_format($request->amount, 0, ',', '.') . ") melebihi sisa piutang (Rp " . number_format($invoice->outstanding_amount, 0, ',', '.') . ").")->withInput();
        }

        DB::transaction(function () use ($request, $invoice) {
            $payment = SalesPayment::create([
                'sales_invoice_id' => $invoice->id,
                'user_id'          => Auth::id(),
                'amount'           => $request->amount,
                'payment_date'     => $request->payment_date,
                'method'           => $request->method,
                'reference_number' => $request->reference_number,
                'notes'            => $request->notes,
            ]);

            // Update status invoice
            $newTotalPaid = $invoice->total_paid + $request->amount;
            $status = ($newTotalPaid >= $invoice->effective_total_amount - 0.01) ? 'paid' : 'partial';
            $invoice->update(['status' => $status]);

            // Automatic Journal Entry (Kas/Bank -> Piutang Usaha)
            $entry = $this->journalService->createFromSalesPayment($payment);
            $this->journalService->postEntry($entry);
        });

        return redirect()->route('sales.payments.index')
            ->with('success', 'Penerimaan Piutang berhasil dicatat dan Jurnal Akuntansi otomatis diposting.');
    }

    public function show(SalesPayment $payment): View
    {
        $payment->load(['salesInvoice.salesOrder.customer', 'user']);

        return view('sales.payments.show', compact('payment'));
    }
}
