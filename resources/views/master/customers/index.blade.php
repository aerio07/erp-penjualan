@extends('layouts.app')
@section('title', 'Customer')
@section('page-title', 'Manajemen Customer')

@section('content')
<div class="animate-in">
    <x-page-header title="Customer" subtitle="Kelola semua data pelanggan">
        <x-slot:actions>
            <a href="{{ route('master.customers.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Tambah Customer
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-list-filter-bar :action="route('master.customers.index')" placeholder="Cari Kode, Nama Customer, CP, Telepon...">
        <select name="sales_person_id" class="form-control filter-select">
            <option value="">Semua Sales PIC</option>
            @foreach($salesUsers as $su)
            <option value="{{ $su->id }}" {{ request('sales_person_id') == $su->id ? 'selected' : '' }}>
                {{ $su->name }}
            </option>
            @endforeach
        </select>
        <select name="tax_type" class="form-control filter-select">
            <option value="">Semua Tipe</option>
            <option value="pkp" {{ request('tax_type') === 'pkp' ? 'selected' : '' }}>PKP</option>
            <option value="non_pkp" {{ request('tax_type') === 'non_pkp' ? 'selected' : '' }}>Non-PKP</option>
        </select>
        <select name="is_active" class="form-control filter-select">
            <option value="">Semua Status</option>
            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
        </select>
    </x-list-filter-bar>

    <x-data-table :paginator="$customers">
        <x-slot:head>
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
        </x-slot:head>

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
            <td>{{ $c->salesPerson->name ?? '-' }}</td>
            <td>{{ $c->phone ?? '-' }}</td>
            <td>
                <span class="badge {{ $c->tax_type === 'pkp' ? 'badge-primary' : 'badge-pending' }}">
                    {{ strtoupper($c->tax_type ?? 'NON_PKP') }}
                </span>
            </td>
            <td style="text-align:right;">Rp {{ number_format($c->credit_limit, 0, ',', '.') }}</td>
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
            <td class="col-actions">
                <div style="display:flex; gap:6px; justify-content:center;">
                    <a href="{{ route('master.customers.show', $c) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                        <i class="fa-solid fa-eye"></i>
                    </a>
                    <a href="{{ route('master.customers.edit', $c) }}" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                        <i class="fa-solid fa-pen"></i>
                    </a>
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
    </x-data-table>
</div>
@endsection
