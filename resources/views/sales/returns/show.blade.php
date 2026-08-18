@extends('layouts.app')
@section('title', 'Detail Retur - ' . $return->return_number)
@section('page-title', 'Detail Retur Penjualan')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>{{ $return->return_number }}</h1>
            <p>Customer: <strong>{{ $return->customer->name ?? '-' }}</strong> · Ref Surat Jalan: {{ $return->delivery->delivery_number ?? '-' }}</p>
        </div>
        <div style="display:flex; gap:8px;">
            @if($return->status === 'draft')
            <form method="POST" action="{{ route('sales.returns.receive', $return) }}" style="display:inline;">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-primary" onclick="return confirm('Konfirmasi penerimaan fisik barang retur di gudang dan tambah stok?')">
                    <i class="fa-solid fa-boxes-packing"></i> Konfirmasi Terima Fisik di Gudang (Return In)
                </button>
            </form>
            @elseif($return->status === 'received')
            <form method="POST" action="{{ route('sales.returns.complete', $return) }}" style="display:inline;">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-success" onclick="return confirm('Selesaikan proses retur penjualan ini?')">
                    <i class="fa-solid fa-circle-check"></i> Selesaikan Retur (Completed)
                </button>
            </form>
            @endif
            <a href="{{ route('sales.returns.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-3" style="margin-bottom:20px;">
        <div class="card" style="grid-column:span 2;">
            <div class="card-header">
                <h3>Informasi Retur</h3>
                <span class="badge badge-{{ $return->status === 'completed' ? 'done' : ($return->status === 'received' ? 'confirmed' : 'pending') }}">
                    {{ ucfirst($return->status) }}
                </span>
            </div>
            <div class="card-body">
                <div class="form-row form-row-2">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Gudang Penerima Retur</div>
                        <div style="font-weight:600;"><i class="fa-solid fa-warehouse"></i> {{ $return->delivery->warehouse->name ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Tanggal Retur</div>
                        <div style="font-weight:600;">{{ $return->return_date ? $return->return_date->format('d F Y') : '-' }}</div>
                    </div>
                </div>

                @if($return->reason)
                <div style="margin-top:16px; padding:12px; background:#f8fafc; border-radius:10px; font-size:14px;">
                    <strong style="color:var(--text-secondary);">Alasan Retur:</strong> {{ $return->reason }}
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Statistik</h3></div>
            <div class="card-body">
                <div style="font-size:12px; color:var(--text-secondary);">Total Item Diterima Kembali</div>
                <div style="font-size:24px; font-weight:700; color:var(--success); margin-top:4px;">
                    {{ number_format($return->items->sum('qty')) }} pcs
                </div>
            </div>
        </div>
    </div>

    {{-- Items Table --}}
    <div class="card">
        <div class="card-header"><h3>Item Dikembalikan Customer</h3></div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produk</th>
                        <th style="text-align:center;">Qty Retur</th>
                        <th style="text-align:center;">Kondisi</th>
                        <th>Alasan Item</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($return->items as $idx => $item)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <div style="font-weight:600;">{{ $item->product->name ?? '-' }}</div>
                            <div style="font-size:12px; color:var(--text-secondary);">{{ $item->product->sku ?? '-' }}</div>
                        </td>
                        <td style="text-align:center; font-weight:600; color:var(--success);">
                            {{ $item->qty }} {{ $item->product->unit ?? 'pcs' }}
                        </td>
                        <td style="text-align:center;">
                            @if($item->condition === 'baik')
                                <span class="badge badge-done" title="Masuk ke Stok Siap Jual">
                                    <i class="fa-solid fa-check"></i> Bagus (Siap Jual)
                                </span>
                            @else
                                <span class="badge badge-cancelled" title="Masuk ke Stok Karantina / Rusak (Tidak Dijual)">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Rusak (Karantina)
                                </span>
                            @endif
                        </td>
                        <td>{{ $item->reason ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
