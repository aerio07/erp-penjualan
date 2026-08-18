<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GoodsReceiptController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index(): View
    {
        $receipts = GoodsReceipt::with(['purchaseOrder.supplier', 'items.warehouse', 'user'])
            ->latest('received_date')
            ->paginate(20);

        return view('purchase.goods-receipts.index', compact('receipts'));
    }

    public function create(Request $request): View
    {
        $selectedPoId = $request->query('po_id');
        $confirmedPos = PurchaseOrder::with(['supplier', 'items.product', 'items.goodsReceiptItems'])
            ->whereIn('status', ['confirmed', 'partially_received'])
            ->orderByDesc('id')
            ->get();

        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('purchase.goods-receipts.create', compact('confirmedPos', 'warehouses', 'selectedPoId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'purchase_order_id'                  => 'required|exists:purchase_orders,id',
            'received_date'                      => 'required|date',
            'notes'                              => 'nullable|string',
            'items'                              => 'required|array|min:1',
            'items.*.purchase_order_item_id'    => 'required|exists:purchase_order_items,id',
            'items.*.warehouse_id'               => 'required|exists:warehouses,id',
            'items.*.qty_physical'               => 'nullable|integer|min:0',
            'items.*.qty_received'               => 'nullable|integer|min:0',
            'items.*.qty_rejected'               => 'nullable|integer|min:0',
            'items.*.condition'                  => 'nullable|string',
            'items.*.shortage_reason'            => 'nullable|in:none,not_shipped,damaged_in_transit',
        ]);

        $po = PurchaseOrder::with(['items.product', 'items.goodsReceiptItems'])->findOrFail($request->purchase_order_id);
        abort_if(!in_array($po->status, ['confirmed', 'partially_received']), 403, 'PO tidak dapat diterima.');

        $receivedByItem = [];
        $rejectedByItem = [];
        $physicalByItem = [];
        foreach ($request->items as $itemData) {
            $itemId = (int) $itemData['purchase_order_item_id'];
            $qtyRejected = (int) ($itemData['qty_rejected'] ?? 0);
            $qtyPhysical = isset($itemData['qty_physical']) 
                ? (int) $itemData['qty_physical'] 
                : ((int)($itemData['qty_received'] ?? 0) + $qtyRejected);
            $qtyGood = max(0, $qtyPhysical - $qtyRejected);

            if ($qtyRejected > $qtyPhysical) {
                return back()
                    ->with('error', "Qty rusak tidak boleh melebihi qty datang fisik.")
                    ->withInput();
            }

            $physicalByItem[$itemId] = ($physicalByItem[$itemId] ?? 0) + $qtyPhysical;
            $receivedByItem[$itemId] = ($receivedByItem[$itemId] ?? 0) + $qtyGood;
            $rejectedByItem[$itemId] = ($rejectedByItem[$itemId] ?? 0) + $qtyRejected;
        }

        $hasAnyQty = false;
        foreach ($physicalByItem as $poItemId => $totalPhysical) {
            $poItem = $po->items->firstWhere('id', $poItemId);
            abort_if(!$poItem, 422, 'Item penerimaan tidak sesuai dengan PO yang dipilih.');

            if ($totalPhysical > 0) {
                $hasAnyQty = true;
            }

            if ($totalPhysical > $poItem->qty_remaining) {
                return back()
                    ->with('error', "Total datang fisik untuk '{$poItem->product->name}' melebihi sisa PO. Sisa: {$poItem->qty_remaining}, total diinput: {$totalPhysical}.")
                    ->withInput();
            }
        }

        if (!$hasAnyQty) {
            return back()->with('error', 'Isi minimal satu qty fisik barang yang datang.')->withInput();
        }

        $autoReturn = null;

        DB::transaction(function () use ($request, $po, &$autoReturn) {
            $grnNumber = $this->generateNumber();

            $firstWarehouseId = $request->items[0]['warehouse_id'] ?? null;

            $grn = GoodsReceipt::create([
                'receipt_number'    => $grnNumber,
                'purchase_order_id' => $po->id,
                'warehouse_id'      => $firstWarehouseId,
                'user_id'           => Auth::id(),
                'qc_status'         => 'passed',
                'received_date'     => $request->received_date,
                'notes'             => $request->notes,
            ]);

            $rejectedItemsList = [];

            foreach ($request->items as $itemData) {
                $poItem = PurchaseOrderItem::findOrFail($itemData['purchase_order_item_id']);
                $qtyRejected = (int) ($itemData['qty_rejected'] ?? 0);
                $qtyPhysical = isset($itemData['qty_physical']) 
                    ? (int) $itemData['qty_physical'] 
                    : ((int)($itemData['qty_received'] ?? 0) + $qtyRejected);
                $qtyGood = max(0, $qtyPhysical - $qtyRejected);
                $warehouseId = (int) $itemData['warehouse_id'];

                if ($qtyPhysical <= 0) {
                    continue;
                }

                // Default shortage reason
                $shortageReason = $itemData['shortage_reason'] ?? ($qtyRejected > 0 ? 'damaged_in_transit' : 'none');

                $grnItem = GoodsReceiptItem::create([
                    'goods_receipt_id'       => $grn->id,
                    'purchase_order_item_id' => $poItem->id,
                    'warehouse_id'           => $warehouseId,
                    'qty_received'           => $qtyGood,
                    'qty_rejected'           => $qtyRejected,
                    'unit_cost'              => $poItem->unit_price,
                    'condition'              => $itemData['condition'] ?? 'Good',
                    'shortage_reason'        => $shortageReason,
                ]);

                // Kumpulkan item rusak untuk dibuatkan Draft Retur Pembelian otomatis
                if ($qtyRejected > 0) {
                    $rejectedItemsList[] = [
                        'grn_item'   => $grnItem,
                        'product_id' => $poItem->product_id,
                        'qty'        => $qtyRejected,
                        'unit_cost'  => $poItem->unit_price,
                        'reason'     => 'Barang rusak / reject saat penerimaan barang (GRN #' . $grnNumber . ')',
                    ];
                }

                // Record stock movement HANYA untuk barang yang kondisi baik (qtyGood > 0)
                if ($qtyGood > 0) {
                    $this->stockService->recordMovement([
                        'product_id'     => $poItem->product_id,
                        'warehouse_id'   => $warehouseId,
                        'type'           => 'in',
                        'quantity'       => $qtyGood,
                        'unit_cost'      => $poItem->unit_price,
                        'reference_type' => GoodsReceipt::class,
                        'reference_id'   => $grn->id,
                        'movement_date'  => $request->received_date,
                        'notes'          => "Penerimaan Barang #{$grnNumber} (PO #{$po->po_number})",
                        'user_id'        => Auth::id(),
                    ]);
                }
            }

            // AUTO-GENERATE DRAFT RETUR PEMBELIAN jika terdapat barang rusak/reject
            if (!empty($rejectedItemsList)) {
                $returnNumber = $this->generateReturnNumber();

                $autoReturn = PurchaseReturn::create([
                    'return_number'    => $returnNumber,
                    'goods_receipt_id' => $grn->id,
                    'supplier_id'      => $po->supplier_id,
                    'return_date'      => $request->received_date,
                    'reason'           => "Auto-generate dari GRN #{$grnNumber}: Ditemukan barang rusak/reject saat penerimaan",
                    'status'           => 'draft',
                    'notes'            => "Draft retur otomatis dibuat saat GRN #{$grnNumber} disimpan.",
                ]);

                foreach ($rejectedItemsList as $rej) {
                    PurchaseReturnItem::create([
                        'purchase_return_id'    => $autoReturn->id,
                        'product_id'            => $rej['product_id'],
                        'goods_receipt_item_id' => $rej['grn_item']->id,
                        'source_type'           => 'rejected',
                        'qty'                   => $rej['qty'],
                        'unit_cost'             => $rej['unit_cost'],
                        'reason'                => $rej['reason'],
                    ]);
                }
            }

            // Update PO Status: Sisa PO (kewajiban kirim) = qty_ordered - total fisik tiba (qty_received + qty_rejected)
            $po->refresh();
            $po->load('items.goodsReceiptItems');
            $allDone = true;
            foreach ($po->items as $item) {
                $totalArrived = $item->qty_received + $item->qty_rejected;
                if ($totalArrived < $item->qty_ordered) {
                    $allDone = false;
                    break;
                }
            }

            $po->update([
                'status' => $allDone ? 'done' : 'partially_received'
            ]);
        });

        if ($autoReturn) {
            return redirect()->route('purchase.goods-receipts.index')
                ->with('success', "Penerimaan Barang berhasil dicatat. Draft Retur Pembelian (#{$autoReturn->return_number}) otomatis dibuat untuk item yang rusak.");
        }

        return redirect()->route('purchase.goods-receipts.index')
            ->with('success', 'Penerimaan Barang (GRN) berhasil dicatat dan stok telah diperbarui.');
    }

    public function show(GoodsReceipt $goodsReceipt): View
    {
        $goodsReceipt->load([
            'purchaseOrder.supplier',
            'items.warehouse',
            'user',
            'items.purchaseOrderItem.product',
            'purchaseReturns',
        ]);

        return view('purchase.goods-receipts.show', compact('goodsReceipt'));
    }

    private function generateNumber(): string
    {
        $prefix = 'GRN-' . date('Ym') . '-';
        $last   = GoodsReceipt::where('receipt_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('receipt_number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    private function generateReturnNumber(): string
    {
        $prefix = 'PRET-' . date('Ym') . '-';
        $last   = PurchaseReturn::where('return_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('return_number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
