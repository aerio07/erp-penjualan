@extends('layouts.app')
@section('title', 'Transfer Gudang')
@section('page-title', 'Transfer Antar Gudang')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Transfer Antar Gudang</h1>
            <p>Pindahkan stok produk antar lokasi gudang perusahaan</p>
        </div>
        <a href="{{ route('inventory.transfers.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Buat Transfer Baru
        </a>
    </div>

    {{-- Filter Form --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-body" style="padding:16px;">
            <form method="GET" action="{{ route('inventory.transfers.index') }}" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
                <div style="flex:1; min-width:160px;">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">Semua Status</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="in_transit" {{ request('status') == 'in_transit' ? 'selected' : '' }}>In Transit (Dikirim)</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed (Selesai)</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled (Batal)</option>
                    </select>
                </div>

                <div style="flex:1; min-width:180px;">
                    <label class="form-label">Gudang Asal</label>
                    <select name="from_warehouse_id" class="form-control">
                        <option value="">Semua Gudang Asal</option>
                        @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ request('from_warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }} ({{ $wh->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div style="flex:1; min-width:180px;">
                    <label class="form-label">Gudang Tujuan</label>
                    <select name="to_warehouse_id" class="form-control">
                        <option value="">Semua Gudang Tujuan</option>
                        @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ request('to_warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }} ({{ $wh->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div style="width:140px;">
                    <label class="form-label">Dari Tgl</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                </div>

                <div style="width:140px;">
                    <label class="form-label">Sampai Tgl</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                </div>

                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
                    @if(request()->hasAny(['status', 'from_warehouse_id', 'to_warehouse_id', 'date_from', 'date_to']))
                    <a href="{{ route('inventory.transfers.index') }}" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i> Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Daftar Transfer Gudang</h3>
            <span style="font-size:13px; color:var(--text-secondary);">Total {{ $transfers->total() }} Dokumen</span>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>No. Transfer</th>
                        <th>Gudang Asal</th>
                        <th>Gudang Tujuan</th>
                        <th>Tgl Dokumen</th>
                        <th>Dikirim Oleh / Tgl</th>
                        <th>Diterima Oleh / Tgl</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $trf)
                    <tr>
                        <td>
                            <a href="{{ route('inventory.transfers.show', $trf) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                                {{ $trf->transfer_number }}
                            </a>
                        </td>
                        <td style="font-weight:500;">
                            <span style="color:var(--danger);"><i class="fa-solid fa-warehouse"></i> {{ $trf->fromWarehouse->name ?? '-' }}</span>
                        </td>
                        <td style="font-weight:500;">
                            <span style="color:var(--success);"><i class="fa-solid fa-warehouse"></i> {{ $trf->toWarehouse->name ?? '-' }}</span>
                        </td>
                        <td>{{ $trf->transfer_date->format('d/m/Y') }}</td>
                        <td>
                            @if($trf->shippedBy)
                                <div>{{ $trf->shippedBy->name }}</div>
                                <div style="font-size:11px; color:var(--text-secondary);">{{ $trf->shipped_at ? $trf->shipped_at->format('d/m/Y H:i') : '-' }}</div>
                            @else
                                <span style="color:var(--text-secondary);">-</span>
                            @endif
                        </td>
                        <td>
                            @if($trf->receivedBy)
                                <div>{{ $trf->receivedBy->name }}</div>
                                <div style="font-size:11px; color:var(--text-secondary);">{{ $trf->received_at ? $trf->received_at->format('d/m/Y H:i') : '-' }}</div>
                            @else
                                <span style="color:var(--text-secondary);">-</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if($trf->status === 'draft')
                                <span class="badge badge-draft"><i class="fa-solid fa-pen"></i> Draft</span>
                            @elseif($trf->status === 'in_transit')
                                <span class="badge badge-confirmed" style="background:#dbeafe; color:#1d4ed8;"><i class="fa-solid fa-truck-fast"></i> In Transit</span>
                            @elseif($trf->status === 'completed')
                                <span class="badge badge-done" style="background:#d1fae5; color:#065f46;"><i class="fa-solid fa-circle-check"></i> Completed</span>
                            @else
                                <span class="badge badge-cancelled"><i class="fa-solid fa-circle-xmark"></i> Cancelled</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <a href="{{ route('inventory.transfers.show', $trf) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            <i class="fa-solid fa-arrow-right-arrow-left" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                            Belum ada riwayat transfer barang antar gudang
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transfers->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border);">
            {{ $transfers->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
