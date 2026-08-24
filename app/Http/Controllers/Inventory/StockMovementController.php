<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockService;
use App\Traits\HasListFilters;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    use HasListFilters;

    public function __construct(private StockService $stockService) {}

    public function summary(Request $request): View
    {
        $warehouseId = $request->query('warehouse_id');
        $warehouses  = Warehouse::where('is_active', true)->orderBy('name')->get();

        $products = Product::where('is_active', true)->orderBy('name')->get()->map(function ($product) use ($warehouseId) {
            $onHand    = $this->stockService->getOnHandStock($product->id, $warehouseId);
            $reserved  = $this->stockService->getReservedStock($product->id, $warehouseId);
            $available = $this->stockService->getAvailableStock($product->id, $warehouseId);
            $backorder = $this->stockService->getBackorderStock($product->id, $warehouseId);
            $incoming  = $this->stockService->getIncomingStock($product->id);
            $quarantine= $this->stockService->getQuarantineStock($product->id, $warehouseId);

            $product->current_stock    = $onHand;
            $product->reserved_stock   = $reserved;
            $product->available_stock  = $available;
            $product->backorder_stock  = $backorder;
            $product->incoming_stock   = $incoming;
            $product->quarantine_stock = $quarantine;
            $product->stock_value      = $onHand * $product->purchase_price;
            return $product;
        });

        return view('inventory.stock-summary', compact('products', 'warehouses', 'warehouseId'));
    }

    public function stockCard(Request $request): View
    {
        $productId   = $request->query('product_id');
        $warehouseId = $request->query('warehouse_id');
        $dateFrom    = $request->query('date_from');
        $dateTo      = $request->query('date_to');

        $products   = Product::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        $movements = collect();
        if ($productId && $warehouseId) {
            $movements = $this->stockService->getStockCard($productId, $warehouseId, $dateFrom, $dateTo);
        }

        return view('inventory.stock-card', compact('products', 'warehouses', 'productId', 'warehouseId', 'dateFrom', 'dateTo', 'movements'));
    }

    public function index(Request $request): View
    {
        $query = StockMovement::with(['product', 'warehouse', 'user']);

        $query = $this->applySearch($query, $request, ['product.name', 'product.sku', 'notes']);
        $query = $this->applyFilter($query, $request, 'product_id');
        $query = $this->applyFilter($query, $request, 'warehouse_id');
        $query = $this->applyFilter($query, $request, 'type');
        $query = $this->applyDateRange($query, $request, 'movement_date');
        $query = $this->applySort($query, $request, ['movement_date', 'quantity', 'unit_cost', 'type', 'created_at'], 'movement_date', 'desc');

        $perPage = (int) $request->get('per_page', 20);
        $movements  = $query->paginate($perPage)->withQueryString();
        $products   = Product::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('inventory.movements.index', compact('movements', 'products', 'warehouses'));
    }
}

