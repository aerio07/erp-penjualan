@extends('layouts.app')
@section('title', 'Detail Surat Jalan - ' . $delivery->delivery_number)
@section('page-title', 'Detail Surat Jalan (Pengiriman)')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>{{ $delivery->delivery_number }}</h1>
            <p>Pengiriman untuk SO: <a href="{{ route('sales.orders.show', $delivery->salesOrder) }}" style="color:var(--primary); font-weight:600;">{{ $delivery->salesOrder->so_number }}</a></p>
        </div>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('pdf.delivery', $delivery) }}" class="btn btn-secondary" target="_blank">
                <i class="fa-solid fa-file-pdf"></i> Cetak Surat Jalan PDF
            </a>
            <a href="{{ route('sales.deliveries.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-3" style="margin-bottom:20px;">
        <div class="card" style="grid-column:span 2;">
            <div class="card-header">
                <h3>Informasi Pengiriman</h3>
                <span class="badge badge-done">Dikirim</span>
            </div>
            <div class="card-body">
                <div class="form-row form-row-2">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Customer / Penerima</div>
                        <div style="font-weight:600;">{{ $delivery->recipient_name ?? $delivery->salesOrder->customer->name }}</div>
                        <div style="font-size:13px; color:var(--text-secondary);">{{ $delivery->shipping_address ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Gudang Asal Barang</div>
                        <div style="font-weight:600; color:var(--primary);">
                            <i class="fa-solid fa-warehouse"></i> {{ $delivery->warehouse->name ?? '-' }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Tanggal Pengiriman</div>
                        <div style="font-weight:600;">{{ $delivery->delivery_date ? $delivery->delivery_date->format('d F Y') : '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Dikirim Oleh (User)</div>
                        <div style="font-weight:600;">{{ $delivery->user->name ?? '-' }}</div>
                    </div>
                </div>

                @if($delivery->notes)
                <div style="margin-top:16px; padding:12px; background:#f8fafc; border-radius:10px; font-size:13.5px; color:var(--text-secondary);">
                    <i class="fa-solid fa-note-sticky" style="margin-right:6px;"></i> {{ $delivery->notes }}
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Statistik</h3></div>
            <div class="card-body">
                <div style="font-size:12px; color:var(--text-secondary);">Total Qty Barang Dikirim</div>
                <div style="font-size:24px; font-weight:700; color:var(--primary); margin-top:4px;">
                    {{ number_format($delivery->items->sum('qty_delivered')) }} pcs
                </div>
            </div>
        </div>
    </div>

    {{-- Items Table --}}
    <div class="card">
        <div class="card-header"><h3>Detail Barang Dalam Surat Jalan</h3></div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produk</th>
                        <th style="text-align:center;">Qty Dikirim</th>
                        <th style="text-align:center;">Kondisi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($delivery->items as $idx => $item)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <div style="font-weight:600;">{{ $item->salesOrderItem->product->name ?? '-' }}</div>
                            <div style="font-size:12px; color:var(--text-secondary);">{{ $item->salesOrderItem->product->sku ?? '-' }}</div>
                        </td>
                        <td style="text-align:center; font-weight:600; font-size:14.5px;">
                            {{ $item->qty_delivered }} {{ $item->salesOrderItem->product->unit ?? 'pcs' }}
                        </td>
                        <td style="text-align:center;">
                            <span class="badge badge-done">{{ $item->condition }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
