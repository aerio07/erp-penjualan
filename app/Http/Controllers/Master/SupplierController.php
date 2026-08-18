<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(): View
    {
        $suppliers = Supplier::orderBy('name')->paginate(20);
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
