<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseTransferItem;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

use App\Traits\HasListFilters;
use App\Traits\GeneratesDocumentNumber;

class WarehouseTransferController extends Controller
{
    use HasListFilters, GeneratesDocumentNumber;

    public function __construct(private StockService $stockService) {}

    public function index(Request $request): View
    {
        $query = WarehouseTransfer::with(['fromWarehouse', 'toWarehouse', 'user', 'shippedBy', 'receivedBy']);

        $query = $this->applySearch($query, $request, ['transfer_number', 'fromWarehouse.name', 'toWarehouse.name', 'notes']);
        $query = $this->applyFilter($query, $request, 'status');
        $query = $this->applyFilter($query, $request, 'from_warehouse_id');
        $query = $this->applyFilter($query, $request, 'to_warehouse_id');
        $query = $this->applyDateRange($query, $request, 'transfer_date');
        $query = $this->applySort($query, $request, ['transfer_number', 'transfer_date', 'status', 'created_at'], 'transfer_date', 'desc');

        $perPage = (int) $request->get('per_page', 10);
        $transfers  = $query->paginate($perPage)->withQueryString();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('inventory.transfers.index', compact('transfers', 'warehouses'));
    }

    public function create(): View
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $products   = Product::where('is_active', true)->orderBy('name')->get();
        $stockMatrix = $this->getStockMatrix($products, $warehouses);

        return view('inventory.transfers.create', compact('warehouses', 'products', 'stockMatrix'));
    }

    public function store(Request $request): RedirectResponse
    {
        // Filter empty items
        $rawItems = $request->input('items', []);
        $cleanedItems = collect($rawItems)->filter(function ($i) {
            return !empty($i['product_id']) && (int)($i['qty'] ?? $i['qty_requested'] ?? 0) > 0;
        })->values()->all();

        if (empty($cleanedItems)) {
            return back()->with('error', 'Minimal 1 produk valid harus dipilih dengan jumlah transfer minimal 1.')->withInput();
        }

        $request->merge(['items' => $cleanedItems]);

        $request->validate([
            'from_warehouse_id'     => 'required|exists:warehouses,id|different:to_warehouse_id',
            'to_warehouse_id'       => 'required|exists:warehouses,id',
            'transfer_date'         => 'required|date',
            'notes'                 => 'nullable|string',
            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|exists:products,id',
            'items.*.qty'           => 'required_without:items.*.qty_requested|nullable|integer|min:1',
            'items.*.qty_requested' => 'nullable|integer|min:1',
        ]);

        // Standardize qty input
        $itemsData = collect($request->items)->map(function ($i) {
            $qty = $i['qty'] ?? $i['qty_requested'] ?? 1;
            return [
                'product_id' => (int) $i['product_id'],
                'qty'        => (int) $qty,
            ];
        });

        // Check stock availability in origin warehouse
        foreach ($itemsData as $item) {
            $availableStock = $this->stockService->getAvailableStock($item['product_id'], $request->from_warehouse_id);
            if ($availableStock < $item['qty']) {
                $product = Product::find($item['product_id']);
                return back()->with('error', "Stok tersedia produk '{$product->name}' di gudang asal tidak mencukupi (Tersedia: {$availableStock}, Diminta: {$item['qty']}).")->withInput();
            }
        }

        $transfer = DB::transaction(function () use ($request, $itemsData) {
            $transferNumber = $this->generateNumber();

            $transfer = WarehouseTransfer::create([
                'transfer_number'   => $transferNumber,
                'from_warehouse_id' => $request->from_warehouse_id,
                'to_warehouse_id'   => $request->to_warehouse_id,
                'user_id'           => Auth::id(),
                'status'            => 'draft',
                'transfer_date'     => $request->transfer_date,
                'notes'             => $request->notes,
            ]);

            foreach ($itemsData as $item) {
                WarehouseTransferItem::create([
                    'warehouse_transfer_id' => $transfer->id,
                    'product_id'            => $item['product_id'],
                    'qty'                   => $item['qty'],
                ]);
            }

            return $transfer;
        });

        return redirect()->route('inventory.transfers.index')
            ->with('success', "Draft Transfer Gudang #{$transfer->transfer_number} berhasil dibuat.");
    }

    public function edit(WarehouseTransfer $transfer): View
    {
        abort_if($transfer->status !== 'draft', 403, 'Hanya dokumen transfer berstatus Draft yang dapat diedit.');

        $transfer->load(['items.product']);
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $products   = Product::where('is_active', true)->orderBy('name')->get();
        $stockMatrix = $this->getStockMatrix($products, $warehouses);

        return view('inventory.transfers.edit', compact('transfer', 'warehouses', 'products', 'stockMatrix'));
    }

    public function update(Request $request, WarehouseTransfer $transfer): RedirectResponse
    {
        abort_if($transfer->status !== 'draft', 403, 'Hanya dokumen transfer berstatus Draft yang dapat diedit.');

        // Filter empty items
        $rawItems = $request->input('items', []);
        $cleanedItems = collect($rawItems)->filter(function ($i) {
            return !empty($i['product_id']) && (int)($i['qty'] ?? $i['qty_requested'] ?? 0) > 0;
        })->values()->all();

        if (empty($cleanedItems)) {
            return back()->with('error', 'Minimal 1 produk valid harus dipilih dengan jumlah transfer minimal 1.')->withInput();
        }

        $request->merge(['items' => $cleanedItems]);

        $request->validate([
            'from_warehouse_id'     => 'required|exists:warehouses,id|different:to_warehouse_id',
            'to_warehouse_id'       => 'required|exists:warehouses,id',
            'transfer_date'         => 'required|date',
            'notes'                 => 'nullable|string',
            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|exists:products,id',
            'items.*.qty'           => 'required_without:items.*.qty_requested|nullable|integer|min:1',
            'items.*.qty_requested' => 'nullable|integer|min:1',
        ]);

        $itemsData = collect($request->items)->map(function ($i) {
            $qty = $i['qty'] ?? $i['qty_requested'] ?? 1;
            return [
                'product_id' => (int) $i['product_id'],
                'qty'        => (int) $qty,
            ];
        });

        // Check stock availability in origin warehouse
        foreach ($itemsData as $item) {
            $availableStock = $this->stockService->getAvailableStock($item['product_id'], $request->from_warehouse_id);
            if ($availableStock < $item['qty']) {
                $product = Product::find($item['product_id']);
                return back()->with('error', "Stok tersedia produk '{$product->name}' di gudang asal tidak mencukupi (Tersedia: {$availableStock}, Diminta: {$item['qty']}).")->withInput();
            }
        }

        DB::transaction(function () use ($transfer, $request, $itemsData) {
            $transfer->update([
                'from_warehouse_id' => $request->from_warehouse_id,
                'to_warehouse_id'   => $request->to_warehouse_id,
                'transfer_date'     => $request->transfer_date,
                'notes'             => $request->notes,
            ]);

            $transfer->items()->delete();

            foreach ($itemsData as $item) {
                WarehouseTransferItem::create([
                    'warehouse_transfer_id' => $transfer->id,
                    'product_id'            => $item['product_id'],
                    'qty'                   => $item['qty'],
                ]);
            }
        });

        return redirect()->route('inventory.transfers.show', $transfer)
            ->with('success', "Draft Transfer Gudang #{$transfer->transfer_number} berhasil diperbarui.");
    }

    public function show(WarehouseTransfer $transfer): View
    {
        $transfer->load(['fromWarehouse', 'toWarehouse', 'user', 'shippedBy', 'receivedBy', 'items.product']);

        foreach ($transfer->items as $item) {
            $item->available_stock = $this->stockService->getAvailableStock($item->product_id, $transfer->from_warehouse_id);
        }

        return view('inventory.transfers.show', compact('transfer'));
    }

    public function ship(WarehouseTransfer $transfer): RedirectResponse
    {
        return DB::transaction(function () use ($transfer) {
            $transfer = WarehouseTransfer::with(['fromWarehouse', 'toWarehouse', 'items.product'])->lockForUpdate()->findOrFail($transfer->id);
            abort_if($transfer->status !== 'draft', 403, 'Hanya dokumen transfer berstatus Draft yang dapat dikirim.');

            // Lock all involved product rows to serialize stock checks
            $productIds = $transfer->items->pluck('product_id')->unique()->toArray();
            Product::whereIn('id', $productIds)->lockForUpdate()->get();

            // Re-validate stock availability
            foreach ($transfer->items as $item) {
                $currentStock = $this->stockService->getCurrentStock($item->product_id, $transfer->from_warehouse_id);
                if ($currentStock < $item->qty) {
                    throw ValidationException::withMessages([
                        'stock' => "Stok produk '{$item->product->name}' di gudang asal sudah berkurang dan tidak mencukupi (Tersedia: {$currentStock}, Dibutuhkan: {$item->qty}).",
                    ]);
                }
            }

            // Record transfer_out movement
            foreach ($transfer->items as $item) {
                $this->stockService->recordMovement([
                    'product_id'     => $item->product_id,
                    'warehouse_id'   => $transfer->from_warehouse_id,
                    'type'           => 'transfer_out',
                    'quantity'       => $item->qty,
                    'unit_cost'      => $item->product->purchase_price,
                    'reference_type' => WarehouseTransfer::class,
                    'reference_id'   => $transfer->id,
                    'movement_date'  => now()->toDateString(),
                    'notes'          => "Transfer keluar ke {$transfer->toWarehouse->name} (Ref #{$transfer->transfer_number})",
                    'user_id'        => Auth::id(),
                ]);
            }

            $transfer->update([
                'status'     => 'in_transit',
                'shipped_by' => Auth::id(),
                'shipped_at' => now(),
            ]);

            return back()->with('success', "Transfer Gudang #{$transfer->transfer_number} telah dikonfirmasi Kirim (In Transit) dan stok gudang asal telah dikurangi.");
        });
    }

    public function receive(WarehouseTransfer $transfer): RedirectResponse
    {
        return DB::transaction(function () use ($transfer) {
            $transfer = WarehouseTransfer::with(['fromWarehouse', 'toWarehouse', 'items.product'])->lockForUpdate()->findOrFail($transfer->id);
            abort_if($transfer->status !== 'in_transit', 403, 'Hanya dokumen transfer berstatus Dalam Perjalanan (In Transit) yang dapat dikonfirmasi penerimaannya.');

            // Record transfer_in movement and allocate to pending demands
            foreach ($transfer->items as $item) {
                $this->stockService->recordMovement([
                    'product_id'     => $item->product_id,
                    'warehouse_id'   => $transfer->to_warehouse_id,
                    'type'           => 'transfer_in',
                    'quantity'       => $item->qty,
                    'unit_cost'      => $item->product->purchase_price,
                    'reference_type' => WarehouseTransfer::class,
                    'reference_id'   => $transfer->id,
                    'movement_date'  => now()->toDateString(),
                    'notes'          => "Transfer masuk dari {$transfer->fromWarehouse->name} (Ref #{$transfer->transfer_number})",
                    'user_id'        => Auth::id(),
                ]);

                // Alokasikan stok masuk ke antrean kebutuhan pengadaan (backorder)
                $this->stockService->allocateStockToPendingDemands($item->product_id, $transfer->to_warehouse_id);
            }

            $transfer->update([
                'status'      => 'completed',
                'received_by' => Auth::id(),
                'received_at' => now(),
            ]);

            return back()->with('success', "Transfer Gudang #{$transfer->transfer_number} telah diterima di gudang tujuan dan stok telah bertambah.");
        });
    }

    public function cancel(WarehouseTransfer $transfer): RedirectResponse
    {
        abort_if($transfer->status !== 'draft', 403, 'Hanya dokumen transfer berstatus Draft yang dapat dibatalkan.');

        $transfer->update(['status' => 'cancelled']);

        return back()->with('success', "Draft Transfer Gudang #{$transfer->transfer_number} telah dibatalkan.");
    }

    private function getStockMatrix($products, $warehouses): array
    {
        $matrix = [];
        foreach ($products as $p) {
            $matrix[$p->id] = [];
            foreach ($warehouses as $w) {
                $matrix[$p->id][$w->id] = $this->stockService->getAvailableStock($p->id, $w->id);
            }
        }
        return $matrix;
    }

    private function generateNumber(): string
    {
        return $this->generateDocumentNumber(WarehouseTransfer::class, 'transfer_number', 'TRF');
    }
}
