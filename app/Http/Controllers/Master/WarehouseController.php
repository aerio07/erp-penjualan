<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use App\Traits\HasListFilters;

class WarehouseController extends Controller
{
    use HasListFilters;

    public function __construct(private StockService $stockService) {}

    public function index(Request $request): View
    {
        $query = Warehouse::withCount('stockMovements');

        $query = $this->applySearch($query, $request, ['code', 'name', 'address']);
        $query = $this->applyFilter($query, $request, 'is_active');
        $query = $this->applySort($query, $request, ['code', 'name', 'created_at'], 'name', 'asc');

        $perPage = (int) $request->get('per_page', 20);
        $warehouses = $query->paginate($perPage)->withQueryString();

        return view('master.warehouses.index', compact('warehouses'));
    }

    public function create(): View
    {
        return view('master.warehouses.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $existingWh = $request->filled('code') 
            ? Warehouse::where('code', trim($request->code))->first() 
            : null;

        $customCodeMsg = $existingWh 
            ? "Kode gudang \"{$request->code}\" sudah digunakan oleh gudang \"{$existingWh->name}\"." 
            : 'Kode gudang ":input" sudah terdaftar. Gunakan kode yang berbeda.';

        $request->validate([
            'code'    => 'required|string|max:50|unique:warehouses,code',
            'name'    => 'required|string|max:255',
            'address' => 'required|string',
        ], [
            'code.required'    => 'Kode gudang wajib diisi.',
            'code.unique'      => $customCodeMsg,
            'name.required'    => 'Nama gudang wajib diisi.',
            'address.required' => 'Alamat gudang wajib diisi.',
        ]);

        Warehouse::create([
            'code'      => strtoupper(trim($request->code)),
            'name'      => trim($request->name),
            'address'   => trim($request->address),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('master.warehouses.index')
            ->with('success', 'Gudang berhasil ditambahkan.');
    }

    public function show(Warehouse $warehouse): View
    {
        $stockSummary = $this->stockService->getStockByWarehouse($warehouse);
        return view('master.warehouses.show', compact('warehouse', 'stockSummary'));
    }

    public function edit(Warehouse $warehouse): View
    {
        return view('master.warehouses.form', compact('warehouse'));
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $existingWh = $request->filled('code') 
            ? Warehouse::where('code', trim($request->code))->where('id', '!=', $warehouse->id)->first() 
            : null;

        $customCodeMsg = $existingWh 
            ? "Kode gudang \"{$request->code}\" sudah digunakan oleh gudang \"{$existingWh->name}\"." 
            : 'Kode gudang ":input" sudah terdaftar. Gunakan kode yang berbeda.';

        $request->validate([
            'code'    => 'required|string|max:50|unique:warehouses,code,' . $warehouse->id,
            'name'    => 'required|string|max:255',
            'address' => 'required|string',
        ], [
            'code.required'    => 'Kode gudang wajib diisi.',
            'code.unique'      => $customCodeMsg,
            'name.required'    => 'Nama gudang wajib diisi.',
            'address.required' => 'Alamat gudang wajib diisi.',
        ]);

        $warehouse->update([
            'code'      => strtoupper(trim($request->code)),
            'name'      => trim($request->name),
            'address'   => trim($request->address),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('master.warehouses.index')
            ->with('success', 'Gudang berhasil diperbarui.');
    }

    public function toggleStatus(Warehouse $warehouse): RedirectResponse
    {
        $warehouse->update([
            'is_active' => !$warehouse->is_active,
        ]);

        $statusLabel = $warehouse->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Status gudang \"{$warehouse->name}\" berhasil {$statusLabel}.");
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        $hasMovements    = $warehouse->stockMovements()->exists();
        $hasReceipts     = $warehouse->goodsReceipts()->exists();
        $hasDeliveries   = $warehouse->deliveries()->exists();
        $hasOpnames      = $warehouse->stockOpnames()->exists();
        $hasTransferFrom = $warehouse->transfersFrom()->exists();
        $hasTransferTo   = $warehouse->transfersTo()->exists();

        if ($hasMovements || $hasReceipts || $hasDeliveries || $hasOpnames || $hasTransferFrom || $hasTransferTo) {
            return back()->with('error', "Gudang \"{$warehouse->name}\" ({$warehouse->code}) tidak dapat dihapus karena sudah memiliki riwayat transaksi/stok. Anda dapat menonaktifkan gudang ini sebagai gantinya.");
        }

        try {
            $whName = $warehouse->name;
            $warehouse->delete();
            return redirect()->route('master.warehouses.index')
                ->with('success', "Gudang \"{$whName}\" berhasil dihapus.");
        } catch (\Exception $e) {
            return back()->with('error', "Gagal menghapus gudang: " . $e->getMessage());
        }
    }
}
