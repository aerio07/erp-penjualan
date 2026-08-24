@extends('layouts.app')
@section('title', isset($category) ? 'Edit Kategori — ' . $category->name : 'Tambah Kategori Produk')
@section('page-title', isset($category) ? 'Edit Kategori Produk' : 'Tambah Kategori Produk')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>{{ isset($category) ? 'Edit Kategori Produk' : 'Tambah Kategori Baru' }}</h1>
            <p>{{ isset($category) ? 'Perbarui informasi data kategori produk' : 'Buat kategori baru untuk pengelompokan master produk' }}</p>
        </div>
        <a href="{{ route('master.categories.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card" style="max-width:600px;">
        <div class="card-header">
            <h3><i class="fa-solid fa-tags" style="color:var(--primary); margin-right:8px;"></i> Informasi Kategori</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ isset($category) ? route('master.categories.update', $category) : route('master.categories.store') }}">
                @csrf
                @if(isset($category)) @method('PUT') @endif

                <div class="form-group">
                    <label class="form-label">Kode Kategori <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="code" value="{{ old('code', $category->code ?? '') }}" class="form-control {{ $errors->has('code') ? 'is-invalid' : '' }}" required placeholder="Contoh: KAT-001, ELK, FNB" style="text-transform:uppercase;">
                    @if(!$errors->has('code'))
                    <span class="form-text" style="font-size:11px; color:var(--text-secondary); margin-top:3px;">Kode unik pengenal kategori.</span>
                    @endif
                    @error('code')
                    <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                        <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                        <span>{{ $message }}</span>
                    </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Kategori <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $category->name ?? '') }}" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" required placeholder="Contoh: Elektronik, Aksesoris Komputer, Makanan & Minuman">
                    @error('name')
                    <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                        <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                        <span>{{ $message }}</span>
                    </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Deskripsi / Keterangan</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Penjelasan singkat mengenai kategori produk ini...">{{ old('description', $category->description ?? '') }}</textarea>
                </div>

                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }} style="width:16px; height:16px; cursor:pointer;">
                        <span class="form-label" style="margin-bottom:0;">Kategori Aktif</span>
                    </label>
                </div>

                <div style="display:flex; gap:12px; margin-top:8px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> {{ isset($category) ? 'Perbarui Kategori' : 'Simpan Kategori' }}
                    </button>
                    <a href="{{ route('master.categories.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
