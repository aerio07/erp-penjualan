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

    <div class="card w-full">
        <div class="card-header"><h3>Informasi Produk</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ isset($product) ? route('master.products.update', $product) : route('master.products.store') }}" enctype="multipart/form-data">
                @csrf
                @if(isset($product)) @method('PUT') @endif

                <!-- Row 1: 4 Kolom (SKU, Nama Produk, Kategori, Satuan) -->
                <div class="form-row form-row-4">
                    <div class="form-group">
                        <label class="form-label">Kode SKU <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="sku" value="{{ old('sku', $product->sku ?? '') }}" class="form-control {{ $errors->has('sku') ? 'is-invalid' : '' }}" required placeholder="Contoh: PRD-001, ELK-102">
                        @if(!$errors->has('sku'))
                        <span class="form-text" style="font-size:11px; color:var(--text-secondary); margin-top:3px;">Kode pengenal unik.</span>
                        @endif
                        @error('sku')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Produk <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" required placeholder="Contoh: Keyboard Mechanical K2">
                        @error('name')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kategori Produk</label>
                        <select name="category_id" class="form-control {{ $errors->has('category_id') ? 'is-invalid' : '' }}">
                            <option value="">-- Tanpa Kategori (Umum) --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ (string) old('category_id', $product->category_id ?? '') === (string) $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }} ({{ $cat->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Satuan Unit <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="unit" value="{{ old('unit', $product->unit ?? '') }}" class="form-control {{ $errors->has('unit') ? 'is-invalid' : '' }}" required placeholder="pcs, box, unit...">
                        @error('unit')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                </div>

                <!-- Row 2: 3 Kolom (Harga Beli, Harga Jual, Min Stok) -->
                <div class="form-row form-row-3">
                    <div class="form-group">
                        <label class="form-label">Harga Beli (Rp)</label>
                        <input type="number" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price ?? 0) }}" class="form-control" min="0" step="100">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga Jual (Rp)</label>
                        <input type="number" name="sell_price" value="{{ old('sell_price', $product->sell_price ?? 0) }}" class="form-control" min="0" step="100">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Min Stok (Restock Alert)</label>
                        <input type="number" name="min_stock" value="{{ old('min_stock', $product->min_stock ?? 0) }}" class="form-control" min="0">
                    </div>
                </div>

                <!-- Row 3: 2 Kolom (Foto Produk & Catatan) -->
                <div class="form-row form-row-2">
                    <!-- Upload Foto Produk -->
                    <div class="form-group" x-data="{ 
                        previewUrl: '{{ isset($product) && $product->image_url ? $product->image_url : '' }}',
                        removeImage: false,
                        handleFileChange(event) {
                            const file = event.target.files[0];
                            if (file) {
                                this.previewUrl = URL.createObjectURL(file);
                                this.removeImage = false;
                            }
                        },
                        clearFile() {
                            this.previewUrl = '';
                            this.removeImage = true;
                            this.$refs.fileInput.value = '';
                        }
                    }">
                        <label class="form-label">Foto Produk</label>
                        <input type="hidden" name="remove_image" :value="removeImage ? '1' : '0'">
                        
                        <div style="display:flex; gap:14px; align-items:center;">
                            <!-- Preview Box -->
                            <div style="width:70px; height:70px; border-radius:8px; border:2px dashed #cbd5e1; background:#f8fafc; display:flex; align-items:center; justify-content:center; overflow:hidden; position:relative; flex-shrink:0;">
                                <template x-if="previewUrl">
                                    <div style="width:100%; height:100%; position:relative;">
                                        <img :src="previewUrl" alt="Preview Foto" style="width:100%; height:100%; object-fit:cover;">
                                        <button type="button" @click="clearFile()" style="position:absolute; top:2px; right:2px; background:rgba(220, 38, 38, 0.85); color:#fff; border:none; border-radius:50%; width:18px; height:18px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:9px;" title="Hapus foto">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </template>
                                <template x-if="!previewUrl">
                                    <div style="text-align:center; color:#94a3b8;">
                                        <i class="fa-solid fa-image" style="font-size:20px; margin-bottom:1px; display:block;"></i>
                                        <span style="font-size:9px;">Foto</span>
                                    </div>
                                </template>
                            </div>

                            <!-- File Input Controls -->
                            <div style="flex:1;">
                                <input type="file" name="image" x-ref="fileInput" @change="handleFileChange($event)" accept="image/jpeg,image/png,image/jpg,image/webp,image/svg+xml" class="form-control {{ $errors->has('image') ? 'is-invalid' : '' }}" style="padding:6px 10px; font-size:12px;">
                                <div style="font-size:11px; color:var(--text-secondary); margin-top:3px;">
                                    Format: JPG, PNG, WEBP (Maks. 3MB).
                                </div>
                                @error('image')
                                <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                                    <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Catatan / Spesifikasi Singkat</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Catatan tambahan spesifikasi atau deskripsi produk...">{{ old('notes', $product->notes ?? '') }}</textarea>
                    </div>
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
