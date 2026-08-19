@extends('layouts.app')
@section('title', 'Beranda')
@section('page-title', 'Beranda / Dashboard Eksekutif')

@section('content')
<div class="animate-in">

    {{-- Welcome Banner --}}
    <div style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 50%, #818cf8 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 10px 25px -5px rgba(99,102,241,0.3);">
        <div>
            <h2 style="font-size: 22px; font-weight: 700; margin-bottom: 6px;">Selamat Datang kembali, {{ auth()->user()->name }}! 👋</h2>
            <p style="font-size: 13.5px; opacity: 0.9;">Ringkasan pergerakan bisnis dan notifikasi aksi yang membutuhkan perhatian Anda hari ini.</p>
        </div>
        <div style="text-align: right; display: flex; gap: 10px; align-items: center;">
            <span style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; text-transform: capitalize;">
                <i class="fa-solid fa-user-shield" style="margin-right: 6px;"></i> Role: {{ $role }}
            </span>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- BARIS 1: STAT CARDS (ANGKA BESAR) --}}
    {{-- ========================================================= --}}
    <div class="grid grid-4" style="margin-bottom: 24px;">

        {{-- 1. Total Piutang --}}
        @if(in_array($role, ['admin', 'manager', 'finance', 'sales']))
        <div class="stat-card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                <div class="icon" style="background: #dbeafe; color: #1d4ed8; margin-bottom:0;">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </div>
                <span class="badge badge-confirmed">Sales</span>
            </div>
            <div class="value" style="font-size: 22px;">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</div>
            <div class="label">Total Piutang Belum Lunas</div>
            <div style="margin-top: 8px; font-size: 12px; color: var(--text-secondary);">
                <a href="{{ route('accounting.reports.receivables') }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">
                    Rincian Piutang <i class="fa-solid fa-arrow-right" style="font-size: 10px;"></i>
                </a>
            </div>
        </div>
        @endif

        {{-- 2. Total Hutang --}}
        @if(in_array($role, ['admin', 'manager', 'finance', 'purchasing']))
        <div class="stat-card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                <div class="icon" style="background: #fee2e2; color: #991b1b; margin-bottom:0;">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <span class="badge badge-cancelled">Purchase</span>
            </div>
            <div class="value" style="font-size: 22px;">Rp {{ number_format($totalHutang, 0, ',', '.') }}</div>
            <div class="label">Total Hutang Belum Lunas</div>
            <div style="margin-top: 8px; font-size: 12px; color: var(--text-secondary);">
                <a href="{{ route('accounting.reports.payables') }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">
                    Rincian Hutang <i class="fa-solid fa-arrow-right" style="font-size: 10px;"></i>
                </a>
            </div>
        </div>
        @endif

        {{-- 3. Laba Bulan Ini --}}
        @if(in_array($role, ['admin', 'manager', 'finance']))
        <div class="stat-card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                <div class="icon" style="background: #d1fae5; color: #065f46; margin-bottom:0;">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <span class="badge badge-posted">Accounting</span>
            </div>
            <div class="value" style="font-size: 22px; color: {{ $labaBulanIni >= 0 ? 'var(--success)' : 'var(--danger)' }};">
                Rp {{ number_format($labaBulanIni, 0, ',', '.') }}
            </div>
            <div class="label">Estimasi Laba Bersih Bulan Ini</div>
            <div style="margin-top: 8px; font-size: 12px; color: var(--text-secondary);">
                <a href="{{ route('accounting.reports.profit-loss') }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">
                    Laporan Laba Rugi <i class="fa-solid fa-arrow-right" style="font-size: 10px;"></i>
                </a>
            </div>
        </div>
        @endif

        {{-- 4. Saldo Kas & Bank --}}
        @if(in_array($role, ['admin', 'manager', 'finance']))
        <div class="stat-card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                <div class="icon" style="background: #ede9fe; color: #6d28d9; margin-bottom:0;">
                    <i class="fa-solid fa-vault"></i>
                </div>
                <span class="badge badge-waiting_approval">Kas & Bank</span>
            </div>
            <div class="value" style="font-size: 22px;">Rp {{ number_format($saldoKas, 0, ',', '.') }}</div>
            <div class="label">Total Saldo Kas & Bank</div>
            <div style="margin-top: 8px; font-size: 12px; color: var(--text-secondary);">
                <a href="{{ route('accounting.reports.ledger') }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">
                    Buku Besar Kas <i class="fa-solid fa-arrow-right" style="font-size: 10px;"></i>
                </a>
            </div>
        </div>
        @endif

        {{-- Alternative Stat Card for Gudang --}}
        @if($role === 'gudang')
        <div class="stat-card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                <div class="icon" style="background: #fef3c7; color: #92400e; margin-bottom:0;">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <span class="badge badge-pending">Gudang</span>
            </div>
            <div class="value" style="font-size: 22px; color: var(--warning);">{{ $alerts['low_stock'] }} Produk</div>
            <div class="label">Stok Kritis (<= Min Stok)</div>
            <div style="margin-top: 8px; font-size: 12px;">
                <a href="{{ route('inventory.stock-summary') }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">
                    Ringkasan Stok <i class="fa-solid fa-arrow-right" style="font-size: 10px;"></i>
                </a>
            </div>
        </div>
        @endif

    </div>

    {{-- ========================================================= --}}
    {{-- BARIS 2: GRAFIK (TREN PENJUALAN & TOP 5 PRODUK TERLARIS) --}}
    {{-- ========================================================= --}}
    @if(in_array($role, ['admin', 'manager', 'sales', 'finance']))
    <div class="grid grid-3" style="margin-bottom: 24px;">

        {{-- Grafik Tren Penjualan 30 Hari --}}
        <div class="card" style="grid-column: span {{ in_array($role, ['admin', 'manager', 'sales']) ? '2' : '3' }};">
            <div class="card-header">
                <h3><i class="fa-solid fa-chart-area" style="color:var(--primary); margin-right:8px;"></i> Tren Penjualan 30 Hari Terakhir</h3>
                <span style="font-size:12px; color:var(--text-secondary);">Agregat Nilai Invoice Harian</span>
            </div>
            <div class="card-body" style="padding:16px;">
                <div id="salesTrendChart" style="min-height: 290px;"></div>
            </div>
        </div>

        {{-- Grafik Top 5 Produk Terlaris --}}
        @if(in_array($role, ['admin', 'manager', 'sales']))
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-fire" style="color:#f97316; margin-right:8px;"></i> Top 5 Produk Terlaris</h3>
                <span style="font-size:12px; color:var(--text-secondary);">30 Hari</span>
            </div>
            <div class="card-body" style="padding:16px;">
                <div id="topProductsChart" style="min-height: 290px;"></div>
            </div>
        </div>
        @endif

    </div>
    @endif

    {{-- ========================================================= --}}
    {{-- BARIS 3: NOTIFIKASI / ALERT AKSI --}}
    {{-- ========================================================= --}}
    <div style="margin-bottom: 24px;">
        <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-bell-concierge" style="color: var(--warning);"></i> Perhatian & Tindakan Hari Ini
        </h3>

        <div class="grid grid-4">
            
            {{-- Alert 1: PO Menunggu Approval --}}
            @if(in_array($role, ['admin', 'manager', 'finance', 'purchasing']))
            <div class="card" style="border-left: 4px solid #8b5cf6;">
                <div class="card-body" style="padding: 16px;">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                        <div>
                            <div style="font-size: 12px; font-weight: 600; color: #8b5cf6; text-transform: uppercase;">Approval</div>
                            <div style="font-size: 20px; font-weight: 700; margin: 4px 0;">{{ $alerts['po_waiting_approval'] }} PO</div>
                            <div style="font-size: 12.5px; color: var(--text-secondary);">Menunggu persetujuan limit</div>
                        </div>
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: #f5f3ff; color: #8b5cf6; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                    </div>
                    <div style="margin-top: 14px; padding-top: 10px; border-top: 1px solid var(--border);">
                        <a href="{{ route('approvals.index') }}" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center;">
                            <i class="fa-solid fa-circle-check"></i> Proses Approval
                        </a>
                    </div>
                </div>
            </div>
            @endif

            {{-- Alert 2: Invoice Jatuh Tempo <= 7 hari --}}
            @if(in_array($role, ['admin', 'manager', 'finance', 'sales', 'purchasing']))
            <div class="card" style="border-left: 4px solid #ef4444;">
                <div class="card-body" style="padding: 16px;">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                        <div>
                            <div style="font-size: 12px; font-weight: 600; color: #ef4444; text-transform: uppercase;">Jatuh Tempo</div>
                            <div style="font-size: 20px; font-weight: 700; margin: 4px 0;">{{ $alerts['due_receivables'] + $alerts['due_payables'] }} Invoice</div>
                            <div style="font-size: 12.5px; color: var(--text-secondary);">Jatuh tempo dalam 7 hari</div>
                        </div>
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: #fee2e2; color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                    </div>
                    <div style="margin-top: 14px; padding-top: 10px; border-top: 1px solid var(--border); display: flex; gap: 6px;">
                        <a href="{{ route('accounting.reports.receivables') }}" class="btn btn-secondary btn-sm" style="flex:1; justify-content: center; font-size:11px;">
                            Piutang ({{ $alerts['due_receivables'] }})
                        </a>
                        <a href="{{ route('accounting.reports.payables') }}" class="btn btn-secondary btn-sm" style="flex:1; justify-content: center; font-size:11px;">
                            Hutang ({{ $alerts['due_payables'] }})
                        </a>
                    </div>
                </div>
            </div>
            @endif

            {{-- Alert 3: Stok Kritis (<= min_stock) --}}
            @if(in_array($role, ['admin', 'manager', 'gudang', 'purchasing']))
            <div class="card" style="border-left: 4px solid #f59e0b;">
                <div class="card-body" style="padding: 16px;">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                        <div>
                            <div style="font-size: 12px; font-weight: 600; color: #d97706; text-transform: uppercase;">Stok Kritis</div>
                            <div style="font-size: 20px; font-weight: 700; margin: 4px 0;">{{ $alerts['low_stock'] }} Produk</div>
                            <div style="font-size: 12.5px; color: var(--text-secondary);">Stok di bawah minimum</div>
                        </div>
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                            <i class="fa-solid fa-box-open"></i>
                        </div>
                    </div>
                    <div style="margin-top: 14px; padding-top: 10px; border-top: 1px solid var(--border);">
                        <a href="{{ route('inventory.stock-summary') }}" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center;">
                            <i class="fa-solid fa-boxes-stacked"></i> Cek Stok Kritis
                        </a>
                    </div>
                </div>
            </div>
            @endif

            {{-- Alert 4: Barang Karantina Belum Diselesaikan --}}
            @if(in_array($role, ['admin', 'manager', 'gudang']))
            <div class="card" style="border-left: 4px solid #06b6d4;">
                <div class="card-body" style="padding: 16px;">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                        <div>
                            <div style="font-size: 12px; font-weight: 600; color: #0891b2; text-transform: uppercase;">Karantina</div>
                            <div style="font-size: 20px; font-weight: 700; margin: 4px 0;">{{ $alerts['quarantine_pending'] }} Produk</div>
                            <div style="font-size: 12.5px; color: var(--text-secondary);">Barang rusak di karantina</div>
                        </div>
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: #cffafe; color: #0891b2; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                            <i class="fa-solid fa-box-archive"></i>
                        </div>
                    </div>
                    <div style="margin-top: 14px; padding-top: 10px; border-top: 1px solid var(--border);">
                        <a href="{{ route('inventory.dispositions.create') }}" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center;">
                            <i class="fa-solid fa-plus"></i> Disposisi Karantina
                        </a>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- BARIS 4: AKTIVITAS TERBARU & DAFTAR PENGAWASAN --}}
    {{-- ========================================================= --}}
    <div class="grid grid-3">

        {{-- Tabel 7 Transaksi Terakhir Lintas Modul --}}
        <div class="card" style="grid-column: span 2;">
            <div class="card-header">
                <h3><i class="fa-solid fa-clock-rotate-left" style="color:var(--primary); margin-right:8px;"></i> Aktivitas Terbaru Lintas Modul</h3>
                <span style="font-size:12px; color:var(--text-secondary);">7 Transaksi Terakhir</span>
            </div>
            <div class="table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Modul / Ref</th>
                            <th>Keterangan</th>
                            <th style="text-align:right;">Nominal</th>
                            <th style="text-align:center;">Waktu</th>
                            <th style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($aktivitas as $act)
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="width:28px; height:28px; border-radius:6px; background:{{ $act['color'] }}20; color:{{ $act['color'] }}; display:flex; align-items:center; justify-content:center; font-size:12px;">
                                        <i class="fa-solid {{ $act['icon'] }}"></i>
                                    </div>
                                    <span style="font-weight:600; font-size:13px;">{{ $act['ref'] }}</span>
                                </div>
                            </td>
                            <td style="font-size:13px;">{{ $act['desc'] }}</td>
                            <td style="text-align:right; font-weight:600; font-size:13px;">
                                @if($act['amount'] !== null)
                                    Rp {{ number_format($act['amount'], 0, ',', '.') }}
                                @else
                                    <span style="color:var(--text-secondary);">-</span>
                                @endif
                            </td>
                            <td style="text-align:center; font-size:12px; color:var(--text-secondary);">
                                {{ $act['created_at']->diffForHumans() }}
                            </td>
                            <td style="text-align:center;">
                                <a href="{{ $act['url'] }}" class="btn btn-secondary btn-sm btn-icon" title="Buka Detail">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align:center; padding:32px; color:var(--text-secondary);">
                                Belum ada aktivitas transaksi tercatat.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Panel Samping: Low Stock Preview / Quick Navigation --}}
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-list-check" style="color:var(--warning); margin-right:8px;"></i> Stok Perlu Re-order</h3>
            </div>
            <div class="card-body" style="padding:0;">
                @forelse($lowStockProducts as $product)
                <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid #f1f5f9;">
                    <div>
                        <div style="font-size:13px; font-weight:600;">{{ $product->name }}</div>
                        <div style="font-size:11px; color:var(--text-secondary);">SKU: {{ $product->sku }}</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:15px; font-weight:700; color:var(--danger);">{{ $product->current_stock }} {{ $product->unit }}</div>
                        <div style="font-size:10.5px; color:var(--text-secondary);">min: {{ $product->min_stock }}</div>
                    </div>
                </div>
                @empty
                <div style="padding:24px; text-align:center; color:var(--text-secondary); font-size:13px;">
                    <i class="fa-solid fa-circle-check" style="font-size:24px; color:var(--success); margin-bottom:8px; display:block;"></i>
                    Semua produk mencukupi stok minimum.
                </div>
                @endforelse
            </div>
            <div style="padding: 12px 16px; background: #f8fafc; border-top: 1px solid var(--border);">
                <a href="{{ route('purchase.orders.create') }}" class="btn btn-primary btn-sm" style="width: 100%; justify-content: center;">
                    <i class="fa-solid fa-plus"></i> Buat PO Baru
                </a>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // --- 1. ApexChart Tren Penjualan 30 Hari ---
    var salesTrendData = @json($trenPenjualan);
    var salesOptions = {
        series: [{
            name: 'Total Penjualan',
            data: salesTrendData.data || []
        }],
        chart: {
            type: 'area',
            height: 290,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        colors: ['#6366f1'],
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05 }
        },
        stroke: { curve: 'smooth', width: 3 },
        xaxis: {
            categories: salesTrendData.categories || [],
            labels: { style: { colors: '#64748b', fontSize: '11px' } }
        },
        yaxis: {
            labels: {
                style: { colors: '#64748b', fontSize: '11px' },
                formatter: (v) => 'Rp ' + new Intl.NumberFormat('id-ID').format(v)
            }
        },
        grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: {
            y: { formatter: (v) => 'Rp ' + new Intl.NumberFormat('id-ID').format(v) }
        }
    };

    var salesChartEl = document.querySelector("#salesTrendChart");
    if (salesChartEl) {
        var salesChart = new ApexCharts(salesChartEl, salesOptions);
        salesChart.render();
    }

    // --- 2. ApexChart Top 5 Produk Terlaris ---
    var topProductsData = @json($topProduk);
    var topOptions = {
        series: [{
            name: 'Qty Terjual',
            data: topProductsData.series || []
        }],
        chart: {
            type: 'bar',
            height: 290,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        plotOptions: {
            bar: {
                horizontal: true,
                barHeight: '50%',
                borderRadius: 6
            }
        },
        colors: ['#f97316'],
        xaxis: {
            categories: topProductsData.labels || [],
            labels: { style: { colors: '#64748b', fontSize: '11px' } }
        },
        yaxis: {
            labels: { style: { colors: '#64748b', fontSize: '11px' } }
        },
        grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
        dataLabels: { enabled: true, style: { fontSize: '11px' } },
        tooltip: {
            y: { formatter: (v) => v + ' pcs' }
        }
    };

    var topChartEl = document.querySelector("#topProductsChart");
    if (topChartEl) {
        if ((topProductsData.series || []).length > 0) {
            var topChart = new ApexCharts(topChartEl, topOptions);
            topChart.render();
        } else {
            topChartEl.innerHTML = '<div style="display:flex; align-items:center; justify-content:center; height:290px; color:#94a3b8; font-size:13px;">Belum ada transaksi penjualan 30 hari terakhir.</div>';
        }
    }

});
</script>
@endpush
