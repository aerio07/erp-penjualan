@extends('layouts.app')
@section('title', isset($supplier) ? 'Edit Supplier' : 'Tambah Supplier')
@section('page-title', isset($supplier) ? 'Edit Supplier' : 'Tambah Supplier')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div><h1>{{ isset($supplier) ? 'Edit Supplier' : 'Tambah Supplier Baru' }}</h1></div>
        <a href="{{ route('master.suppliers.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card" style="max-width:700px;">
        <div class="card-header"><h3>Informasi Supplier</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ isset($supplier) ? route('master.suppliers.update', $supplier) : route('master.suppliers.store') }}">
                @csrf
                @if(isset($supplier)) @method('PUT') @endif

                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Kode Supplier <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="code" value="{{ old('code', $supplier->code ?? '') }}"
                            class="form-control {{ $errors->has('code') ? 'is-invalid' : '' }}" required placeholder="Contoh: SUP-001, PT-ABC" style="text-transform:uppercase;">
                        @if(!$errors->has('code'))
                        <span class="form-text" style="font-size:11px; color:var(--text-secondary); margin-top:3px;">Kode unik pengenal supplier.</span>
                        @endif
                        @error('code')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Supplier <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $supplier->name ?? '') }}"
                            class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" required placeholder="Contoh: PT. Sumber Makmur Abadi">
                        @error('name')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" value="{{ old('contact_person', $supplier->contact_person ?? '') }}" class="form-control" placeholder="Nama PIC / Kontak">
                    </div>
                    <div class="form-group">
                        <label class="form-label">No. Telepon / WhatsApp <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="phone" value="{{ old('phone', $supplier->phone ?? '') }}" class="form-control {{ $errors->has('phone') ? 'is-invalid' : '' }}" required placeholder="08xx-xxxx-xxxx">
                        @error('phone')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ old('email', $supplier->email ?? '') }}" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" placeholder="supplier@example.com">
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
                            <option value="{{ $term }}" {{ old('payment_term', $supplier->payment_term ?? '') === $term ? 'selected' : '' }}>{{ $term }}</option>
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

                <div class="form-group">
                    <label class="form-label">Nomor NPWP <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="npwp" value="{{ old('npwp', $supplier->npwp ?? '') }}" class="form-control {{ $errors->has('npwp') ? 'is-invalid' : '' }}" required placeholder="00.000.000.0-000.000">
                    @error('npwp')
                    <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                        <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                        <span>{{ $message }}</span>
                    </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Alamat Lengkap <span style="color:var(--danger);">*</span></label>
                    <textarea name="address" class="form-control {{ $errors->has('address') ? 'is-invalid' : '' }}" rows="3" required placeholder="Masukkan alamat lengkap supplier / kantor...">{{ old('address', $supplier->address ?? '') }}</textarea>
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
                            {{ old('is_active', $supplier->is_active ?? true) ? 'checked' : '' }}
                            style="width:16px; height:16px; cursor:pointer;">
                        <span class="form-label" style="margin-bottom:0;">Supplier Aktif</span>
                    </label>
                </div>

                <div style="display:flex; gap:12px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> {{ isset($supplier) ? 'Perbarui' : 'Simpan' }}
                    </button>
                    <a href="{{ route('master.suppliers.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
