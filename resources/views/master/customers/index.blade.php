@extends('layouts.app')
@section('title', 'Customer')
@section('page-title', 'Manajemen Customer')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Customer</h1>
            <p>Kelola semua data pelanggan</p>
        </div>
        <a href="{{ route('master.customers.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Tambah Customer
        </a>
    </div>

    <x-list-filter-bar :action="route('master.customers.index')" placeholder="Cari Kode, Nama Customer, CP, Telepon...">
        <select name="sales_person_id" class="form-control" style="height:38px; font-size:13px; min-width:160px; border-radius:6px;">
            <option value="">Semua Sales PIC</option>
            @foreach($salesUsers as $su)
            <option value="{{ $su->id }}" {{ request('sales_person_id') == $su->id ? 'selected' : '' }}>
                {{ $su->name }}
            </option>
            @endforeach
        </select>
        <select name="tax_type" class="form-control" style="height:38px; font-size:13px; min-width:140px; border-radius:6px;">
            <option value="">Semua Tipe</option>
            <option value="pkp" {{ request('tax_type') === 'pkp' ? 'selected' : '' }}>PKP</option>
            <option value="non_pkp" {{ request('tax_type') === 'non_pkp' ? 'selected' : '' }}>Non-PKP</option>
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
                        <x-sortable-header column="code" title="Kode" />
                        <x-sortable-header column="name" title="Nama Customer" />
                        <x-sortable-header column="contact_person" title="Contact Person" />
                        <th>Sales PIC</th>
                        <th>Telepon</th>
                        <x-sortable-header column="tax_type" title="Tipe" />
                        <x-sortable-header column="credit_limit" title="Credit Limit" align="right" />
                        <x-sortable-header column="payment_term" title="Payment Term" />
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $c)
                    <tr>
                        <td style="font-weight:600;">
                            <a href="{{ route('master.customers.show', $c) }}" style="color:var(--primary); text-decoration:none;" title="Lihat Detail Customer">
                                {{ $c->code }}
                            </a>
                        </td>
                        <td>
                            <div style="font-weight:600;">
                                <a href="{{ route('master.customers.show', $c) }}" style="color:inherit; text-decoration:none;" title="Lihat Detail Customer">
                                    {{ $c->name }}
                                </a>
                            </div>
                            @if($c->email)
                            <div style="font-size:12px; color:var(--text-secondary);">{{ $c->email }}</div>
                            @endif
                        </td>
                        <td>{{ $c->contact_person ?? '-' }}</td>
                        <td>
                            @if($c->salesPerson)
                                <div style="font-weight:600; font-size:12.5px; color:#1e293b;">
                                    <i class="fa-solid fa-user-tie" style="color:var(--primary); margin-right:4px;"></i>
                                    {{ $c->salesPerson->name }}
                                </div>
                            @else
                                <span style="color:var(--text-secondary); font-size:12px; font-style:italic;">-</span>
                            @endif
                        </td>
                        <td>{{ $c->phone ?? '-' }}</td>
                        <td>
                            <x-status-badge :status="$c->tax_type ?? 'non_pkp'" />
                            @if($c->tax_type === 'pkp' && $c->npwp)
                                <div style="font-size:10.5px; color:#64748b; margin-top:3px; font-family:monospace;">NPWP: {{ $c->npwp }}</div>
                            @elseif($c->nik)
                                <div style="font-size:10.5px; color:#64748b; margin-top:3px; font-family:monospace;">NIK: {{ $c->nik }}</div>
                            @endif
                        </td>
                        <td style="text-align:right; font-weight:600;">
                            Rp {{ number_format($c->credit_limit ?? 0, 0, ',', '.') }}
                        </td>
                        <td>
                            @if($c->payment_term)
                            <span class="badge badge-confirmed">{{ $c->payment_term }}</span>
                            @else
                            <span style="color:var(--text-secondary);">-</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <form method="POST" action="{{ route('master.customers.toggle-status', $c) }}" style="display:inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" style="background:none; border:none; padding:0; cursor:pointer;" title="Klik untuk {{ $c->is_active ? 'menonaktifkan' : 'mengaktifkan' }} customer ini">
                                    <span class="badge {{ $c->is_active ? 'badge-done' : 'badge-cancelled' }}" style="cursor:pointer; transition:opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                                        <i class="fa-solid {{ $c->is_active ? 'fa-check' : 'fa-xmark' }}" style="font-size:10px; margin-right:3px;"></i>
                                        {{ $c->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </button>
                            </form>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                <a href="{{ route('master.customers.show', $c) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="{{ route('master.customers.edit', $c) }}" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form method="POST" action="{{ route('master.customers.toggle-status', $c) }}" style="display:inline;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-icon {{ $c->is_active ? 'btn-secondary' : 'btn-primary' }}" style="{{ $c->is_active ? 'color:#dc2626;' : 'background:#16a34a; border-color:#16a34a;' }}" title="{{ $c->is_active ? 'Nonaktifkan Customer' : 'Aktifkan Customer' }}">
                                        <i class="fa-solid {{ $c->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                                    </button>
                                </form>
                                <button type="button" data-confirm-delete="del-cust-{{ $c->id }}" data-name="{{ $c->name }} ({{ $c->code }})" class="btn btn-danger btn-sm btn-icon" title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <form id="del-cust-{{ $c->id }}" method="POST" action="{{ route('master.customers.destroy', $c) }}" style="display:none;">
                                    @csrf @method('DELETE')
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            <i class="fa-solid fa-users" style="font-size:32px; opacity:0.3; display:block; margin-bottom:12px;"></i>
                            Belum ada data customer yang sesuai filter
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($customers->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border);">
            {{ $customers->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
