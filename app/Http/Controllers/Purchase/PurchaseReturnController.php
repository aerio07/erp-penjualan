<?php

namespace App\Http\Controllers\Purchase;

use App\Enums\PurchaseInvoiceStatus;
use App\Enums\PurchaseReturnStatus;
use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Supplier;
use App\Services\JournalService;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

use App\Traits\HasListFilters;
use App\Traits\GeneratesDocumentNumber;

class PurchaseReturnController extends Controller
{
    use HasListFilters, GeneratesDocumentNumber;

    public function __construct(
        private StockService $stockService,
        private JournalService $journalService,
    ) {}

    public function index(Request $request): View
    {
        $query = PurchaseReturn::with(['goodsReceipt.purchaseOrder.supplier', 'supplier', 'items.product']);

        $query = $this->applySearch($query, $request, ['return_number', 'goodsReceipt.receipt_number', 'supplier.name', 'reason', 'notes']);
        $query = $this->applyFilter($query, $request, 'supplier_id');
        $query = $this->applyFilter($query, $request, 'status');
        $query = $this->applyDateRange($query, $request, 'return_date');
        $query = $this->applySort($query, $request, ['return_number', 'return_date', 'status', 'created_at'], 'return_date', 'desc');

        $perPage = (int) $request->get('per_page', 20);
        $returns   = $query->paginate($perPage)->withQueryString();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        return view('purchase.returns.index', compact('returns', 'suppliers'));
    }

    public function create(Request $request): View
    {
        $selectedGrnId = $request->query('grn_id');
        $selectedPoId  = $request->query('po_id');

        $receipts = GoodsReceipt::with([
            'purchaseOrder.supplier',
            'warehouse',
            'items.purchaseOrderItem.product',
            'items.purchaseReturnItems',
            'items.purchaseInvoiceItem',
            'purchaseInvoice.items',
            'purchaseInvoice.payments',
        ])
            ->whereIn('qc_status', ['passed', 'partial'])
            ->orderByDesc('id')
            ->get();

        // If grn_id is given but not po_id, resolve po_id
        if ($selectedGrnId && !$selectedPoId) {
            $matchedGrn = $receipts->firstWhere('id', (int) $selectedGrnId);
            if ($matchedGrn) {
                $selectedPoId = $matchedGrn->purchase_order_id;
            }
        }

        // Get unique POs that have eligible receipts
        $poIds = $receipts->pluck('purchase_order_id')->unique()->filter();
        $purchaseOrders = PurchaseOrder::with('supplier')
            ->whereIn('id', $poIds)
            ->orderByDesc('id')
            ->get();

        // Hitung ketersediaan stok fisik riil di gudang untuk setiap item GRN
        foreach ($receipts as $grn) {
            foreach ($grn->items as $item) {
                $productId   = $item->purchaseOrderItem->product_id ?? null;
                $whId        = $item->warehouse_id ?? $grn->warehouse_id;

                if ($productId && $whId) {
                    $onHand    = $this->stockService->getOnHandStock($productId, $whId);
                    $reserved  = $this->stockService->getReservedStock($productId, $whId);
                    $available = $this->stockService->getAvailableStock($productId, $whId);

                    $item->physical_on_hand        = $onHand;
                    $item->physical_reserved       = $reserved;
                    $item->physical_available      = $available;
                    $item->max_returnable_accepted = min($item->qty_available_for_return_accepted, $available);
                } else {
                    $item->physical_on_hand        = 0;
                    $item->physical_reserved       = 0;
                    $item->physical_available      = 0;
                    $item->max_returnable_accepted = 0;
                }
            }
        }

        return view('purchase.returns.create', compact('receipts', 'purchaseOrders', 'selectedGrnId', 'selectedPoId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'purchase_order_id'             => 'nullable|exists:purchase_orders,id',
            'goods_receipt_id'              => 'required|exists:goods_receipts,id',
            'return_date'                   => 'required|date',
            'reason'                        => 'nullable|string',
            'notes'                         => 'nullable|string',
            'items'                         => 'required|array|min:1',
            'items.*.goods_receipt_item_id' => 'required|exists:goods_receipt_items,id',
            'items.*.product_id'            => 'required|exists:products,id',
            'items.*.source_type'           => 'required|in:accepted,rejected',
            'items.*.qty'                   => 'required|integer|min:0',
            'items.*.unit_cost'             => 'required|numeric|min:0',
        ]);

        $grn = GoodsReceipt::with([
            'purchaseOrder.supplier',
            'warehouse',
            'items.purchaseOrderItem.product',
            'items.purchaseInvoiceItem',
            'purchaseInvoice.items',
            'purchaseInvoice.payments',
        ])->findOrFail($request->goods_receipt_id);

        $invoice = $grn->purchaseInvoice ?? PurchaseInvoice::with(['items', 'payments'])->where('goods_receipt_id', $grn->id)->first();

        // Tolak retur jika invoice LPB masih berstatus Draft
        if ($invoice && $invoice->isDraft()) {
            $msg = "LPB terkait masih memiliki Invoice Pembelian berstatus Draft ({$invoice->invoice_number}). Silakan hapus draft invoice atau posting invoice terlebih dahulu sebelum mengajukan retur.";
            return back()->with('error', $msg)->withErrors(['items' => $msg])->withInput();
        }

        $sisaBelumBayar = $invoice ? round((float) $invoice->effective_total_amount - (float) $invoice->total_paid, 2) : null;

        // Jika invoice sudah lunas penuh (outstanding <= 0.01), tolak retur otomatis untuk mencegah overpayment
        if ($invoice && $sisaBelumBayar !== null && $sisaBelumBayar <= 0.01) {
            $msg = "Invoice pembelian {$invoice->invoice_number} sudah lunas dibayar penuh ke supplier. Retur tidak dapat diproses otomatis karena akan menyebabkan kelebihan pembayaran dari sisi kita. Silakan koordinasikan dengan supplier untuk penanganan manual.";
            return back()->with('error', $msg)->withErrors(['items' => $msg])->withInput();
        }

        $hasAnyQty = false;
        $totalNilaiRetur = 0;

        foreach ($request->items as $itemData) {
            $qty = (int) $itemData['qty'];
            if ($qty <= 0) {
                continue;
            }

            $hasAnyQty = true;
            $grnItem = GoodsReceiptItem::with(['purchaseOrderItem.product', 'purchaseInvoiceItem'])->findOrFail($itemData['goods_receipt_item_id']);
            abort_if($grnItem->goods_receipt_id !== $grn->id, 422, 'Item retur tidak sesuai dengan GRN yang dipilih.');
            abort_if($grnItem->purchaseOrderItem->product_id !== (int) $itemData['product_id'], 422, 'Produk retur tidak sesuai dengan item GRN.');

            $product = $grnItem->purchaseOrderItem->product;
            $warehouseId = $grnItem->warehouse_id ?? $grn->warehouse_id;

            if ($itemData['source_type'] === 'accepted') {
                $grnQuotaRemaining = $grnItem->qty_available_for_return_accepted;
                $physicalAvailable = $this->stockService->getAvailableStock($product->id, $warehouseId);
                $maxReturnable     = min($grnQuotaRemaining, $physicalAvailable);

                if ($qty > $maxReturnable) {
                    if ($physicalAvailable <= 0) {
                        $errMsg = "Gagal membuat retur '{$product->name}'. Stok fisik di gudang saat ini 0 {$product->unit} (barang telah habis terjual/dikirim ke pelanggan atau di-booking Sales Order). Barang tidak dapat diretur ke supplier.";
                        return back()->with('error', $errMsg)->withErrors(['items' => $errMsg])->withInput();
                    }

                    if ($qty > $physicalAvailable) {
                        $errMsg = "Qty retur '{$product->name}' ({$qty} {$product->unit}) melebihi stok fisik bebas yang ada di gudang ({$physicalAvailable} {$product->unit}).";
                        return back()->with('error', $errMsg)->withErrors(['items' => $errMsg])->withInput();
                    }

                    $errMsg = "Qty retur '{$product->name}' (diterima stok) melebihi kuota sisa GRN yang bisa diretur ({$grnQuotaRemaining} {$product->unit}).";
                    return back()->with('error', $errMsg)->withErrors(['items' => $errMsg])->withInput();
                }
            } else {
                $available = $grnItem->qty_available_for_return_rejected;
                if ($qty > $available) {
                    $errMsg = "Qty retur '{$product->name}' (rusak/reject) melebihi sisa yang bisa diretur. Sisa: {$available}, diinput: {$qty}.";
                    return back()->with('error', $errMsg)->withErrors(['items' => $errMsg])->withInput();
                }
            }

            // Batas 2: Batas finansial berbasis sisa hutang invoice (jika sudah di-invoice)
            if ($invoice) {
                $maxAllowedQty = $invoice->calculateMaxReturnQty($grnItem, $itemData['source_type']);
                if ($qty > $maxAllowedQty) {
                    $errorMsg = $maxAllowedQty === 0
                        ? "Qty retur untuk '{$product->name}' tidak dapat diproses karena akan menyebabkan kelebihan pembayaran ke supplier (sisa tagihan hutang tidak mencukupi)."
                        : "Qty retur untuk '{$product->name}' maksimal {$maxAllowedQty} unit (dibatasi oleh sisa hutang invoice yang belum dibayar).";

                    return back()->with('error', $errorMsg)->withErrors(['items' => $errorMsg])->withInput();
                }

                $invoiceItem = $grnItem->purchaseInvoiceItem ?? $invoice->items->firstWhere('goods_receipt_item_id', $grnItem->id);
                $effectiveUnitPrice = ($invoiceItem && (int) $invoiceItem->qty_invoiced > 0)
                    ? ((float) $invoiceItem->subtotal + (float) $invoiceItem->tax_amount) / (int) $invoiceItem->qty_invoiced
                    : (float) $itemData['unit_cost'];

                $totalNilaiRetur += round($qty * $effectiveUnitPrice, 2);
            }
        }

        if (!$hasAnyQty) {
            return back()->with('error', 'Isi minimal satu qty barang yang akan diretur.')->withInput();
        }

        // Validasi Tambahan Agregat: Total nilai retur multi-item tidak boleh melebihi sisa belum bayar
        if ($invoice && $sisaBelumBayar !== null && $totalNilaiRetur > ($sisaBelumBayar + 0.009)) {
            $formattedTotal = number_format($totalNilaiRetur, 0, ',', '.');
            $formattedSisa  = number_format($sisaBelumBayar, 0, ',', '.');
            $multiItemMsg = "Total nilai retur (Rp {$formattedTotal}) melebihi sisa hutang yang belum dibayar (Rp {$formattedSisa}).";

            return back()->with('error', $multiItemMsg)->withErrors(['items' => $multiItemMsg])->withInput();
        }

        DB::transaction(function () use ($request, $grn) {
            $return = PurchaseReturn::create([
                'return_number'    => $this->generateNumber(),
                'goods_receipt_id' => $grn->id,
                'supplier_id'      => $grn->purchaseOrder->supplier_id,
                'return_date'      => $request->return_date,
                'reason'           => $request->reason,
                'status'           => PurchaseReturnStatus::Draft,
                'notes'            => $request->notes,
            ]);

            foreach ($request->items as $itemData) {
                if ((int)$itemData['qty'] <= 0) continue;

                PurchaseReturnItem::create([
                    'purchase_return_id'    => $return->id,
                    'product_id'            => $itemData['product_id'],
                    'goods_receipt_item_id' => $itemData['goods_receipt_item_id'],
                    'source_type'           => $itemData['source_type'],
                    'qty'                   => $itemData['qty'],
                    'unit_cost'             => $itemData['unit_cost'],
                    'reason'                => $itemData['reason'] ?? $request->reason,
                ]);
            }
        });

        return redirect()->route('purchase.returns.index')
            ->with('success', 'Draft Retur Pembelian berhasil dibuat.');
    }

    public function show(PurchaseReturn $return): View
    {
        $return->load(['goodsReceipt.warehouse', 'supplier', 'items.product', 'items.goodsReceiptItem']);

        return view('purchase.returns.show', compact('return'));
    }

    public function send(PurchaseReturn $return): RedirectResponse
    {
        return DB::transaction(function () use ($return) {
            $return = PurchaseReturn::with(['goodsReceipt', 'items.product', 'items.goodsReceiptItem'])->lockForUpdate()->findOrFail($return->id);
            abort_if($return->status !== PurchaseReturnStatus::Draft, 403, 'Hanya retur draft yang dapat dikirim.');

            $grn = $return->goodsReceipt;
            foreach ($return->items as $item) {
                if ($item->source_type === 'accepted') {
                    $warehouseId = $item->goodsReceiptItem?->warehouse_id ?? $grn->warehouse_id;
                    $onHand = $this->stockService->getOnHandStock($item->product_id, $warehouseId);
                    if ($item->qty > $onHand) {
                        return back()->with('error', "Tidak dapat mengirim retur. Stok fisik '{$item->product->name}' di gudang saat ini ({$onHand}) tidak mencukupi untuk diretur ({$item->qty}).");
                    }
                }
            }

            $return->update(['status' => PurchaseReturnStatus::Sent]);

            return back()->with('success', 'Retur Pembelian berhasil ditandai sudah dikirim ke supplier.');
        });
    }

    public function complete(PurchaseReturn $return): RedirectResponse
    {
        return DB::transaction(function () use ($return) {
            $return = PurchaseReturn::with(['goodsReceipt', 'items.product', 'items.goodsReceiptItem'])->lockForUpdate()->findOrFail($return->id);
            abort_if($return->status === PurchaseReturnStatus::Completed, 403, 'Retur sudah selesai.');

            $grn = $return->goodsReceipt;

            // Lock involved products to serialize inventory deduction
            $productIds = $return->items->pluck('product_id')->unique()->toArray();
            Product::whereIn('id', $productIds)->lockForUpdate()->get();

            foreach ($return->items as $item) {
                if ($item->source_type === 'accepted') {
                    $warehouseId = $item->goodsReceiptItem?->warehouse_id ?? $grn->warehouse_id;
                    $currentOnHand = $this->stockService->getOnHandStock($item->product_id, $warehouseId);

                    if ($item->qty > $currentOnHand) {
                        return back()->with('error', "Gagal menyelesaikan retur. Stok fisik '{$item->product->name}' di gudang ({$currentOnHand}) tidak mencukupi untuk diretur ({$item->qty}). Barang kemungkinan sudah terpakai untuk transaksi lain.");
                    }

                    // Barang accepted pernah masuk stok, jadi saat retur stok harus dikurangi dari gudang tersebut.
                    $this->stockService->recordMovement([
                        'product_id'     => $item->product_id,
                        'warehouse_id'   => $warehouseId,
                        'type'           => 'return_out',
                        'quantity'       => $item->qty,
                        'unit_cost'      => $item->unit_cost,
                        'reference_type' => PurchaseReturn::class,
                        'reference_id'   => $return->id,
                        'movement_date'  => now()->toDateString(),
                        'notes'          => "Retur Pembelian #{$return->return_number}",
                        'user_id'        => Auth::id(),
                    ]);
                }
            }

            // Auto-journaling: balik hutang/PPN/persediaan proporsional (skip jika belum di-invoice)
            $entry = $this->journalService->createFromPurchaseReturn($return);
            if ($entry) {
                $this->journalService->postEntry($entry);
            }

            $return->update(['status' => PurchaseReturnStatus::Completed]);
            $this->refreshAffectedInvoiceStatuses($return);

            return back()->with('success', 'Retur Pembelian berhasil diselesaikan dan jurnal akuntansi otomatis diposting (jika sudah pernah di-invoice).');
        });
    }

    private function refreshAffectedInvoiceStatuses(PurchaseReturn $return): void
    {
        $grnItemIds = $return->items->pluck('goods_receipt_item_id')->filter()->unique();

        if ($grnItemIds->isEmpty()) {
            return;
        }

        PurchaseInvoice::with(['items', 'payments'])
            ->where('status', '!=', PurchaseInvoiceStatus::Draft->value)
            ->whereHas('items', fn($query) => $query->whereIn('goods_receipt_item_id', $grnItemIds))
            ->get()
            ->each(function (PurchaseInvoice $invoice) {
                $invoice->update(['status' => $this->statusFromOutstanding($invoice)]);
            });
    }

    private function statusFromOutstanding(PurchaseInvoice $invoice): PurchaseInvoiceStatus
    {
        if ($invoice->outstanding_amount <= 0.01) {
            return PurchaseInvoiceStatus::Paid;
        }

        return $invoice->total_paid > 0 ? PurchaseInvoiceStatus::Partial : PurchaseInvoiceStatus::Unpaid;
    }

    private function generateNumber(): string
    {
        return $this->generateDocumentNumber(PurchaseReturn::class, 'return_number', 'RET');
    }
}
