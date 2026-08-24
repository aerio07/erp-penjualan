<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

use App\Traits\HasListFilters;

class StockOpnameController extends Controller
{
    use HasListFilters;

    public function __construct(private StockService $stockService) {}

    public function index(Request $request): View
    {
        $query = StockOpname::with(['warehouse', 'user']);

        $query = $this->applySearch($query, $request, ['opname_number', 'warehouse.name', 'notes']);
        $query = $this->applyFilter($query, $request, 'status');
        $query = $this->applyFilter($query, $request, 'warehouse_id');
        $query = $this->applyDateRange($query, $request, 'opname_date');
        $query = $this->applySort($query, $request, ['opname_number', 'opname_date', 'status', 'created_at'], 'opname_date', 'desc');

        $perPage = (int) $request->get('per_page', 20);
        $opnames    = $query->paginate($perPage)->withQueryString();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('inventory.opname.index', compact('opnames', 'warehouses'));
    }

    public function create(Request $request): View
    {
        $selectedWarehouseId = $request->query('warehouse_id');
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        $products = collect();
        if ($selectedWarehouseId) {
            $products = Product::where('is_active', true)->orderBy('name')->get()->map(function ($p) use ($selectedWarehouseId) {
                $p->system_qty = $this->stockService->getCurrentStock($p->id, $selectedWarehouseId);
                return $p;
            });
        }

        return view('inventory.opname.create', compact('warehouses', 'selectedWarehouseId', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'warehouse_id'           => 'required|exists:warehouses,id',
            'opname_date'            => 'required|date',
            'notes'                  => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.system_qty'     => 'required|integer',
            'items.*.physical_qty'   => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $opnameNumber = $this->generateNumber();

            $opname = StockOpname::create([
                'opname_number' => $opnameNumber,
                'warehouse_id'  => $request->warehouse_id,
                'user_id'       => Auth::id(),
                'status'        => 'draft',
                'opname_date'   => $request->opname_date,
                'notes'         => $request->notes,
            ]);

            foreach ($request->items as $item) {
                $systemQty   = (int) $item['system_qty'];
                $physicalQty = (int) $item['physical_qty'];
                $difference  = $physicalQty - $systemQty;

                StockOpnameItem::create([
                    'stock_opname_id' => $opname->id,
                    'product_id'      => $item['product_id'],
                    'system_qty'      => $systemQty,
                    'physical_qty'    => $physicalQty,
                    'difference'      => $difference,
                    'notes'           => $item['notes'] ?? null,
                ]);
            }
        });

        return redirect()->route('inventory.opname.index')
            ->with('success', 'Draft Stock Opname berhasil disimpan.');
    }

    public function show(StockOpname $opname): View
    {
        $opname->load(['warehouse', 'user', 'items.product']);

        return view('inventory.opname.show', compact('opname'));
    }

    public function complete(StockOpname $opname): RedirectResponse
    {
        return DB::transaction(function () use ($opname) {
            $opname = StockOpname::with(['items.product'])->lockForUpdate()->findOrFail($opname->id);
            abort_if($opname->status === 'completed', 403, 'Stock Opname sudah diselesaikan.');

            foreach ($opname->items as $item) {
                if ($item->difference != 0) {
                    $this->stockService->recordMovement([
                        'product_id'     => $item->product_id,
                        'warehouse_id'   => $opname->warehouse_id,
                        'type'           => 'adjustment',
                        'quantity'       => $item->difference,
                        'unit_cost'      => $item->product->purchase_price,
                        'reference_type' => StockOpname::class,
                        'reference_id'   => $opname->id,
                        'movement_date'  => now()->toDateString(),
                        'notes'          => "Penyesuaian Stock Opname #{$opname->opname_number} (Selisih: {$item->difference})",
                        'user_id'        => Auth::id(),
                    ]);

                    if ($item->difference > 0) {
                        $this->stockService->allocateStockToPendingDemands($item->product_id, $opname->warehouse_id);
                    }
                }
            }

            $opname->update(['status' => 'completed']);

            return back()->with('success', 'Stock Opname telah diselesaikan dan penyesuaian stok otomatis dibuat.');
        });
    }

    private function generateNumber(): string
    {
        $prefix = 'SOP-' . date('Ym') . '-';
        $last   = StockOpname::where('opname_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('opname_number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
