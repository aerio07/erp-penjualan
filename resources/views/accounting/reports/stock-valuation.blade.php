@extends('layouts.app')
@section('title', 'Laporan Valuasi Stok')
@section('page-title', 'Laporan Valuasi Persediaan (Stock Valuation)')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Laporan Valuasi Persediaan</h1>
            <p>Perhitungan nilai aset persediaan berdasarkan Average Cost (HPP)</p>
        </div>
    </div>

    <div class="stat-card" style="max-width:350px; margin-bottom:24px;">
        <div class="icon" style="background:#ede9fe; color:#6d28d9;"><i class="fa-solid fa-boxes-stacked"></i></div>
        <div class="value">Rp {{ number_format($totalValue, 0, ',', '.') }}</div>
        <div class="label">Total Nilai Persediaan Aset</div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th style="text-align:right;">Stok Fisik</th>
                        <th style="text-align:right;">Biaya Rata-Rata (Avg Cost)</th>
                        <th style="text-align:right;">Total Valuasi Stok</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $p)
                    <tr>
                        <td style="font-weight:600; color:var(--primary);">{{ $p->sku }}</td>
                        <td style="font-weight:500;">{{ $p->name }}</td>
                        <td>{{ $p->category ?? '-' }}</td>
                        <td style="text-align:right; font-weight:600;">{{ number_format($p->current_stock) }} {{ $p->unit }}</td>
                        <td style="text-align:right;">Rp {{ number_format($p->avg_cost, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:700; color:var(--primary);">
                            Rp {{ number_format($p->stock_value, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            Belum ada data stok produk
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
