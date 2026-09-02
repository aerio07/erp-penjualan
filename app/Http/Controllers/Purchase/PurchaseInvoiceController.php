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

class PurchaseInvoiceController extends Controller
{
    use HasListFilters;

    public function __construct(private JournalService $journalService) {}

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
        $request->validate([
            'purchase_order_id'       => 'required|exists:purchase_orders,id',
            'goods_receipt_id'        => 'required|exists:goods_receipts,id',
            'supplier_invoice_number' => 'nullable|string',
            'invoice_date'            => 'required|date',
            'due_date'                => 'required|date|after_or_equal:invoice_date',
            'tax_rate'                => 'required|numeric|min:0|max:100',
            'notes'                   => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            // Lock GRN untuk cegah race condition
            $grn = GoodsReceipt::with(['items.purchaseOrderItem'])
                ->lockForUpdate()
                ->findOrFail($request->goods_receipt_id);

            // Guard: pastikan LPB ini milik PO yang dipilih
            if ((int) $grn->purchase_order_id !== (int) $request->purchase_order_id) {
                return back()
                    ->with('error', 'LPB yang dipilih bukan milik Purchase Order yang dipilih.')
                    ->withInput();
            }

            // Guard: pastikan LPB belum pernah dipakai untuk invoice lain
            if ($grn->is_invoiced) {
                return back()
                    ->with('error', 'LPB ini sudah pernah digunakan untuk invoice lain. Satu LPB hanya bisa dipakai untuk satu invoice.')
                    ->withInput();
            }

            $po = PurchaseOrder::with(['items', 'invoices.items'])->lockForUpdate()->findOrFail($request->purchase_order_id);
            $taxRate = (float) $request->tax_rate;

            // Hitung subtotal dari item di LPB ini (qty_received bersih dikurangi retur pre-invoice jika ada)
            $subtotalGrn = 0;
            $itemsToCreate = [];

            foreach ($grn->items as $grnItem) {
                $poItem = $grnItem->purchaseOrderItem;
                if (!$poItem) continue;

                $returnedCompleted = (int) $grnItem->purchaseReturnItems()
                    ->where('source_type', 'accepted')
                    ->whereHas('purchaseReturn', fn($q) => $q->where('status', 'completed'))
                    ->sum('qty');
                $qty = max(0, (int) $grnItem->qty_received - $returnedCompleted);
                if ($qty <= 0) continue;

                $lineBase = $qty * $poItem->unit_price;
                $disc = $lineBase * ($poItem->discount_percent / 100);
                $netLine = $lineBase - $disc;
                $subtotalGrn += $netLine;

                $itemsToCreate[] = [
                    'purchase_order_item_id' => $poItem->id,
                    'goods_receipt_item_id'  => $grnItem->id,
                    'product_id'             => $poItem->product_id,
                    'qty_invoiced'           => $qty,
                    'unit_price'             => $poItem->unit_price,
                    'discount_percent'       => $poItem->discount_percent,
                    'discount_amount'        => $disc,
                    'subtotal'               => $netLine,
                    'tax_amount'             => 0, // dihitung setelah prorasi diskon header
                ];
            }

            if (empty($itemsToCreate)) {
                return back()
                    ->with('error', 'Seluruh barang dalam LPB ini telah diretur ke supplier, sehingga tidak ada barang yang dapat ditagihkan.')
                    ->withInput();
            }

            // Hitung diskon header dan PPN yang sudah diserap oleh invoice-invoice sebelumnya untuk PO ini
            $usedHeaderDiscount = 0;
            $usedTaxAmount = 0;
            $totalInvoicedSubtotal = 0;
            foreach ($po->invoices as $existingInv) {
                $invBaseSubtotal = 0;
                foreach ($existingInv->items as $invItem) {
                    $lineBase = $invItem->qty_invoiced * $invItem->unit_price;
                    $lineDisc = $lineBase * (($invItem->discount_percent ?? 0) / 100);
                    $invBaseSubtotal += ($lineBase - $lineDisc);
                }
                $totalInvoicedSubtotal += $invBaseSubtotal;
                $usedHeaderDiscount += max(0, round($invBaseSubtotal - $existingInv->amount, 2));
                $usedTaxAmount += (float) $existingInv->tax_amount;
            }

            // Cek apakah ini LPB terakhir yang belum diinvoice untuk PO ini
            $otherUninvoicedGrnsCount = GoodsReceipt::where('purchase_order_id', $po->id)
                ->where('is_invoiced', false)
                ->where('id', '!=', $grn->id)
                ->count();

            $totalOrderSubtotal = (float) $po->items->sum('subtotal');
            $headerDiscountAmount = (float) ($po->discount_amount ?? 0);

            $poFullyReceived = $po->items->every(function ($item) {
                return ($item->qty_received + $item->qty_rejected) >= $item->qty_ordered;
            });

            $isLastInvoiceForPo = ($otherUninvoicedGrnsCount === 0) && (
                $poFullyReceived ||
                in_array($po->status, ['done', 'completed']) ||
                round($subtotalGrn + $totalInvoicedSubtotal, 2) >= round($totalOrderSubtotal, 2)
            );

            // Presisi desimal: Jika PO bernilai rupiah bulat (tanpa sen), bulatkan ke rupiah bulat (0 desimal)
            // agar tampilan di UI dan database tidak memiliki selisih 1 rupiah akibat pecahan 50 sen.
            $poTotalTax = (float) ($po->tax_amount ?? 0);
            $isIntegerRupiah = (round($poTotalTax) == $poTotalTax) && 
                               (round($totalOrderSubtotal) == $totalOrderSubtotal) && 
                               (round($headerDiscountAmount) == $headerDiscountAmount);
            $dec = $isIntegerRupiah ? 0 : 2;

            // Alokasi diskon header:
            // Jika invoice terakhir: ambil SELURUH sisa diskon header agar totalnya presisi 100% tanpa selisih pembulatan.
            if ($isLastInvoiceForPo && $headerDiscountAmount > 0) {
                $proratedHeaderDiscount = max(0, round($headerDiscountAmount - $usedHeaderDiscount, $dec));
            } else {
                $proratedHeaderDiscount = $totalOrderSubtotal > 0
                    ? round(($subtotalGrn / $totalOrderSubtotal) * $headerDiscountAmount, $dec)
                    : 0;
            }

            $dpp = max(0, round($subtotalGrn - $proratedHeaderDiscount, $dec));

            // Alokasi PPN:
            // Jika invoice terakhir dan tarif PPN sama dengan PO: ambil SELURUH sisa PPN dari PO agar totalnya presisi 100% tanpa selisih pembulatan.
            if ($isLastInvoiceForPo && abs((float) $po->tax_rate - $taxRate) < 0.001 && $poTotalTax > 0) {
                $taxAmount = max(0, round($poTotalTax - $usedTaxAmount, $dec));
            } else {
                $taxAmount = round($dpp * ($taxRate / 100), $dec);
            }

            $totalAmount = round($dpp + $taxAmount, $dec);

            $invoice = PurchaseInvoice::create([
                'invoice_number'          => $this->generateNumber(),
                'purchase_order_id'       => $po->id,
                'goods_receipt_id'        => $grn->id,
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

            // Hitung faktor prorasi diskon header untuk setiap baris item
            $discountRatio = ($subtotalGrn > 0 && $proratedHeaderDiscount > 0)
                ? ($proratedHeaderDiscount / $subtotalGrn)
                : 0;

            // Simpan item invoice dengan prorasi diskon header dan PPN per baris
            // Baris item terakhir mengambil sisa pembulatan agar sum(subtotal) == DPP dan sum(tax) == taxAmount
            $allocatedHeaderDiscount = 0;
            $allocatedSubtotal = 0;
            $allocatedTax = 0;
            $itemCount = count($itemsToCreate);

            foreach ($itemsToCreate as $idx => $itemData) {
                $itemData['purchase_invoice_id'] = $invoice->id;
                $isLastItem = ($idx === $itemCount - 1);

                if ($isLastItem) {
                    $itemHeaderDiscount = max(0, round($proratedHeaderDiscount - $allocatedHeaderDiscount, $dec));
                    $itemNetSubtotal    = max(0, round($dpp - $allocatedSubtotal, $dec));
                    $itemTax            = max(0, round($taxAmount - $allocatedTax, $dec));
                } else {
                    $itemHeaderDiscount = round($itemData['subtotal'] * $discountRatio, $dec);
                    $itemNetSubtotal    = max(0, round($itemData['subtotal'] - $itemHeaderDiscount, $dec));
                    $itemTax            = round($itemNetSubtotal * ($taxRate / 100), $dec);

                    $allocatedHeaderDiscount += $itemHeaderDiscount;
                    $allocatedSubtotal       += $itemNetSubtotal;
                    $allocatedTax            += $itemTax;
                }

                $itemData['discount_amount'] = round($itemData['discount_amount'] + $itemHeaderDiscount, $dec);
                $itemData['subtotal']        = $itemNetSubtotal;
                $itemData['tax_amount']      = $itemTax;

                PurchaseInvoiceItem::create($itemData);
            }

            // Tandai LPB sebagai sudah diinvoice — TIDAK bisa dipakai lagi
            $grn->update([
                'is_invoiced' => true,
                'purchase_invoice_id' => $invoice->id,
            ]);

            // Automatic Journal Entry (Persediaan & PPN Masukan -> Hutang Usaha)
            $entry = $this->journalService->createFromPurchaseInvoice($invoice);
            $this->journalService->postEntry($entry);

            return redirect()->route('purchase.invoices.index')
                ->with('success', 'Invoice Pembelian berhasil diterbitkan dari LPB ' . $grn->receipt_number . ' dan Jurnal Akuntansi otomatis diposting.');
        });
    }

    public function show(PurchaseInvoice $invoice): View
    {
        $invoice->load(['purchaseOrder.supplier', 'goodsReceipt', 'items.product', 'items.goodsReceiptItem.goodsReceipt', 'payments']);

        return view('purchase.invoices.show', compact('invoice'));
    }

    public function exportPdf(PurchaseInvoice $invoice)
    {
        $invoice->load(['purchaseOrder.supplier', 'goodsReceipt', 'items.product', 'items.goodsReceiptItem.goodsReceipt', 'payments']);
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
