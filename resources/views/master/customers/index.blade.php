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

    <div class="card">
        <div class="card-header">
            <h3>Daftar Customer</h3>
            <span style="font-size:13px; color:var(--text-secondary);">{{ $customers->total() }} customer</span>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Customer</th>
                        <th>Contact Person</th>
                        <th>Telepon</th>
                        <th style="text-align:right;">Credit Limit</th>
                        <th>Payment Term</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $c)
                    <tr>
                        <td style="font-weight:600; color:var(--primary);">{{ $c->code }}</td>
                        <td>
                            <div style="font-weight:500;">{{ $c->name }}</div>
                            @if($c->email)
                            <div style="font-size:12px; color:var(--text-secondary);">{{ $c->email }}</div>
                            @endif
                        </td>
                        <td>{{ $c->contact_person ?? '-' }}</td>
                        <td>{{ $c->phone ?? '-' }}</td>
                        <td style="text-align:right;">
                            @if($c->credit_limit)
                            Rp {{ number_format($c->credit_limit, 0, ',', '.') }}
                            @else
                            <span style="color:var(--text-secondary);">-</span>
                            @endif
                        </td>
                        <td>
                            @if($c->payment_term)
                            <span class="badge badge-confirmed">{{ $c->payment_term }}</span>
                            @else
                            <span style="color:var(--text-secondary);">-</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <span class="badge {{ $c->is_active ? 'badge-done' : 'badge-cancelled' }}">
                                {{ $c->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                <a href="{{ route('master.customers.show', $c) }}" class="btn btn-secondary btn-sm btn-icon" title="Detail">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="{{ route('master.customers.edit', $c) }}" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <button data-confirm-delete="del-cust-{{ $c->id }}" class="btn btn-danger btn-sm btn-icon" title="Hapus">
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
                        <td colspan="8" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            <i class="fa-solid fa-users" style="font-size:32px; opacity:0.3; display:block; margin-bottom:12px;"></i>
                            Belum ada data customer
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
