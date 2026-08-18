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

    <div class="card">
        <div class="card-header">
            <h3>Daftar Surat Jalan</h3>
            <span style="font-size:13px; color:var(--text-secondary);">{{ $deliveries->total() }} pengiriman</span>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>No. Surat Jalan</th>
                        <th>Ref. SO</th>
                        <th>Customer</th>
                        <th>Gudang Asal</th>
                        <th>Tgl Kirim</th>
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
                            <a href="{{ route('sales.orders.show', $del->salesOrder) }}" style="color:var(--text-primary); text-decoration:none; font-weight:500;">
                                {{ $del->salesOrder->so_number }}
                            </a>
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
                            Belum ada dokumen surat jalan pengiriman
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
