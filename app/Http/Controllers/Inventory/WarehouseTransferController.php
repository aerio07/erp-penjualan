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
use Illuminate\View\View;

class WarehouseTransferController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index(): View
    {
        $transfers = WarehouseTransfer::with(['fromWarehouse', 'toWarehouse', 'user'])
            ->latest('transfer_date')
            ->paginate(20);

        return view('inventory.transfers.index', compact('transfers'));
    }

    public function create(): View
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $products   = Product::where('is_active', true)->orderBy('name')->get();

        return view('inventory.transfers.create', compact('warehouses', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'from_warehouse_id'      => 'required|exists:warehouses,id|different:to_warehouse_id',
            'to_warehouse_id'        => 'required|exists:warehouses,id',
            'transfer_date'          => 'required|date',
            'notes'                  => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.qty_requested'  => 'required|integer|min:1',
        ]);

        // Cek kecukupan stok di gudang asal
        foreach ($request->items as $item) {
            $currentStock = $this->stockService->getCurrentStock($item['product_id'], $request->from_warehouse_id);
            if ($currentStock < $item['qty_requested']) {
                $product = Product::find($item['product_id']);
                return back()->with('error', "Stok produk '{$product->name}' di gudang asal tidak mencukupi (Tersedia: {$currentStock}, Diminta: {$item['qty_requested']}).")->withInput();
            }
        }

        DB::transaction(function () use ($request) {
            $transferNumber = $this->generateNumber();

            $transfer = WarehouseTransfer::create([
                'transfer_number'   => $transferNumber,
                'from_warehouse_id' => $request->from_warehouse_id,
                'to_warehouse_id'   => $request->to_warehouse_id,
                'user_id'           => Auth::id(),
                'status'            => 'in_transit',
                'transfer_date'     => $request->transfer_date,
                'notes'             => $request->notes,
            ]);

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);

                WarehouseTransferItem::create([
                    'warehouse_transfer_id' => $transfer->id,
                    'product_id'            => $item['product_id'],
                    'qty_requested'         => $item['qty_requested'],
                    'qty_received'          => 0,
                ]);

                // Kurangi stok di gudang asal (transfer_out)
                $this->stockService->recordMovement([
                    'product_id'     => $item['product_id'],
                    'warehouse_id'   => $request->from_warehouse_id,
                    'type'           => 'transfer_out',
                    'quantity'       => $item['qty_requested'],
                    'unit_cost'      => $product->purchase_price,
                    'reference_type' => WarehouseTransfer::class,
                    'reference_id'   => $transfer->id,
                    'movement_date'  => $request->transfer_date,
                    'notes'          => "Transfer keluar ke gudang #{$transfer->to_warehouse_id} (Ref #{$transferNumber})",
                    'user_id'        => Auth::id(),
                ]);
            }
        });

        return redirect()->route('inventory.transfers.index')
            ->with('success', 'Transfer Gudang berhasil dibuat dan stok gudang asal telah dikurangi.');
    }

    public function show(WarehouseTransfer $transfer): View
    {
        $transfer->load(['fromWarehouse', 'toWarehouse', 'user', 'items.product']);

        return view('inventory.transfers.show', compact('transfer'));
    }

    public function receive(WarehouseTransfer $transfer): RedirectResponse
    {
        abort_if($transfer->status === 'received', 403, 'Transfer sudah selesai dan barang sudah diterima.');

        DB::transaction(function () use ($transfer) {
            foreach ($transfer->items as $item) {
                $item->update(['qty_received' => $item->qty_requested]);

                // Tambah stok di gudang tujuan (transfer_in)
                $this->stockService->recordMovement([
                    'product_id'     => $item->product_id,
                    'warehouse_id'   => $transfer->to_warehouse_id,
                    'type'           => 'transfer_in',
                    'quantity'       => $item->qty_requested,
                    'unit_cost'      => $item->product->purchase_price,
                    'reference_type' => WarehouseTransfer::class,
                    'reference_id'   => $transfer->id,
                    'movement_date'  => now()->toDateString(),
                    'notes'          => "Transfer masuk dari gudang #{$transfer->from_warehouse_id} (Ref #{$transfer->transfer_number})",
                    'user_id'        => Auth::id(),
                ]);
            }

            $transfer->update([
                'status'        => 'received',
                'received_date' => now()->toDateString(),
            ]);
        });

        return back()->with('success', 'Transfer Gudang telah diterima dan stok gudang tujuan telah bertambah.');
    }

    private function generateNumber(): string
    {
        $prefix = 'TRF-' . date('Ym') . '-';
        $last   = WarehouseTransfer::where('transfer_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('transfer_number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
