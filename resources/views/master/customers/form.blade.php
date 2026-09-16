@extends('layouts.app')
@section('title', isset($customer) ? 'Edit Customer' : 'Tambah Customer')
@section('page-title', isset($customer) ? 'Edit Customer' : 'Tambah Customer')

@section('content')
<div class="animate-in" x-data="{ taxType: '{{ old('tax_type', $customer->tax_type ?? 'non_pkp') }}' }">
    <x-page-header 
        :title="isset($customer) ? 'Edit Customer' : 'Tambah Customer Baru'" 
        subtitle="Lengkapi formulir data pelanggan di bawah ini" 
        :back-url="route('master.customers.index')" 
    />

    <div class="card w-full">
        <div class="card-header"><h3>Informasi Customer</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ isset($customer) ? route('master.customers.update', $customer) : route('master.customers.store') }}" enctype="multipart/form-data">
                @csrf
                @if(isset($customer)) @method('PUT') @endif

                <!-- Row 1: 4 Kolom (Kode, Nama, CP, Payment Term) -->
                <div class="form-row form-row-4">
                    <div class="form-group">
                        <label class="form-label">Kode Customer <span class="text-xs text-slate-400 font-normal lowercase">(otomatis sistem)</span></label>
                        @if(isset($customer))
                            <input type="text" name="code" value="{{ $customer->code }}" class="form-control bg-slate-100 font-bold text-[#0e1b35]" readonly title="Kode customer tetap">
                            <span class="form-text" style="font-size:11px; color:var(--text-secondary); margin-top:3px;">Kode pengenal permanen customer.</span>
                        @else
                            <input type="text" value="[Dibuat Otomatis oleh Sistem]" class="form-control bg-slate-100 font-semibold text-slate-500 cursor-not-allowed" readonly style="border-style:dashed;">
                            <span class="form-text" style="font-size:11px; color:var(--text-secondary); margin-top:3px;">Format: CUST-XXXX digenerate saat disimpan.</span>
                        @endif
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Customer <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $customer->name ?? '') }}"
                            class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" required placeholder="Contoh: Toko Berkah Jaya / PT. Maju Mandiri">
                        @error('name')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" value="{{ old('contact_person', $customer->contact_person ?? '') }}" class="form-control" placeholder="Nama PIC / Kontak">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Term <span style="color:var(--danger);">*</span></label>
                        <select name="payment_term" class="form-control {{ $errors->has('payment_term') ? 'is-invalid' : '' }}" required>
                            <option value="">-- Pilih Syarat --</option>
                            @foreach(['COD','NET 7','NET 14','NET 30','NET 45','NET 60'] as $term)
                            <option value="{{ $term }}" {{ old('payment_term', $customer->payment_term ?? '') === $term ? 'selected' : '' }}>{{ $term }}</option>
                            @endforeach
                        </select>
                        @error('payment_term')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                </div>

                <!-- Row 2: 4 Kolom (Telepon, Email, Credit Limit, Sales PIC) -->
                <div class="form-row form-row-4">
                    <div class="form-group">
                        <label class="form-label">No. Telepon / WA <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="phone" value="{{ old('phone', $customer->phone ?? '') }}" class="form-control {{ $errors->has('phone') ? 'is-invalid' : '' }}" required placeholder="08xx-xxxx-xxxx">
                        @error('phone')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ old('email', $customer->email ?? '') }}" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" placeholder="customer@example.com">
                        @error('email')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Credit Limit (Rp) <span style="color:var(--danger);">*</span></label>
                        <input type="text" inputmode="numeric" name="credit_limit" value="{{ old('credit_limit', isset($customer) && $customer->credit_limit !== null ? round($customer->credit_limit) : 0) }}"
                            class="form-control format-currency {{ $errors->has('credit_limit') ? 'is-invalid' : '' }}" required placeholder="0 = tanpa limit">
                        <span class="form-text" style="font-size:11px; color:var(--text-secondary); margin-top:3px;">Plafon piutang maksimal.</span>
                        @error('credit_limit')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sales PIC</label>
                        <select name="sales_person_id" class="form-control {{ $errors->has('sales_person_id') ? 'is-invalid' : '' }}">
                            <option value="">-- Belum Ditentukan --</option>
                            @foreach($salesUsers as $su)
                            <option value="{{ $su->id }}" {{ (string) old('sales_person_id', $customer->sales_person_id ?? '') === (string) $su->id ? 'selected' : '' }}>
                                {{ $su->name }}
                            </option>
                            @endforeach
                        </select>
                        <span class="form-text" style="font-size:11px; color:var(--text-secondary); margin-top:3px;">Staff Sales penanggung jawab.</span>
                        @error('sales_person_id')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- INFORMASI PERPAJAKAN & IDENTITAS (SECTION) -->
                <!-- ========================================================= -->
                <div class="p-4 rounded-lg border border-border-light bg-[#FAF9FF] mb-5 w-full">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-sm font-bold text-[#0e1b35] font-sans">Informasi Perpajakan & Identitas</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#E0E7FF] text-[#3730A3] border border-[#3730A3]/20">Status Pajak</span>
                    </div>
                    <p class="text-xs text-on-surface-variant mb-4">Klasifikasi Pengusaha Kena Pajak (PKP), NPWP perusahaan, atau NIK untuk customer non-pajak/perorangan.</p>

                    <div class="form-row form-row-3 mb-3">
                        <!-- Tipe Customer (PKP / Non-PKP) -->
                        <div class="form-group mb-0">
                            <label class="form-label">Tipe Customer <span style="color:var(--danger);">*</span></label>
                            <select name="tax_type" x-model="taxType" class="form-control {{ $errors->has('tax_type') ? 'is-invalid' : '' }}" required>
                                <option value="non_pkp">Non-PKP (Bukan Pengusaha Kena Pajak / Perorangan)</option>
                                <option value="pkp">PKP (Pengusaha Kena Pajak / Badan)</option>
                            </select>
                            <span class="form-text" style="font-size:11px; color:var(--text-secondary); margin-top:3px;">
                                Customer PKP akan dapat diterbitkan Faktur Pajak resmi pada invoice penjualan.
                            </span>
                            @error('tax_type')
                            <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                                <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                                <span>{{ $message }}</span>
                            </div>
                            @enderror
                        </div>

                        <!-- NPWP (Wajib jika PKP) -->
                        <div class="form-group mb-0">
                            <label class="form-label">
                                Nomor NPWP 
                                <template x-if="taxType === 'pkp'">
                                    <span style="color:var(--danger);">*</span>
                                </template>
                            </label>
                            <input type="text" name="npwp" value="{{ old('npwp', $customer->npwp ?? '') }}" 
                                class="form-control {{ $errors->has('npwp') ? 'is-invalid' : '' }}" 
                                :required="taxType === 'pkp'"
                                placeholder="00.000.000.0-000.000">
                            <span class="form-text" style="font-size:11px; color:var(--text-secondary); margin-top:3px;">
                                <span x-show="taxType === 'pkp'" class="text-red-600 font-semibold">Wajib diisi untuk customer PKP.</span>
                                <span x-show="taxType !== 'pkp'">Opsional jika non-PKP.</span>
                            </span>
                            @error('npwp')
                            <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                                <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                                <span>{{ $message }}</span>
                            </div>
                            @enderror
                        </div>

                        <!-- NIK (Nomor Induk Kependudukan - Wajib untuk Non-PKP) -->
                        <div class="form-group mb-0">
                            <label class="form-label">
                                NIK (Nomor KTP)
                                <template x-if="taxType === 'non_pkp'">
                                    <span style="color:var(--danger);">*</span>
                                </template>
                            </label>
                            <input type="text" name="nik" value="{{ old('nik', $customer->nik ?? '') }}" 
                                class="form-control {{ $errors->has('nik') ? 'is-invalid' : '' }}" 
                                :required="taxType === 'non_pkp'"
                                placeholder="Contoh: 3201xxxxxxxxxxxx" maxlength="20">
                            <span class="form-text" style="font-size:11px; color:var(--text-secondary); margin-top:3px;">
                                <span x-show="taxType === 'non_pkp'" class="text-red-600 font-semibold">Wajib diisi untuk customer Non-PKP (non-pajak).</span>
                                <span x-show="taxType === 'pkp'">Opsional untuk perwakilan PKP.</span>
                            </span>
                            @error('nik')
                            <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                                <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                                <span>{{ $message }}</span>
                            </div>
                            @enderror
                        </div>
                    </div>

                    <!-- Upload Dokumen NPWP / NIK -->
                    <div class="pt-2 border-t border-slate-200/80">
                        <x-file-upload 
                            name="tax_document" 
                            label="Dokumen NPWP / NIK (KTP)" 
                            :value="$customer->tax_document ?? null"
                            helper-text="Unggah salinan dokumen NPWP (Badan/PKP) atau KTP/NIK (Perorangan) format PDF/JPG/PNG max 3MB"
                        />
                    </div>
                </div>

                <!-- Row 3: Alamat Lengkap Bertingkat (Cascading) -->
                <div class="mb-5">
                    <x-address-cascading 
                        name="address" 
                        :value="$customer->address ?? ''" 
                        label="Alamat Lengkap Customer" 
                        :required="true"
                    />
                </div>

                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <input type="checkbox" name="is_active" value="1"
                            {{ old('is_active', $customer->is_active ?? true) ? 'checked' : '' }}
                            style="width:16px; height:16px; cursor:pointer;">
                        <span class="form-label" style="margin-bottom:0;">Customer Aktif</span>
                    </label>
                </div>

                <x-form-actions 
                    :cancel-url="route('master.customers.index')" 
                    :submit-label="isset($customer) ? 'Perbarui Customer' : 'Simpan Customer'" 
                />
            </form>
        </div>
    </div>
</div>
@endsection
