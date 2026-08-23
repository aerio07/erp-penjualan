<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Services\JournalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

use App\Models\Customer;
use App\Traits\HasListFilters;

class SalesInvoiceController extends Controller
{
    use HasListFilters;

    public function __construct(private JournalService $journalService) {}

    public function index(Request $request): View
    {
        $query = SalesInvoice::with(['salesOrder.customer', 'payments', 'items']);

        $query = $this->applySearch($query, $request, ['invoice_number', 'salesOrder.so_number', 'salesOrder.customer.name', 'notes']);
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
        $selectedSoId = $request->query('so_id');
        $orders = SalesOrder::with(['customer', 'items.product', 'items.deliveryItems'])
            ->whereIn('status', ['confirmed', 'partially_delivered', 'done'])
            ->whereHas('deliveries.items', function ($q) {
                $q->whereColumn('qty_delivered', '>', 'invoiced_qty');
            })
            ->orderByDesc('id')
            ->get()
            ->filter(fn($order) => $this->availableQtyForInvoice($order) > 0)
            ->values();

        return view('sales.invoices.create', compact('orders', 'selectedSoId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'sales_order_id' => 'required|exists:sales_orders,id',
            'invoice_date'   => 'required|date',
            'due_date'       => 'required|date|after_or_equal:invoice_date',
            'tax_rate'       => 'required|numeric|min:0|max:100',
            'notes'          => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            $so = SalesOrder::with(['items.product', 'items.deliveryItems.delivery'])
                ->lockForUpdate()
                ->findOrFail($request->sales_order_id);

            // 3-Way Match: Pastikan ada barang terkirim yang BELUM pernah di-invoice
            $totalUnbilledQty = $this->availableQtyForInvoice($so);
            if ($totalUnbilledQty <= 0) {
                return back()
                    ->with('error', 'Semua barang yang sudah dikirim pada Sales Order ini sudah pernah diterbitkan fakturnya (tidak ada sisa yang belum ditagih).')
                    ->withInput();
            }

            // Tagihan HANYA dihitung dari barang yang sudah dikirim (qty_unbilled)
            $subtotalUnbilled = 0;
            $cogs = 0;
            $itemsToCreate = [];
            $taxRate = (float) $request->tax_rate;

            foreach ($so->items as $item) {
                $qtyToBill = (int) $item->deliveryItems->sum(fn($delItem) => $delItem->qty_available_for_invoice);
                if ($qtyToBill <= 0) continue;

                // Split per Delivery Item untuk audit trail fisik yang presisi
                $neededToInvoice = $qtyToBill;
                foreach ($item->deliveryItems as $delItem) {
                    $availableInThisDel = $delItem->qty_available_for_invoice;
                    if ($availableInThisDel > 0 && $neededToInvoice > 0) {
                        $qtyThisDel = min($neededToInvoice, $availableInThisDel);
                        $delItem->increment('invoiced_qty', $qtyThisDel);
                        $neededToInvoice -= $qtyThisDel;

                        $lineBase = $qtyThisDel * $item->unit_price;
                        $disc = $lineBase * ($item->discount_percent / 100);
                        $netLine = $lineBase - $disc;
                        $lineTax = $netLine * ($taxRate / 100);

                        $itemCogs = $qtyThisDel * ($item->product->purchase_price ?? 0);

                        $subtotalUnbilled += $netLine;
                        $cogs += $itemCogs;

                        $itemsToCreate[] = [
                            'sales_order_item_id' => $item->id,
                            'delivery_item_id'    => $delItem->id,
                            'product_id'          => $item->product_id,
                            'qty_invoiced'        => $qtyThisDel,
                            'unit_price'          => $item->unit_price,
                            'discount_percent'    => $item->discount_percent,
                            'discount_amount'     => $disc,
                            'subtotal'            => $netLine,
                            'tax_amount'          => $lineTax,
                            'cogs_amount'         => $itemCogs,
                        ];
                    }
                }
            }

            // Prorate Diskon Header SO secara proporsional
            $totalOrderSubtotal = $so->items->sum('subtotal');
            $headerDiscountAmount = (float) ($so->discount_amount ?? 0);
            $proratedHeaderDiscount = $totalOrderSubtotal > 0
                ? ($subtotalUnbilled / $totalOrderSubtotal) * $headerDiscountAmount
                : 0;

            $dpp = max(0, $subtotalUnbilled - $proratedHeaderDiscount);
            $taxAmount = $dpp * ($taxRate / 100);
            $totalAmount = $dpp + $taxAmount;

            $invoice = SalesInvoice::create([
                'invoice_number' => $this->generateNumber(),
                'sales_order_id' => $so->id,
                'amount'         => $dpp,
                'tax_rate'       => $taxRate,
                'tax_amount'     => $taxAmount,
                'total_amount'   => $totalAmount,
                'invoice_date'   => $request->invoice_date,
                'due_date'       => $request->due_date,
                'status'         => 'unpaid',
                'notes'          => $request->notes,
            ]);

            // Simpan snapshot rincian item invoice lengkap dengan link ke Delivery Item dan tax_amount
            foreach ($itemsToCreate as $itemData) {
                $itemData['sales_invoice_id'] = $invoice->id;
                SalesInvoiceItem::create($itemData);
            }

            // Automatic Journal Entry (Piutang -> Penjualan, PPN Keluaran, serta HPP -> Persediaan)
            $entry = $this->journalService->createFromSalesInvoice($invoice, $cogs);
            $this->journalService->postEntry($entry);

            return redirect()->route('sales.invoices.index')
                ->with('success', 'Invoice Penjualan (3-Way Match) berhasil diterbitkan dan Jurnal Akuntansi otomatis diposting.');
        });
    }

    public function show(SalesInvoice $invoice): View
    {
        $invoice->load(['salesOrder.customer', 'items.product', 'items.deliveryItem.delivery', 'payments']);

        return view('sales.invoices.show', compact('invoice'));
    }

    public function exportPdf(SalesInvoice $invoice)
    {
        $invoice->load(['salesOrder.customer', 'items.product', 'items.deliveryItem.delivery', 'payments']);
        $pdf = Pdf::loadView('pdf.sales-invoice', compact('invoice'));

        return $pdf->download("SINV-{$invoice->invoice_number}.pdf");
    }

    private function generateNumber(): string
    {
        $prefix = 'SINV-' . date('Ym') . '-';
        $last   = SalesInvoice::where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('invoice_number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    private function availableQtyForInvoice(SalesOrder $order): int
    {
        return (int) $order->items->sum(
            fn($item) => $item->deliveryItems->sum(
                fn($deliveryItem) => $deliveryItem->qty_available_for_invoice
            )
        );
    }
}
