<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Services\JournalService;
use App\Traits\HasListFilters;
use App\Traits\GeneratesDocumentNumber;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SalesInvoiceController extends Controller
{
    use HasListFilters, GeneratesDocumentNumber;

    public function __construct(
        private JournalService $journalService,
        private \App\Services\SalesInvoiceService $salesInvoiceService
    ) {}

    public function index(Request $request): View
    {
        $query = SalesInvoice::with(['salesOrder.customer', 'delivery', 'payments', 'items']);

        $query = $this->applySearch($query, $request, ['invoice_number', 'salesOrder.so_number', 'salesOrder.customer.name', 'delivery.delivery_number', 'notes']);
        $query = $this->applyFilter($query, $request, 'status');
        if ($request->filled('customer_id')) {
            $query->whereHas('salesOrder', function ($q) use ($request) {
                $q->where('customer_id', $request->customer_id);
            });
        }
        $query = $this->applyDateRange($query, $request, 'invoice_date');
        $query = $this->applySort($query, $request, ['invoice_number', 'invoice_date', 'due_date', 'total_amount', 'status', 'created_at'], 'invoice_date', 'desc');

        $perPage = (int) $request->get('per_page', 20);
        $invoices  = $query->paginate($perPage)->withQueryString();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('sales.invoices.index', compact('invoices', 'customers'));
    }

    public function create(Request $request): View
    {
        // SO yang memiliki minimal 1 Surat Jalan (Delivery) yang SUDAH DITERIMA (delivered) dan belum diinvoice
        $orders = SalesOrder::with(['customer'])
            ->whereIn('status', ['confirmed', 'partially_delivered', 'done'])
            ->whereHas('deliveries', function ($q) {
                $q->where('is_invoiced', false)->where('status', 'delivered');
            })
            ->orderByDesc('id')
            ->get();

        // Ambil SEMUA Delivery (Surat Jalan) yang sudah diterima dan belum diinvoice untuk seluruh SO yang eligible
        $availableDeliveries = Delivery::whereIn('sales_order_id', $orders->pluck('id'))
            ->where('is_invoiced', false)
            ->where('status', 'delivered')
            ->with([
                'items.salesOrderItem.product',
                'items.salesReturnItems.salesReturn',
                'warehouse'
            ])
            ->orderByDesc('id')
            ->get();

        $selectedSoId = $request->query('so_id');
        $selectedDeliveryId = $request->query('delivery_id');

        // Data SO lengkap untuk kalkulasi diskon header & PPN di frontend
        $ordersData = SalesOrder::with(['customer', 'items.product', 'items.deliveryItems', 'invoices.items'])
            ->whereIn('id', $orders->pluck('id'))
            ->get()
            ->map(function ($so) {
                $usedDiscount = 0;
                $usedTax = 0;
                foreach ($so->invoices as $inv) {
                    $invBaseSubtotal = $inv->items->sum(function ($it) {
                        $lineBase = $it->qty_invoiced * $it->unit_price;
                        $lineDisc = $lineBase * (($it->discount_percent ?? 0) / 100);
                        return $lineBase - $lineDisc;
                    });
                    $usedDiscount += max(0, round($invBaseSubtotal - $inv->amount, 2));
                    $usedTax += (float) $inv->tax_amount;
                }
                $so->used_header_discount = round($usedDiscount, 2);
                $so->used_tax_amount = round($usedTax, 2);
                return $so;
            })
            ->keyBy('id');

        return view('sales.invoices.create', compact('orders', 'availableDeliveries', 'selectedSoId', 'selectedDeliveryId', 'ordersData'));
    }

    public function store(Request $request): RedirectResponse
    {
        // Auto-resolve delivery_id jika hanya ada 1 delivery yang belum di-invoice
        if (!$request->filled('delivery_id') && $request->filled('sales_order_id')) {
            $singleDelivery = Delivery::where('sales_order_id', $request->sales_order_id)
                ->where('is_invoiced', false)
                ->first();
            if ($singleDelivery) {
                if ($singleDelivery->status !== 'delivered') {
                    $singleDelivery->update(['status' => 'delivered']);
                }
                $request->merge(['delivery_id' => $singleDelivery->id]);
            }
        }

        $request->validate([
            'sales_order_id' => 'required|exists:sales_orders,id',
            'delivery_id'    => 'required|exists:deliveries,id',
            'invoice_date'   => 'required|date',
            'due_date'       => 'required|date|after_or_equal:invoice_date',
            'tax_rate'       => 'required|numeric|min:0|max:100',
            'notes'          => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            // Lock Delivery untuk cegah race condition
            $delivery = Delivery::with(['items.salesOrderItem.product'])
                ->lockForUpdate()
                ->findOrFail($request->delivery_id);

            // Guard: pastikan Surat Jalan ini milik SO yang dipilih
            if ((int) $delivery->sales_order_id !== (int) $request->sales_order_id) {
                return back()
                    ->with('error', 'Surat Jalan yang dipilih bukan milik Sales Order yang dipilih.')
                    ->withInput();
            }

            // Guard: pastikan Surat Jalan belum pernah dipakai untuk invoice lain
            if ($delivery->is_invoiced) {
                return back()
                    ->with('error', 'Surat Jalan ini sudah pernah digunakan untuk invoice lain. Satu Surat Jalan hanya bisa dipakai untuk satu invoice.')
                    ->withInput();
            }

            // Guard: pastikan Surat Jalan sudah berstatus delivered (sudah diterima)
            if ($delivery->status !== 'delivered') {
                return back()
                    ->with('error', "Surat Jalan #{$delivery->delivery_number} masih dalam pengiriman (In Transit). Barang harus dikonfirmasi telah diterima pembeli sebelum invoice diterbitkan.")
                    ->withInput();
            }

            try {
                $invoice = $this->salesInvoiceService->createFromDelivery($delivery, [
                    'invoice_date' => $request->invoice_date,
                    'due_date'     => $request->due_date,
                    'tax_rate'     => $request->tax_rate,
                    'notes'        => $request->notes,
                ]);

                return redirect()->route('sales.invoices.index')
                    ->with('success', "Invoice Penjualan #{$invoice->invoice_number} berhasil diterbitkan dari Surat Jalan #{$delivery->delivery_number} dan Jurnal Akuntansi otomatis diposting.");
            } catch (\InvalidArgumentException $e) {
                return back()->with('error', $e->getMessage())->withInput();
            }
        });
    }

    public function show(SalesInvoice $invoice): View
    {
        $invoice->load(['salesOrder.customer', 'delivery.warehouse', 'items.product', 'items.deliveryItem.delivery', 'payments']);

        return view('sales.invoices.show', compact('invoice'));
    }

    public function updateTaxInvoice(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        $request->validate([
            'tax_invoice_number' => 'nullable|string|max:50',
        ], [
            'tax_invoice_number.max' => 'Nomor Faktur Pajak maksimal 50 karakter.',
        ]);

        $invoice->update([
            'tax_invoice_number' => $request->filled('tax_invoice_number') ? trim($request->tax_invoice_number) : null,
        ]);

        return back()->with('success', 'Nomor Faktur Pajak berhasil diperbarui.');
    }

    public function exportPdf(SalesInvoice $invoice)
    {
        $invoice->load(['salesOrder.customer', 'delivery.warehouse', 'items.product', 'items.deliveryItem.delivery', 'payments']);
        $pdf = Pdf::loadView('pdf.sales-invoice', compact('invoice'));

        return $pdf->download("SINV-{$invoice->invoice_number}.pdf");
    }

    private function generateNumber(): string
    {
        return $this->generateDocumentNumber(SalesInvoice::class, 'invoice_number', 'SINV');
    }
}
