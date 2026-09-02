@extends('layouts.app')

@section('title', 'Rekap Pembelian per Barang')

@section('content')
<div class="animate-in">
    {{-- Header --}}
    <div class="page-header" style="margin-bottom: 20px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">
                <i class="fa-solid fa-boxes-packing" style="color: var(--primary); margin-right: 8px;"></i>
                Rekap Pembelian per Barang
            </h1>
            <p style="color: var(--text-secondary); font-size: 13px; margin: 0;">
                Analisis riwayat pengadaan produk dari tagihan Invoice Pembelian. Pantau volume belanja, total pengeluaran, dan tren harga beli rata-rata.
            </p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('purchase.orders.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-cart-shopping"></i> Purchase Order
            </a>
            <a href="{{ route('purchase.reports.fulfillment') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-chart-pie"></i> Monitoring PO
            </a>
            <button onclick="window.print()" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-print"></i> Cetak
            </button>
        </div>
    </div>

    {{-- Executive Summary KPI Cards --}}
    <div class="grid grid-3" style="gap: 16px; margin-bottom: 20px;">
        <div class="card" style="border-left: 4px solid var(--primary); padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                Total Kuantitas Dibeli
            </div>
            <div style="font-size: 24px; font-weight: 700; color: var(--text-primary); margin-top: 4px;">
                {{ number_format($totalItemsPurchased) }} <span style="font-size: 13px; font-weight: 400; color: var(--text-secondary);">Unit / Pcs</span>
            </div>
            <div style="font-size: 12px; color: var(--primary); margin-top: 4px; font-weight: 600;">
                Volume fisik barang masuk
            </div>
        </div>

        <div class="card" style="border-left: 4px solid #ef4444; padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                Total Pengeluaran Belanja (DPP)
            </div>
            <div style="font-size: 24px; font-weight: 700; color: #dc2626; margin-top: 4px;">
                Rp {{ number_format($totalSpend, 0, ',', '.') }}
            </div>
            <div style="font-size: 12px; color: #dc2626; margin-top: 4px; font-weight: 500;">
                <i class="fa-solid fa-receipt"></i> Akumulasi Nilai Pembelian
            </div>
        </div>

        <div class="card" style="border-left: 4px solid #10b981; padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                Rata-rata Belanja per Item
            </div>
            <div style="font-size: 24px; font-weight: 700; color: #059669; margin-top: 4px;">
                Rp {{ number_format($totalItemsPurchased > 0 ? ($totalSpend / $totalItemsPurchased) : 0, 0, ',', '.') }}
            </div>
            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">
                Efisiensi biaya pengadaan rata-rata
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <form method="GET" action="{{ route('purchase.reports.recap-by-product') }}" class="card" style="padding: 14px 20px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
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
            <a href="{{ route('purchase.reports.recap-by-product') }}" class="btn btn-secondary btn-sm" style="height: 38px; padding: 0 12px;" title="Reset Filter">
                Reset
            </a>
            @endif
        </div>
        <div style="font-size: 12px; color: var(--text-secondary);">
            Total <strong>{{ $products->total() }}</strong> jenis produk terdaftar
        </div>
    </form>

    {{-- Tabel Rekap Pembelian Produk --}}
    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-responsive">
            <table class="erp-table" style="margin: 0; width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 280px;">Produk</th>
                        <th style="width: 140px;">Kategori</th>
                        <th style="text-align: center; width: 120px;">Qty Dibeli</th>
                        <th style="text-align: right; width: 180px;">Total Nilai Pembelian</th>
                        <th style="text-align: right; width: 150px;">Rata-rata Harga Beli</th>
                        <th style="text-align: right; width: 150px;">Harga Master</th>
                        <th style="text-align: center; width: 110px;">Transaksi</th>
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

                        {{-- Total Qty Dibeli --}}
                        <td style="text-align: center; font-weight: 700; font-size: 13.5px; font-family: monospace;">
                            @if($p->total_qty > 0)
                                <span style="color: var(--primary);">{{ number_format($p->total_qty) }}</span> {{ $p->unit }}
                            @else
                                <span style="color: var(--text-secondary); font-weight: normal;">0</span>
                            @endif
                        </td>

                        {{-- Total Nilai Pembelian --}}
                        <td style="text-align: right; font-weight: 700; color: {{ $p->total_amount > 0 ? '#dc2626' : 'var(--text-secondary)' }}; font-size: 13.5px;">
                            Rp {{ number_format($p->total_amount ?: 0, 0, ',', '.') }}
                        </td>

                        {{-- Rata-rata Harga Beli --}}
                        <td style="text-align: right; font-weight: 600; color: var(--text-primary);">
                            @if($p->total_qty > 0)
                                Rp {{ number_format($p->avg_price, 0, ',', '.') }}
                            @else
                                <span style="color: var(--text-secondary);">-</span>
                            @endif
                        </td>

                        {{-- Harga Master --}}
                        <td style="text-align: right; color: var(--text-secondary); font-size: 12px;">
                            Rp {{ number_format($p->purchase_price, 0, ',', '.') }}
                        </td>

                        {{-- Jumlah Transaksi --}}
                        <td style="text-align: center;">
                            @if($p->transaction_count > 0)
                                <span class="badge badge-primary" style="font-size: 10.5px;">
                                    {{ $p->transaction_count }} Invoice
                                </span>
                            @else
                                <span style="color: var(--text-secondary); font-size: 11px;">Belum Ada</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 36px; color: var(--text-secondary);">
                            <i class="fa-solid fa-box-open" style="font-size: 28px; margin-bottom: 8px; display: block; opacity: 0.4;"></i>
                            Tidak ada data pembelian produk yang sesuai filter.
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
