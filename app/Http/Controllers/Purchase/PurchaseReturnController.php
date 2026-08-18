<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Services\JournalService;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseReturnController extends Controller
{
    public function __construct(
        private StockService $stockService,
        private JournalService $journalService,
    ) {}

    public function index(): View
    {
        $returns = PurchaseReturn::with(['goodsReceipt.purchaseOrder.supplier', 'items.product'])
            ->latest('return_date')
            ->paginate(20);

        return view('purchase.returns.index', compact('returns'));
    }

    public function create(Request $request): View
    {
        $selectedGrnId = $request->query('grn_id');
        $receipts = GoodsReceipt::with(['purchaseOrder.supplier', 'items.purchaseOrderItem.product', 'warehouse'])
            ->with('items.purchaseReturnItems')
            ->orderByDesc('id')
            ->get();

        return view('purchase.returns.create', compact('receipts', 'selectedGrnId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'goods_receipt_id'           => 'required|exists:goods_receipts,id',
            'return_date'                => 'required|date',
            'reason'                     => 'nullable|string',
            'notes'                      => 'nullable|string',
            'items'                      => 'required|array|min:1',
            'items.*.goods_receipt_item_id' => 'required|exists:goods_receipt_items,id',
            'items.*.product_id'         => 'required|exists:products,id',
            'items.*.source_type'         => 'required|in:accepted,rejected',
            'items.*.qty'                => 'required|integer|min:0',
            'items.*.unit_cost'          => 'required|numeric|min:0',
        ]);

        $grn = GoodsReceipt::with('purchaseOrder')->findOrFail($request->goods_receipt_id);

        $hasAnyQty = false;
        foreach ($request->items as $itemData) {
            $qty = (int) $itemData['qty'];
            if ($qty <= 0) {
                continue;
            }

            $hasAnyQty = true;
            $grnItem = GoodsReceiptItem::with('purchaseOrderItem.product')->findOrFail($itemData['goods_receipt_item_id']);
            abort_if($grnItem->goods_receipt_id !== $grn->id, 422, 'Item retur tidak sesuai dengan GRN yang dipilih.');
            abort_if($grnItem->purchaseOrderItem->product_id !== (int) $itemData['product_id'], 422, 'Produk retur tidak sesuai dengan item GRN.');

            $available = $itemData['source_type'] === 'accepted'
                ? $grnItem->qty_available_for_return_accepted
                : $grnItem->qty_available_for_return_rejected;

            if ($qty > $available) {
                $sourceLabel = $itemData['source_type'] === 'accepted' ? 'diterima stok' : 'rusak/reject';
                return back()
                    ->with('error', "Qty retur '{$grnItem->purchaseOrderItem->product->name}' ({$sourceLabel}) melebihi sisa yang bisa diretur. Sisa: {$available}, diinput: {$qty}.")
                    ->withInput();
            }
        }

        if (!$hasAnyQty) {
            return back()->with('error', 'Isi minimal satu qty barang yang akan diretur.')->withInput();
        }

        DB::transaction(function () use ($request, $grn) {
            $return = PurchaseReturn::create([
                'return_number'    => $this->generateNumber(),
                'goods_receipt_id' => $grn->id,
                'supplier_id'      => $grn->purchaseOrder->supplier_id,
                'return_date'      => $request->return_date,
                'reason'           => $request->reason,
                'status'           => 'draft',
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
        abort_if($return->status !== 'draft', 403, 'Hanya retur draft yang dapat dikirim.');

        $return->update(['status' => 'sent']);

        return back()->with('success', 'Retur Pembelian berhasil ditandai sudah dikirim.');
    }

    public function complete(PurchaseReturn $return): RedirectResponse
    {
        abort_if($return->status === 'completed', 403, 'Retur sudah selesai.');

        DB::transaction(function () use ($return) {
            $return->load(['goodsReceipt', 'items.goodsReceiptItem']);
            $grn = $return->goodsReceipt;

            foreach ($return->items as $item) {
                if ($item->source_type === 'accepted') {
                    $warehouseId = $item->goodsReceiptItem?->warehouse_id ?? $grn->warehouse_id;

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

            $return->update(['status' => 'completed']);
        });

        return back()->with('success', 'Retur Pembelian selesai. Stok gudang dikurangi dan jurnal akuntansi otomatis diposting (jika sudah pernah di-invoice).');
    }

    private function generateNumber(): string
    {
        $prefix = 'PRET-' . date('Ym') . '-';
        $last   = PurchaseReturn::where('return_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('return_number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}

