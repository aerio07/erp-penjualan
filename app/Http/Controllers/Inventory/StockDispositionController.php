<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SalesReturnItem;
use App\Models\StockDisposition;
use App\Models\Warehouse;
use App\Services\JournalService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

use App\Traits\HasListFilters;

class StockDispositionController extends Controller
{
    use HasListFilters;

    public function __construct(
        private StockService $stockService,
        private JournalService $journalService
    ) {}

    public function index(Request $request): View
    {
        $query = StockDisposition::with(['product', 'warehouse', 'user', 'journalEntry']);

        $query = $this->applySearch($query, $request, ['disposition_number', 'product.name', 'product.sku', 'warehouse.name', 'notes']);
        $query = $this->applyFilter($query, $request, 'product_id');
        $query = $this->applyFilter($query, $request, 'warehouse_id');
        $query = $this->applyFilter($query, $request, 'resolution_type');
        $query = $this->applyDateRange($query, $request, 'disposed_at');
        $query = $this->applySort($query, $request, ['disposition_number', 'disposed_at', 'qty', 'resolution_type', 'created_at'], 'disposed_at', 'desc');

        $perPage = (int) $request->get('per_page', 20);
        $dispositions = $query->paginate($perPage)->withQueryString();
        $products     = Product::where('is_active', true)->orderBy('name')->get();
        $warehouses   = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('inventory.dispositions.index', compact('dispositions', 'products', 'warehouses'));
    }

    public function create(Request $request): View
    {
        $productId   = $request->query('product_id');
        $warehouseId = $request->query('warehouse_id');

        $products   = Product::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        $selectedProduct   = $productId ? Product::find($productId) : null;
        $selectedWarehouse = $warehouseId ? Warehouse::find($warehouseId) : null;

        $availableQuarantine = 0;
        $unitCost = 0;

        if ($selectedProduct && $selectedWarehouse) {
            $availableQuarantine = $this->stockService->getQuarantineStockAvailable($selectedProduct->id, $selectedWarehouse->id);

            // Cari snapshot unit cost dari retur rusak terakhir atau purchase_price produk
            $latestDamagedItem = SalesReturnItem::where('product_id', $selectedProduct->id)
                ->where('condition', 'rusak')
                ->latest('id')
                ->first();

            $unitCost = $latestDamagedItem && $latestDamagedItem->unit_cost > 0
                ? (float) $latestDamagedItem->unit_cost
                : (float) $selectedProduct->purchase_price;
        }

        return view('inventory.dispositions.create', compact(
            'products', 'warehouses', 'selectedProduct', 'selectedWarehouse',
            'productId', 'warehouseId', 'availableQuarantine', 'unitCost'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id'      => 'required|exists:products,id',
            'warehouse_id'    => 'required|exists:warehouses,id',
            'qty'             => 'required|integer|min:1',
            'resolution_type' => 'required|in:write_off,sold_as_reject',
            'sale_price'      => 'required_if:resolution_type,sold_as_reject|nullable|numeric|min:0',
            'disposed_at'     => 'required|date',
            'notes'           => 'nullable|string|max:1000',
        ]);

        $disposition = DB::transaction(function () use ($request) {
            // 1. Lock product row to serialize disposition operations
            $product = Product::lockForUpdate()->findOrFail($request->product_id);

            // 2. Validasi stok karantina tersedia
            $available = $this->stockService->getQuarantineStockAvailable($request->product_id, $request->warehouse_id);
            if ($request->qty > $available) {
                throw ValidationException::withMessages([
                    'qty' => "Jumlah yang diselesaikan ({$request->qty}) melebihi stok karantina yang tersedia ({$available}).",
                ]);
            }

            // 3. Ambil snapshot unit cost
            $latestDamagedItem = SalesReturnItem::where('product_id', $request->product_id)
                ->where('condition', 'rusak')
                ->latest('id')
                ->first();

            $unitCost = $latestDamagedItem && $latestDamagedItem->unit_cost > 0
                ? (float) $latestDamagedItem->unit_cost
                : (float) $product->purchase_price;

            // 3. Buat nomor disposisi
            $dispositionNumber = $this->generateDispositionNumber();

            // 4. Create StockDisposition
            $disposition = StockDisposition::create([
                'disposition_number'   => $dispositionNumber,
                'sales_return_item_id' => $latestDamagedItem?->id,
                'product_id'           => $request->product_id,
                'warehouse_id'         => $request->warehouse_id,
                'qty'                  => $request->qty,
                'resolution_type'      => $request->resolution_type,
                'unit_cost'            => $unitCost,
                'sale_price'           => $request->resolution_type === 'sold_as_reject' ? $request->sale_price : null,
                'journal_entry_id'     => null,
                'user_id'              => Auth::id() ?? 1,
                'disposed_at'          => $request->disposed_at,
                'notes'                => $request->notes,
            ]);

            // 5. Catat stock movement (write_off / reject_out)
            $movementType = $request->resolution_type === 'write_off' ? 'write_off' : 'reject_out';
            $typeLabel    = $request->resolution_type === 'write_off' ? 'Write Off' : 'Sold as Reject';

            $this->stockService->recordMovement([
                'product_id'     => $disposition->product_id,
                'warehouse_id'   => $disposition->warehouse_id,
                'type'           => $movementType,
                'quantity'       => $disposition->qty,
                'unit_cost'      => $disposition->unit_cost,
                'reference_type' => StockDisposition::class,
                'reference_id'   => $disposition->id,
                'movement_date'  => $disposition->disposed_at->toDateString(),
                'notes'          => "Disposisi karantina: {$typeLabel}" . ($disposition->notes ? " ({$disposition->notes})" : ""),
                'user_id'        => $disposition->user_id,
            ]);

            // 6. Generate & post Journal Entry
            $journalEntry = $this->journalService->createFromStockDisposition($disposition);
            $disposition->update(['journal_entry_id' => $journalEntry->id]);

            return $disposition;
        });

        return redirect()->route('inventory.dispositions.index')
            ->with('success', "Penyelesaian barang karantina #{$disposition->disposition_number} berhasil disimpan.");
    }

    private function generateDispositionNumber(): string
    {
        $prefix = 'DSP-' . date('Ym') . '-';
        $last   = StockDisposition::where('disposition_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('disposition_number');

        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
