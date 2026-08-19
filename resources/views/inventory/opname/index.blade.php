@extends('layouts.app')
@section('title', 'Stock Opname')
@section('page-title', 'Stock Opname')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Stock Opname</h1>
            <p>Pemeriksaan dan penyesuaian fisik persediaan di gudang</p>
        </div>
        <a href="{{ route('inventory.opname.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Mulai Stock Opname
        </a>
    </div>

    <x-list-filter-bar :action="route('inventory.opname.index')" placeholder="Cari No. Opname, Gudang, Catatan..." :showDateFilter="true">
        <select name="warehouse_id" class="form-control" style="height:38px; font-size:13px; min-width:160px; border-radius:6px;">
            <option value="">Semua Gudang</option>
            @foreach($warehouses as $wh)
            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
            @endforeach
        </select>

        <select name="status" class="form-control" style="height:38px; font-size:13px; min-width:150px; border-radius:6px;">
            <option value="">Semua Status</option>
            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
            <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed (Selesai)</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
        </select>
    </x-list-filter-bar>

    <div class="card">
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <x-sortable-header column="opname_number" title="No. Opname" />
                        <th>Gudang</th>
                        <x-sortable-header column="opname_date" title="Tgl Opname" />
                        <th>Petugas</th>
                        <x-sortable-header column="status" title="Status" align="center" />
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($opnames as $op)
                    <tr>
                        <td>
                            <a href="{{ route('inventory.opname.show', $op) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                                {{ $op->opname_number }}
                            </a>
                        </td>
                        <td>
                            <span class="badge badge-confirmed">
                                <i class="fa-solid fa-warehouse"></i> {{ $op->warehouse->name ?? '-' }}
                            </span>
                        </td>
                        <td>{{ $op->opname_date ? $op->opname_date->format('d/m/Y') : '-' }}</td>
                        <td>{{ $op->user->name ?? '-' }}</td>
                        <td style="text-align:center;">
                            <span class="badge badge-{{ $op->status === 'completed' ? 'done' : 'pending' }}">
                                {{ ucfirst(str_replace('_', ' ', $op->status)) }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <a href="{{ route('inventory.opname.show', $op) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            <i class="fa-solid fa-clipboard-check" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                            Belum ada dokumen Stock Opname yang sesuai filter
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($opnames->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border);">
            {{ $opnames->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
