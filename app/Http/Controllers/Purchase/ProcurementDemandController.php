<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\ProcurementDemand;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Traits\HasListFilters;

class ProcurementDemandController extends Controller
{
    use HasListFilters;

    public function __construct(private StockService $stockService) {}

    public function index(Request $request): View
    {
        // Auto-sync SO terkonfirmasi yang belum dialokasikan stoknya & sync seluruh status demand
        $this->stockService->syncAllPendingSalesOrders();
        $this->stockService->syncAllProcurementDemands();

        $status = $request->get('status', 'active'); // 'active' (pending + ordered), 'pending', 'ordered', 'fulfilled', 'all'
        $warehouseId = $request->get('warehouse_id');

        $query = ProcurementDemand::with(['product', 'warehouse', 'salesOrder.customer', 'purchaseOrder.supplier']);

        if ($status === 'active') {
            $query->whereIn('status', ['pending', 'ordered']);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $query = $this->applySearch($query, $request, ['demand_number', 'product.name', 'product.sku', 'salesOrder.so_number', 'salesOrder.customer.name']);

        $demands = $query->orderBy('required_date', 'asc')->paginate(25)->withQueryString();

        // Konsolidasi Kebutuhan per Produk untuk Tim Purchasing
        $activeDemands = ProcurementDemand::with(['product', 'salesOrder.customer'])
            ->whereIn('status', ['pending', 'ordered'])
            ->get();

        $consolidatedProducts = $activeDemands->groupBy('product_id')->map(function ($items, $productId) use ($warehouseId) {
            $product = $items->first()->product;
            $totalDemanded = $items->sum(fn($d) => max(0, $d->qty_demanded - $d->qty_fulfilled));
            $onHand        = $this->stockService->getOnHandStock($productId, $warehouseId);
            $reserved      = $this->stockService->getReservedStock($productId, $warehouseId);
            $available     = $this->stockService->getAvailableStock($productId, $warehouseId);
            $incoming      = $this->stockService->getIncomingStock($productId);
            $netNeeded     = max(0, $totalDemanded - ($available + $incoming));

            return [
                'product'              => $product,
                'total_demanded'       => $totalDemanded,
                'on_hand'              => $onHand,
                'reserved'             => $reserved,
                'available'            => $available,
                'incoming'             => $incoming,
                'net_needed'           => $netNeeded,
                'demand_ids'           => $items->where('status', 'pending')->pluck('id')->toArray(),
                'orders_count'         => $items->pluck('sales_order_id')->unique()->count(),
                'demands_list'         => $items,
            ];
        })->values();

        // Kartu Metrik Dashboard Pengadaan
        $totalBackorderItems = $activeDemands->pluck('product_id')->unique()->count();
        $totalWaitingOrders  = $activeDemands->pluck('sales_order_id')->unique()->count();
        $totalShortageQty    = $activeDemands->sum(fn($d) => max(0, $d->qty_demanded - $d->qty_fulfilled));
        $totalIncomingQty    = $consolidatedProducts->sum('incoming');

        $warehouses = Warehouse::where('is_active', true)->get();

        return view('purchase.demands.index', compact(
            'demands',
            'consolidatedProducts',
            'totalBackorderItems',
            'totalWaitingOrders',
            'totalShortageQty',
            'totalIncomingQty',
            'warehouses',
            'status'
        ));
    }
}
