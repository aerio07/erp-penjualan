<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use App\Traits\HasListFilters;

class SupplierController extends Controller
{
    use HasListFilters;

    public function index(Request $request): View
    {
        $query = Supplier::query();

        $query = $this->applySearch($query, $request, ['code', 'name', 'contact_person', 'phone', 'email', 'address']);
        $query = $this->applyFilter($query, $request, 'is_active');
        $query = $this->applySort($query, $request, ['code', 'name', 'contact_person', 'payment_term', 'created_at'], 'name', 'asc');

        $perPage = (int) $request->get('per_page', 20);
        $suppliers = $query->paginate($perPage)->withQueryString();

        return view('master.suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        return view('master.suppliers.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code'           => 'required|unique:suppliers,code',
            'name'           => 'required|string',
            'contact_person' => 'nullable|string',
            'phone'          => 'nullable|string',
            'address'        => 'nullable|string',
            'payment_term'   => 'nullable|string',
        ]);

        Supplier::create($request->only('code', 'name', 'contact_person', 'phone', 'address', 'payment_term', 'email', 'npwp') + [
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('master.suppliers.index')
            ->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load(['purchaseOrders' => fn($q) => $q->latest()->limit(10)]);
        $totalPurchase = $supplier->purchaseOrders()->sum('total_amount');
        return view('master.suppliers.show', compact('supplier', 'totalPurchase'));
    }

    public function edit(Supplier $supplier): View
    {
        return view('master.suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $request->validate([
            'code' => 'required|unique:suppliers,code,' . $supplier->id,
            'name' => 'required|string',
        ]);

        $supplier->update($request->only('code', 'name', 'contact_person', 'phone', 'address', 'payment_term', 'email', 'npwp') + [
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('master.suppliers.index')
            ->with('success', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchaseOrders()->exists()) {
            return back()->with('error', 'Supplier tidak dapat dihapus karena sudah memiliki riwayat Purchase Order.');
        }
        $supplier->delete();
        return redirect()->route('master.suppliers.index')
            ->with('success', 'Supplier berhasil dihapus.');
    }
}
