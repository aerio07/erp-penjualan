@extends('layouts.app')
@section('title', 'Retur Penjualan')
@section('page-title', 'Retur Penjualan Customer')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Retur Penjualan Customer</h1>
            <p>Pengembalian barang dari customer ke gudang</p>
        </div>
        <a href="{{ route('sales.returns.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Buat Retur Penjualan
        </a>
    </div>

    <x-list-filter-bar :action="route('sales.returns.index')" placeholder="Cari No. Retur, Ref. SJ, Customer, Alasan..." :showDateFilter="true">
        <select name="customer_id" class="form-control" style="height:38px; font-size:13px; min-width:170px; border-radius:6px;">
            <option value="">Semua Customer</option>
            @foreach($customers as $cust)
            <option value="{{ $cust->id }}" {{ request('customer_id') == $cust->id ? 'selected' : '' }}>{{ $cust->name }}</option>
            @endforeach
        </select>

        <select name="status" class="form-control" style="height:38px; font-size:13px; min-width:150px; border-radius:6px;">
            <option value="">Semua Status</option>
            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
            <option value="received" {{ request('status') === 'received' ? 'selected' : '' }}>Diterima Gudang (Received)</option>
            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai (Completed)</option>
        </select>
    </x-list-filter-bar>

    <div class="card">
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <x-sortable-header column="return_number" title="No. Retur" />
                        <th>Ref. Surat Jalan</th>
                        <th>Customer</th>
                        <x-sortable-header column="return_date" title="Tgl Retur" />
                        <th>Alasan Utama</th>
                        <x-sortable-header column="status" title="Status" align="center" />
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $r)
                    <tr>
                        <td>
                            <a href="{{ route('sales.returns.show', $r) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                                {{ $r->return_number }}
                            </a>
                        </td>
                        <td>{{ $r->delivery->delivery_number ?? '-' }}</td>
                        <td>{{ $r->customer->name ?? '-' }}</td>
                        <td>{{ $r->return_date ? $r->return_date->format('d/m/Y') : '-' }}</td>
                        <td>{{ Str::limit($r->reason ?? '-', 40) }}</td>
                        <td style="text-align:center;">
                            <span class="badge badge-{{ $r->status === 'completed' ? 'done' : ($r->status === 'received' ? 'confirmed' : 'pending') }}">
                                {{ ucfirst($r->status) }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <a href="{{ route('sales.returns.show', $r) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            <i class="fa-solid fa-rotate-left" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                            Belum ada catatan retur penjualan customer yang sesuai filter
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($returns->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border);">
            {{ $returns->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
