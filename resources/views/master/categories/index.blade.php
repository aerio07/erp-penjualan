@extends('layouts.app')
@section('title', 'Kategori Produk')
@section('page-title', 'Kategori Produk')

@section('content')
<div class="animate-in">
    <x-page-header title="Kategori Produk" subtitle="Kelola master kategori produk dan pengelompokan item">
        <x-slot:actions>
            <a href="{{ route('master.categories.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Tambah Kategori
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-list-filter-bar :action="route('master.categories.index')" placeholder="Cari Kode, Nama Kategori, Deskripsi...">
        <select name="is_active" class="form-control filter-select">
            <option value="">Semua Status</option>
            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
        </select>
    </x-list-filter-bar>

    <x-data-table :paginator="$categories">
        <x-slot:head>
            <tr>
                <x-sortable-header column="code" title="Kode Kategori" />
                <x-sortable-header column="name" title="Nama Kategori" />
                <th>Deskripsi</th>
                <x-sortable-header column="products_count" title="Total Produk" align="center" />
                <th style="text-align:center;">Status</th>
                <th style="text-align:center;">Aksi</th>
            </tr>
        </x-slot:head>

        @forelse($categories as $category)
        <tr>
            <td style="font-weight:600;">
                <a href="{{ route('master.categories.show', $category) }}" style="color:var(--primary); text-decoration:none;" title="Lihat Detail Kategori">
                    {{ $category->code }}
                </a>
            </td>
            <td>
                <a href="{{ route('master.categories.show', $category) }}" style="color:inherit; font-weight:600; text-decoration:none;" title="Lihat Detail Kategori">
                    {{ $category->name }}
                </a>
            </td>
            <td class="col-notes" title="{{ $category->description ?? '' }}">
                {{ $category->description ?? '-' }}
            </td>
            <td style="text-align:center;">
                <span class="badge" style="background:#e0e7ff; color:#3730a3; font-weight:700; font-size:12px;">
                    {{ $category->products_count }} Produk
                </span>
            </td>
            <td style="text-align:center;">
                <form method="POST" action="{{ route('master.categories.toggle-status', $category) }}" style="display:inline;">
                    @csrf
                    @method('PATCH')
                    <button type="submit" style="background:none; border:none; padding:0; cursor:pointer;" title="Klik untuk {{ $category->is_active ? 'menonaktifkan' : 'mengaktifkan' }} kategori ini">
                        <span class="badge {{ $category->is_active ? 'badge-done' : 'badge-cancelled' }}" style="cursor:pointer; transition:opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                            <i class="fa-solid {{ $category->is_active ? 'fa-check' : 'fa-xmark' }}" style="font-size:10px; margin-right:3px;"></i>
                            {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </button>
                </form>
            </td>
            <td class="col-actions">
                <div style="display:flex; gap:6px; justify-content:center;">
                    <a href="{{ route('master.categories.show', $category) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                        <i class="fa-solid fa-eye"></i>
                    </a>
                    <a href="{{ route('master.categories.edit', $category) }}" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                        <i class="fa-solid fa-pen"></i>
                    </a>
                    <button type="button" data-confirm-delete="del-cat-{{ $category->id }}" data-name="{{ $category->name }} ({{ $category->code }})" class="btn btn-danger btn-sm btn-icon" title="Hapus Kategori">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                    <form id="del-cat-{{ $category->id }}" method="POST" action="{{ route('master.categories.destroy', $category) }}" style="display:none;">
                        @csrf @method('DELETE')
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align:center; padding:48px; color:var(--text-secondary);">
                <i class="fa-solid fa-tags" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                Belum ada kategori produk yang sesuai filter
            </td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection
