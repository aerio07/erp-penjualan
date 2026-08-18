<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index(): View
    {
        $warehouses = Warehouse::withCount('stockMovements')->orderBy('name')->get();
        return view('master.warehouses.index', compact('warehouses'));
    }

    public function create(): View
    {
        return view('master.warehouses.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code'    => 'required|unique:warehouses,code',
            'name'    => 'required|string',
            'address' => 'nullable|string',
        ]);

        Warehouse::create($request->only('code', 'name', 'address', 'is_active'));

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
        $request->validate([
            'code' => 'required|unique:warehouses,code,' . $warehouse->id,
            'name' => 'required|string',
        ]);

        $warehouse->update($request->only('code', 'name', 'address') + [
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('master.warehouses.index')
            ->with('success', 'Gudang berhasil diperbarui.');
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        if ($warehouse->stockMovements()->exists()) {
            return back()->with('error', 'Gudang tidak dapat dihapus karena sudah memiliki riwayat transaksi.');
        }
        $warehouse->delete();
        return redirect()->route('master.warehouses.index')
            ->with('success', 'Gudang berhasil dihapus.');
    }
}
