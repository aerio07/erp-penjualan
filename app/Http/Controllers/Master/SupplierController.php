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

        $query = $this->applySearch($query, $request, ['code', 'name', 'contact_person', 'phone', 'email', 'address', 'npwp', 'ktp_number', 'bank_name', 'bank_account_number', 'bank_account_holder']);
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
        $existingSup = $request->filled('code') 
            ? Supplier::where('code', trim($request->code))->first() 
            : null;

        $customCodeMsg = $existingSup 
            ? "Kode supplier \"{$request->code}\" sudah digunakan oleh supplier \"{$existingSup->name}\"." 
            : 'Kode supplier ":input" sudah terdaftar. Gunakan kode yang berbeda.';

        $request->validate([
            'code'                => 'required|string|max:50|unique:suppliers,code',
            'name'                => 'required|string|max:255',
            'contact_person'      => 'nullable|string|max:255',
            'phone'               => 'required|string|max:50',
            'email'               => 'nullable|email|max:255',
            'payment_term'        => 'required|string|max:50',
            'npwp'                => 'nullable|string|max:50',
            'ktp_number'          => 'nullable|string|max:50',
            'address'             => 'required|string',
            'bank_name'           => 'required|string|max:100',
            'bank_account_number' => 'required|string|max:50',
            'bank_account_holder' => 'required|string|max:255',
        ], [
            'code.required'                => 'Kode supplier wajib diisi.',
            'code.unique'                  => $customCodeMsg,
            'name.required'                => 'Nama supplier wajib diisi.',
            'phone.required'               => 'Nomor telepon supplier wajib diisi.',
            'payment_term.required'        => 'Payment term (syarat pembayaran) wajib dipilih.',
            'address.required'             => 'Alamat supplier wajib diisi.',
            'bank_name.required'           => 'Nama bank tujuan transfer wajib dipilih/diisi.',
            'bank_account_number.required' => 'Nomor rekening bank supplier wajib diisi.',
            'bank_account_holder.required' => 'Nama pemilik rekening bank wajib diisi.',
            'email.email'                  => 'Format email tidak valid.',
        ]);

        Supplier::create([
            'code'                => strtoupper(trim($request->code)),
            'name'                => trim($request->name),
            'contact_person'      => $request->contact_person ? trim($request->contact_person) : null,
            'phone'               => trim($request->phone),
            'email'               => $request->email ? trim($request->email) : null,
            'payment_term'        => $request->payment_term,
            'npwp'                => $request->npwp ? trim($request->npwp) : null,
            'ktp_number'          => $request->ktp_number ? trim($request->ktp_number) : null,
            'address'             => trim($request->address),
            'bank_name'           => trim($request->bank_name),
            'bank_account_number' => trim($request->bank_account_number),
            'bank_account_holder' => trim($request->bank_account_holder),
            'is_active'           => $request->boolean('is_active', true),
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
        $existingSup = $request->filled('code') 
            ? Supplier::where('code', trim($request->code))->where('id', '!=', $supplier->id)->first() 
            : null;

        $customCodeMsg = $existingSup 
            ? "Kode supplier \"{$request->code}\" sudah digunakan oleh supplier \"{$existingSup->name}\"." 
            : 'Kode supplier ":input" sudah terdaftar. Gunakan kode yang berbeda.';

        $request->validate([
            'code'                => 'required|string|max:50|unique:suppliers,code,' . $supplier->id,
            'name'                => 'required|string|max:255',
            'contact_person'      => 'nullable|string|max:255',
            'phone'               => 'required|string|max:50',
            'email'               => 'nullable|email|max:255',
            'payment_term'        => 'required|string|max:50',
            'npwp'                => 'nullable|string|max:50',
            'ktp_number'          => 'nullable|string|max:50',
            'address'             => 'required|string',
            'bank_name'           => 'required|string|max:100',
            'bank_account_number' => 'required|string|max:50',
            'bank_account_holder' => 'required|string|max:255',
        ], [
            'code.required'                => 'Kode supplier wajib diisi.',
            'code.unique'                  => $customCodeMsg,
            'name.required'                => 'Nama supplier wajib diisi.',
            'phone.required'               => 'Nomor telepon supplier wajib diisi.',
            'payment_term.required'        => 'Payment term (syarat pembayaran) wajib dipilih.',
            'address.required'             => 'Alamat supplier wajib diisi.',
            'bank_name.required'           => 'Nama bank tujuan transfer wajib dipilih/diisi.',
            'bank_account_number.required' => 'Nomor rekening bank supplier wajib diisi.',
            'bank_account_holder.required' => 'Nama pemilik rekening bank wajib diisi.',
            'email.email'                  => 'Format email tidak valid.',
        ]);

        $supplier->update([
            'code'                => strtoupper(trim($request->code)),
            'name'                => trim($request->name),
            'contact_person'      => $request->contact_person ? trim($request->contact_person) : null,
            'phone'               => trim($request->phone),
            'email'               => $request->email ? trim($request->email) : null,
            'payment_term'        => $request->payment_term,
            'npwp'                => $request->npwp ? trim($request->npwp) : null,
            'ktp_number'          => $request->ktp_number ? trim($request->ktp_number) : null,
            'address'             => trim($request->address),
            'bank_name'           => trim($request->bank_name),
            'bank_account_number' => trim($request->bank_account_number),
            'bank_account_holder' => trim($request->bank_account_holder),
            'is_active'           => $request->boolean('is_active'),
        ]);

        return redirect()->route('master.suppliers.index')
            ->with('success', 'Supplier berhasil diperbarui.');
    }

    public function toggleStatus(Supplier $supplier): RedirectResponse
    {
        $supplier->update([
            'is_active' => !$supplier->is_active,
        ]);

        $statusLabel = $supplier->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Status supplier \"{$supplier->name}\" berhasil {$statusLabel}.");
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $hasPOs      = $supplier->purchaseOrders()->exists();
        $hasInvoices = method_exists($supplier, 'purchaseInvoices') ? $supplier->purchaseInvoices()->exists() : false;
        $hasDemands  = method_exists($supplier, 'procurementDemands') ? $supplier->procurementDemands()->exists() : false;

        if ($hasPOs || $hasInvoices || $hasDemands) {
            return back()->with('error', "Supplier \"{$supplier->name}\" ({$supplier->code}) tidak dapat dihapus karena sudah memiliki riwayat transaksi pengadaan/PO. Anda dapat menonaktifkan supplier ini sebagai gantinya.");
        }

        try {
            $supName = $supplier->name;
            $supplier->delete();
            return redirect()->route('master.suppliers.index')
                ->with('success', "Supplier \"{$supName}\" berhasil dihapus.");
        } catch (\Exception $e) {
            return back()->with('error', "Gagal menghapus supplier: " . $e->getMessage());
        }
    }
}
