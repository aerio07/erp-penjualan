<?php

namespace App\Http\Controllers\Purchase;

use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseReturnStatus;
use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Warehouse;
use App\Services\ApprovalService;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

use App\Traits\HasListFilters;
use App\Traits\GeneratesDocumentNumber;

class GoodsReceiptController extends Controller
{
    use HasListFilters, GeneratesDocumentNumber;

    public function __construct(
        private StockService $stockService,
        private \App\Services\PurchaseInvoiceService $purchaseInvoiceService,
        private ApprovalService $approvalService
    ) {}

    public function index(Request $request): View
    {
        $query = GoodsReceipt::with(['purchaseOrder.supplier', 'warehouse', 'items.warehouse', 'user', 'purchaseInvoice', 'approvalRequests']);

        $query = $this->applySearch($query, $request, ['receipt_number', 'purchaseOrder.po_number', 'purchaseOrder.supplier.name', 'notes']);
        $query = $this->applyFilter($query, $request, 'status');
        $query = $this->applyFilter($query, $request, 'qc_status');
        $query = $this->applyFilter($query, $request, 'warehouse_id');
        $query = $this->applyFilter($query, $request, 'is_invoiced');
        $query = $this->applyDateRange($query, $request, 'received_date');
        $query = $this->applySort($query, $request, ['receipt_number', 'received_date', 'status', 'qc_status', 'created_at'], 'received_date', 'desc');

        $perPage = (int) $request->get('per_page', 10);
        $receipts   = $query->paginate($perPage)->withQueryString();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('purchase.goods-receipts.index', compact('receipts', 'warehouses'));
    }

    public function create(Request $request): View
    {
        $selectedPoId = $request->query('po_id');
        $confirmedPos = PurchaseOrder::with(['supplier', 'warehouse', 'items.product', 'items.goodsReceiptItems'])
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
            'warehouse_id'                       => 'required|exists:warehouses,id',
            'received_date'                      => 'required|date',
            'notes'                              => 'nullable|string',
            'auto_generate_invoice'              => 'nullable|boolean',
            'supplier_invoice_number'            => 'nullable|string',
            'invoice_date'                       => 'nullable|date',
            'due_date'                           => 'nullable|date|after_or_equal:invoice_date',
            'invoice_tax_rate'                   => 'nullable|numeric|min:0|max:100',
            'items'                              => 'required|array|min:1',
            'items.*.purchase_order_item_id'     => 'required|exists:purchase_order_items,id',
            'items.*.qty_physical'               => 'nullable|integer|min:0',
            'items.*.qty_rejected'               => 'nullable|integer|min:0',
            'items.*.condition'                  => 'nullable|string',
            'items.*.shortage_reason'            => 'nullable|in:none,not_shipped,damaged_in_transit',
        ]);

        return DB::transaction(function () use ($request) {
            $po = PurchaseOrder::with(['items.product', 'items.goodsReceiptItems'])->lockForUpdate()->findOrFail($request->purchase_order_id);
            abort_if(!$po->status->canReceiveGoods(), 403, 'PO tidak dapat diterima.');

            $physicalByItem = [];
            $receivedByItem = [];
            $rejectedByItem = [];
            foreach ($request->items as $itemData) {
                $itemId = (int) $itemData['purchase_order_item_id'];
                $qtyRejected = (int) ($itemData['qty_rejected'] ?? 0);
                $qtyPhysical = (int) ($itemData['qty_physical'] ?? 0);
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

            $grnNumber = $this->generateNumber();
            $headerWarehouseId = (int) $request->warehouse_id;

            $grn = GoodsReceipt::create([
                'receipt_number'          => $grnNumber,
                'purchase_order_id'       => $po->id,
                'warehouse_id'            => $headerWarehouseId,
                'user_id'                 => Auth::id(),
                'qc_status'               => 'passed',
                'status'                  => GoodsReceiptStatus::Draft,
                'received_date'           => $request->received_date,
                'notes'                   => $request->notes,
                'auto_generate_invoice'   => $request->boolean('auto_generate_invoice'),
                'supplier_invoice_number' => $request->supplier_invoice_number,
                'invoice_date'            => $request->invoice_date,
                'due_date'                => $request->due_date,
                'invoice_tax_rate'        => $request->filled('invoice_tax_rate') ? (float) $request->invoice_tax_rate : null,
            ]);

            foreach ($request->items as $itemData) {
                $poItem = PurchaseOrderItem::findOrFail($itemData['purchase_order_item_id']);
                $qtyRejected = (int) ($itemData['qty_rejected'] ?? 0);
                $qtyPhysical = (int) ($itemData['qty_physical'] ?? 0);
                $qtyGood = max(0, $qtyPhysical - $qtyRejected);

                if ($qtyPhysical <= 0) {
                    continue;
                }

                $shortageReason = $itemData['shortage_reason'] ?? ($qtyRejected > 0 ? 'damaged_in_transit' : 'none');

                GoodsReceiptItem::create([
                    'goods_receipt_id'       => $grn->id,
                    'purchase_order_item_id' => $poItem->id,
                    'warehouse_id'           => $headerWarehouseId,
                    'qty_received'           => $qtyGood,
                    'qty_rejected'           => $qtyRejected,
                    'unit_cost'              => $poItem->unit_price,
                    'condition'              => $itemData['condition'] ?? 'Good',
                    'shortage_reason'        => $shortageReason,
                ]);
            }

            return redirect()->route('purchase.goods-receipts.index')
                ->with('success', "Penerimaan Barang #{$grn->receipt_number} berhasil disimpan sebagai Draft.");
        });
    }

    public function edit(GoodsReceipt $goodsReceipt): View|RedirectResponse
    {
        if (!$goodsReceipt->status->isEditable()) {
            return redirect()->route('purchase.goods-receipts.show', $goodsReceipt)
                ->with('error', 'Hanya Penerimaan Barang berstatus Draft atau Revisi yang dapat diedit.');
        }

        $goodsReceipt->load([
            'purchaseOrder.supplier',
            'purchaseOrder.warehouse',
            'purchaseOrder.items.product',
            'purchaseOrder.items.goodsReceiptItems',
            'items.purchaseOrderItem.product'
        ]);

        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('purchase.goods-receipts.edit', compact('goodsReceipt', 'warehouses'));
    }

    public function update(Request $request, GoodsReceipt $goodsReceipt): RedirectResponse
    {
        if (!$goodsReceipt->status->isEditable()) {
            return redirect()->route('purchase.goods-receipts.show', $goodsReceipt)
                ->with('error', 'Hanya Penerimaan Barang berstatus Draft atau Revisi yang dapat diedit.');
        }

        $request->validate([
            'warehouse_id'                   => 'required|exists:warehouses,id',
            'received_date'                  => 'required|date',
            'notes'                          => 'nullable|string',
            'auto_generate_invoice'          => 'nullable|boolean',
            'supplier_invoice_number'        => 'nullable|string',
            'invoice_date'                   => 'nullable|date',
            'due_date'                       => 'nullable|date|after_or_equal:invoice_date',
            'invoice_tax_rate'               => 'nullable|numeric|min:0|max:100',
            'items'                          => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.qty_physical'           => 'nullable|integer|min:0',
            'items.*.qty_rejected'           => 'nullable|integer|min:0',
            'items.*.condition'              => 'nullable|string',
            'items.*.shortage_reason'        => 'nullable|in:none,not_shipped,damaged_in_transit',
        ]);

        return DB::transaction(function () use ($request, $goodsReceipt) {
            $po = PurchaseOrder::with(['items.product', 'items.goodsReceiptItems'])->findOrFail($goodsReceipt->purchase_order_id);

            $physicalByItem = [];
            $receivedByItem = [];
            $rejectedByItem = [];
            foreach ($request->items as $itemData) {
                $itemId = (int) $itemData['purchase_order_item_id'];
                $qtyRejected = (int) ($itemData['qty_rejected'] ?? 0);
                $qtyPhysical = (int) ($itemData['qty_physical'] ?? 0);
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
                abort_if(!$poItem, 422, 'Item penerimaan tidak sesuai dengan PO.');

                if ($totalPhysical > 0) {
                    $hasAnyQty = true;
                }

                // Sisa PO di luar LPB ini
                $otherReceived = $poItem->goodsReceiptItems
                    ->where('goods_receipt_id', '!=', $goodsReceipt->id)
                    ->sum(fn($i) => ($i->qty_received + $i->qty_rejected));
                $availableRemaining = max(0, $poItem->qty_ordered - $otherReceived);

                if ($totalPhysical > $availableRemaining) {
                    return back()
                        ->with('error', "Total datang fisik untuk '{$poItem->product->name}' melebihi sisa PO ({$availableRemaining}).")
                        ->withInput();
                }
            }

            if (!$hasAnyQty) {
                return back()->with('error', 'Isi minimal satu qty fisik barang yang datang.')->withInput();
            }

            $headerWarehouseId = (int) $request->warehouse_id;

            $goodsReceipt->update([
                'warehouse_id'            => $headerWarehouseId,
                'received_date'           => $request->received_date,
                'notes'                   => $request->notes,
                'auto_generate_invoice'   => $request->boolean('auto_generate_invoice'),
                'supplier_invoice_number' => $request->supplier_invoice_number,
                'invoice_date'            => $request->invoice_date,
                'due_date'                => $request->due_date,
                'invoice_tax_rate'        => $request->filled('invoice_tax_rate') ? (float) $request->invoice_tax_rate : null,
            ]);

            // Hapus item lama dan buat baru
            $goodsReceipt->items()->delete();

            foreach ($request->items as $itemData) {
                $poItem = PurchaseOrderItem::findOrFail($itemData['purchase_order_item_id']);
                $qtyRejected = (int) ($itemData['qty_rejected'] ?? 0);
                $qtyPhysical = (int) ($itemData['qty_physical'] ?? 0);
                $qtyGood = max(0, $qtyPhysical - $qtyRejected);

                if ($qtyPhysical <= 0) {
                    continue;
                }

                $shortageReason = $itemData['shortage_reason'] ?? ($qtyRejected > 0 ? 'damaged_in_transit' : 'none');

                GoodsReceiptItem::create([
                    'goods_receipt_id'       => $goodsReceipt->id,
                    'purchase_order_item_id' => $poItem->id,
                    'warehouse_id'           => $headerWarehouseId,
                    'qty_received'           => $qtyGood,
                    'qty_rejected'           => $qtyRejected,
                    'unit_cost'              => $poItem->unit_price,
                    'condition'              => $itemData['condition'] ?? 'Good',
                    'shortage_reason'        => $shortageReason,
                ]);
            }

            return redirect()->route('purchase.goods-receipts.index')
                ->with('success', "Draft Penerimaan Barang #{$goodsReceipt->receipt_number} berhasil diperbarui.");
        });
    }

    public function destroy(GoodsReceipt $goodsReceipt): RedirectResponse
    {
        if (!$goodsReceipt->status->isEditable()) {
            return back()->with('error', 'Hanya Penerimaan Barang berstatus Draft atau Revisi yang dapat dihapus.');
        }

        DB::transaction(function () use ($goodsReceipt) {
            $goodsReceipt->approvalRequests()->delete();
            $goodsReceipt->items()->delete();
            $goodsReceipt->delete();
        });

        return redirect()->route('purchase.goods-receipts.index')
            ->with('success', "Draft Penerimaan Barang #{$goodsReceipt->receipt_number} berhasil dihapus.");
    }

    public function submit(GoodsReceipt $goodsReceipt): RedirectResponse
    {
        if (!in_array($goodsReceipt->status, [GoodsReceiptStatus::Draft, GoodsReceiptStatus::Revised, GoodsReceiptStatus::Rejected])) {
            return back()->with('error', 'Hanya dokumen berstatus Draft, Revisi, atau Ditolak yang dapat diajukan untuk approval.');
        }

        $goodsReceipt->load(['items.purchaseOrderItem', 'user', 'purchaseOrder']);

        $goodsReceipt->update([
            'status'           => GoodsReceiptStatus::WaitingApproval,
            'rejection_reason' => null,
        ]);

        $totalAmount = (float) $goodsReceipt->items->sum(function ($item) {
            return ($item->qty_received + $item->qty_rejected) * $item->unit_cost;
        });

        $this->approvalService->request(
            GoodsReceipt::class,
            $goodsReceipt->id,
            $totalAmount,
            "Penerimaan Barang #{$goodsReceipt->receipt_number} (PO #{$goodsReceipt->purchaseOrder->po_number}) diajukan oleh " . (Auth::user()->name ?? 'User') . "."
        );

        return redirect()->route('purchase.goods-receipts.index')
            ->with('success', "Penerimaan Barang {$goodsReceipt->receipt_number} berhasil diajukan. Dokumen kini berada di antrean Menu Approval.");
    }

    /**
     * Eksekusi final saat Penerimaan Barang disetujui (Approved).
     * Dipanggil oleh ApprovalController saat approver klik Setujui.
     */
    public function processApproval(GoodsReceipt $goodsReceipt, int $approverId): void
    {
        $goodsReceipt->load([
            'items.purchaseOrderItem.product',
            'purchaseOrder.supplier',
            'purchaseOrder.items.goodsReceiptItems'
        ]);

        $goodsReceipt->update([
            'status'      => GoodsReceiptStatus::Confirmed,
            'approved_by' => $approverId,
            'approved_at' => now(),
        ]);

        // 1. Record stock movements untuk item yang baik
        foreach ($goodsReceipt->items as $item) {
            if ($item->qty_received > 0) {
                $this->stockService->recordMovement([
                    'product_id'     => $item->purchaseOrderItem->product_id,
                    'warehouse_id'   => $item->warehouse_id,
                    'type'           => 'in',
                    'quantity'       => $item->qty_received,
                    'unit_cost'      => $item->unit_cost,
                    'reference_type' => GoodsReceipt::class,
                    'reference_id'   => $goodsReceipt->id,
                    'movement_date'  => $goodsReceipt->received_date,
                    'notes'          => "Penerimaan Barang #{$goodsReceipt->receipt_number} (PO #{$goodsReceipt->purchaseOrder->po_number})",
                    'user_id'        => $goodsReceipt->user_id ?? $approverId,
                ]);

                $this->stockService->allocateStockToPendingDemands(
                    $item->purchaseOrderItem->product_id,
                    $item->warehouse_id
                );
            }
        }

        // 2. Auto-generate Draft Retur jika ada barang rusak/reject
        $rejectedItems = $goodsReceipt->items->where('qty_rejected', '>', 0);
        if ($rejectedItems->isNotEmpty()) {
            $returnNumber = $this->generateReturnNumber();
            $autoReturn = PurchaseReturn::create([
                'return_number'    => $returnNumber,
                'goods_receipt_id' => $goodsReceipt->id,
                'supplier_id'      => $goodsReceipt->purchaseOrder->supplier_id,
                'return_date'      => $goodsReceipt->received_date,
                'reason'           => "Auto-generate dari LPB #{$goodsReceipt->receipt_number}: Ditemukan barang rusak/reject saat penerimaan",
                'status'           => PurchaseReturnStatus::Draft,
                'notes'            => "Draft retur otomatis dibuat saat LPB #{$goodsReceipt->receipt_number} disetujui.",
            ]);

            foreach ($rejectedItems as $rej) {
                PurchaseReturnItem::create([
                    'purchase_return_id'    => $autoReturn->id,
                    'product_id'            => $rej->purchaseOrderItem->product_id,
                    'goods_receipt_item_id' => $rej->id,
                    'source_type'           => 'rejected',
                    'qty'                   => $rej->qty_rejected,
                    'unit_cost'             => $rej->unit_cost,
                    'reason'                => 'Barang rusak / reject saat penerimaan barang (LPB #' . $goodsReceipt->receipt_number . ')',
                ]);
            }
        }

        // 3. Update PO status
        $po = $goodsReceipt->purchaseOrder;
        if ($po) {
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
                'status' => $allDone ? PurchaseOrderStatus::Done : PurchaseOrderStatus::PartiallyReceived
            ]);
        }

        // 4. Auto-generate Purchase Invoice jika dipilih
        if ($goodsReceipt->auto_generate_invoice && !$goodsReceipt->is_invoiced) {
            $this->purchaseInvoiceService->createFromGoodsReceipt($goodsReceipt, [
                'supplier_invoice_number' => $goodsReceipt->supplier_invoice_number,
                'invoice_date'            => $goodsReceipt->invoice_date ?? $goodsReceipt->received_date,
                'due_date'                => $goodsReceipt->due_date ?? ($goodsReceipt->invoice_date ?? $goodsReceipt->received_date),
                'tax_rate'                => $goodsReceipt->invoice_tax_rate ?? ($goodsReceipt->purchaseOrder->tax_rate ?? 0),
                'notes'                   => !empty(trim($goodsReceipt->notes ?? '')) ? trim($goodsReceipt->notes) : null,
            ], asDraft: false);
        }
    }

    public function show(GoodsReceipt $goodsReceipt): View
    {
        $goodsReceipt->load([
            'purchaseOrder.supplier',
            'warehouse',
            'items.warehouse',
            'user',
            'approvedBy',
            'approvalRequests.approver',
            'items.purchaseOrderItem.product',
            'purchaseReturns',
            'purchaseInvoice',
        ]);

        return view('purchase.goods-receipts.show', compact('goodsReceipt'));
    }

    private function generateNumber(): string
    {
        return $this->generateDocumentNumber(GoodsReceipt::class, 'receipt_number', 'GRN');
    }

    private function generateReturnNumber(): string
    {
        return $this->generateDocumentNumber(PurchaseReturn::class, 'return_number', 'PRET');
    }
}
