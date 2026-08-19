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
        $query = $this->applyDateRange($query, $request, 'payment_date');
        $query = $this->applySort($query, $request, ['payment_date', 'amount', 'method', 'created_at'], 'payment_date', 'desc');

        $perPage = (int) $request->get('per_page', 20);
        $payments = $query->paginate($perPage)->withQueryString();

        return view('purchase.payments.index', compact('payments'));
    }

    public function create(Request $request): View
    {
        $selectedInvoiceId = $request->query('invoice_id');
        $unpaidInvoices = PurchaseInvoice::with(['purchaseOrder.supplier', 'payments'])
            ->where('status', '!=', 'paid')
            ->orderByDesc('id')
            ->get();

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
        ]);

        $invoice = PurchaseInvoice::findOrFail($request->purchase_invoice_id);

        if ($request->amount > $invoice->outstanding_amount + 0.01) {
            return back()->with('error', "Jumlah pembayaran (Rp " . number_format($request->amount, 0, ',', '.') . ") melebihi sisa hutang (Rp " . number_format($invoice->outstanding_amount, 0, ',', '.') . ").")->withInput();
        }

        DB::transaction(function () use ($request, $invoice) {
            $payment = PurchasePayment::create([
                'purchase_invoice_id' => $invoice->id,
                'user_id'             => Auth::id(),
                'amount'              => $request->amount,
                'payment_date'        => $request->payment_date,
                'method'              => $request->method,
                'reference_number'    => $request->reference_number,
                'notes'               => $request->notes,
            ]);

            // Update status invoice
            $newTotalPaid = $invoice->total_paid + $request->amount;
            $status = ($newTotalPaid >= $invoice->total_amount - 0.01) ? 'paid' : 'partial';
            $invoice->update(['status' => $status]);

            // Jurnal Otomatis (Hutang Usaha -> Kas/Bank)
            $entry = $this->journalService->createFromPurchasePayment($payment);
            $this->journalService->postEntry($entry);
        });

        return redirect()->route('purchase.payments.index')
            ->with('success', 'Pembayaran Hutang berhasil dicatat dan Jurnal Akuntansi otomatis diposting.');
    }

    public function show(PurchasePayment $payment): View
    {
        $payment->load(['purchaseInvoice.purchaseOrder.supplier', 'user']);

        return view('purchase.payments.show', compact('payment'));
    }
}
