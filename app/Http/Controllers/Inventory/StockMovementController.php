<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function summary(Request $request): View
    {
        $warehouseId = $request->query('warehouse_id');
        $warehouses  = Warehouse::where('is_active', true)->orderBy('name')->get();

        $products = Product::where('is_active', true)->orderBy('name')->get()->map(function ($product) use ($warehouseId) {
            $stock = $this->stockService->getCurrentStock($product->id, $warehouseId);
            $quarantine = $this->stockService->getQuarantineStock($product->id, $warehouseId);
            $product->current_stock    = $stock;
            $product->quarantine_stock = $quarantine;
            $product->stock_value      = $stock * $product->purchase_price;
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
        $query = StockMovement::with(['product', 'warehouse', 'user'])
            ->latest('movement_date')
            ->latest('id');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $movements  = $query->paginate(25);
        $products   = Product::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('inventory.movements.index', compact('movements', 'products', 'warehouses'));
    }
}
