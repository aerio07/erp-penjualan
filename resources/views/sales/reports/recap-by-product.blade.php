@extends('layouts.app')

@section('title', 'Rekap Penjualan per Barang')

@section('content')
<div class="animate-in">
    {{-- Header --}}
    <div class="page-header" style="margin-bottom: 20px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">
                <i class="fa-solid fa-chart-simple" style="color: var(--primary); margin-right: 8px;"></i>
                Rekap Penjualan per Barang & Profitabilitas
            </h1>
            <p style="color: var(--text-secondary); font-size: 13px; margin: 0;">
                Analisis omset penjualan, beban pokok penjualan (HPP), dan margin keuntungan kotor (Gross Margin) per item produk dari Invoice Penjualan.
            </p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('sales.orders.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-tags"></i> Sales Order
            </a>
            <a href="{{ route('sales.reports.fulfillment') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-chart-pie"></i> Monitoring SO
            </a>
            <button onclick="window.print()" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-print"></i> Cetak
            </button>
        </div>
    </div>

    {{-- Executive Summary KPI Cards --}}
    <div class="grid grid-4" style="gap: 16px; margin-bottom: 20px;">
        <div class="card" style="border-left: 4px solid var(--primary); padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                Total Volume Terjual
            </div>
            <div style="font-size: 22px; font-weight: 700; color: var(--text-primary); margin-top: 4px;">
                {{ number_format($totalItemsSold) }} <span style="font-size: 13px; font-weight: 400; color: var(--text-secondary);">Unit</span>
            </div>
            <div style="font-size: 11.5px; color: var(--primary); margin-top: 4px; font-weight: 600;">
                Barang keluar ke customer
            </div>
        </div>

        <div class="card" style="border-left: 4px solid #0284c7; padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                Total Omset Penjualan
            </div>
            <div style="font-size: 22px; font-weight: 700; color: #0284c7; margin-top: 4px;">
                Rp {{ number_format($totalRevenue, 0, ',', '.') }}
            </div>
            <div style="font-size: 11.5px; color: #0284c7; margin-top: 4px; font-weight: 500;">
                <i class="fa-solid fa-file-invoice"></i> Nilai Penjualan Bersih
            </div>
        </div>

        <div class="card" style="border-left: 4px solid #64748b; padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                Total HPP (Harga Pokok)
            </div>
            <div style="font-size: 22px; font-weight: 700; color: var(--text-primary); margin-top: 4px;">
                Rp {{ number_format($totalCogsAll, 0, ',', '.') }}
            </div>
            <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 4px;">
                Beban modal perolehan barang
            </div>
        </div>

        <div class="card" style="border-left: 4px solid #10b981; padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                Laba Kotor (Gross Profit)
            </div>
            <div style="font-size: 22px; font-weight: 700; color: #059669; margin-top: 4px;">
                Rp {{ number_format($totalGrossProfit, 0, ',', '.') }}
            </div>
            <div style="font-size: 11.5px; color: #059669; margin-top: 4px; font-weight: 700;">
                Margin Rata-rata: {{ $avgGrossMargin }}%
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <form method="GET" action="{{ route('sales.reports.recap-by-product') }}" class="card" style="padding: 14px 20px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama atau SKU produk..." class="form-control" style="height: 38px; font-size: 13px; border-radius: 6px; padding: 0 12px; width: 220px; margin-bottom: 0;">

            <select name="category_id" class="form-control" style="height: 38px; font-size: 13px; border-radius: 6px; padding: 0 10px; width: 170px; margin-bottom: 0;">
                <option value="">-- Semua Kategori --</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
                @endforeach
            </select>

            <div style="display: flex; align-items: center; gap: 6px;">
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control" style="height: 38px; font-size: 13px; border-radius: 6px; padding: 0 10px; width: 140px; margin-bottom: 0;" title="Dari Tanggal">
                <span style="color: var(--text-secondary); font-size: 12px;">s/d</span>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control" style="height: 38px; font-size: 13px; border-radius: 6px; padding: 0 10px; width: 140px; margin-bottom: 0;" title="Sampai Tanggal">
            </div>

            <button type="submit" class="btn btn-primary btn-sm" style="height: 38px; padding: 0 16px;">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
            @if($search || $categoryId || $dateFrom || $dateTo)
            <a href="{{ route('sales.reports.recap-by-product') }}" class="btn btn-secondary btn-sm" style="height: 38px; padding: 0 12px;" title="Reset Filter">
                Reset
            </a>
            @endif
        </div>
        <div style="font-size: 12px; color: var(--text-secondary);">
            Total <strong>{{ $products->total() }}</strong> jenis produk terdaftar
        </div>
    </form>

    {{-- Tabel Rekap Penjualan & Margin --}}
    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-responsive">
            <table class="erp-table" style="margin: 0; width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 250px;">Produk</th>
                        <th style="width: 130px;">Kategori</th>
                        <th style="text-align: center; width: 110px;">Qty Terjual</th>
                        <th style="text-align: right; width: 160px;">Omset Penjualan</th>
                        <th style="text-align: right; width: 150px;">Total HPP</th>
                        <th style="text-align: right; width: 160px; color: #059669;">Margin Kotor</th>
                        <th style="text-align: center; width: 110px;">Margin %</th>
                        <th style="text-align: right; width: 140px;">Rata-rata Harga</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $p)
                    <tr>
                        {{-- Produk --}}
                        <td>
                            <div style="font-weight: 700; color: var(--text-primary); font-size: 13.5px;">
                                {{ $p->name }}
                            </div>
                            <div style="font-size: 11.5px; color: var(--text-secondary); font-family: monospace;">
                                SKU: {{ $p->sku }} · Satuan: {{ $p->unit ?? 'pcs' }}
                            </div>
                        </td>

                        {{-- Kategori --}}
                        <td>
                            <span class="badge" style="background: #f1f5f9; color: #475569; font-size: 11px;">
                                {{ $p->category->name ?? 'Uncategorized' }}
                            </span>
                        </td>

                        {{-- Qty Terjual --}}
                        <td style="text-align: center; font-weight: 700; font-size: 13.5px; font-family: monospace;">
                            @if($p->total_qty > 0)
                                <span style="color: var(--primary);">{{ number_format($p->total_qty) }}</span> {{ $p->unit }}
                            @else
                                <span style="color: var(--text-secondary); font-weight: normal;">0</span>
                            @endif
                        </td>

                        {{-- Omset Penjualan --}}
                        <td style="text-align: right; font-weight: 700; color: {{ $p->total_amount > 0 ? '#0284c7' : 'var(--text-secondary)' }}; font-size: 13.5px;">
                            Rp {{ number_format($p->total_amount ?: 0, 0, ',', '.') }}
                        </td>

                        {{-- HPP --}}
                        <td style="text-align: right; color: var(--text-primary); font-size: 13px;">
                            Rp {{ number_format($p->calculated_cogs, 0, ',', '.') }}
                        </td>

                        {{-- Margin Kotor (Gross Profit) --}}
                        <td style="text-align: right; font-weight: 700; color: {{ $p->gross_margin >= 0 ? '#059669' : '#dc2626' }}; font-size: 13.5px;">
                            Rp {{ number_format($p->gross_margin, 0, ',', '.') }}
                        </td>

                        {{-- Margin % Badge --}}
                        <td style="text-align: center;">
                            @if($p->total_amount > 0)
                                <span class="badge {{ $p->margin_percentage >= 20 ? 'badge-done' : ($p->margin_percentage > 0 ? 'badge-primary' : 'badge-danger') }}" style="font-size: 11px; font-weight: 700;">
                                    {{ $p->margin_percentage }}%
                                </span>
                            @else
                                <span style="color: var(--text-secondary); font-size: 11px;">-</span>
                            @endif
                        </td>

                        {{-- Rata-rata Harga Jual --}}
                        <td style="text-align: right; font-weight: 500; font-size: 12.5px; color: var(--text-primary);">
                            @if($p->total_qty > 0)
                                Rp {{ number_format($p->avg_selling_price, 0, ',', '.') }}
                            @else
                                <span style="color: var(--text-secondary);">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 36px; color: var(--text-secondary);">
                            <i class="fa-solid fa-box-open" style="font-size: 28px; margin-bottom: 8px; display: block; opacity: 0.4;"></i>
                            Tidak ada data penjualan produk yang sesuai filter.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
        <div style="padding: 16px 20px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 13px; color: var(--text-secondary);">
                Menampilkan {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} dari {{ $products->total() }} Produk
            </div>
            <div>
                {{ $products->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
