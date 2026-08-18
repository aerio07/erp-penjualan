@extends('layouts.app')
@section('title', 'Detail Transfer - ' . $transfer->transfer_number)
@section('page-title', 'Detail Transfer Gudang')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>{{ $transfer->transfer_number }}</h1>
            <p>Pemindahan persediaan barang antar gudang</p>
        </div>
        <div style="display:flex; gap:8px;">
            @if($transfer->status === 'in_transit')
            <form method="POST" action="{{ route('inventory.transfers.receive', $transfer) }}" style="display:inline;">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-success" onclick="return confirm('Konfirmasi penerimaan barang di gudang tujuan?')">
                    <i class="fa-solid fa-boxes-packing"></i> Konfirmasi Terima Barang di Gudang Tujuan
                </button>
            </form>
            @endif
            <a href="{{ route('inventory.transfers.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-3" style="margin-bottom:20px;">
        <div class="card" style="grid-column:span 2;">
            <div class="card-header">
                <h3>Informasi Rute Transfer</h3>
                <span class="badge badge-{{ $transfer->status === 'received' ? 'done' : ($transfer->status === 'in_transit' ? 'confirmed' : 'pending') }}">
                    {{ ucfirst(str_replace('_', ' ', $transfer->status)) }}
                </span>
            </div>
            <div class="card-body">
                <div class="form-row form-row-2">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Gudang Asal</div>
                        <div style="font-size:16px; font-weight:700; color:var(--danger);">
                            <i class="fa-solid fa-warehouse"></i> {{ $transfer->fromWarehouse->name ?? '-' }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Gudang Tujuan</div>
                        <div style="font-size:16px; font-weight:700; color:var(--success);">
                            <i class="fa-solid fa-warehouse"></i> {{ $transfer->toWarehouse->name ?? '-' }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Tanggal Kirim</div>
                        <div style="font-weight:600;">{{ $transfer->transfer_date->format('d F Y') }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Tanggal Diterima</div>
                        <div style="font-weight:600;">{{ $transfer->received_date ? $transfer->received_date->format('d F Y') : 'Belum Diterima' }}</div>
                    </div>
                </div>

                @if($transfer->notes)
                <div style="margin-top:16px; padding:12px; background:#f8fafc; border-radius:10px; font-size:13.5px; color:var(--text-secondary);">
                    <i class="fa-solid fa-note-sticky" style="margin-right:6px;"></i> {{ $transfer->notes }}
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Statistik</h3></div>
            <div class="card-body">
                <div style="font-size:12px; color:var(--text-secondary);">Total Qty Ditransfer</div>
                <div style="font-size:24px; font-weight:700; color:var(--primary); margin-top:4px;">
                    {{ number_format($transfer->items->sum('qty_requested')) }} pcs
                </div>
            </div>
        </div>
    </div>

    {{-- Items Table --}}
    <div class="card">
        <div class="card-header"><h3>Detail Barang</h3></div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produk</th>
                        <th style="text-align:center;">Qty Kirim</th>
                        <th style="text-align:center;">Qty Diterima</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transfer->items as $idx => $item)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <div style="font-weight:600;">{{ $item->product->name ?? '-' }}</div>
                            <div style="font-size:12px; color:var(--text-secondary);">{{ $item->product->sku ?? '-' }}</div>
                        </td>
                        <td style="text-align:center; font-weight:600;">
                            {{ $item->qty_requested }} {{ $item->product->unit ?? 'pcs' }}
                        </td>
                        <td style="text-align:center; font-weight:600; color:var(--success);">
                            {{ $item->qty_received }} {{ $item->product->unit ?? 'pcs' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
