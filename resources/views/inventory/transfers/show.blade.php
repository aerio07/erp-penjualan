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
            @if($transfer->status === 'draft')
                <form method="POST" action="{{ route('inventory.transfers.ship', $transfer) }}" style="display:inline;">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Konfirmasi pengiriman barang? Stok gudang asal akan dikurangi.')">
                        <i class="fa-solid fa-truck-fast"></i> Kirim Barang (Ship)
                    </button>
                </form>
                <form method="POST" action="{{ route('inventory.transfers.cancel', $transfer) }}" style="display:inline;">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin membatalkan draft transfer ini?')">
                        <i class="fa-solid fa-ban"></i> Batalkan Transfer
                    </button>
                </form>
            @elseif($transfer->status === 'in_transit')
                <form method="POST" action="{{ route('inventory.transfers.receive', $transfer) }}" style="display:inline;">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-success" onclick="return confirm('Konfirmasi penerimaan barang di gudang tujuan? Stok gudang tujuan akan bertambah.')">
                        <i class="fa-solid fa-boxes-packing"></i> Konfirmasi Diterima (Receive)
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
                @if($transfer->status === 'draft')
                    <span class="badge badge-draft" style="font-size:13px; font-weight:700;"><i class="fa-solid fa-pen"></i> DRAFT</span>
                @elseif($transfer->status === 'in_transit')
                    <span class="badge badge-confirmed" style="font-size:13px; font-weight:700; background:#dbeafe; color:#1d4ed8;"><i class="fa-solid fa-truck-fast"></i> IN TRANSIT (DIKIRIM)</span>
                @elseif($transfer->status === 'completed')
                    <span class="badge badge-done" style="font-size:13px; font-weight:700; background:#d1fae5; color:#065f46;"><i class="fa-solid fa-circle-check"></i> COMPLETED (SELESAI)</span>
                @else
                    <span class="badge badge-cancelled" style="font-size:13px; font-weight:700;"><i class="fa-solid fa-circle-xmark"></i> CANCELLED</span>
                @endif
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
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Tanggal Dokumen</div>
                        <div style="font-weight:600;">{{ $transfer->transfer_date->format('d F Y') }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Dibuat Oleh</div>
                        <div style="font-weight:600;">{{ $transfer->user->name ?? '-' }}</div>
                    </div>
                </div>

                @if($transfer->notes)
                <div style="margin-top:16px; padding:12px; background:#f8fafc; border-radius:10px; font-size:13.5px; color:var(--text-secondary);">
                    <i class="fa-solid fa-note-sticky" style="margin-right:6px;"></i> {{ $transfer->notes }}
                </div>
                @endif
            </div>
        </div>

        {{-- Audit Trail & Status Card --}}
        <div class="card">
            <div class="card-header"><h3>Audit Trail Transfer</h3></div>
            <div class="card-body" style="font-size:13px;">
                <div style="margin-bottom:12px;">
                    <div style="color:var(--text-secondary); font-size:11.5px;">1. Pengirim (Ship)</div>
                    @if($transfer->shippedBy)
                        <div style="font-weight:600; color:var(--text-primary);"><i class="fa-solid fa-user-check"></i> {{ $transfer->shippedBy->name }}</div>
                        <div style="font-size:11px; color:var(--text-secondary);">{{ $transfer->shipped_at->format('d F Y, H:i') }}</div>
                    @else
                        <div style="color:var(--text-secondary); font-style:italic;">Belum dikirim</div>
                    @endif
                </div>

                <hr style="border:none; border-top:1px dashed var(--border); margin:12px 0;">

                <div style="margin-bottom:12px;">
                    <div style="color:var(--text-secondary); font-size:11.5px;">2. Penerima (Receive)</div>
                    @if($transfer->receivedBy)
                        <div style="font-weight:600; color:var(--success);"><i class="fa-solid fa-box-open"></i> {{ $transfer->receivedBy->name }}</div>
                        <div style="font-size:11px; color:var(--text-secondary);">{{ $transfer->received_at->format('d F Y, H:i') }}</div>
                    @else
                        <div style="color:var(--text-secondary); font-style:italic;">Belum diterima</div>
                    @endif
                </div>

                <hr style="border:none; border-top:1px dashed var(--border); margin:12px 0;">

                <div>
                    <div style="font-size:11.5px; color:var(--text-secondary);">Total Barang Ditransfer</div>
                    <div style="font-size:22px; font-weight:800; color:var(--primary); margin-top:2px;">
                        {{ number_format($transfer->items->sum('qty')) }} <span style="font-size:13px; font-weight:500;">pcs</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Items Table --}}
    <div class="card">
        <div class="card-header"><h3>Detail Barang Ditransfer</h3></div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Kode SKU</th>
                        <th>Nama Produk</th>
                        <th style="text-align:center;">Jumlah (Qty)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transfer->items as $idx => $item)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td style="font-weight:600; color:var(--primary);">{{ $item->product->sku ?? '-' }}</td>
                        <td style="font-weight:500;">{{ $item->product->name ?? '-' }}</td>
                        <td style="text-align:center; font-weight:700; font-size:14.5px;">
                            {{ number_format($item->qty) }} {{ $item->product->unit ?? 'pcs' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
