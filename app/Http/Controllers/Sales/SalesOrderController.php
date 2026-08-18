<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

class SalesOrderController extends Controller
{
    public function __construct(private ApprovalService $approvalService) {}

    public function index(): View
    {
        $orders = SalesOrder::with(['customer', 'user'])
            ->latest('order_date')
            ->paginate(20);

        return view('sales.orders.index', compact('orders'));
    }

    public function create(): View
    {
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $products  = Product::where('is_active', true)->orderBy('name')->get();

        return view('sales.orders.create', compact('customers', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'customer_id'           => 'required|exists:customers,id',
            'order_date'            => 'required|date',
            'expected_delivery_date'=> 'nullable|date|after_or_equal:order_date',
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

            foreach ($request->items as $item) {
                $lineSubtotal = $item['qty_ordered'] * $item['unit_price'];
                $disc = $lineSubtotal * (($item['discount_percent'] ?? 0) / 100);
                $subtotal += $lineSubtotal - $disc;
            }

            $discountHeader = $request->discount_amount ?? 0;
            $taxableAmount  = $subtotal - $discountHeader;
            $taxAmount      = $taxableAmount * ($request->tax_rate / 100);
            $totalAmount    = $taxableAmount + $taxAmount;

            $so = SalesOrder::create([
                'so_number'              => $this->generateNumber(),
                'customer_id'            => $request->customer_id,
                'user_id'                => Auth::id(),
                'status'                 => 'draft',
                'order_date'             => $request->order_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'discount_amount'        => $discountHeader,
                'tax_rate'               => $request->tax_rate,
                'tax_amount'             => $taxAmount,
                'total_amount'           => $totalAmount,
                'notes'                  => $request->notes,
            ]);

            foreach ($request->items as $item) {
                $lineSubtotal = $item['qty_ordered'] * $item['unit_price'];
                $discPercent  = $item['discount_percent'] ?? 0;
                $discAmount   = $lineSubtotal * ($discPercent / 100);

                SalesOrderItem::create([
                    'sales_order_id'   => $so->id,
                    'product_id'       => $item['product_id'],
                    'qty_ordered'      => $item['qty_ordered'],
                    'unit_price'       => $item['unit_price'],
                    'discount_percent' => $discPercent,
                    'discount_amount'  => $discAmount,
                    'subtotal'         => $lineSubtotal - $discAmount,
                ]);
            }
        });

        return redirect()->route('sales.orders.index')
            ->with('success', 'Sales Order berhasil dibuat.');
    }

    public function show(SalesOrder $order): View
    {
        $order->load(['customer', 'user', 'items.product', 'deliveries', 'invoices']);

        return view('sales.orders.show', compact('order'));
    }

    public function edit(SalesOrder $order): View
    {
        abort_if($order->status !== 'draft', 403, 'SO yang sudah dikonfirmasi tidak dapat diedit.');

        $order->load(['items.product']);
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $products  = Product::where('is_active', true)->orderBy('name')->get();

        return view('sales.orders.edit', compact('order', 'customers', 'products'));
    }

    public function update(Request $request, SalesOrder $order): RedirectResponse
    {
        abort_if($order->status !== 'draft', 403);

        $request->validate([
            'customer_id'  => 'required|exists:customers,id',
            'order_date'   => 'required|date',
            'tax_rate'     => 'required|numeric|min:0|max:100',
            'items'        => 'required|array|min:1',
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
                'customer_id'            => $request->customer_id,
                'order_date'             => $request->order_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'discount_amount'        => $discountHeader,
                'tax_rate'               => $request->tax_rate,
                'tax_amount'             => $taxAmount,
                'total_amount'           => $taxableAmount + $taxAmount,
                'notes'                  => $request->notes,
            ]);

            foreach ($request->items as $item) {
                $lineSubtotal = $item['qty_ordered'] * $item['unit_price'];
                $discPercent  = $item['discount_percent'] ?? 0;
                $discAmount   = $lineSubtotal * ($discPercent / 100);

                SalesOrderItem::create([
                    'sales_order_id'   => $order->id,
                    'product_id'       => $item['product_id'],
                    'qty_ordered'      => $item['qty_ordered'],
                    'unit_price'       => $item['unit_price'],
                    'discount_percent' => $discPercent,
                    'discount_amount'  => $discAmount,
                    'subtotal'         => $lineSubtotal - $discAmount,
                ]);
            }
        });

        return redirect()->route('sales.orders.show', $order)
            ->with('success', 'Sales Order berhasil diperbarui.');
    }

    public function destroy(SalesOrder $order): RedirectResponse
    {
        abort_if($order->status !== 'draft', 403);
        $order->delete();

        return redirect()->route('sales.orders.index')
            ->with('success', 'Sales Order berhasil dihapus.');
    }

    public function confirm(SalesOrder $order): RedirectResponse
    {
        abort_if($order->status !== 'draft', 403);

        if ($this->approvalService->needsApproval('sales_order', $order->total_amount)) {
            $order->update(['status' => 'waiting_approval']);
            $this->approvalService->request(
                SalesOrder::class,
                $order->id,
                $order->total_amount,
                "SO #{$order->so_number} membutuhkan approval direksi."
            );

            return back()->with('info', 'Sales Order butuh approval karena nilai transaksi melebihi Rp 50.000.000.');
        }

        $order->update(['status' => 'confirmed']);

        return back()->with('success', 'Sales Order berhasil dikonfirmasi.');
    }

    public function cancel(SalesOrder $order): RedirectResponse
    {
        abort_if(!in_array($order->status, ['draft', 'waiting_approval', 'confirmed']), 403);
        $order->update(['status' => 'cancelled']);

        return back()->with('success', 'Sales Order berhasil dibatalkan.');
    }

    public function exportPdf(SalesOrder $order)
    {
        $order->load(['customer', 'user', 'items.product']);
        $pdf = Pdf::loadView('pdf.sales-order', compact('order'));

        return $pdf->download("SO-{$order->so_number}.pdf");
    }

    private function generateNumber(): string
    {
        $prefix = 'SO-' . date('Ym') . '-';
        $last   = SalesOrder::where('so_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('so_number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
