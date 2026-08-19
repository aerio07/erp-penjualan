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
                        <td style="font-weight:600; color:var(--primary);">{{ $s->code }}</td>
                        <td>
                            <div style="font-weight:500;">{{ $s->name }}</div>
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
                            <span class="badge {{ $s->is_active ? 'badge-done' : 'badge-cancelled' }}">
                                {{ $s->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                <a href="{{ route('master.suppliers.show', $s) }}" class="btn btn-secondary btn-sm btn-icon" title="Detail">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="{{ route('master.suppliers.edit', $s) }}" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <button data-confirm-delete="del-sup-{{ $s->id }}" class="btn btn-danger btn-sm btn-icon" title="Hapus">
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
