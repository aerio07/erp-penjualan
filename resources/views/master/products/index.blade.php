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

    <div class="card">
        <div class="card-header">
            <h3>Daftar Produk</h3>
            <span style="font-size:13px; color:var(--text-secondary);">{{ $products->total() }} produk</span>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Unit</th>
                        <th style="text-align:right;">Harga Beli</th>
                        <th style="text-align:right;">Harga Jual</th>
                        <th style="text-align:center;">Min Stok</th>
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
                            Belum ada produk
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
