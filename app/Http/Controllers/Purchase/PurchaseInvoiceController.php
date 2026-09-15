<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
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
use App\Traits\GeneratesDocumentNumber;
use App\Traits\ExportsPdfDocument;

class PurchaseInvoiceController extends Controller
{
    use HasListFilters, GeneratesDocumentNumber, ExportsPdfDocument;

    public function __construct(
        private JournalService $journalService,
        private \App\Services\PurchaseInvoiceService $purchaseInvoiceService
    ) {}

    public function index(Request $request): View
    {
        $query = PurchaseInvoice::with(['purchaseOrder.supplier', 'goodsReceipt', 'payments', 'items']);

        $query = $this->applySearch($query, $request, ['invoice_number', 'supplier_invoice_number', 'purchaseOrder.po_number', 'purchaseOrder.supplier.name', 'goodsReceipt.receipt_number', 'notes']);
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
        // PO yang memiliki minimal 1 LPB belum diinvoice
        $orders = PurchaseOrder::with(['supplier'])
            ->whereIn('status', ['confirmed', 'partially_received', 'done'])
            ->whereHas('goodsReceipts', function ($q) {
                $q->where('is_invoiced', false);
            })
            ->orderByDesc('id')
            ->get();

        // Ambil SEMUA LPB yang belum diinvoice untuk seluruh PO yang eligible
        $availableReceipts = GoodsReceipt::whereIn('purchase_order_id', $orders->pluck('id'))
            ->where('is_invoiced', false)
            ->with([
                'items.purchaseOrderItem.product',
                'items.purchaseReturnItems.purchaseReturn',
                'warehouse'
            ])
            ->orderByDesc('id')
            ->get();

        $selectedPoId = $request->query('po_id');
        $selectedGrnId = $request->query('grn_id');

        // Data PO lengkap untuk kalkulasi diskon header & PPN di frontend
        $ordersData = PurchaseOrder::with(['supplier', 'items.product', 'items.goodsReceiptItems', 'invoices.items'])
            ->whereIn('id', $orders->pluck('id'))
            ->get()
            ->map(function ($po) {
                $usedDiscount = 0;
                $usedTax = 0;
                foreach ($po->invoices as $inv) {
                    $invBaseSubtotal = $inv->items->sum(function ($it) {
                        $lineBase = $it->qty_invoiced * $it->unit_price;
                        $lineDisc = $lineBase * (($it->discount_percent ?? 0) / 100);
                        return $lineBase - $lineDisc;
                    });
                    $usedDiscount += max(0, round($invBaseSubtotal - $inv->amount, 2));
                    $usedTax += (float) $inv->tax_amount;
                }
                $po->used_header_discount = round($usedDiscount, 2);
                $po->used_tax_amount = round($usedTax, 2);
                return $po;
            })
            ->keyBy('id');

        return view('purchase.invoices.create', compact('orders', 'availableReceipts', 'selectedPoId', 'selectedGrnId', 'ordersData'));
    }

    public function store(Request $request): RedirectResponse
    {
        // Auto-resolve goods_receipt_id jika hanya ada 1 LPB yang belum di-invoice
        if (!$request->filled('goods_receipt_id') && $request->filled('purchase_order_id')) {
            $singleReceipt = GoodsReceipt::where('purchase_order_id', $request->purchase_order_id)
                ->where('is_invoiced', false)
                ->first();
            if ($singleReceipt) {
                $request->merge(['goods_receipt_id' => $singleReceipt->id]);
            }
        }

        $request->validate([
            'purchase_order_id'       => 'required|exists:purchase_orders,id',
            'goods_receipt_id'        => 'required|exists:goods_receipts,id',
            'supplier_invoice_number' => 'nullable|string',
            'invoice_date'            => 'required|date',
            'due_date'                => 'required|date|after_or_equal:invoice_date',
            'tax_rate'                => 'required|numeric|min:0|max:100',
            'notes'                   => 'nullable|string',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                // Lock GRN untuk cegah race condition
                $grn = GoodsReceipt::with(['items.purchaseOrderItem'])
                    ->lockForUpdate()
                    ->findOrFail($request->goods_receipt_id);

                // Guard: pastikan LPB ini milik PO yang dipilih
                if ((int) $grn->purchase_order_id !== (int) $request->purchase_order_id) {
                    throw new \InvalidArgumentException('LPB yang dipilih bukan milik Purchase Order yang dipilih.');
                }

                // Guard: pastikan LPB belum pernah dipakai untuk invoice lain
                if ($grn->is_invoiced) {
                    throw new \InvalidArgumentException('LPB ini sudah pernah digunakan untuk invoice lain. Satu LPB hanya bisa dipakai untuk satu invoice.');
                }

                $invoice = $this->purchaseInvoiceService->createFromGoodsReceipt($grn, [
                    'supplier_invoice_number' => $request->supplier_invoice_number,
                    'invoice_date'            => $request->invoice_date,
                    'due_date'                => $request->due_date,
                    'tax_rate'                => $request->tax_rate,
                    'notes'                   => $request->notes,
                ], asDraft: true);

                return redirect()->route('purchase.invoices.index')
                    ->with('success', 'Invoice Pembelian #' . $invoice->invoice_number . ' berhasil disimpan sebagai Draft.');
            });
        } catch (\InvalidArgumentException|\App\Exceptions\PeriodClosedException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show(PurchaseInvoice $invoice): View
    {
        $invoice->load(['purchaseOrder.supplier', 'goodsReceipt', 'items.product', 'items.goodsReceiptItem.goodsReceipt', 'payments']);

        return view('purchase.invoices.show', compact('invoice'));
    }

    public function edit(PurchaseInvoice $invoice): View|RedirectResponse
    {
        if ($invoice->status !== 'draft') {
            return redirect()->route('purchase.invoices.show', $invoice)
                ->with('error', 'Hanya invoice berstatus Draft yang dapat diedit.');
        }

        $invoice->load(['purchaseOrder.supplier', 'goodsReceipt', 'items.product', 'items.goodsReceiptItem']);

        return view('purchase.invoices.edit', compact('invoice'));
    }

    public function update(Request $request, PurchaseInvoice $invoice): RedirectResponse
    {
        if ($invoice->status !== 'draft') {
            return redirect()->route('purchase.invoices.show', $invoice)
                ->with('error', 'Hanya invoice berstatus Draft yang dapat diedit.');
        }

        $request->validate([
            'supplier_invoice_number' => 'nullable|string|max:100',
            'invoice_date'            => 'required|date',
            'due_date'                => 'required|date|after_or_equal:invoice_date',
            'tax_rate'                => 'required|numeric|min:0|max:100',
            'notes'                   => 'nullable|string',
        ]);

        try {
            return DB::transaction(function () use ($request, $invoice) {
                $taxRate = (float) $request->tax_rate;
                $dpp = (float) $invoice->amount;

                $isIntegerRupiah = (round($invoice->total_amount) == $invoice->total_amount);
                $dec = $isIntegerRupiah ? 0 : 2;
                $taxAmount = round($dpp * ($taxRate / 100), $dec);
                $totalAmount = round($dpp + $taxAmount, $dec);

                $invoice->update([
                    'supplier_invoice_number' => $request->supplier_invoice_number,
                    'invoice_date'            => $request->invoice_date,
                    'due_date'                => $request->due_date,
                    'tax_rate'                => $taxRate,
                    'tax_amount'              => $taxAmount,
                    'total_amount'            => $totalAmount,
                    'notes'                   => $request->notes,
                ]);

                // Update tax pada item jika tax_rate berubah
                $items = $invoice->items;
                $allocatedTax = 0;
                $itemCount = $items->count();
                foreach ($items as $idx => $item) {
                    $isLast = ($idx === $itemCount - 1);
                    $itemTax = $isLast ? max(0, round($taxAmount - $allocatedTax, $dec)) : round($item->subtotal * ($taxRate / 100), $dec);
                    $allocatedTax += $itemTax;
                    $item->update(['tax_amount' => $itemTax]);
                }

                return redirect()->route('purchase.invoices.show', $invoice)
                    ->with('success', "Draft Invoice Pembelian #{$invoice->invoice_number} berhasil diperbarui.");
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui draft invoice: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(PurchaseInvoice $invoice): RedirectResponse
    {
        if ($invoice->status !== 'draft') {
            return redirect()->route('purchase.invoices.show', $invoice)
                ->with('error', 'Hanya invoice berstatus Draft yang dapat dihapus.');
        }

        try {
            return DB::transaction(function () use ($invoice) {
                $invoiceNumber = $invoice->invoice_number;

                // Lepaskan kaitan LPB (Goods Receipt)
                if ($invoice->goods_receipt_id) {
                    GoodsReceipt::where('id', $invoice->goods_receipt_id)->update([
                        'is_invoiced'         => false,
                        'purchase_invoice_id' => null,
                    ]);
                }
                GoodsReceipt::where('purchase_invoice_id', $invoice->id)->update([
                    'is_invoiced'         => false,
                    'purchase_invoice_id' => null,
                ]);

                // Hapus items lalu invoice
                $invoice->items()->delete();
                $invoice->delete();

                return redirect()->route('purchase.invoices.index')
                    ->with('success', "Draft Invoice Pembelian #{$invoiceNumber} berhasil dihapus dan LPB terkait telah dilepaskan.");
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus draft invoice: ' . $e->getMessage());
        }
    }

    public function post(PurchaseInvoice $invoice): RedirectResponse
    {
        if ($invoice->status !== 'draft') {
            return redirect()->route('purchase.invoices.show', $invoice)
                ->with('error', 'Hanya invoice berstatus Draft yang dapat diposting.');
        }

        try {
            return DB::transaction(function () use ($invoice) {
                $invoice = PurchaseInvoice::lockForUpdate()->findOrFail($invoice->id);
                if ($invoice->status !== 'draft') {
                    throw new \InvalidArgumentException('Invoice ini sudah pernah diposting.');
                }

                $this->purchaseInvoiceService->postInvoice($invoice);

                return redirect()->route('purchase.invoices.show', $invoice)
                    ->with('success', "Invoice Pembelian #{$invoice->invoice_number} berhasil diposting dan Jurnal Akuntansi otomatis diterbitkan.");
            });
        } catch (\InvalidArgumentException|\App\Exceptions\PeriodClosedException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function exportPdf(PurchaseInvoice $invoice)
    {
        $invoice->load(['purchaseOrder.supplier', 'goodsReceipt', 'items.product', 'items.goodsReceiptItem.goodsReceipt', 'payments']);
        $safeNumber = str_replace(['/', '\\'], '-', $invoice->invoice_number);
        $filename = str_starts_with($safeNumber, 'PINV-') ? $safeNumber : "PINV-{$safeNumber}";

        return $this->streamPdf('pdf.purchase-invoice', compact('invoice'), $filename);
    }

    private function generateNumber(): string
    {
        return $this->generateDocumentNumber(PurchaseInvoice::class, 'invoice_number', 'PINV');
    }
}
