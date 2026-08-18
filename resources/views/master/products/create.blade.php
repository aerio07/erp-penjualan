@extends('layouts.app')
@section('title', isset($product) ? 'Edit Produk' : 'Tambah Produk')
@section('page-title', isset($product) ? 'Edit Produk' : 'Tambah Produk')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>{{ isset($product) ? 'Edit Produk' : 'Tambah Produk Baru' }}</h1>
        </div>
        <a href="{{ route('master.products.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card" style="max-width:700px;">
        <div class="card-header"><h3>Informasi Produk</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ isset($product) ? route('master.products.update', $product) : route('master.products.store') }}">
                @csrf
                @if(isset($product)) @method('PUT') @endif

                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">SKU <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="sku" value="{{ old('sku', $product->sku ?? '') }}" class="form-control {{ $errors->has('sku') ? 'is-invalid' : '' }}" required placeholder="PRD-001">
                        @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="unit" value="{{ old('unit', $product->unit ?? '') }}" class="form-control" required placeholder="pcs, box, kg...">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Produk <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Kategori</label>
                        <input type="text" name="category" value="{{ old('category', $product->category ?? '') }}" class="form-control" placeholder="Elektronik, Aksesoris...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Min Stok (Restock Alert)</label>
                        <input type="number" name="min_stock" value="{{ old('min_stock', $product->min_stock ?? 0) }}" class="form-control" min="0">
                    </div>
                </div>

                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Harga Beli (Rp)</label>
                        <input type="number" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price ?? 0) }}" class="form-control" min="0" step="100">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga Jual (Rp)</label>
                        <input type="number" name="sell_price" value="{{ old('sell_price', $product->sell_price ?? 0) }}" class="form-control" min="0" step="100">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes', $product->notes ?? '') }}</textarea>
                </div>

                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }} style="width:16px; height:16px; cursor:pointer;">
                        <span class="form-label" style="margin-bottom:0;">Produk Aktif</span>
                    </label>
                </div>

                <div style="display:flex; gap:12px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> {{ isset($product) ? 'Perbarui' : 'Simpan' }}
                    </button>
                    <a href="{{ route('master.products.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
