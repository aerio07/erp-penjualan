<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Traits\HasListFilters;
use App\Traits\GeneratesDocumentNumber;
use App\Traits\ExportsPdfDocument;

class PurchaseOrderController extends Controller
{
    use HasListFilters, GeneratesDocumentNumber, ExportsPdfDocument;

    public function __construct(private ApprovalService $approvalService) {}

    public function index(Request $request): View
    {
        $query = PurchaseOrder::with(['supplier', 'user', 'items']);

        $query = $this->applySearch($query, $request, ['po_number', 'supplier.name', 'notes']);
        $query = $this->applyFilter($query, $request, 'status');
        $query = $this->applyFilter($query, $request, 'supplier_id');
        $query = $this->applyDateRange($query, $request, 'order_date');
        $query = $this->applySort($query, $request, ['po_number', 'order_date', 'expected_date', 'total_amount', 'status', 'created_at'], 'order_date', 'desc');

        $perPage = (int) $request->get('per_page', 15);
        $orders = $query->paginate($perPage)->withQueryString();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        return view('purchase.orders.index', compact('orders', 'suppliers'));
    }

    public function create(Request $request): View
    {
        $suppliers  = Supplier::where('is_active', true)->orderBy('name')->get();
        $products   = Product::where('is_active', true)->orderBy('name')->get();

        $prefilledProductId = $request->get('product_id');
        $prefilledQty       = $request->get('qty');
        $demandIds          = $request->get('demand_ids');
        $prefilledPrId      = $request->get('purchase_request_id');

        $purchaseRequests = \App\Models\PurchaseRequest::with(['items.product'])
            ->where(function($q) use ($prefilledPrId) {
                $q->where('status', 'approved');
                if ($prefilledPrId) {
                    $q->orWhere('id', $prefilledPrId);
                }
            })
            ->orderBy('pr_number', 'desc')
            ->get();

        return view('purchase.orders.create', compact('suppliers', 'products', 'prefilledProductId', 'prefilledQty', 'demandIds', 'purchaseRequests', 'prefilledPrId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->sanitizeOrderInputs($request);

        $request->validate([
            'supplier_id'           => 'required|exists:suppliers,id',
            'purchase_request_id'   => 'required|exists:purchase_requests,id',
            'order_date'            => 'required|date',
            'expected_date'         => 'required|date|after_or_equal:order_date',
            'tax_rate'              => 'required|numeric|min:0|max:100',
            'discount_amount'       => 'nullable|numeric|min:0',
            'notes'                 => 'nullable|string',
            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|exists:products,id',
            'items.*.qty_ordered'   => 'required|integer|min:1',
            'items.*.unit_price'    => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'demand_ids'            => 'nullable|array',
            'demand_ids.*'          => 'exists:procurement_demands,id',
        ], [
            'purchase_request_id.required' => 'Purchase Order wajib merujuk pada Purchase Request (PR) yang telah disetujui.',
            'purchase_request_id.exists'   => 'Purchase Request yang dipilih tidak valid.',
            'expected_date.required'       => 'Tanggal expected delivery wajib diisi.',
            'expected_date.after_or_equal' => 'Tanggal expected delivery harus sama dengan atau setelah tanggal PO.',
        ]);

        // Validasi status PR harus sudah disetujui (Approved)
        $pr = \App\Models\PurchaseRequest::with('items')->findOrFail($request->purchase_request_id);
        if (!in_array($pr->status, ['approved', 'confirmed', 'ordered'])) {
            return back()
                ->withErrors([
                    'purchase_request_id' => 'Purchase Request #' . $pr->pr_number . ' belum disetujui (Approved). PO hanya dapat dibuat dari PR yang telah disetujui.'
                ])
                ->withInput();
        }

        // Qty item otomatis dikunci mengikuti kuantitas PR
        $items = $request->items;
        foreach ($items as &$item) {
            $prItem = $pr->items->firstWhere('product_id', $item['product_id']);
            if ($prItem) {
                $item['qty_ordered'] = (int) $prItem->qty;
            }
        }
        unset($item);

        // Hitung subtotal semua item terlebih dahulu untuk memvalidasi diskon header
        $subtotal = 0;
        foreach ($items as $item) {
            $lineSubtotal = $item['qty_ordered'] * $item['unit_price'];
            $disc = $lineSubtotal * (($item['discount_percent'] ?? 0) / 100);
            $subtotal += $lineSubtotal - $disc;
        }

        $discountHeader = (float) ($request->discount_amount ?? 0);
        if ($discountHeader > $subtotal) {
            return back()
                ->withErrors([
                    'discount_amount' => 'Diskon header (Rp ' . number_format($discountHeader, 0, ',', '.') . ') tidak boleh melebihi subtotal item (Rp ' . number_format($subtotal, 0, ',', '.') . ').'
                ])
                ->withInput();
        }

        DB::transaction(function () use ($request, $items, $subtotal, $discountHeader) {
            $taxableAmount  = max(0, $subtotal - $discountHeader);
            $taxAmount      = max(0, $taxableAmount * ($request->tax_rate / 100));
            $totalAmount    = $taxableAmount + $taxAmount;

            $po = PurchaseOrder::create([
                'po_number'           => $this->generateNumber(),
                'supplier_id'         => $request->supplier_id,
                'purchase_request_id' => $request->purchase_request_id,
                'user_id'             => Auth::id(),
                'status'              => 'draft',
                'order_date'          => $request->order_date,
                'expected_date'       => $request->expected_date,
                'discount_amount'     => $discountHeader,
                'tax_rate'            => $request->tax_rate,
                'tax_amount'          => $taxAmount,
                'total_amount'        => $totalAmount,
                'notes'               => $request->notes,
            ]);

            foreach ($items as $item) {
                $lineSubtotal = $item['qty_ordered'] * $item['unit_price'];
                $discPercent  = $item['discount_percent'] ?? 0;
                $discAmount   = $lineSubtotal * ($discPercent / 100);

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id'        => $item['product_id'],
                    'qty_ordered'       => $item['qty_ordered'],
                    'unit_price'        => $item['unit_price'],
                    'discount_percent'  => $discPercent,
                    'discount_amount'   => $discAmount,
                    'subtotal'          => $lineSubtotal - $discAmount,
                ]);
            }

            // Update tracking Purchase Request jika PO ini berasal dari PR
            if (!empty($request->purchase_request_id)) {
                $pr = \App\Models\PurchaseRequest::with('items')->find($request->purchase_request_id);
                if ($pr) {
                    foreach ($items as $item) {
                        $prItem = $pr->items->firstWhere('product_id', $item['product_id']);
                        if ($prItem) {
                            $prItem->increment('qty_ordered', $item['qty_ordered']);
                        }
                    }
                    $allFulfilled = $pr->fresh()->items->every(fn($it) => $it->qty_ordered >= $it->qty);
                    $pr->update(['status' => $allFulfilled ? 'confirmed' : 'approved']);
                }
            }

            // Hubungkan Procurement Demands ke PO jika berasal dari Demand Hub
            if (!empty($request->demand_ids)) {
                \App\Models\ProcurementDemand::whereIn('id', $request->demand_ids)
                    ->update([
                        'purchase_order_id' => $po->id,
                        'status'            => 'ordered',
                    ]);
            }
        });

        return redirect()->route('purchase.orders.index')
            ->with('success', 'Purchase Order berhasil dibuat.');
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $order = $purchaseOrder;
        $order->load(['supplier', 'purchaseRequest', 'user', 'items.product', 'goodsReceipts', 'invoices', 'approvalRequests.approver']);

        return view('purchase.orders.show', compact('order'));
    }


    public function edit(PurchaseOrder $purchaseOrder): View
    {
        $order = $purchaseOrder;
        abort_if(!in_array($order->status, ['draft']), 403, 'PO yang sudah dikonfirmasi tidak dapat diedit.');

        $order->load(['items.product', 'purchaseRequest']);
        $suppliers  = Supplier::where('is_active', true)->orderBy('name')->get();
        $products   = Product::where('is_active', true)->orderBy('name')->get();

        return view('purchase.orders.edit', compact('order', 'suppliers', 'products'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->sanitizeOrderInputs($request);

        $order = $purchaseOrder;
        abort_if($order->status !== 'draft', 403);

        // Reuse store validation logic
        $request->validate([
            'supplier_id'    => 'required|exists:suppliers,id',
            'order_date'     => 'required|date',
            'expected_date'  => 'required|date|after_or_equal:order_date',
            'tax_rate'       => 'required|numeric|min:0|max:100',
            'discount_amount'=> 'nullable|numeric|min:0',
            'notes'          => 'nullable|string',
            'items'          => 'required|array|min:1',
            'items.*.product_id'   => 'required|exists:products,id',
            'items.*.qty_ordered'  => 'required|integer|min:1',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ], [
            'expected_date.required' => 'Tanggal expected delivery wajib diisi.',
            'expected_date.after_or_equal' => 'Tanggal expected delivery harus sama dengan atau setelah tanggal PO.',
        ]);

        // Jika PO terkait dengan PR, pastikan qty item terkunci mengikuti kuantitas PR
        $items = $request->items;
        if (!empty($order->purchase_request_id)) {
            $pr = \App\Models\PurchaseRequest::with('items')->find($order->purchase_request_id);
            if ($pr) {
                foreach ($items as &$item) {
                    $prItem = $pr->items->firstWhere('product_id', $item['product_id']);
                    if ($prItem) {
                        $item['qty_ordered'] = (int) $prItem->qty;
                    }
                }
                unset($item);
            }
        }

        // Hitung subtotal semua item terlebih dahulu untuk memvalidasi diskon header
        $subtotal = 0;
        foreach ($items as $item) {
            $lineSubtotal = $item['qty_ordered'] * $item['unit_price'];
            $disc = $lineSubtotal * (($item['discount_percent'] ?? 0) / 100);
            $subtotal += $lineSubtotal - $disc;
        }

        $discountHeader = (float) ($request->discount_amount ?? 0);
        if ($discountHeader > $subtotal) {
            return back()
                ->withErrors([
                    'discount_amount' => 'Diskon header (Rp ' . number_format($discountHeader, 0, ',', '.') . ') tidak boleh melebihi subtotal item (Rp ' . number_format($subtotal, 0, ',', '.') . ').'
                ])
                ->withInput();
        }

        DB::transaction(function () use ($request, $order, $items, $subtotal, $discountHeader) {
            $order->items()->delete();

            $taxableAmount  = max(0, $subtotal - $discountHeader);
            $taxAmount      = max(0, $taxableAmount * ($request->tax_rate / 100));
            $totalAmount    = $taxableAmount + $taxAmount;

            $order->update([
                'supplier_id'    => $request->supplier_id,
                'order_date'     => $request->order_date,
                'expected_date'  => $request->expected_date,
                'discount_amount'=> $discountHeader,
                'tax_rate'       => $request->tax_rate,
                'tax_amount'     => $taxAmount,
                'total_amount'   => $totalAmount,
                'notes'          => $request->notes,
            ]);

            foreach ($items as $item) {
                $lineSubtotal = $item['qty_ordered'] * $item['unit_price'];
                $discPercent  = $item['discount_percent'] ?? 0;
                $discAmount   = $lineSubtotal * ($discPercent / 100);

                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id'        => $item['product_id'],
                    'qty_ordered'       => $item['qty_ordered'],
                    'unit_price'        => $item['unit_price'],
                    'discount_percent'  => $discPercent,
                    'discount_amount'   => $discAmount,
                    'subtotal'          => $lineSubtotal - $discAmount,
                ]);
            }
        });

        return redirect()->route('purchase.orders.show', $order)
            ->with('success', 'Purchase Order berhasil diperbarui.');
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $order = $purchaseOrder;
        abort_if($order->status !== 'draft', 403, 'Hanya PO berstatus draft yang dapat dihapus.');

        // Reset linked procurement demands if any
        $order->procurementDemands()->update([
            'purchase_order_id' => null,
            'status'            => 'pending',
        ]);

        $order->delete();

        return redirect()->route('purchase.orders.index')
            ->with('success', 'Purchase Order berhasil dihapus.');
    }

    public function confirm(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $order = $purchaseOrder;
        abort_if($order->status !== 'draft', 403);

        $order->update(['status' => 'waiting_approval']);
        $this->approvalService->request(
            PurchaseOrder::class,
            $order->id,
            $order->total_amount,
            "PO #{$order->po_number} membutuhkan approval sebelum dapat diproses."
        );

        return redirect()->route('purchase.orders.index')
            ->with('info', "Purchase Order #{$order->po_number} telah diajukan ke Menu Approval.");
    }

    public function approve(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_if(!Auth::user()->isAdmin(), 403, 'Hanya Admin yang dapat menyetujui Purchase Order.');
        abort_if($purchaseOrder->status !== 'waiting_approval', 403, 'Purchase Order tidak dalam status menunggu persetujuan.');

        $approval = $purchaseOrder->approvalRequests()->where('status', 'pending')->latest()->first();
        if ($approval) {
            $this->approvalService->approve($approval);
        }

        $purchaseOrder->update(['status' => 'confirmed']);

        return back()->with('success', "Purchase Order #{$purchaseOrder->po_number} berhasil disetujui (Approved) dan siap diteruskan ke supplier.");
    }

    public function revise(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_if(!Auth::user()->isAdmin(), 403, 'Hanya Admin yang dapat meminta revisi Purchase Order.');
        abort_if($purchaseOrder->status !== 'waiting_approval', 403, 'Purchase Order tidak dalam status menunggu persetujuan.');

        $request->validate([
            'revision_reason' => 'required|string|min:3',
        ], [
            'revision_reason.required' => 'Catatan revisi wajib diisi agar tim purchasing mengetahui apa yang perlu diperbaiki.',
            'revision_reason.min'      => 'Catatan revisi minimal 3 karakter.',
        ]);

        $approval = $purchaseOrder->approvalRequests()->where('status', 'pending')->latest()->first();
        if ($approval) {
            $this->approvalService->revise($approval, $request->revision_reason);
        }

        $purchaseOrder->update(['status' => 'draft']);

        return back()->with('success', "Purchase Order #{$purchaseOrder->po_number} berhasil dikembalikan ke status Draft untuk direvisi.");
    }

    public function reject(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_if(!Auth::user()->isAdmin(), 403, 'Hanya Admin yang dapat menolak Purchase Order.');
        abort_if($purchaseOrder->status !== 'waiting_approval', 403, 'Purchase Order tidak dalam status menunggu persetujuan.');

        $request->validate([
            'rejection_reason' => 'required|string|min:3|max:500',
        ], [
            'rejection_reason.required' => 'Alasan penolakan Purchase Order wajib diisi.',
            'rejection_reason.min'      => 'Alasan penolakan minimal 3 karakter.',
            'rejection_reason.max'      => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        $reason = trim((string) $request->rejection_reason);

        $approval = $purchaseOrder->approvalRequests()->where('status', 'pending')->latest()->first();
        if ($approval) {
            $this->approvalService->reject($approval, $reason);
        }

        // Kembalikan procurement demands ke pending jika ada
        $purchaseOrder->procurementDemands()->update([
            'purchase_order_id' => null,
            'status'            => 'pending',
        ]);

        // Kembalikan kuantitas Purchase Request jika PO berasal dari PR
        if (!empty($purchaseOrder->purchase_request_id)) {
            $pr = \App\Models\PurchaseRequest::with('items')->find($purchaseOrder->purchase_request_id);
            if ($pr) {
                foreach ($purchaseOrder->items as $poItem) {
                    $prItem = $pr->items->firstWhere('product_id', $poItem->product_id);
                    if ($prItem) {
                        $prItem->decrement('qty_ordered', min((int)$prItem->qty_ordered, (int)$poItem->qty_ordered));
                    }
                }
                $pr->update(['status' => 'approved']);
            }
        }

        $updateData = ['status' => 'cancelled'];
        if (\Illuminate\Support\Facades\Schema::hasColumn('purchase_orders', 'cancel_reason')) {
            $updateData['cancel_reason'] = $reason;
        } else {
            $updateData['notes'] = ($purchaseOrder->notes ? $purchaseOrder->notes . "\n" : '') . "[Alasan Penolakan]: " . $reason;
        }

        $purchaseOrder->update($updateData);

        return back()->with('success', "Purchase Order #{$purchaseOrder->po_number} berhasil ditolak (Rejected) dan dibatalkan.");
    }


    public function cancel(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $order = $purchaseOrder;
        abort_if(!in_array($order->status, ['draft', 'waiting_approval']), 403, 'Purchase Order yang sudah dikonfirmasi tidak dapat dibatalkan.');

        $request->validate([
            'cancel_reason' => 'required|string|min:3|max:500',
        ], [
            'cancel_reason.required' => 'Alasan pembatalan Purchase Order wajib diisi.',
            'cancel_reason.min'      => 'Alasan pembatalan minimal 3 karakter.',
            'cancel_reason.max'      => 'Alasan pembatalan maksimal 500 karakter.',
        ]);

        $reason = trim((string) $request->cancel_reason);

        // Batalkan approval request yang masih pending jika ada
        $order->approvalRequests()->where('status', 'pending')->update([
            'status'           => 'rejected',
            'rejection_reason' => $reason,
        ]);

        // Kembalikan procurement demands ke pending jika ada
        $order->procurementDemands()->update([
            'purchase_order_id' => null,
            'status'            => 'pending',
        ]);

        // Kembalikan kuantitas Purchase Request jika PO berasal dari PR
        if (!empty($order->purchase_request_id)) {
            $pr = \App\Models\PurchaseRequest::with('items')->find($order->purchase_request_id);
            if ($pr) {
                foreach ($order->items as $poItem) {
                    $prItem = $pr->items->firstWhere('product_id', $poItem->product_id);
                    if ($prItem) {
                        $prItem->decrement('qty_ordered', min((int)$prItem->qty_ordered, (int)$poItem->qty_ordered));
                    }
                }
                // Kembalikan status PR ke approved agar siap dibuatkan PO baru
                $pr->update(['status' => 'approved']);
            }
        }

        $updateData = ['status' => 'cancelled'];
        if (\Illuminate\Support\Facades\Schema::hasColumn('purchase_orders', 'cancel_reason')) {
            $updateData['cancel_reason'] = $reason;
        } else {
            $updateData['notes'] = ($order->notes ? $order->notes . "\n" : '') . "[Alasan Pembatalan]: " . $reason;
        }

        $order->update($updateData);

        return back()->with('success', 'Purchase Order #' . $order->po_number . ' berhasil dibatalkan.');
    }

    public function exportPdf(PurchaseOrder $purchaseOrder)
    {
        abort_if(!in_array($purchaseOrder->status, ['confirmed', 'partially_received', 'done']), 403, 'PDF hanya dapat diunduh setelah Purchase Order disetujui.');

        $order = $purchaseOrder;
        $order->load(['supplier', 'warehouse', 'user', 'items.product', 'approvalRequests.approver']);

        $safeNumber = str_replace(['/', '\\'], '-', $order->po_number);
        $filename = str_starts_with($safeNumber, 'PO-') ? $safeNumber : "PO-{$safeNumber}";

        return $this->streamPdf('pdf.purchase-order', compact('order'), $filename);
    }

    private function generateNumber(): string
    {
        return $this->generateDocumentNumber(PurchaseOrder::class, 'po_number', 'PO');
    }

    private function sanitizeOrderInputs(Request $request): void
    {
        $mergeData = [];
        if ($request->has('discount_amount')) {
            $cleaned = str_replace(['.', ','], ['', '.'], (string)$request->discount_amount);
            $mergeData['discount_amount'] = is_numeric($cleaned) ? (float)$cleaned : 0;
        }
        if ($request->has('items') && is_array($request->items)) {
            $items = $request->items;
            foreach ($items as $k => $item) {
                if (isset($item['unit_price'])) {
                    $cleaned = str_replace(['.', ','], ['', '.'], (string)$item['unit_price']);
                    $items[$k]['unit_price'] = is_numeric($cleaned) ? (float)$cleaned : 0;
                }
            }
            $mergeData['items'] = $items;
        }
        if (!empty($mergeData)) {
            $request->merge($mergeData);
        }
    }
}
