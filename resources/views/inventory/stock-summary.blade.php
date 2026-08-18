@extends('layouts.app')
@section('title', 'Ringkasan Stok')
@section('page-title', 'Ringkasan Stok Barang')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Ringkasan Stok & Karantina</h1>
            <p>Posisi stok produk terkini per gudang: memisahkan stok siap jual dan stok karantina (rusak/cacat)</p>
        </div>
    </div>

    {{-- Filter Gudang --}}
    <div class="card" style="margin-bottom:16px;">
        <div class="card-body" style="padding:16px;">
            <form method="GET" action="{{ route('inventory.stock-summary') }}" style="display:flex; gap:12px; align-items:flex-end;">
                <div style="flex:1; max-width:300px;">
                    <label class="form-label">Filter Gudang</label>
                    <select name="warehouse_id" class="form-control" onchange="this.form.submit()">
                        <option value="">Semua Gudang (Konsolidasi)</option>
                        @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ $warehouseId == $wh->id ? 'selected' : '' }}>{{ $wh->name }} ({{ $wh->code }})</option>
                        @endforeach
                    </select>
                </div>
                @if($warehouseId)
                <a href="{{ route('inventory.stock-summary') }}" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i> Reset Filter</a>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h3>Daftar Stok Produk</h3>
                <div style="font-size:13px; color:var(--text-secondary); margin-top:2px;">
                    Total Nilai Persediaan (Siap Jual): <strong>Rp {{ number_format($products->sum('stock_value'), 0, ',', '.') }}</strong>
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
                        <th>SKU</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Unit</th>
                        <th style="text-align:right; color:var(--success);">Stok Siap Jual</th>
                        <th style="text-align:right; color:#b45309; background:rgba(245, 158, 11, 0.08);">Karantina (Rusak)</th>
                        <th style="text-align:center;">Min Stok</th>
                        <th style="text-align:right;">Nilai HPP (Siap Jual)</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $p)
                    <tr>
                        <td style="font-weight:600; color:var(--primary);">{{ $p->sku }}</td>
                        <td style="font-weight:500;">{{ $p->name }}</td>
                        <td>{{ $p->category ?? '-' }}</td>
                        <td>{{ $p->unit }}</td>
                        <td style="text-align:right; font-weight:700; font-size:14.5px; color:var(--success);">
                            {{ number_format($p->current_stock) }}
                        </td>
                        <td style="text-align:right; background:rgba(245, 158, 11, 0.04);">
                            @if($p->quarantine_stock > 0)
                                <span class="badge badge-warning" style="background:#fef3c7; color:#92400e; font-weight:700; font-size:13px;">
                                    <i class="fa-solid fa-triangle-exclamation"></i> {{ number_format($p->quarantine_stock) }} {{ $p->unit }}
                                </span>
                            @else
                                <span style="color:var(--text-secondary);">-</span>
                            @endif
                        </td>
                        <td style="text-align:center;">{{ $p->min_stock }}</td>
                        <td style="text-align:right;">Rp {{ number_format($p->stock_value, 0, ',', '.') }}</td>
                        <td style="text-align:center;">
                            @if($p->current_stock <= 0)
                                <span class="badge badge-cancelled">Habis</span>
                            @elseif($p->current_stock <= $p->min_stock)
                                <span class="badge badge-pending">Hampir Habis</span>
                            @else
                                <span class="badge badge-done">Aman</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                <a href="{{ route('inventory.stock-card') }}?product_id={{ $p->id }}&warehouse_id={{ $warehouseId }}" class="btn btn-secondary btn-sm" title="Kartu Stok">
                                    <i class="fa-solid fa-rectangle-list"></i> Kartu Stok
                                </a>
                                @if($p->quarantine_stock > 0)
                                <a href="{{ route('inventory.dispositions.create') }}?product_id={{ $p->id }}&warehouse_id={{ $warehouseId }}" class="btn btn-warning btn-sm" title="Selesaikan Barang Karantina">
                                    <i class="fa-solid fa-box-archive"></i> Selesaikan
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" style="text-align:center; padding:48px; color:var(--text-secondary);">
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
