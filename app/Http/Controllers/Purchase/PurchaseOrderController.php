<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseOrderController extends Controller
{
    public function __construct(private ApprovalService $approvalService) {}

    public function index(): View
    {
        $orders = PurchaseOrder::with(['supplier', 'user'])
            ->latest()
            ->paginate(20);

        return view('purchase.orders.index', compact('orders'));
    }

    public function create(): View
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $products  = Product::where('is_active', true)->orderBy('name')->get();

        return view('purchase.orders.create', compact('suppliers', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'supplier_id'           => 'required|exists:suppliers,id',
            'order_date'            => 'required|date',
            'expected_date'         => 'nullable|date|after_or_equal:order_date',
            'tax_rate'              => 'required|numeric|min:0|max:100',
            'discount_amount'       => 'nullable|numeric|min:0',
            'notes'                 => 'nullable|string',
            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|exists:products,id',
            'items.*.qty_ordered'   => 'required|integer|min:1',
            'items.*.unit_price'    => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
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
                'user_id'         => Auth::id(),
                'status'          => 'draft',
                'order_date'      => $request->order_date,
                'expected_date'   => $request->expected_date,
                'discount_amount' => $discountHeader,
                'tax_rate'        => $request->tax_rate,
                'tax_amount'      => $taxAmount,
                'total_amount'    => $totalAmount,
                'notes'           => $request->notes,
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
        });

        return redirect()->route('purchase.orders.index')
            ->with('success', 'Purchase Order berhasil dibuat.');
    }

    public function show(PurchaseOrder $order): View
    {
        $order->load(['supplier', 'user', 'items.product', 'goodsReceipts', 'invoices']);

        return view('purchase.orders.show', compact('order'));
    }

    public function edit(PurchaseOrder $order): View
    {
        abort_if(!in_array($order->status, ['draft']), 403, 'PO yang sudah dikonfirmasi tidak dapat diedit.');

        $order->load(['items.product']);
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $products  = Product::where('is_active', true)->orderBy('name')->get();

        return view('purchase.orders.edit', compact('order', 'suppliers', 'products'));
    }

    public function update(Request $request, PurchaseOrder $order): RedirectResponse
    {
        abort_if($order->status !== 'draft', 403);

        // Reuse store validation logic
        $request->validate([
            'supplier_id'    => 'required|exists:suppliers,id',
            'order_date'     => 'required|date',
            'expected_date'  => 'nullable|date|after_or_equal:order_date',
            'tax_rate'       => 'required|numeric|min:0|max:100',
            'discount_amount'=> 'nullable|numeric|min:0',
            'notes'          => 'nullable|string',
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
                'order_date'     => $request->order_date,
                'expected_date'  => $request->expected_date,
                'discount_amount'=> $discountHeader,
                'tax_rate'       => $request->tax_rate,
                'tax_amount'     => $taxAmount,
                'total_amount'   => $taxableAmount + $taxAmount,
                'notes'          => $request->notes,
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

    public function destroy(PurchaseOrder $order): RedirectResponse
    {
        abort_if($order->status !== 'draft', 403, 'Hanya PO berstatus draft yang dapat dihapus.');
        $order->delete();

        return redirect()->route('purchase.orders.index')
            ->with('success', 'Purchase Order berhasil dihapus.');
    }

    public function confirm(PurchaseOrder $order): RedirectResponse
    {
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

    public function cancel(PurchaseOrder $order): RedirectResponse
    {
        abort_if(!in_array($order->status, ['draft', 'waiting_approval', 'confirmed']), 403);
        $order->update(['status' => 'cancelled']);

        return back()->with('success', 'Purchase Order berhasil dibatalkan.');
    }

    public function exportPdf(PurchaseOrder $order)
    {
        $order->load(['supplier', 'user', 'items.product']);
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
