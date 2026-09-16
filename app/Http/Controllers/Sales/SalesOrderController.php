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
use App\Traits\GeneratesDocumentNumber;
use App\Traits\ExportsPdfDocument;

class SalesOrderController extends Controller
{
    use HasListFilters, GeneratesDocumentNumber, ExportsPdfDocument;

    public function __construct(
        private ApprovalService $approvalService,
        private StockService $stockService
    ) {}

    public function index(Request $request): View
    {
        $query = SalesOrder::with(['customer', 'user', 'items.reservations.warehouse']);

        $query = $this->applySearch($query, $request, ['so_number', 'customer.name', 'notes']);
        $query = $this->applyFilter($query, $request, 'status');
        $query = $this->applyFilter($query, $request, 'fulfillment_status');
        $query = $this->applyFilter($query, $request, 'channel');
        $query = $this->applyFilter($query, $request, 'customer_id');
        $query = $this->applyDateRange($query, $request, 'order_date');
        $query = $this->applySort($query, $request, ['so_number', 'order_date', 'total_amount', 'status', 'created_at'], 'order_date', 'desc');

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
            'channel'                => 'nullable|in:online,offline',
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

        // Hitung subtotal semua item terlebih dahulu untuk memvalidasi diskon header
        $subtotal = 0;
        foreach ($request->items as $item) {
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

        $so = DB::transaction(function () use ($request, $subtotal, $discountHeader) {
            $taxableAmount  = max(0, $subtotal - $discountHeader);
            $taxAmount      = max(0, $taxableAmount * ($request->tax_rate / 100));
            $totalAmount    = $taxableAmount + $taxAmount;

            $salesOrder = SalesOrder::create([
                'so_number'              => $this->generateNumber(),
                'customer_id'            => $request->customer_id,
                'channel'                => $request->input('channel', 'offline'),
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

        return redirect()->route('sales.orders.index')
            ->with('success', 'Sales Order #' . $so->so_number . ' berhasil dibuat.');
    }

    public function show(SalesOrder $salesOrder): View
    {
        $order = $salesOrder;
        $order->load([
            'customer', 
            'user', 
            'items.product', 
            'items.reservations.warehouse', 
            'items.procurementDemands', 
            'deliveries.warehouse', 
            'invoices',
            'approvalRequests.approver',
            'approvalRequests.requester',
        ]);

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
            'customer_id'            => 'required|exists:customers,id',
            'channel'                => 'nullable|in:online,offline',
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

        // Hitung subtotal semua item terlebih dahulu untuk memvalidasi diskon header
        $subtotal = 0;
        foreach ($request->items as $item) {
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

        DB::transaction(function () use ($request, $order, $subtotal, $discountHeader) {
            $order->items()->delete();

            $taxableAmount  = max(0, $subtotal - $discountHeader);
            $taxAmount      = max(0, $taxableAmount * ($request->tax_rate / 100));
            $totalAmount    = $taxableAmount + $taxAmount;

            $order->update([
                'customer_id'            => $request->customer_id,
                'channel'                => $request->input('channel', $order->channel ?? 'offline'),
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

        $order->approvalRequests()->where('status', 'pending')->update([
            'status'           => 'rejected',
            'rejection_reason' => 'Sales Order dihapus oleh pengguna.',
        ]);

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
                "SO #{$order->so_number} membutuhkan approval sebelum dapat diproses."
            );

            return back()->with('info', "Sales Order #{$order->so_number} telah dikirim untuk approval.");
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

        if ($order->deliveries()->where('status', '!=', 'cancelled')->exists()) {
            return back()->with('error', 'Sales Order tidak dapat dibatalkan karena sudah memiliki Surat Jalan pengiriman.');
        }

        // Batalkan approval request yang masih pending jika ada
        $order->approvalRequests()->where('status', 'pending')->update([
            'status'           => 'rejected',
            'rejection_reason' => 'Sales Order dibatalkan oleh pengguna.',
        ]);

        $order->update(['status' => 'cancelled']);
        $this->stockService->releaseReservationsForSalesOrder($order);

        return back()->with('success', 'Sales Order berhasil dibatalkan dan alokasi stok telah dilepaskan.');
    }

    public function approve(SalesOrder $salesOrder): RedirectResponse
    {
        abort_if(!Auth::user()->isAdmin(), 403, 'Hanya Admin yang dapat menyetujui Sales Order.');
        abort_if($salesOrder->status !== 'waiting_approval', 403, 'Sales Order tidak dalam status menunggu persetujuan.');

        $approval = $salesOrder->approvalRequests()->where('status', 'pending')->latest()->first();
        if ($approval) {
            $this->approvalService->approve($approval);
        }

        $salesOrder->update(['status' => 'confirmed']);
        $this->stockService->allocateStockForSalesOrder($salesOrder);

        $salesOrder->refresh();
        $statusLabel = match ($salesOrder->fulfillment_status) {
            'ready_to_ship'       => 'Stok lengkap dan SIAP DIKIRIM.',
            'partially_available' => 'Stok tersedia SEBAGIAN (Partial Delivery siap).',
            'backorder'           => 'Stok tidak mencukupi (Kebutuhan Pengadaan otomatis dicatat untuk Purchasing).',
            default               => '',
        };

        return back()->with('success', "Sales Order #{$salesOrder->so_number} berhasil disetujui (Approved). {$statusLabel}");
    }

    public function revise(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        abort_if(!Auth::user()->isAdmin(), 403, 'Hanya Admin yang dapat meminta revisi Sales Order.');
        abort_if($salesOrder->status !== 'waiting_approval', 403, 'Sales Order tidak dalam status menunggu persetujuan.');

        $request->validate([
            'revision_reason' => 'required|string|min:3',
        ], [
            'revision_reason.required' => 'Catatan revisi wajib diisi agar tim sales mengetahui apa yang perlu diperbaiki.',
            'revision_reason.min'      => 'Catatan revisi minimal 3 karakter.',
        ]);

        $approval = $salesOrder->approvalRequests()->where('status', 'pending')->latest()->first();
        if ($approval) {
            $this->approvalService->revise($approval, $request->revision_reason);
        }

        $salesOrder->update([
            'status'             => 'draft',
            'fulfillment_status' => 'pending',
        ]);
        $this->stockService->releaseReservationsForSalesOrder($salesOrder);

        return back()->with('success', "Sales Order #{$salesOrder->so_number} berhasil dikembalikan ke status Draft untuk direvisi.");
    }

    public function reject(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        abort_if(!Auth::user()->isAdmin(), 403, 'Hanya Admin yang dapat menolak Sales Order.');
        abort_if($salesOrder->status !== 'waiting_approval', 403, 'Sales Order tidak dalam status menunggu persetujuan.');

        $request->validate([
            'rejection_reason' => 'nullable|string',
        ]);

        $approval = $salesOrder->approvalRequests()->where('status', 'pending')->latest()->first();
        if ($approval) {
            $this->approvalService->reject($approval, $request->rejection_reason);
        }

        $salesOrder->update(['status' => 'cancelled']);
        $this->stockService->releaseReservationsForSalesOrder($salesOrder);

        return back()->with('success', "Sales Order #{$salesOrder->so_number} berhasil ditolak (Rejected) dan dibatalkan.");
    }

    public function exportPdf(SalesOrder $salesOrder)
    {
        abort_if(!in_array($salesOrder->status, ['confirmed', 'partially_delivered', 'done']), 403, 'PDF hanya dapat diunduh setelah Sales Order disetujui.');

        $order = $salesOrder;
        $order->load(['customer', 'user', 'items.product', 'approvalRequests.approver']);

        $safeNumber = str_replace(['/', '\\'], '-', $order->so_number);
        $filename = str_starts_with($safeNumber, 'SO-') ? $safeNumber : "SO-{$safeNumber}";

        return $this->streamPdf('pdf.sales-order', compact('order'), $filename);
    }

    private function generateNumber(): string
    {
        return $this->generateDocumentNumber(SalesOrder::class, 'so_number', 'SO');
    }
}
