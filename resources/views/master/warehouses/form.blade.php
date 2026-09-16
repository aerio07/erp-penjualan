@extends('layouts.app')
@section('title', isset($warehouse) ? 'Edit Gudang' : 'Tambah Gudang')
@section('page-title', isset($warehouse) ? 'Edit Gudang' : 'Tambah Gudang')

@section('content')
<div class="animate-in">
    <x-page-header 
        :title="isset($warehouse) ? 'Edit Gudang' : 'Tambah Gudang Baru'" 
        subtitle="Lengkapi data lokasi penyimpanan barang di bawah ini" 
        :back-url="route('master.warehouses.index')" 
    />

    <div class="card w-full">
        <div class="card-header"><h3>Informasi Gudang</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ isset($warehouse) ? route('master.warehouses.update', $warehouse) : route('master.warehouses.store') }}">
                @csrf
                @if(isset($warehouse)) @method('PUT') @endif

                <!-- Row 1: 2 Kolom (Kode & Nama Gudang) -->
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Kode Gudang <span class="text-xs text-slate-400 font-normal lowercase">(otomatis sistem)</span></label>
                        @if(isset($warehouse))
                            <input type="text" name="code" value="{{ $warehouse->code }}" class="form-control bg-slate-100 font-bold text-[#0e1b35]" readonly title="Kode gudang tetap">
                            <span class="form-text" style="font-size:11px; color:var(--text-secondary); margin-top:3px;">Kode pengenal permanen gudang.</span>
                        @else
                            <input type="text" value="[Dibuat Otomatis oleh Sistem]" class="form-control bg-slate-100 font-semibold text-slate-500 cursor-not-allowed" readonly style="border-style:dashed;">
                            <span class="form-text" style="font-size:11px; color:var(--text-secondary); margin-top:3px;">Format: WH-XXX digenerate saat disimpan.</span>
                        @endif
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

                <!-- Row 2: Alamat Lengkap Bertingkat (Cascading) -->
                <div class="mb-5">
                    <x-address-cascading 
                        name="address" 
                        :value="$warehouse->address ?? ''" 
                        label="Alamat Lengkap Gudang" 
                        :required="true"
                    />
                </div>

                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <input type="checkbox" name="is_active" value="1"
                            {{ old('is_active', $warehouse->is_active ?? true) ? 'checked' : '' }}
                            style="width:16px; height:16px; cursor:pointer;">
                        <span class="form-label" style="margin-bottom:0;">Gudang Aktif</span>
                    </label>
                </div>

                <x-form-actions 
                    :cancel-url="route('master.warehouses.index')" 
                    :submit-label="isset($warehouse) ? 'Perbarui Gudang' : 'Simpan Gudang'" 
                />
            </form>
        </div>
    </div>
</div>
@endsection
