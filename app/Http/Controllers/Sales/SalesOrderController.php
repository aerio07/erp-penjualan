<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Services\ApprovalService;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Traits\HasListFilters;

class SalesOrderController extends Controller
{
    use HasListFilters;

    public function __construct(
        private ApprovalService $approvalService,
        private StockService $stockService
    ) {}

    public function index(Request $request): View
    {
        $query = SalesOrder::with(['customer', 'user']);

        $query = $this->applySearch($query, $request, ['so_number', 'customer.name', 'notes']);
        $query = $this->applyFilter($query, $request, 'status');
        $query = $this->applyFilter($query, $request, 'fulfillment_status');
        $query = $this->applyFilter($query, $request, 'customer_id');
        $query = $this->applyDateRange($query, $request, 'order_date');
        $query = $this->applySort($query, $request, ['so_number', 'order_date', 'total_amount', 'status', 'created_at'], 'created_at', 'desc');

        $perPage = (int) $request->get('per_page', 15);
        $orders = $query->paginate($perPage)->withQueryString();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('sales.orders.index', compact('orders', 'customers'));
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
            'customer_id'            => 'required|exists:customers,id',
            'order_date'             => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'tax_rate'               => 'required|numeric|min:0|max:100',
            'discount_amount'        => 'nullable|numeric|min:0',
            'notes'                  => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.qty_ordered'    => 'required|integer|min:1',
            'items.*.unit_price'     => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $so = DB::transaction(function () use ($request) {
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

            $salesOrder = SalesOrder::create([
                'so_number'              => $this->generateNumber(),
                'customer_id'            => $request->customer_id,
                'user_id'                => Auth::id(),
                'status'                 => 'draft',
                'fulfillment_status'     => 'pending',
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
                    'sales_order_id'   => $salesOrder->id,
                    'product_id'       => $item['product_id'],
                    'qty_ordered'      => $item['qty_ordered'],
                    'unit_price'       => $item['unit_price'],
                    'discount_percent' => $discPercent,
                    'discount_amount'  => $discAmount,
                    'subtotal'         => $lineSubtotal - $discAmount,
                ]);
            }

            return $salesOrder;
        });

        return redirect()->route('sales.orders.show', $so)
            ->with('success', 'Sales Order berhasil dibuat.');
    }

    public function show(SalesOrder $salesOrder): View
    {
        $order = $salesOrder;
        $order->load(['customer', 'user', 'items.product', 'deliveries.warehouse', 'invoices']);

        return view('sales.orders.show', compact('order'));
    }

    public function edit(SalesOrder $salesOrder): View
    {
        $order = $salesOrder;
        abort_if($order->status !== 'draft', 403, 'SO yang sudah dikonfirmasi tidak dapat diedit.');

        $order->load(['items.product']);
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $products  = Product::where('is_active', true)->orderBy('name')->get();

        return view('sales.orders.edit', compact('order', 'customers', 'products'));
    }

    public function update(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $order = $salesOrder;
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

    public function destroy(SalesOrder $salesOrder): RedirectResponse
    {
        $order = $salesOrder;
        abort_if($order->status !== 'draft', 403);
        $order->delete();

        return redirect()->route('sales.orders.index')
            ->with('success', 'Sales Order berhasil dihapus.');
    }

    public function confirm(SalesOrder $salesOrder): RedirectResponse
    {
        $order = $salesOrder;
        abort_if($order->status !== 'draft', 403);

        if ($this->approvalService->needsApproval('sales_order', $order->total_amount)) {
            $order->update([
                'status' => 'waiting_approval',
                'fulfillment_status' => 'pending',
            ]);
            $this->approvalService->request(
                SalesOrder::class,
                $order->id,
                $order->total_amount,
                "SO #{$order->so_number} membutuhkan approval direksi."
            );

            return back()->with('info', 'Sales Order butuh approval karena nilai transaksi melebihi Rp 50.000.000.');
        }

        $order->update(['status' => 'confirmed']);
        $this->stockService->allocateStockForSalesOrder($order);

        $order->refresh();
        $statusLabel = match ($order->fulfillment_status) {
            'ready_to_ship' => 'Stok lengkap dan SIAP DIKIRIM.',
            'partially_available' => 'Stok tersedia SEBAGIAN (Partial Delivery siap).',
            'backorder' => 'Stok tidak mencukupi (Kebutuhan Pengadaan/Backorder otomatis dicatat untuk Purchasing).',
            default => '',
        };

        return back()->with('success', "Sales Order berhasil dikonfirmasi. {$statusLabel}");
    }

    public function cancel(SalesOrder $salesOrder): RedirectResponse
    {
        $order = $salesOrder;
        abort_if(!in_array($order->status, ['draft', 'waiting_approval', 'confirmed']), 403);
        $order->update(['status' => 'cancelled']);
        $this->stockService->releaseReservationsForSalesOrder($order);

        return back()->with('success', 'Sales Order berhasil dibatalkan dan alokasi stok telah dilepaskan.');
    }

    public function exportPdf(SalesOrder $salesOrder)
    {
        $order = $salesOrder;
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
