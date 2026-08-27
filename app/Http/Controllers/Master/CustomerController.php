<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use App\Traits\HasListFilters;

class CustomerController extends Controller
{
    use HasListFilters;

    public function index(Request $request): View
    {
        $query = Customer::with('salesPerson');

        $query = $this->applySearch($query, $request, ['code', 'name', 'contact_person', 'phone', 'email', 'address']);
        $query = $this->applyFilter($query, $request, 'is_active');
        $query = $this->applyFilter($query, $request, 'tax_type');
        $query = $this->applyFilter($query, $request, 'sales_person_id');
        $query = $this->applySort($query, $request, ['code', 'name', 'contact_person', 'credit_limit', 'payment_term', 'tax_type', 'created_at'], 'name', 'asc');

        $perPage = (int) $request->get('per_page', 20);
        $customers = $query->paginate($perPage)->withQueryString();
        $salesUsers = User::where('role', 'sales')->orderBy('name')->get();

        return view('master.customers.index', compact('customers', 'salesUsers'));
    }

    public function create(): View
    {
        $salesUsers = User::where('role', 'sales')->orderBy('name')->get();

        return view('master.customers.form', compact('salesUsers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $existingCust = $request->filled('code') 
            ? Customer::where('code', trim($request->code))->first() 
            : null;

        $customCodeMsg = $existingCust 
            ? "Kode customer \"{$request->code}\" sudah digunakan oleh customer \"{$existingCust->name}\"." 
            : 'Kode customer ":input" sudah terdaftar. Gunakan kode yang berbeda.';

        $request->validate([
            'code'            => 'required|string|max:50|unique:customers,code',
            'name'            => 'required|string|max:255',
            'contact_person'  => 'nullable|string|max:255',
            'phone'           => 'required|string|max:50',
            'email'           => 'nullable|email|max:255',
            'payment_term'    => 'required|string|max:50',
            'credit_limit'    => 'required|numeric|min:0',
            'sales_person_id' => 'nullable|exists:users,id',
            'tax_type'        => 'required|in:pkp,non_pkp',
            'npwp'            => 'required_if:tax_type,pkp|nullable|string|max:50',
            'address'         => 'required|string',
        ], [
            'code.required'           => 'Kode customer wajib diisi.',
            'code.unique'             => $customCodeMsg,
            'name.required'           => 'Nama customer wajib diisi.',
            'phone.required'          => 'Nomor telepon customer wajib diisi.',
            'payment_term.required'   => 'Payment term (syarat pembayaran) wajib dipilih.',
            'credit_limit.required'   => 'Credit limit (plafon kredit) wajib diisi.',
            'credit_limit.numeric'    => 'Credit limit harus berupa angka nominal.',
            'credit_limit.min'        => 'Credit limit tidak boleh bernilai negatif.',
            'sales_person_id.exists'  => 'Sales PIC yang dipilih tidak valid / tidak terdaftar.',
            'tax_type.required'       => 'Tipe customer (PKP / Non-PKP) wajib dipilih.',
            'tax_type.in'             => 'Pilihan tipe customer tidak valid.',
            'npwp.required_if'        => 'Nomor NPWP wajib diisi untuk customer bertipe PKP.',
            'address.required'        => 'Alamat customer wajib diisi.',
            'email.email'             => 'Format email tidak valid.',
        ]);

        Customer::create([
            'code'            => strtoupper(trim($request->code)),
            'name'            => trim($request->name),
            'contact_person'  => $request->contact_person ? trim($request->contact_person) : null,
            'phone'           => trim($request->phone),
            'email'           => $request->email ? trim($request->email) : null,
            'payment_term'    => $request->payment_term,
            'credit_limit'    => $request->credit_limit ?? 0,
            'sales_person_id' => $request->filled('sales_person_id') ? $request->sales_person_id : null,
            'tax_type'        => $request->tax_type,
            'npwp'            => $request->npwp ? trim($request->npwp) : null,
            'address'         => trim($request->address),
            'is_active'       => $request->boolean('is_active', true),
        ]);

        return redirect()->route('master.customers.index')
            ->with('success', 'Customer berhasil ditambahkan.');
    }

    public function show(Customer $customer): View
    {
        $customer->load(['salesPerson', 'salesOrders' => fn($q) => $q->latest()->limit(10)]);
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
        $salesUsers = User::where('role', 'sales')->orderBy('name')->get();

        return view('master.customers.form', compact('customer', 'salesUsers'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $existingCust = $request->filled('code') 
            ? Customer::where('code', trim($request->code))->where('id', '!=', $customer->id)->first() 
            : null;

        $customCodeMsg = $existingCust 
            ? "Kode customer \"{$request->code}\" sudah digunakan oleh customer \"{$existingCust->name}\"." 
            : 'Kode customer ":input" sudah terdaftar. Gunakan kode yang berbeda.';

        $request->validate([
            'code'            => 'required|string|max:50|unique:customers,code,' . $customer->id,
            'name'            => 'required|string|max:255',
            'contact_person'  => 'nullable|string|max:255',
            'phone'           => 'required|string|max:50',
            'email'           => 'nullable|email|max:255',
            'payment_term'    => 'required|string|max:50',
            'credit_limit'    => 'required|numeric|min:0',
            'sales_person_id' => 'nullable|exists:users,id',
            'tax_type'        => 'required|in:pkp,non_pkp',
            'npwp'            => 'required_if:tax_type,pkp|nullable|string|max:50',
            'address'         => 'required|string',
        ], [
            'code.required'           => 'Kode customer wajib diisi.',
            'code.unique'             => $customCodeMsg,
            'name.required'           => 'Nama customer wajib diisi.',
            'phone.required'          => 'Nomor telepon customer wajib diisi.',
            'payment_term.required'   => 'Payment term (syarat pembayaran) wajib dipilih.',
            'credit_limit.required'   => 'Credit limit (plafon kredit) wajib diisi.',
            'credit_limit.numeric'    => 'Credit limit harus berupa angka nominal.',
            'credit_limit.min'        => 'Credit limit tidak boleh bernilai negatif.',
            'sales_person_id.exists'  => 'Sales PIC yang dipilih tidak valid / tidak terdaftar.',
            'tax_type.required'       => 'Tipe customer (PKP / Non-PKP) wajib dipilih.',
            'tax_type.in'             => 'Pilihan tipe customer tidak valid.',
            'npwp.required_if'        => 'Nomor NPWP wajib diisi untuk customer bertipe PKP.',
            'address.required'        => 'Alamat customer wajib diisi.',
            'email.email'             => 'Format email tidak valid.',
        ]);

        $customer->update([
            'code'            => strtoupper(trim($request->code)),
            'name'            => trim($request->name),
            'contact_person'  => $request->contact_person ? trim($request->contact_person) : null,
            'phone'           => trim($request->phone),
            'email'           => $request->email ? trim($request->email) : null,
            'payment_term'    => $request->payment_term,
            'credit_limit'    => $request->credit_limit ?? 0,
            'sales_person_id' => $request->filled('sales_person_id') ? $request->sales_person_id : null,
            'tax_type'        => $request->tax_type,
            'npwp'            => $request->npwp ? trim($request->npwp) : null,
            'address'         => trim($request->address),
            'is_active'       => $request->boolean('is_active'),
        ]);

        return redirect()->route('master.customers.index')
            ->with('success', 'Customer berhasil diperbarui.');
    }

    public function toggleStatus(Customer $customer): RedirectResponse
    {
        $customer->update([
            'is_active' => !$customer->is_active,
        ]);

        $statusLabel = $customer->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Status customer \"{$customer->name}\" berhasil {$statusLabel}.");
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $hasSOs     = $customer->salesOrders()->exists();
        $hasReturns = $customer->salesReturns()->exists();

        if ($hasSOs || $hasReturns) {
            return back()->with('error', "Customer \"{$customer->name}\" ({$customer->code}) tidak dapat dihapus karena sudah memiliki riwayat penjualan/transaksi. Anda dapat menonaktifkan customer ini sebagai gantinya.");
        }

        try {
            $custName = $customer->name;
            $customer->delete();
            return redirect()->route('master.customers.index')
                ->with('success', "Customer \"{$custName}\" berhasil dihapus.");
        } catch (\Exception $e) {
            return back()->with('error', "Gagal menghapus customer: " . $e->getMessage());
        }
    }
}
