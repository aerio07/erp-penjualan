<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseOrder;
use App\Services\JournalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

use App\Models\Supplier;
use App\Traits\HasListFilters;

class PurchaseInvoiceController extends Controller
{
    use HasListFilters;

    public function __construct(private JournalService $journalService) {}

    public function index(Request $request): View
    {
        $query = PurchaseInvoice::with(['purchaseOrder.supplier', 'payments']);

        $query = $this->applySearch($query, $request, ['invoice_number', 'supplier_invoice_number', 'purchaseOrder.po_number', 'purchaseOrder.supplier.name', 'notes']);
        $query = $this->applyFilter($query, $request, 'status');
        if ($request->filled('supplier_id')) {
            $query->whereHas('purchaseOrder', function ($q) use ($request) {
                $q->where('supplier_id', $request->supplier_id);
            });
        }
        $query = $this->applyDateRange($query, $request, 'invoice_date');
        $query = $this->applySort($query, $request, ['invoice_number', 'invoice_date', 'due_date', 'total_amount', 'status', 'created_at'], 'invoice_date', 'desc');

        $perPage = (int) $request->get('per_page', 20);
        $invoices  = $query->paginate($perPage)->withQueryString();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        return view('purchase.invoices.index', compact('invoices', 'suppliers'));
    }

    public function create(Request $request): View
    {
        $selectedPoId = $request->query('po_id');
        $orders = PurchaseOrder::with(['supplier', 'items.product', 'items.goodsReceiptItems'])
            ->whereIn('status', ['confirmed', 'partially_received', 'done'])
            ->whereHas('goodsReceipts.items', function ($q) {
                $q->whereColumn('qty_received', '>', 'invoiced_qty');
            })
            ->orderByDesc('id')
            ->get();

        return view('purchase.invoices.create', compact('orders', 'selectedPoId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'purchase_order_id'       => 'required|exists:purchase_orders,id',
            'supplier_invoice_number' => 'nullable|string',
            'invoice_date'            => 'required|date',
            'due_date'                => 'required|date|after_or_equal:invoice_date',
            'tax_rate'                => 'required|numeric|min:0|max:100',
            'notes'                   => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            $po = PurchaseOrder::with(['items.product', 'items.goodsReceiptItems.goodsReceipt'])
                ->lockForUpdate()
                ->findOrFail($request->purchase_order_id);

            // 3-Way Match: Pastikan ada barang lolos QC yang BELUM pernah di-invoice
            $totalUnbilledQty = $po->items->sum('qty_unbilled');
            if ($totalUnbilledQty <= 0) {
                return back()
                    ->with('error', 'Semua barang yang diterima dalam kondisi baik pada PO ini sudah pernah diterbitkan Invoice-nya (tidak ada sisa yang belum ditagih).')
                    ->withInput();
            }

            // Tagihan HANYA dihitung dari barang lolos QC yang BELUM pernah di-invoice (qty_unbilled)
            $subtotalUnbilled = 0;
            $itemsToCreate = [];
            $taxRate = (float) $request->tax_rate;

            foreach ($po->items as $item) {
                $qtyToBill = $item->qty_unbilled;
                if ($qtyToBill <= 0) continue;

                // Split per Goods Receipt Item untuk audit trail fisik yang presisi
                $neededToInvoice = $qtyToBill;
                foreach ($item->goodsReceiptItems as $grnItem) {
                    $availableInThisGrn = max(0, $grnItem->qty_received - ($grnItem->invoiced_qty ?? 0));
                    if ($availableInThisGrn > 0 && $neededToInvoice > 0) {
                        $qtyThisGrn = min($neededToInvoice, $availableInThisGrn);
                        $grnItem->increment('invoiced_qty', $qtyThisGrn);
                        $neededToInvoice -= $qtyThisGrn;

                        $lineBase = $qtyThisGrn * $item->unit_price;
                        $disc = $lineBase * ($item->discount_percent / 100);
                        $netLine = $lineBase - $disc;
                        $lineTax = $netLine * ($taxRate / 100);
                        $subtotalUnbilled += $netLine;

                        $itemsToCreate[] = [
                            'purchase_order_item_id' => $item->id,
                            'goods_receipt_item_id'  => $grnItem->id,
                            'product_id'             => $item->product_id,
                            'qty_invoiced'           => $qtyThisGrn,
                            'unit_price'             => $item->unit_price,
                            'discount_percent'       => $item->discount_percent,
                            'discount_amount'        => $disc,
                            'subtotal'               => $netLine,
                            'tax_amount'             => $lineTax,
                        ];
                    }
                }
            }

            // Prorate Diskon Header PO secara proporsional
            $totalOrderSubtotal = $po->items->sum('subtotal');
            $headerDiscountAmount = (float) ($po->discount_amount ?? 0);
            $proratedHeaderDiscount = $totalOrderSubtotal > 0
                ? ($subtotalUnbilled / $totalOrderSubtotal) * $headerDiscountAmount
                : 0;

            $dpp = max(0, $subtotalUnbilled - $proratedHeaderDiscount);
            $taxAmount = $dpp * ($taxRate / 100);
            $totalAmount = $dpp + $taxAmount;

            $invoice = PurchaseInvoice::create([
                'invoice_number'          => $this->generateNumber(),
                'purchase_order_id'       => $po->id,
                'supplier_invoice_number' => $request->supplier_invoice_number,
                'amount'                  => $dpp,
                'tax_rate'                => $taxRate,
                'tax_amount'              => $taxAmount,
                'total_amount'            => $totalAmount,
                'invoice_date'            => $request->invoice_date,
                'due_date'                => $request->due_date,
                'status'                  => 'unpaid',
                'notes'                   => $request->notes,
            ]);

            // Simpan snapshot rincian item invoice lengkap dengan link ke GRN item dan tax_amount
            foreach ($itemsToCreate as $itemData) {
                $itemData['purchase_invoice_id'] = $invoice->id;
                PurchaseInvoiceItem::create($itemData);
            }

            // Automatic Journal Entry (Persediaan & PPN Masukan -> Hutang Usaha)
            $entry = $this->journalService->createFromPurchaseInvoice($invoice);
            $this->journalService->postEntry($entry);

            return redirect()->route('purchase.invoices.index')
                ->with('success', 'Invoice Pembelian (3-Way Match) berhasil diterbitkan dan Jurnal Akuntansi otomatis diposting.');
        });
    }

    public function show(PurchaseInvoice $invoice): View
    {
        $invoice->load(['purchaseOrder.supplier', 'items.product', 'items.goodsReceiptItem.goodsReceipt', 'payments']);

        return view('purchase.invoices.show', compact('invoice'));
    }

    public function exportPdf(PurchaseInvoice $invoice)
    {
        $invoice->load(['purchaseOrder.supplier', 'items.product', 'items.goodsReceiptItem.goodsReceipt', 'payments']);
        $pdf = Pdf::loadView('pdf.purchase-invoice', compact('invoice'));

        return $pdf->download("INV-{$invoice->invoice_number}.pdf");
    }

    private function generateNumber(): string
    {
        $prefix = 'PINV-' . date('Ym') . '-';
        $last   = PurchaseInvoice::where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('invoice_number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
