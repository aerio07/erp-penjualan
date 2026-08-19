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
        <select name="category" class="form-control" style="height:38px; font-size:13px; min-width:150px; border-radius:6px;">
            <option value="">Semua Kategori</option>
            @foreach($categories as $cat)
            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
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
                        <td style="font-weight:600; color:var(--primary);">{{ $product->sku }}</td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->category ?? '-' }}</td>
                        <td>{{ $product->unit }}</td>
                        <td style="text-align:right;">Rp {{ number_format($product->purchase_price, 0, ',', '.') }}</td>
                        <td style="text-align:right;">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</td>
                        <td style="text-align:center;">{{ $product->min_stock }}</td>
                        <td style="text-align:center;">
                            <span class="badge {{ $product->is_active ? 'badge-done' : 'badge-cancelled' }}">
                                {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                <a href="{{ route('master.products.edit', $product) }}" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <button data-confirm-delete="delete-product-{{ $product->id }}" class="btn btn-danger btn-sm btn-icon" title="Hapus">
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
