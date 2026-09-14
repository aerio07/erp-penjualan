@extends('layouts.app')
@section('title', 'Kartu Stok')
@section('page-title', 'Kartu Stok Barang')

@section('content')
<div class="animate-in">
    <x-page-header title="Kartu Stok" subtitle="Rincian mutasi masuk/keluar dan saldo berjalan per produk dan gudang">
        <x-slot:actions>
            <a href="{{ route('inventory.stock-summary') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-boxes-stacked"></i> Ringkasan Stok
            </a>
            <a href="{{ route('inventory.transfers.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-right-left"></i> Transfer Stok
            </a>
            <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-print"></i> Cetak
            </button>
        </x-slot:actions>
    </x-page-header>

    {{-- Filter Form --}}
    <x-list-filter-bar 
        :action="route('inventory.stock-card')"
        :showSearch="false"
        :showDateFilter="true"
        dateFromParam="date_from"
        dateToParam="date_to"
        :showPerPage="false"
    >
        <div style="min-width: 280px;">
            <x-searchable-select 
                name="product_id" 
                :options="$products" 
                :value="$productId" 
                placeholder="-- Pilih Produk * --" 
                search-placeholder="Cari nama atau SKU produk..." 
                required 
            />
        </div>

        <div style="min-width: 220px;">
            <x-searchable-select 
                name="warehouse_id" 
                :options="$warehouses" 
                :value="$warehouseId" 
                placeholder="-- Semua Gudang / Lokasi --" 
                search-placeholder="Cari nama atau kode gudang..." 
            />
        </div>
    </x-list-filter-bar>

    @if($productId)
    @php
        $selectedProduct = $products->firstWhere('id', $productId);
        $selectedWarehouse = $warehouseId ? $warehouses->firstWhere('id', $warehouseId) : null;
        $totalIn = 0;
        $totalOut = 0;
        foreach($movements as $row) {
            $m = $row['movement'];
            $isDamaged = ($m->type === 'return_in_damaged');
            $isAdjustmentSurplus = ($m->type === 'adjustment' && $m->quantity > 0);
            $isAdjustmentDeficit = ($m->type === 'adjustment' && $m->quantity < 0);
            $isIncoming = in_array($m->type, ['in', 'return_in', 'transfer_in', 'opening_balance']) || $isAdjustmentSurplus;
            $isOutgoing = in_array($m->type, ['out', 'return_out', 'transfer_out']) || $isAdjustmentDeficit;
            if ($isIncoming) $totalIn += abs($m->quantity);
            if ($isOutgoing) $totalOut += abs($m->quantity);
        }
        $finalBalance = $movements->last()['running_qty'] ?? 0;
    @endphp

    <div class="grid grid-3" style="margin-bottom: 20px;">
        <div class="card" style="padding: 16px; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-color: #bbf7d0;">
            <div style="font-size: 12px; color: #166534; font-weight: 600; text-transform: uppercase;">Total Mutasi Masuk</div>
            <div style="font-size: 24px; font-weight: 800; color: #15803d; margin-top: 4px;">
                +{{ number_format($totalIn) }} <span style="font-size: 13px; font-weight: 500;">{{ $selectedProduct->unit ?? 'pcs' }}</span>
            </div>
        </div>
        <div class="card" style="padding: 16px; background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border-color: #fecaca;">
            <div style="font-size: 12px; color: #991b1b; font-weight: 600; text-transform: uppercase;">Total Mutasi Keluar</div>
            <div style="font-size: 24px; font-weight: 800; color: #b91c1c; margin-top: 4px;">
                -{{ number_format($totalOut) }} <span style="font-size: 13px; font-weight: 500;">{{ $selectedProduct->unit ?? 'pcs' }}</span>
            </div>
        </div>
        <div class="card" style="padding: 16px; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-color: #bfdbfe;">
            <div style="font-size: 12px; color: #1e40af; font-weight: 600; text-transform: uppercase;">Saldo Akhir Persediaan</div>
            <div style="font-size: 24px; font-weight: 800; color: #1d4ed8; margin-top: 4px;">
                {{ number_format($finalBalance) }} <span style="font-size: 13px; font-weight: 500;">{{ $selectedProduct->unit ?? 'pcs' }}</span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin-bottom: 2px;">Riwayat Mutasi Stok: {{ $selectedProduct->name ?? '-' }} ({{ $selectedProduct->sku ?? '-' }})</h3>
                <span style="font-size: 12px; color: var(--text-secondary);">
                    Lokasi: <strong>{{ $selectedWarehouse ? $selectedWarehouse->name : 'Semua Gudang / Konsolidasi' }}</strong>
                </span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Tgl Mutasi</th>
                        <th>Gudang</th>
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
                            <span class="badge" style="background:#f1f5f9; color:#334155; font-weight:600;">
                                <i class="fa-solid fa-warehouse"></i> {{ $m->warehouse->name ?? '-' }}
                            </span>
                        </td>
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
                        <td colspan="8" style="text-align:center; padding:48px; color:var(--text-secondary);">
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
            Silakan pilih <strong>Produk</strong> di atas untuk melihat riwayat kartu stok.
        </div>
    </div>
    @endif
</div>
@endsection
