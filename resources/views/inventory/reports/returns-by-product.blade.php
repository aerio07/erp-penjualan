@extends('layouts.app')

@section('title', 'Rekap Retur per Barang')

@section('content')
<div class="animate-in">
    {{-- Header --}}
    <div class="page-header" style="margin-bottom: 20px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">
                <i class="fa-solid fa-rotate-left" style="color: var(--primary); margin-right: 8px;"></i>
                Rekap Retur per Barang & Analisis Retur Rate
            </h1>
            <p style="color: var(--text-secondary); font-size: 13px; margin: 0;">
                Pemantauan komprehensif retur pembelian (ke supplier) dan retur penjualan (kondisi baik vs rusak), serta indikator early warning <strong>Retur Rate (%)</strong> terhadap volume barang terjual.
            </p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('purchase.returns.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-truck-ramp-box"></i> Retur Pembelian
            </a>
            <a href="{{ route('sales.returns.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-rotate-left"></i> Retur Penjualan
            </a>
            <button onclick="window.print()" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-print"></i> Cetak
            </button>
        </div>
    </div>

    {{-- Executive Summary KPI Cards --}}
    <div class="grid grid-4" style="gap: 16px; margin-bottom: 20px;">
        <div class="card" style="border-left: 4px solid #ef4444; padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                Total Retur Pembelian
            </div>
            <div style="font-size: 22px; font-weight: 700; color: #dc2626; margin-top: 4px;">
                {{ number_format($kpiPurchaseReturnQty) }} <span style="font-size: 13px; font-weight: 400; color: var(--text-secondary);">Unit</span>
            </div>
            <div style="font-size: 11.5px; color: #dc2626; margin-top: 4px; font-weight: 500;">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Dikembalikan ke supplier
            </div>
        </div>

        <div class="card" style="border-left: 4px solid #f59e0b; padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                Total Retur Penjualan
            </div>
            <div style="font-size: 22px; font-weight: 700; color: #d97706; margin-top: 4px;">
                {{ number_format($kpiSalesReturnQty) }} <span style="font-size: 13px; font-weight: 400; color: var(--text-secondary);">Unit</span>
            </div>
            <div style="font-size: 11.5px; color: #d97706; margin-top: 4px; font-weight: 500;">
                <i class="fa-solid fa-arrow-down-left-and-up-right-to-center"></i> Dari pelanggan (baik + rusak)
            </div>
        </div>

        <div class="card" style="border-left: 4px solid #6366f1; padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                Total Estimasi Nilai Retur
            </div>
            <div style="font-size: 22px; font-weight: 700; color: #4f46e5; margin-top: 4px;">
                Rp {{ number_format($kpiReturnValue, 0, ',', '.') }}
            </div>
            <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 4px;">
                Akumulasi rupiah barang diretur
            </div>
        </div>

        <div class="card" style="border-left: 4px solid {{ $kpiAvgReturnRate > 5 ? '#ef4444' : '#10b981' }}; padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                Rata-rata Retur Rate Jual
            </div>
            <div style="font-size: 22px; font-weight: 700; color: {{ $kpiAvgReturnRate > 5 ? '#dc2626' : '#059669' }}; margin-top: 4px;">
                {{ $kpiAvgReturnRate }}%
            </div>
            <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 4px;">
                Threshold ideal: &lt; 5.0%
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <form method="GET" action="{{ route('inventory.reports.returns-by-product') }}" class="card" style="padding: 14px 20px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
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
            <a href="{{ route('inventory.reports.returns-by-product') }}" class="btn btn-secondary btn-sm" style="height: 38px; padding: 0 12px;" title="Reset Filter">
                Reset
            </a>
            @endif
        </div>
        <div style="font-size: 12px; color: var(--text-secondary);">
            Total <strong>{{ $paginatedProducts->total() }}</strong> jenis produk terdaftar
        </div>
    </form>

    {{-- Tabel Rekap Retur Barang --}}
    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-responsive">
            <table class="erp-table" style="margin: 0; width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 250px;">Produk</th>
                        <th style="text-align: center; width: 120px; color: #dc2626;">Retur Beli</th>
                        <th style="text-align: center; width: 110px; color: #059669;">Retur Jual (Baik)</th>
                        <th style="text-align: center; width: 110px; color: #dc2626;">Retur Jual (Rusak)</th>
                        <th style="text-align: center; width: 120px; font-weight: 700;">Total Retur Jual</th>
                        <th style="text-align: center; width: 100px;">Qty Terjual</th>
                        <th style="text-align: center; width: 120px;">Retur Rate (%)</th>
                        <th style="text-align: right; width: 150px;">Nilai Retur</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paginatedProducts as $p)
                    <tr>
                        {{-- Produk --}}
                        <td>
                            <div style="font-weight: 700; color: var(--text-primary); font-size: 13.5px;">
                                {{ $p->name }}
                            </div>
                            <div style="font-size: 11.5px; color: var(--text-secondary); font-family: monospace;">
                                SKU: {{ $p->sku }} · {{ $p->category->name ?? 'Uncategorized' }}
                            </div>
                        </td>

                        {{-- Retur Beli --}}
                        <td style="text-align: center; font-weight: 600; color: {{ $p->purchase_return_qty > 0 ? '#dc2626' : 'var(--text-secondary)' }};">
                            {{ $p->purchase_return_qty > 0 ? $p->purchase_return_qty . ' ' . $p->unit : '-' }}
                        </td>

                        {{-- Retur Jual Baik --}}
                        <td style="text-align: center; font-weight: 500; color: {{ $p->sales_return_good_qty > 0 ? '#059669' : 'var(--text-secondary)' }};">
                            {{ $p->sales_return_good_qty > 0 ? $p->sales_return_good_qty . ' ' . $p->unit : '-' }}
                        </td>

                        {{-- Retur Jual Rusak --}}
                        <td style="text-align: center; font-weight: 600; color: {{ $p->sales_return_damaged_qty > 0 ? '#dc2626' : 'var(--text-secondary)' }};">
                            {{ $p->sales_return_damaged_qty > 0 ? $p->sales_return_damaged_qty . ' ' . $p->unit : '-' }}
                        </td>

                        {{-- Total Retur Jual --}}
                        <td style="text-align: center; font-weight: 700; font-size: 13.5px; color: {{ $p->total_sales_return_qty > 0 ? '#d97706' : 'var(--text-secondary)' }};">
                            {{ $p->total_sales_return_qty > 0 ? $p->total_sales_return_qty . ' ' . $p->unit : '-' }}
                        </td>

                        {{-- Qty Terjual --}}
                        <td style="text-align: center; font-size: 12.5px; color: var(--text-secondary);">
                            {{ $p->total_sold_qty > 0 ? number_format($p->total_sold_qty) : '-' }}
                        </td>

                        {{-- % Retur Rate --}}
                        <td style="text-align: center;">
                            @if($p->total_sold_qty > 0)
                                @if($p->return_rate > 5)
                                    <span class="badge badge-danger" style="font-size: 11px; font-weight: 700;" title="Perhatian! Retur rate melebihi 5%">
                                        <i class="fa-solid fa-triangle-exclamation"></i> {{ $p->return_rate }}%
                                    </span>
                                @elseif($p->return_rate > 0)
                                    <span class="badge badge-warning" style="font-size: 11px; font-weight: 600;">
                                        {{ $p->return_rate }}%
                                    </span>
                                @else
                                    <span class="badge badge-done" style="font-size: 10.5px;">0%</span>
                                @endif
                            @else
                                <span style="color: var(--text-secondary); font-size: 11.5px;">-</span>
                            @endif
                        </td>

                        {{-- Nilai Rupiah Retur --}}
                        <td style="text-align: right; font-weight: 700; color: {{ $p->total_return_value > 0 ? '#4f46e5' : 'var(--text-secondary)' }}; font-size: 13px;">
                            {{ $p->total_return_value > 0 ? 'Rp ' . number_format($p->total_return_value, 0, ',', '.') : '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 36px; color: var(--text-secondary);">
                            <i class="fa-solid fa-boxes-stacked" style="font-size: 28px; margin-bottom: 8px; display: block; opacity: 0.4;"></i>
                            Tidak ada produk terdaftar.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($paginatedProducts->hasPages())
        <div style="padding: 16px 20px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 13px; color: var(--text-secondary);">
                Menampilkan {{ $paginatedProducts->firstItem() ?? 0 }} - {{ $paginatedProducts->lastItem() ?? 0 }} dari {{ $paginatedProducts->total() }} Produk
            </div>
            <div>
                {{ $paginatedProducts->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
