@extends('layouts.app')
@section('title', isset($warehouse) ? 'Edit Gudang' : 'Tambah Gudang')
@section('page-title', isset($warehouse) ? 'Edit Gudang' : 'Tambah Gudang')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>{{ isset($warehouse) ? 'Edit Gudang' : 'Tambah Gudang Baru' }}</h1>
        </div>
        <a href="{{ route('master.warehouses.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card" style="max-width:600px;">
        <div class="card-header"><h3>Informasi Gudang</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ isset($warehouse) ? route('master.warehouses.update', $warehouse) : route('master.warehouses.store') }}">
                @csrf
                @if(isset($warehouse)) @method('PUT') @endif

                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Kode Gudang <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="code" value="{{ old('code', $warehouse->code ?? '') }}"
                            class="form-control {{ $errors->has('code') ? 'is-invalid' : '' }}"
                            required placeholder="Contoh: WH-001, GUD-JKT" style="text-transform:uppercase;">
                        @if(!$errors->has('code'))
                        <span class="form-text" style="font-size:11px; color:var(--text-secondary); margin-top:3px;">Kode unik pengenal gudang.</span>
                        @endif
                        @error('code')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Gudang <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $warehouse->name ?? '') }}"
                            class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" required placeholder="Contoh: Gudang Utama Jakarta">
                        @error('name')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Alamat Lengkap <span style="color:var(--danger);">*</span></label>
                    <textarea name="address" class="form-control {{ $errors->has('address') ? 'is-invalid' : '' }}" rows="3" required
                        placeholder="Masukkan alamat lengkap lokasi gudang...">{{ old('address', $warehouse->address ?? '') }}</textarea>
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
                            {{ old('is_active', $warehouse->is_active ?? true) ? 'checked' : '' }}
                            style="width:16px; height:16px; cursor:pointer;">
                        <span class="form-label" style="margin-bottom:0;">Gudang Aktif</span>
                    </label>
                </div>

                <div style="display:flex; gap:12px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> {{ isset($warehouse) ? 'Perbarui' : 'Simpan' }}
                    </button>
                    <a href="{{ route('master.warehouses.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
