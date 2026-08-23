<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use App\Traits\HasListFilters;

class CustomerController extends Controller
{
    use HasListFilters;

    public function index(Request $request): View
    {
        $query = Customer::query();

        $query = $this->applySearch($query, $request, ['code', 'name', 'contact_person', 'phone', 'email', 'address']);
        $query = $this->applyFilter($query, $request, 'is_active');
        $query = $this->applySort($query, $request, ['code', 'name', 'contact_person', 'credit_limit', 'payment_term', 'created_at'], 'name', 'asc');

        $perPage = (int) $request->get('per_page', 20);
        $customers = $query->paginate($perPage)->withQueryString();

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
        $outstandingDebt  = \App\Models\SalesInvoice::with(['payments', 'items'])
            ->whereHas('salesOrder', fn($q) => $q->where('customer_id', $customer->id))
            ->where('status', '!=', 'paid')
            ->get()
            ->sum->outstanding_amount;

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
