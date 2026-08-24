@extends('layouts.app')
@section('title', 'Kategori — ' . $category->name)
@section('page-title', 'Detail Kategori Produk')

@section('content')
<div class="animate-in">
    <!-- Header -->
    <div class="page-header">
        <div>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:4px;">
                <h1 style="margin-bottom:0;">{{ $category->name }}</h1>
                <span class="badge {{ $category->is_active ? 'badge-done' : 'badge-cancelled' }}" style="font-size:12px; padding:4px 10px;">
                    {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>
            <p style="margin-bottom:0;">
                Kode Kategori: <strong style="color:var(--primary);">{{ $category->code }}</strong> &nbsp;·&nbsp;
                Total Item: <span>{{ $totalProducts }} Produk Terdaftar</span>
            </p>
        </div>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <!-- Toggle Status Form -->
            <form method="POST" action="{{ route('master.categories.toggle-status', $category) }}" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin {{ $category->is_active ? 'menonaktifkan' : 'mengaktifkan' }} kategori ini?');">
                @csrf
                @method('PATCH')
                @if($category->is_active)
                    <button type="submit" class="btn btn-secondary" style="color:#b91c1c; border-color:#fca5a5;" title="Nonaktifkan Kategori">
                        <i class="fa-solid fa-ban"></i> Nonaktifkan
                    </button>
                @else
                    <button type="submit" class="btn btn-primary" style="background:#16a34a; border-color:#16a34a;" title="Aktifkan Kategori">
                        <i class="fa-solid fa-check"></i> Aktifkan
                    </button>
                @endif
            </form>

            <a href="{{ route('master.categories.edit', $category) }}" class="btn btn-secondary">
                <i class="fa-solid fa-pen"></i> Edit
            </a>

            <button type="button" data-confirm-delete="del-cat-show" data-name="{{ $category->name }} ({{ $category->code }})" class="btn btn-danger" title="Hapus Kategori">
                <i class="fa-solid fa-trash"></i> Hapus
            </button>
            <form id="del-cat-show" method="POST" action="{{ route('master.categories.destroy', $category) }}" style="display:none;">
                @csrf
                @method('DELETE')
            </form>

            <a href="{{ route('master.categories.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-3" style="margin-bottom:24px; gap:16px;">
        <div class="card" style="padding:16px; border-left:4px solid var(--primary);">
            <div style="font-size:12px; color:var(--text-secondary); text-transform:uppercase; font-weight:600; margin-bottom:6px;">
                <i class="fa-solid fa-box-archive" style="margin-right:4px;"></i> Total Varian Produk
            </div>
            <div style="font-size:24px; font-weight:800; color:var(--primary);">
                {{ number_format($totalProducts) }} <span style="font-size:14px; font-weight:500; color:var(--text-secondary);">Item</span>
            </div>
        </div>

        <div class="card" style="padding:16px; border-left:4px solid #16a34a;">
            <div style="font-size:12px; color:var(--text-secondary); text-transform:uppercase; font-weight:600; margin-bottom:6px;">
                <i class="fa-solid fa-boxes-stacked" style="margin-right:4px; color:#16a34a;"></i> Total Stok Fisik Gabungan
            </div>
            <div style="font-size:24px; font-weight:800; color:#16a34a;">
                {{ number_format($totalStock) }} <span style="font-size:14px; font-weight:500; color:var(--text-secondary);">Unit Fisik</span>
            </div>
        </div>

        <div class="card" style="padding:16px; border-left:4px solid #2563eb;">
            <div style="font-size:12px; color:var(--text-secondary); text-transform:uppercase; font-weight:600; margin-bottom:6px;">
                <i class="fa-solid fa-circle-info" style="margin-right:4px; color:#2563eb;"></i> Keterangan
            </div>
            <div style="font-size:13px; color:#334155; line-height:1.5;">
                {{ $category->description ?? 'Tidak ada keterangan khusus.' }}
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Produk dalam Kategori Ini -->
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h3><i class="fa-solid fa-boxes-stacked" style="color:var(--primary); margin-right:8px;"></i> Daftar Produk dalam Kategori "{{ $category->name }}"</h3>
            <a href="{{ route('master.products.create') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Tambah Produk Baru
            </a>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Nama Produk</th>
                        <th>Satuan</th>
                        <th style="text-align:right;">Harga Beli (HPP)</th>
                        <th style="text-align:right;">Harga Jual</th>
                        <th style="text-align:right;">Stok Fisik</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($category->products as $p)
                    <tr>
                        <td style="font-weight:600;">
                            <a href="{{ route('master.products.show', $p) }}" style="color:var(--primary); text-decoration:none;">
                                {{ $p->sku }}
                            </a>
                        </td>
                        <td>
                            <a href="{{ route('master.products.show', $p) }}" style="color:inherit; font-weight:600; text-decoration:none;">
                                {{ $p->name }}
                            </a>
                        </td>
                        <td>{{ $p->unit }}</td>
                        <td style="text-align:right;">Rp {{ number_format($p->purchase_price, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:600; color:#16a34a;">Rp {{ number_format($p->sell_price, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:700;">{{ number_format($p->onHandStock()) }} {{ $p->unit }}</td>
                        <td style="text-align:center;">
                            <span class="badge {{ $p->is_active ? 'badge-done' : 'badge-cancelled' }}">
                                {{ $p->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                <a href="{{ route('master.products.show', $p) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="{{ route('master.products.edit', $p) }}" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:36px; color:var(--text-secondary);">
                            <i class="fa-solid fa-box-open" style="font-size:28px; margin-bottom:8px; display:block; opacity:0.4;"></i>
                            Belum ada produk yang dimasukkan ke dalam kategori ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
