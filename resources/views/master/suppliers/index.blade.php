@extends('layouts.app')
@section('title', 'Supplier')
@section('page-title', 'Manajemen Supplier')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Supplier</h1>
            <p>Kelola semua data pemasok barang</p>
        </div>
        <a href="{{ route('master.suppliers.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Tambah Supplier
        </a>
    </div>

    <x-list-filter-bar :action="route('master.suppliers.index')" placeholder="Cari Kode, Nama Supplier, CP, Telepon...">
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
                        <x-sortable-header column="code" title="Kode" />
                        <x-sortable-header column="name" title="Nama Supplier" />
                        <x-sortable-header column="contact_person" title="Contact Person" />
                        <th>Telepon</th>
                        <x-sortable-header column="payment_term" title="Payment Term" />
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $s)
                    <tr>
                        <td style="font-weight:600;">
                            <a href="{{ route('master.suppliers.show', $s) }}" style="color:var(--primary); text-decoration:none;" title="Lihat Detail Supplier">
                                {{ $s->code }}
                            </a>
                        </td>
                        <td>
                            <div style="font-weight:600;">
                                <a href="{{ route('master.suppliers.show', $s) }}" style="color:inherit; text-decoration:none;" title="Lihat Detail Supplier">
                                    {{ $s->name }}
                                </a>
                            </div>
                            @if($s->email)
                            <div style="font-size:12px; color:var(--text-secondary);">{{ $s->email }}</div>
                            @endif
                        </td>
                        <td>{{ $s->contact_person ?? '-' }}</td>
                        <td>{{ $s->phone ?? '-' }}</td>
                        <td>
                            @if($s->payment_term)
                            <span class="badge badge-confirmed">{{ $s->payment_term }}</span>
                            @else
                            <span style="color:var(--text-secondary);">-</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <form method="POST" action="{{ route('master.suppliers.toggle-status', $s) }}" style="display:inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" style="background:none; border:none; padding:0; cursor:pointer;" title="Klik untuk {{ $s->is_active ? 'menonaktifkan' : 'mengaktifkan' }} supplier ini">
                                    <span class="badge {{ $s->is_active ? 'badge-done' : 'badge-cancelled' }}" style="cursor:pointer; transition:opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                                        <i class="fa-solid {{ $s->is_active ? 'fa-check' : 'fa-xmark' }}" style="font-size:10px; margin-right:3px;"></i>
                                        {{ $s->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </button>
                            </form>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                <a href="{{ route('master.suppliers.show', $s) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="{{ route('master.suppliers.edit', $s) }}" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form method="POST" action="{{ route('master.suppliers.toggle-status', $s) }}" style="display:inline;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-icon {{ $s->is_active ? 'btn-secondary' : 'btn-primary' }}" style="{{ $s->is_active ? 'color:#dc2626;' : 'background:#16a34a; border-color:#16a34a;' }}" title="{{ $s->is_active ? 'Nonaktifkan Supplier' : 'Aktifkan Supplier' }}">
                                        <i class="fa-solid {{ $s->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                                    </button>
                                </form>
                                <button type="button" data-confirm-delete="del-sup-{{ $s->id }}" data-name="{{ $s->name }} ({{ $s->code }})" class="btn btn-danger btn-sm btn-icon" title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <form id="del-sup-{{ $s->id }}" method="POST" action="{{ route('master.suppliers.destroy', $s) }}" style="display:none;">
                                    @csrf @method('DELETE')
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            <i class="fa-solid fa-truck" style="font-size:32px; opacity:0.3; display:block; margin-bottom:12px;"></i>
                            Belum ada data supplier yang sesuai filter
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($suppliers->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border);">
            {{ $suppliers->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
