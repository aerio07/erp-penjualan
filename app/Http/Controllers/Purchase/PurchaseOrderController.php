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

class PurchaseOrderController extends Controller
{
    use HasListFilters;

    public function __construct(private ApprovalService $approvalService) {}

    public function index(Request $request): View
    {
        $query = PurchaseOrder::with(['supplier', 'user']);

        $query = $this->applySearch($query, $request, ['po_number', 'supplier.name', 'notes', 'ship_to']);
        $query = $this->applyFilter($query, $request, 'status');
        $query = $this->applyFilter($query, $request, 'supplier_id');
        $query = $this->applyDateRange($query, $request, 'order_date');
        $query = $this->applySort($query, $request, ['po_number', 'order_date', 'total_amount', 'status', 'created_at'], 'created_at', 'desc');

        $perPage = (int) $request->get('per_page', 15);
        $orders = $query->paginate($perPage)->withQueryString();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        return view('purchase.orders.index', compact('orders', 'suppliers'));
    }

    public function create(Request $request): View
    {
        $suppliers  = Supplier::where('is_active', true)->orderBy('name')->get();
        $products   = Product::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        $prefilledProductId = $request->get('product_id');
        $prefilledQty       = $request->get('qty');
        $demandIds          = $request->get('demand_ids');

        return view('purchase.orders.create', compact('suppliers', 'products', 'warehouses', 'prefilledProductId', 'prefilledQty', 'demandIds'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'supplier_id'           => 'required|exists:suppliers,id',
            'warehouse_id'          => 'nullable|exists:warehouses,id',
            'order_date'            => 'required|date',
            'expected_date'         => 'nullable|date|after_or_equal:order_date',
            'tax_rate'              => 'required|numeric|min:0|max:100',
            'discount_amount'       => 'nullable|numeric|min:0',
            'notes'                 => 'nullable|string',
            'ship_to'               => 'nullable|string',
            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|exists:products,id',
            'items.*.qty_ordered'   => 'required|integer|min:1',
            'items.*.unit_price'    => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'demand_ids'            => 'nullable|array',
            'demand_ids.*'          => 'exists:procurement_demands,id',
        ]);

        DB::transaction(function () use ($request) {
            $subtotal = 0;

            // Hitung subtotal semua item
            foreach ($request->items as $item) {
                $lineSubtotal = $item['qty_ordered'] * $item['unit_price'];
                $disc = $lineSubtotal * (($item['discount_percent'] ?? 0) / 100);
                $subtotal += $lineSubtotal - $disc;
            }

            $discountHeader = $request->discount_amount ?? 0;
            $taxableAmount  = $subtotal - $discountHeader;
            $taxAmount      = $taxableAmount * ($request->tax_rate / 100);
            $totalAmount    = $taxableAmount + $taxAmount;

            $po = PurchaseOrder::create([
                'po_number'       => $this->generateNumber(),
                'supplier_id'     => $request->supplier_id,
                'warehouse_id'    => $request->warehouse_id,
                'user_id'         => Auth::id(),
                'status'          => 'draft',
                'order_date'      => $request->order_date,
                'expected_date'   => $request->expected_date,
                'discount_amount' => $discountHeader,
                'tax_rate'        => $request->tax_rate,
                'tax_amount'      => $taxAmount,
                'total_amount'    => $totalAmount,
                'notes'           => $request->notes,
                'ship_to'         => $request->ship_to,
            ]);

            foreach ($request->items as $item) {
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
        $order->load(['supplier', 'warehouse', 'user', 'items.product', 'goodsReceipts', 'invoices']);

        return view('purchase.orders.show', compact('order'));
    }

    public function edit(PurchaseOrder $purchaseOrder): View
    {
        $order = $purchaseOrder;
        abort_if(!in_array($order->status, ['draft']), 403, 'PO yang sudah dikonfirmasi tidak dapat diedit.');

        $order->load(['items.product', 'warehouse']);
        $suppliers  = Supplier::where('is_active', true)->orderBy('name')->get();
        $products   = Product::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('purchase.orders.edit', compact('order', 'suppliers', 'products', 'warehouses'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $order = $purchaseOrder;
        abort_if($order->status !== 'draft', 403);

        // Reuse store validation logic
        $request->validate([
            'supplier_id'    => 'required|exists:suppliers,id',
            'warehouse_id'   => 'nullable|exists:warehouses,id',
            'order_date'     => 'required|date',
            'expected_date'  => 'nullable|date|after_or_equal:order_date',
            'tax_rate'       => 'required|numeric|min:0|max:100',
            'discount_amount'=> 'nullable|numeric|min:0',
            'notes'          => 'nullable|string',
            'ship_to'        => 'nullable|string',
            'items'          => 'required|array|min:1',
            'items.*.product_id'   => 'required|exists:products,id',
            'items.*.qty_ordered'  => 'required|integer|min:1',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($request, $order) {
            $order->items()->delete();
            $subtotal = 0;

            foreach ($request->items as $item) {
                $lineSubtotal = $item['qty_ordered'] * $item['unit_price'];
                $disc = $lineSubtotal * (($item['discount_percent'] ?? 0) / 100);
                $subtotal += $lineSubtotal - $disc;
            }

            $discountHeader = $request->discount_amount ?? 0;
            $taxableAmount  = $subtotal - $discountHeader;
            $taxAmount      = $taxableAmount * ($request->tax_rate / 100);

            $order->update([
                'supplier_id'    => $request->supplier_id,
                'warehouse_id'   => $request->warehouse_id,
                'order_date'     => $request->order_date,
                'expected_date'  => $request->expected_date,
                'discount_amount'=> $discountHeader,
                'tax_rate'       => $request->tax_rate,
                'tax_amount'     => $taxAmount,
                'total_amount'   => $taxableAmount + $taxAmount,
                'notes'          => $request->notes,
                'ship_to'        => $request->ship_to,
            ]);

            foreach ($request->items as $item) {
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

        return back()->with('info', 'PO telah dikirim untuk approval.');
    }

    public function cancel(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $order = $purchaseOrder;
        abort_if(!in_array($order->status, ['draft', 'waiting_approval', 'confirmed']), 403);
        $order->update(['status' => 'cancelled']);

        return back()->with('success', 'Purchase Order berhasil dibatalkan.');
    }

    public function exportPdf(PurchaseOrder $purchaseOrder)
    {
        $order = $purchaseOrder;
        $order->load(['supplier', 'warehouse', 'user', 'items.product']);
        $pdf = Pdf::loadView('pdf.purchase-order', compact('order'));

        return $pdf->download("PO-{$order->po_number}.pdf");
    }

    private function generateNumber(): string
    {
        $prefix = 'PO-' . date('Ym') . '-';
        $last   = PurchaseOrder::where('po_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('po_number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
