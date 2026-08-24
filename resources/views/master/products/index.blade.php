@extends('layouts.app')
@section('title', 'Produk')
@section('page-title', 'Manajemen Produk')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Produk</h1>
            <p>Kelola semua master data produk</p>
        </div>
        <a href="{{ route('master.products.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Tambah Produk
        </a>
    </div>

    <x-list-filter-bar :action="route('master.products.index')" placeholder="Cari SKU, Nama Produk, Kategori...">
        <select name="category_id" class="form-control" style="height:38px; font-size:13px; min-width:160px; border-radius:6px;">
            <option value="">Semua Kategori</option>
            @foreach($categories as $cat)
            <option value="{{ $cat->id }}" {{ (string) request('category_id') === (string) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>

        <select name="is_active" class="form-control" style="height:38px; font-size:13px; min-width:140px; border-radius:6px;">
            <option value="">Semua Status</option>
            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
        </select>
    </x-list-filter-bar>

    <div class="card">
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <x-sortable-header column="sku" title="SKU" />
                        <x-sortable-header column="name" title="Nama Produk" />
                        <x-sortable-header column="category" title="Kategori" />
                        <th>Unit</th>
                        <x-sortable-header column="purchase_price" title="Harga Beli" align="right" />
                        <x-sortable-header column="sell_price" title="Harga Jual" align="right" />
                        <x-sortable-header column="min_stock" title="Min Stok" align="center" />
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td style="font-weight:600;">
                            <a href="{{ route('master.products.show', $product) }}" style="color:var(--primary); text-decoration:none;" title="Lihat Detail Produk">
                                {{ $product->sku }}
                            </a>
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                @if($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" style="width:36px; height:36px; border-radius:6px; object-fit:cover; border:1px solid #e2e8f0; flex-shrink:0;">
                                @else
                                    <div style="width:36px; height:36px; border-radius:6px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:14px; flex-shrink:0; border:1px solid #e2e8f0;">
                                        <i class="fa-solid fa-box"></i>
                                    </div>
                                @endif
                                <div>
                                    <a href="{{ route('master.products.show', $product) }}" style="color:inherit; font-weight:600; text-decoration:none;" title="Lihat Detail Produk">
                                        {{ $product->name }}
                                    </a>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($product->productCategory)
                                <a href="{{ route('master.categories.show', $product->productCategory) }}" style="color:var(--primary); font-weight:500; text-decoration:none;">
                                    {{ $product->productCategory->name }}
                                </a>
                            @else
                                {{ $product->category ?? '-' }}
                            @endif
                        </td>
                        <td>{{ $product->unit }}</td>
                        <td style="text-align:right;">Rp {{ number_format($product->purchase_price, 0, ',', '.') }}</td>
                        <td style="text-align:right;">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</td>
                        <td style="text-align:center;">{{ $product->min_stock }}</td>
                        <td style="text-align:center;">
                            <form method="POST" action="{{ route('master.products.toggle-status', $product) }}" style="display:inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" style="background:none; border:none; padding:0; cursor:pointer;" title="Klik untuk {{ $product->is_active ? 'menonaktifkan' : 'mengaktifkan' }} produk ini">
                                    <span class="badge {{ $product->is_active ? 'badge-done' : 'badge-cancelled' }}" style="cursor:pointer; transition:opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                                        <i class="fa-solid {{ $product->is_active ? 'fa-check' : 'fa-xmark' }}" style="font-size:10px; margin-right:3px;"></i>
                                        {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </button>
                            </form>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                <a href="{{ route('master.products.show', $product) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="{{ route('master.products.edit', $product) }}" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form method="POST" action="{{ route('master.products.toggle-status', $product) }}" style="display:inline;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-icon {{ $product->is_active ? 'btn-secondary' : 'btn-primary' }}" style="{{ $product->is_active ? 'color:#dc2626;' : 'background:#16a34a; border-color:#16a34a;' }}" title="{{ $product->is_active ? 'Nonaktifkan Produk' : 'Aktifkan Produk' }}">
                                        <i class="fa-solid {{ $product->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                                    </button>
                                </form>
                                <button type="button" data-confirm-delete="delete-product-{{ $product->id }}" data-name="{{ $product->name }} ({{ $product->sku }})" class="btn btn-danger btn-sm btn-icon" title="Hapus Produk">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <form id="delete-product-{{ $product->id }}" method="POST" action="{{ route('master.products.destroy', $product) }}" style="display:none;">
                                    @csrf @method('DELETE')
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            <i class="fa-solid fa-box-open" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                            Belum ada produk yang sesuai filter
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border);">
            {{ $products->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
