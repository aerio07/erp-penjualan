@extends('layouts.app')
@section('title', 'Kartu Stok')
@section('page-title', 'Kartu Stok Barang')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Kartu Stok</h1>
            <p>Rincian mutasi masuk/keluar dan saldo berjalan per produk dan gudang</p>
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-body" style="padding:16px;">
            <form method="GET" action="{{ route('inventory.stock-card') }}" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
                <div style="flex:1; min-width:200px;">
                    <label class="form-label">Produk <span style="color:var(--danger);">*</span></label>
                    <select name="product_id" class="form-control" required>
                        <option value="">-- Pilih Produk --</option>
                        @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ $productId == $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->sku }})</option>
                        @endforeach
                    </select>
                </div>

                <div style="flex:1; min-width:200px;">
                    <label class="form-label">Gudang <span style="color:var(--danger);">*</span></label>
                    <select name="warehouse_id" class="form-control" required>
                        <option value="">-- Pilih Gudang --</option>
                        @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ $warehouseId == $wh->id ? 'selected' : '' }}>{{ $wh->name }} ({{ $wh->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div style="width:150px;">
                    <label class="form-label">Dari Tgl</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
                </div>

                <div style="width:150px;">
                    <label class="form-label">Sampai Tgl</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
                </div>

                <div>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    @if($productId && $warehouseId)
    <div class="card">
        <div class="card-header">
            <h3>Riwayat Mutasi Stok</h3>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Tgl Mutasi</th>
                        <th>Jenis Mutasi</th>
                        <th>Keterangan / Referensi</th>
                        <th style="text-align:right;">Masuk</th>
                        <th style="text-align:right;">Keluar</th>
                        <th style="text-align:right;">Saldo Stok</th>
                        <th>Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $row)
                    @php
                        $m = $row['movement'];
                        $isDamaged = ($m->type === 'return_in_damaged');
                        $isOpeningBalance = ($m->type === 'opening_balance');
                        $isAdjustmentSurplus = ($m->type === 'adjustment' && $m->quantity > 0);
                        $isAdjustmentDeficit = ($m->type === 'adjustment' && $m->quantity < 0);
                        $isIncoming = in_array($m->type, ['in', 'return_in', 'transfer_in', 'opening_balance']) || $isAdjustmentSurplus;
                        $isOutgoing = in_array($m->type, ['out', 'return_out', 'transfer_out']) || $isAdjustmentDeficit;
                    @endphp
                    <tr>
                        <td>{{ $m->movement_date->format('d/m/Y') }}</td>
                        <td>
                            @if($isDamaged)
                                <span class="badge badge-warning" style="background:#fef3c7; color:#92400e; font-weight:700;">
                                    <i class="fa-solid fa-triangle-exclamation"></i> RETUR KARANTINA
                                </span>
                            @elseif($isOpeningBalance)
                                <span class="badge badge-done" style="background:#e0e7ff; color:#3730a3; font-weight:700;">
                                    <i class="fa-solid fa-flag"></i> SALDO AWAL
                                </span>
                            @elseif($isAdjustmentSurplus)
                                <span class="badge badge-done" style="background:#dcfce7; color:#15803d; font-weight:700;">
                                    <i class="fa-solid fa-arrow-trend-up"></i> OPNAME (SURPLUS)
                                </span>
                            @elseif($isAdjustmentDeficit)
                                <span class="badge badge-cancelled" style="background:#fee2e2; color:#b91c1c; font-weight:700;">
                                    <i class="fa-solid fa-arrow-trend-down"></i> OPNAME (DEFISIT)
                                </span>
                            @elseif($isIncoming)
                                <span class="badge badge-done">
                                    {{ strtoupper(str_replace('_', ' ', $m->type)) }}
                                </span>
                            @else
                                <span class="badge badge-cancelled">
                                    {{ strtoupper(str_replace('_', ' ', $m->type)) }}
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($m->source_url)
                                <a href="{{ $m->source_url }}" style="color:var(--primary); font-weight:600; text-decoration:underline;" title="Buka Dokumen Sumber">
                                    {{ $m->notes ?? 'Dokumen Sumber' }} <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:11px;"></i>
                                </a>
                            @else
                                {{ $m->notes ?? '-' }}
                            @endif
                        </td>
                        <td style="text-align:right; font-weight:600; {{ $isDamaged ? 'color:#92400e;' : 'color:var(--success);' }}">
                            {{ $isIncoming ? '+'.number_format(abs($m->quantity)) : ($isDamaged ? '+'.number_format(abs($m->quantity)).' (Karantina)' : '-') }}
                        </td>
                        <td style="text-align:right; color:var(--danger); font-weight:600;">
                            {{ $isOutgoing ? '-'.number_format(abs($m->quantity)) : '-' }}
                        </td>
                        <td style="text-align:right; font-weight:700; font-size:14.5px;">
                            {{ number_format($row['running_qty']) }}
                        </td>
                        <td>{{ $m->user->name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            Tidak ada data mutasi stok pada periode ini
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="card">
        <div class="card-body" style="text-align:center; padding:48px; color:var(--text-secondary);">
            <i class="fa-solid fa-hand-pointer" style="font-size:32px; margin-bottom:12px; opacity:0.4; display:block;"></i>
            Silakan pilih Produk dan Gudang di atas untuk melihat kartu stok.
        </div>
    </div>
    @endif
</div>
@endsection
