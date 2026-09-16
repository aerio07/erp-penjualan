@extends('layouts.app')
@section('title', 'Surat Jalan')
@section('page-title', 'Surat Jalan / Pengiriman')

@section('content')
<div class="animate-in">
    <x-page-header
        title="Surat Jalan / Pengiriman"
        subtitle="Bukti pengiriman fisik barang ke lokasi customer">
        <x-slot:actions>
            <a href="{{ route('sales.deliveries.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Buat Surat Jalan
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-list-filter-bar :action="route('sales.deliveries.index')" placeholder="Cari Surat Jalan, No. SO, Customer, Penerima..." :showDateFilter="true">
        <select name="status" class="form-control filter-select">
            <option value="">Semua Status Kirim</option>
            <option value="in_transit" {{ request('status') === 'in_transit' ? 'selected' : '' }}>Dalam Pengiriman</option>
            <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Diterima (Delivered)</option>
        </select>

        <select name="warehouse_id" class="form-control filter-select">
            <option value="">Semua Gudang Asal</option>
            @foreach($warehouses as $wh)
            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
            @endforeach
        </select>

        <select name="condition_status" class="form-control filter-select">
            <option value="">Semua Kondisi</option>
            <option value="baik" {{ request('condition_status') === 'baik' ? 'selected' : '' }}>Baik</option>
            <option value="rusak" {{ request('condition_status') === 'rusak' ? 'selected' : '' }}>Rusak</option>
            <option value="partial" {{ request('condition_status') === 'partial' ? 'selected' : '' }}>Partial</option>
        </select>
    </x-list-filter-bar>

    <x-data-table :paginator="$deliveries">
        <x-slot:head>
            <tr>
                <x-sortable-header column="delivery_number" title="No. Surat Jalan" />
                <th>Ref. SO</th>
                <th>Customer</th>
                <th>Gudang Asal</th>
                <x-sortable-header column="delivery_date" title="Tgl Kirim" :isDefault="true" />
                <th>Status Pengiriman</th>
                <th>Status Faktur</th>
                <th class="text-center col-actions">Aksi</th>
            </tr>
        </x-slot:head>

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
            <td>
                @if($del->status === 'delivered')
                    <span class="badge badge-success" title="Diterima: {{ $del->received_date ? $del->received_date->format('d/m/Y') : '-' }}">
                        <i class="fa-solid fa-circle-check"></i> Diterima
                    </span>
                @elseif($del->status === 'in_transit')
                    <span class="badge badge-warning">
                        <i class="fa-solid fa-truck-fast"></i> Dalam Kirim
                    </span>
                @else
                    <span class="badge">{{ $del->status_label }}</span>
                @endif
            </td>
            <td>
                @if($del->is_invoiced)
                    <a href="{{ route('sales.invoices.show', $del->sales_invoice_id) }}" class="badge badge-info" style="text-decoration:none;" title="Lihat Invoice Penjualan">
                        <i class="fa-solid fa-file-invoice"></i> Invoiced
                    </a>
                @elseif($del->status === 'delivered')
                    <a href="{{ route('sales.invoices.create', ['so_id' => $del->sales_order_id, 'delivery_id' => $del->id]) }}" class="badge badge-pending" style="text-decoration:none;" title="Barang sudah sampai, klik untuk terbitkan invoice">
                        <i class="fa-solid fa-plus"></i> Siap Ditagih
                    </a>
                @else
                    <span class="badge" style="background:#f1f5f9; color:#64748b;" title="Barang belum sampai, invoice tidak dapat diterbitkan terlebih dahulu">
                        <i class="fa-solid fa-hourglass-start"></i> Belum Diterima
                    </span>
                @endif
            </td>
            <td class="col-actions text-center">
                <div style="display:flex; gap:6px; justify-content:center;">
                    <a href="{{ route('sales.deliveries.show', $del) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                        <i class="fa-solid fa-eye"></i>
                    </a>
                    @if($del->status === 'in_transit')
                    <a href="{{ route('sales.deliveries.show', $del) }}" class="btn btn-primary btn-sm btn-icon" title="Konfirmasi Penerimaan Barang">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </a>
                    @endif
                    @if(in_array($del->status, ['in_transit', 'delivered']))
                    <a href="{{ route('pdf.delivery', $del) }}" class="btn btn-secondary btn-sm btn-icon" title="Cetak Surat Jalan" target="_blank">
                        <i class="fa-solid fa-file-pdf"></i>
                    </a>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" style="text-align:center; padding:48px; color:var(--text-secondary);">
                <i class="fa-solid fa-truck-fast" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                Belum ada dokumen surat jalan pengiriman yang sesuai filter
            </td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection
