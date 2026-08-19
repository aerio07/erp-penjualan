@extends('layouts.app')
@section('title', 'Surat Jalan')
@section('page-title', 'Surat Jalan / Pengiriman')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Surat Jalan / Pengiriman</h1>
            <p>Bukti pengiriman fisik barang ke lokasi customer</p>
        </div>
        <a href="{{ route('sales.deliveries.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Buat Surat Jalan
        </a>
    </div>

    <x-list-filter-bar :action="route('sales.deliveries.index')" placeholder="Cari Surat Jalan, No. SO, Customer, Penerima..." :showDateFilter="true">
        <select name="warehouse_id" class="form-control" style="height:38px; font-size:13px; min-width:160px; border-radius:6px;">
            <option value="">Semua Gudang Asal</option>
            @foreach($warehouses as $wh)
            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
            @endforeach
        </select>

        <select name="condition_status" class="form-control" style="height:38px; font-size:13px; min-width:150px; border-radius:6px;">
            <option value="">Semua Kondisi</option>
            <option value="baik" {{ request('condition_status') === 'baik' ? 'selected' : '' }}>Baik</option>
            <option value="rusak" {{ request('condition_status') === 'rusak' ? 'selected' : '' }}>Rusak</option>
            <option value="partial" {{ request('condition_status') === 'partial' ? 'selected' : '' }}>Partial</option>
        </select>
    </x-list-filter-bar>

    <div class="card">
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <x-sortable-header column="delivery_number" title="No. Surat Jalan" />
                        <th>Ref. SO</th>
                        <th>Customer</th>
                        <th>Gudang Asal</th>
                        <x-sortable-header column="delivery_date" title="Tgl Kirim" />
                        <th>Penerima / Tujuan</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deliveries as $del)
                    <tr>
                        <td>
                            <a href="{{ route('sales.deliveries.show', $del) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                                {{ $del->delivery_number }}
                            </a>
                        </td>
                        <td>
                            @if($del->salesOrder)
                            <a href="{{ route('sales.orders.show', $del->salesOrder) }}" style="color:var(--text-primary); text-decoration:none; font-weight:500;">
                                {{ $del->salesOrder->so_number }}
                            </a>
                            @else
                            -
                            @endif
                        </td>
                        <td>{{ $del->salesOrder->customer->name ?? '-' }}</td>
                        <td>
                            <span class="badge badge-confirmed">
                                <i class="fa-solid fa-warehouse"></i> {{ $del->warehouse->name ?? '-' }}
                            </span>
                        </td>
                        <td>{{ $del->delivery_date ? $del->delivery_date->format('d/m/Y') : '-' }}</td>
                        <td>{{ $del->recipient_name ?? '-' }}</td>
                        <td style="text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                <a href="{{ route('sales.deliveries.show', $del) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="{{ route('pdf.delivery', $del) }}" class="btn btn-secondary btn-sm btn-icon" title="Cetak Surat Jalan" target="_blank">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            <i class="fa-solid fa-truck-fast" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                            Belum ada dokumen surat jalan pengiriman yang sesuai filter
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($deliveries->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border);">
            {{ $deliveries->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
