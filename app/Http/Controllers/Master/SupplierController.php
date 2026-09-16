<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use App\Traits\HasListFilters;
use App\Traits\GeneratesDocumentNumber;
use Illuminate\Support\Facades\Storage;

class SupplierController extends Controller
{
    use HasListFilters, GeneratesDocumentNumber;

    public function index(Request $request): View
    {
        $query = Supplier::withCount('purchaseOrders');

        $query = $this->applySearch($query, $request, ['code', 'name', 'contact_person', 'phone', 'email', 'address', 'npwp', 'ktp_number']);
        $query = $this->applyFilter($query, $request, 'is_active');
        $query = $this->applySort($query, $request, ['code', 'name', 'contact_person', 'payment_term', 'created_at'], 'created_at', 'desc');

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
        // Auto-generate system code if not provided
        $code = $request->filled('code') 
            ? strtoupper(trim($request->code)) 
            : $this->generateMasterCode(Supplier::class, 'code', 'SUP', 4);

        $existingSup = Supplier::where('code', $code)->first();
        $customCodeMsg = $existingSup 
            ? "Kode supplier \"{$code}\" sudah digunakan oleh supplier \"{$existingSup->name}\"." 
            : 'Kode supplier ":input" sudah terdaftar. Gunakan kode yang berbeda.';

        $request->merge(['code' => $code]);

        $request->validate([
            'code'                => 'required|string|max:50|unique:suppliers,code',
            'name'                => 'required|string|max:255',
            'contact_person'      => 'nullable|string|max:255',
            'phone'               => 'required|string|max:50',
            'email'               => 'nullable|email|max:255',
            'payment_term'        => 'required|string|max:50',
            'supplier_type'       => 'nullable|in:pkp,non_pkp',
            'npwp'                => 'required_if:supplier_type,pkp|required_without:ktp_number|nullable|string|max:50',
            'ktp_number'          => 'required_if:supplier_type,non_pkp|required_without:npwp|nullable|digits:16',
            'tax_document'        => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:3072',
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
            'npwp.required_if'             => 'Nomor NPWP wajib diisi untuk supplier bertipe Badan Usaha / PKP.',
            'npwp.required_without'        => 'Nomor NPWP wajib diisi jika Nomor KTP/NIK tidak diisi.',
            'ktp_number.required_if'       => 'Nomor NIK (KTP) 16 digit wajib diisi untuk supplier perorangan.',
            'ktp_number.required_without'  => 'Nomor NIK (KTP) wajib diisi jika Nomor NPWP tidak diisi.',
            'ktp_number.digits'            => 'Nomor KTP / NIK harus berupa 16 digit angka.',
            'address.required'             => 'Alamat supplier wajib diisi.',
            'bank_name.required'           => 'Nama bank tujuan transfer wajib dipilih/diisi.',
            'bank_account_number.required' => 'Nomor rekening bank supplier wajib diisi.',
            'bank_account_holder.required' => 'Nama pemilik rekening bank wajib diisi.',
            'tax_document.file'            => 'Dokumen NPWP / NIK harus berupa file yang valid.',
            'tax_document.mimes'           => 'Format dokumen yang didukung: PDF, JPG, JPEG, PNG, atau WEBP.',
            'tax_document.max'             => 'Ukuran dokumen maksimal 3MB.',
            'email.email'                  => 'Format email tidak valid.',
        ]);

        $taxDocumentPath = null;
        if ($request->hasFile('tax_document')) {
            $taxDocumentPath = $request->file('tax_document')->store('suppliers/documents', 'public');
        }

        Supplier::create([
            'code'                => $code,
            'name'                => trim($request->name),
            'contact_person'      => $request->contact_person ? trim($request->contact_person) : null,
            'phone'               => trim($request->phone),
            'email'               => $request->email ? trim($request->email) : null,
            'payment_term'        => $request->payment_term,
            'npwp'                => $request->npwp ? trim($request->npwp) : null,
            'ktp_number'          => $request->ktp_number ? trim($request->ktp_number) : null,
            'tax_document'        => $taxDocumentPath,
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
        $request->validate([
            'name'                => 'required|string|max:255',
            'contact_person'      => 'nullable|string|max:255',
            'phone'               => 'required|string|max:50',
            'email'               => 'nullable|email|max:255',
            'payment_term'        => 'required|string|max:50',
            'supplier_type'       => 'nullable|in:pkp,non_pkp',
            'npwp'                => 'required_if:supplier_type,pkp|required_without:ktp_number|nullable|string|max:50',
            'ktp_number'          => 'required_if:supplier_type,non_pkp|required_without:npwp|nullable|digits:16',
            'tax_document'        => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:3072',
            'address'             => 'required|string',
            'bank_name'           => 'required|string|max:100',
            'bank_account_number' => 'required|string|max:50',
            'bank_account_holder' => 'required|string|max:255',
        ], [
            'name.required'                => 'Nama supplier wajib diisi.',
            'phone.required'               => 'Nomor telepon supplier wajib diisi.',
            'payment_term.required'        => 'Payment term (syarat pembayaran) wajib dipilih.',
            'npwp.required_if'             => 'Nomor NPWP wajib diisi untuk supplier bertipe Badan Usaha / PKP.',
            'npwp.required_without'        => 'Nomor NPWP wajib diisi jika Nomor KTP/NIK tidak diisi.',
            'ktp_number.required_if'       => 'Nomor NIK (KTP) 16 digit wajib diisi untuk supplier perorangan.',
            'ktp_number.required_without'  => 'Nomor NIK (KTP) wajib diisi jika Nomor NPWP tidak diisi.',
            'ktp_number.digits'            => 'Nomor KTP / NIK harus berupa 16 digit angka.',
            'address.required'             => 'Alamat supplier wajib diisi.',
            'bank_name.required'           => 'Nama bank tujuan transfer wajib dipilih/diisi.',
            'bank_account_number.required' => 'Nomor rekening bank supplier wajib diisi.',
            'bank_account_holder.required' => 'Nama pemilik rekening bank wajib diisi.',
            'tax_document.file'            => 'Dokumen NPWP / NIK harus berupa file yang valid.',
            'tax_document.mimes'           => 'Format dokumen yang didukung: PDF, JPG, JPEG, PNG, atau WEBP.',
            'tax_document.max'             => 'Ukuran dokumen maksimal 3MB.',
            'email.email'                  => 'Format email tidak valid.',
        ]);

        $taxDocumentPath = $supplier->tax_document;
        if ($request->hasFile('tax_document')) {
            if ($supplier->tax_document && Storage::disk('public')->exists($supplier->tax_document)) {
                Storage::disk('public')->delete($supplier->tax_document);
            }
            $taxDocumentPath = $request->file('tax_document')->store('suppliers/documents', 'public');
        }

        $supplier->update([
            'name'                => trim($request->name),
            'contact_person'      => $request->contact_person ? trim($request->contact_person) : null,
            'phone'               => trim($request->phone),
            'email'               => $request->email ? trim($request->email) : null,
            'payment_term'        => $request->payment_term,
            'npwp'                => $request->npwp ? trim($request->npwp) : null,
            'ktp_number'          => $request->ktp_number ? trim($request->ktp_number) : null,
            'tax_document'        => $taxDocumentPath,
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
