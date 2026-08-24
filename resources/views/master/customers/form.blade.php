@extends('layouts.app')
@section('title', isset($customer) ? 'Edit Customer' : 'Tambah Customer')
@section('page-title', isset($customer) ? 'Edit Customer' : 'Tambah Customer')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div><h1>{{ isset($customer) ? 'Edit Customer' : 'Tambah Customer Baru' }}</h1></div>
        <a href="{{ route('master.customers.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card" style="max-width:700px;">
        <div class="card-header"><h3>Informasi Customer</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ isset($customer) ? route('master.customers.update', $customer) : route('master.customers.store') }}">
                @csrf
                @if(isset($customer)) @method('PUT') @endif

                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Kode Customer <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="code" value="{{ old('code', $customer->code ?? '') }}"
                            class="form-control {{ $errors->has('code') ? 'is-invalid' : '' }}" required placeholder="Contoh: CUST-001, CUST-JKT" style="text-transform:uppercase;">
                        @if(!$errors->has('code'))
                        <span class="form-text" style="font-size:11px; color:var(--text-secondary); margin-top:3px;">Kode unik pengenal customer.</span>
                        @endif
                        @error('code')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
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
                        <label class="form-label">No. Telepon / WhatsApp <span style="color:var(--danger);">*</span></label>
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
                        <label class="form-label">Payment Term <span style="color:var(--danger);">*</span></label>
                        <select name="payment_term" class="form-control {{ $errors->has('payment_term') ? 'is-invalid' : '' }}" required>
                            <option value="">-- Pilih Syarat Pembayaran --</option>
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
                    <div class="form-group">
                        <label class="form-label">Credit Limit (Plafon Kredit Rp) <span style="color:var(--danger);">*</span></label>
                        <input type="number" name="credit_limit" value="{{ old('credit_limit', $customer->credit_limit ?? 0) }}"
                            class="form-control {{ $errors->has('credit_limit') ? 'is-invalid' : '' }}" required min="0" step="100000" placeholder="0 = tanpa piutang">
                        @error('credit_limit')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nomor NPWP <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="npwp" value="{{ old('npwp', $customer->npwp ?? '') }}" class="form-control {{ $errors->has('npwp') ? 'is-invalid' : '' }}" required placeholder="00.000.000.0-000.000">
                        @error('npwp')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Alamat Lengkap <span style="color:var(--danger);">*</span></label>
                    <textarea name="address" class="form-control {{ $errors->has('address') ? 'is-invalid' : '' }}" rows="3" required placeholder="Masukkan alamat lengkap customer / toko...">{{ old('address', $customer->address ?? '') }}</textarea>
                    @error('address')
                    <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                        <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                        <span>{{ $message }}</span>
                    </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <input type="checkbox" name="is_active" value="1"
                            {{ old('is_active', $customer->is_active ?? true) ? 'checked' : '' }}
                            style="width:16px; height:16px; cursor:pointer;">
                        <span class="form-label" style="margin-bottom:0;">Customer Aktif</span>
                    </label>
                </div>

                <div style="display:flex; gap:12px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> {{ isset($customer) ? 'Perbarui' : 'Simpan' }}
                    </button>
                    <a href="{{ route('master.customers.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
