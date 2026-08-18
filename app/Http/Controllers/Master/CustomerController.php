<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        $customers = Customer::orderBy('name')->paginate(20);
        return view('master.customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('master.customers.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code'           => 'required|unique:customers,code',
            'name'           => 'required|string',
            'contact_person' => 'nullable|string',
            'phone'          => 'nullable|string',
            'address'        => 'nullable|string',
            'credit_limit'   => 'nullable|numeric|min:0',
            'payment_term'   => 'nullable|string',
        ]);

        Customer::create($request->only('code', 'name', 'contact_person', 'phone', 'address', 'email', 'npwp', 'credit_limit', 'payment_term') + [
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('master.customers.index')
            ->with('success', 'Customer berhasil ditambahkan.');
    }

    public function show(Customer $customer): View
    {
        $customer->load(['salesOrders' => fn($q) => $q->latest()->limit(10)]);
        $totalSales       = $customer->salesOrders()->sum('total_amount');
        $outstandingDebt  = $customer->salesOrders()
            ->join('sales_invoices', 'sales_orders.id', '=', 'sales_invoices.sales_order_id')
            ->where('sales_invoices.status', '!=', 'paid')
            ->sum('sales_invoices.total_amount');

        return view('master.customers.show', compact('customer', 'totalSales', 'outstandingDebt'));
    }

    public function edit(Customer $customer): View
    {
        return view('master.customers.form', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $request->validate([
            'code' => 'required|unique:customers,code,' . $customer->id,
            'name' => 'required|string',
        ]);

        $customer->update($request->only('code', 'name', 'contact_person', 'phone', 'address', 'email', 'npwp', 'credit_limit', 'payment_term') + [
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('master.customers.index')
            ->with('success', 'Customer berhasil diperbarui.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->salesOrders()->exists()) {
            return back()->with('error', 'Customer tidak dapat dihapus karena sudah memiliki riwayat penjualan.');
        }
        $customer->delete();
        return redirect()->route('master.customers.index')
            ->with('success', 'Customer berhasil dihapus.');
    }
}
