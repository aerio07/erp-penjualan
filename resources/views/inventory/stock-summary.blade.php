@extends('layouts.app')
@section('title', 'Ringkasan Stok')
@section('page-title', 'Ringkasan Stok')

@section('content')
<div class="animate-in">
    <x-page-header
        title="Ringkasan Posisi Stok (5 Dimensi)"
        subtitle="Posisi stok komprehensif: On Hand, Booking SO (Reserved), Bebas Jual (Available), Defisit SO (Backorder), dan Incoming PO.">
        <x-slot:actions>
            <a href="{{ route('purchase.requests.index') }}" class="btn btn-primary">
                <i class="fa-solid fa-cart-shopping"></i> Purchase Request
            </a>
            <a href="{{ route('inventory.stock-card') }}" class="btn btn-secondary">
                <i class="fa-solid fa-rectangle-list"></i> Kartu Stok
            </a>
        </x-slot:actions>
    </x-page-header>

    {{-- Filter Gudang & Pencarian --}}
    <x-list-filter-bar 
        :action="route('inventory.stock-summary')" 
        placeholder="Cari nama atau SKU produk..."
        :showPerPage="false"
    >
        <div style="min-width: 260px;">
            <x-searchable-select 
                name="warehouse_id" 
                :options="$warehouses" 
                :value="request('warehouse_id', $warehouseId)" 
                placeholder="-- Semua Gudang (Konsolidasi) --" 
                search-placeholder="Cari nama gudang..." 
            />
        </div>
    </x-list-filter-bar>

    <div class="card">
        <div class="card-header">
            <div>
                <h3>Daftar Stok Produk & Ketersediaan</h3>
                <div style="font-size:13px; color:var(--text-secondary); margin-top:2px;">
                    Total Nilai Persediaan (On Hand): <strong>Rp {{ number_format($products->sum('stock_value'), 0, ',', '.') }}</strong>
                    @if($products->sum('quarantine_stock') > 0)
                        · Total Barang Karantina: <strong style="color:#b45309;">{{ number_format($products->sum('quarantine_stock')) }} pcs</strong>
                    @endif
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>SKU & Nama Produk</th>
                        <th>Kategori</th>
                        <th style="text-align:center;">Unit</th>
                        <th style="text-align:right; font-weight:700;">On Hand (Fisik)</th>
                        <th style="text-align:right; color:#2563eb; background:rgba(37, 99, 235, 0.05);">Reserved (SO)</th>
                        <th style="text-align:right; color:#15803d; background:rgba(16, 185, 129, 0.05);">Available</th>
                        <th style="text-align:right; color:#dc2626; background:rgba(220, 38, 38, 0.05);">Backorder</th>
                        <th style="text-align:right; color:#4f46e5;">Incoming PO</th>
                        <th style="text-align:right;">Karantina</th>
                        <th style="text-align:right;">Nilai HPP</th>
                        <th style="text-align:center;">Status</th>
                        <th class="text-center col-actions">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $p)
                    <tr>
                        <td>
                            <div style="font-weight:600; color:var(--text-primary);">{{ $p->name }}</div>
                            <div style="font-size:12px; color:var(--primary); font-family:monospace;">{{ $p->sku }}</div>
                        </td>
                        <td>{{ $p->category ?? '-' }}</td>
                        <td style="text-align:center;">{{ $p->unit }}</td>
                        <td style="text-align:right; font-weight:700;">
                            {{ number_format($p->current_stock) }}
                        </td>
                        <td style="text-align:right; font-weight:600; color:#2563eb; background:rgba(37, 99, 235, 0.03);">
                            {{ number_format($p->reserved_stock) }}
                        </td>
                        <td style="text-align:right; font-weight:700; color:#15803d; background:rgba(16, 185, 129, 0.03);">
                            {{ number_format($p->available_stock) }}
                        </td>
                        <td style="text-align:right; font-weight:600; color:#dc2626; background:rgba(220, 38, 38, 0.03);">
                            {{ number_format($p->backorder_stock) }}
                        </td>
                        <td style="text-align:right; font-weight:600; color:#4f46e5;">
                            {{ number_format($p->incoming_stock) }}
                        </td>
                        <td style="text-align:right;">
                            @if($p->quarantine_stock > 0)
                                <span style="font-weight:700; color:#b45309;">{{ number_format($p->quarantine_stock) }}</span>
                            @else
                                <span style="color:var(--text-secondary);">-</span>
                            @endif
                        </td>
                        <td style="text-align:right; font-weight:600;">Rp {{ number_format($p->stock_value, 0, ',', '.') }}</td>
                        <td style="text-align:center;">
                            @if($p->available_stock <= 0 && $p->current_stock <= 0)
                                <span class="badge badge-cancelled">Habis</span>
                            @elseif($p->available_stock <= 0)
                                <span class="badge badge-warning" style="background:#fee2e2; color:#b91c1c;">Full Reserved</span>
                            @elseif($p->available_stock <= $p->min_stock)
                                <span class="badge badge-pending">Hampir Habis</span>
                            @else
                                <span class="badge badge-done">Aman</span>
                            @endif
                        </td>
                        <td class="col-actions text-center">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                <a href="{{ route('inventory.stock-card') }}?product_id={{ $p->id }}&warehouse_id={{ $warehouseId }}" class="btn btn-secondary btn-sm btn-icon" title="Kartu Stok">
                                    <i class="fa-solid fa-rectangle-list"></i>
                                </a>
                                @if($p->quarantine_stock > 0)
                                <a href="{{ route('inventory.dispositions.create') }}?product_id={{ $p->id }}&warehouse_id={{ $warehouseId }}" class="btn btn-warning btn-sm btn-icon" title="Selesaikan Barang Karantina">
                                    <i class="fa-solid fa-box-archive"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            Belum ada produk aktif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
