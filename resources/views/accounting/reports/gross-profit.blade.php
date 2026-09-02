@extends('layouts.app')

@section('title', 'Tren Gross Profit & Profitabilitas')

@section('content')
<div class="content-header">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 style="font-size: 24px; font-weight: 700; color: var(--text-primary); margin: 0;">
                Tren Gross Profit (Laba Kotor)
            </h1>
            <p style="font-size: 13px; color: var(--text-secondary); margin: 4px 0 0 0;">
                Analisis dinamika margin laba kotor bulanan dan per kategori produk setelah memperhitungkan retur penjualan
            </p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('accounting.reports.profit-loss') }}" class="btn btn-secondary">
                <i class="fa-solid fa-file-invoice-dollar"></i> Laporan Laba/Rugi
            </a>
            <a href="{{ route('sales.reports.recap-by-product') }}" class="btn btn-secondary">
                <i class="fa-solid fa-boxes-stacked"></i> Rekap Penjualan per Barang
            </a>
        </div>
    </div>
</div>

<div class="content-body" style="display: flex; flex-direction: column; gap: 20px;">
    {{-- Filter Bar --}}
    <div class="card" style="padding: 16px 20px; margin: 0;">
        <form method="GET" action="{{ route('accounting.reports.gross-profit') }}" style="display: flex; align-items: flex-end; gap: 14px; flex-wrap: wrap;">
            <div style="width: 220px;">
                <label style="font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px; display: block;">
                    Rentang Waktu Analisis
                </label>
                <select name="period_months" class="form-control" onchange="this.form.submit()">
                    <option value="6" {{ $periodMonths == 6 ? 'selected' : '' }}>6 Bulan Terakhir</option>
                    <option value="12" {{ $periodMonths == 12 ? 'selected' : '' }}>12 Bulan Terakhir (1 Tahun)</option>
                    <option value="24" {{ $periodMonths == 24 ? 'selected' : '' }}>24 Bulan Terakhir (2 Tahun)</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="height: 38px;">
                <i class="fa-solid fa-filter"></i> Perbarui Grafik
            </button>
        </form>
    </div>

    {{-- Executive Summary KPI Cards --}}
    <div class="grid grid-4" style="gap: 16px;">
        <div class="card" style="border-left: 4px solid #0284c7; padding: 16px; margin: 0;">
            <div style="font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;">
                Total Omset Bersih (Revenue)
            </div>
            <div style="font-size: 22px; font-weight: 700; color: #0284c7; margin-top: 4px;">
                Rp {{ number_format($totalRevenue, 0, ',', '.') }}
            </div>
            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 2px;">
                {{ $periodMonths }} bulan terakhir (minus retur)
            </div>
        </div>

        <div class="card" style="border-left: 4px solid #dc2626; padding: 16px; margin: 0;">
            <div style="font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;">
                Total HPP (COGS)
            </div>
            <div style="font-size: 22px; font-weight: 700; color: #dc2626; margin-top: 4px;">
                Rp {{ number_format($totalCogs, 0, ',', '.') }}
            </div>
            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 2px;">
                Beban pokok barang terjual
            </div>
        </div>

        <div class="card" style="border-left: 4px solid #10b981; padding: 16px; margin: 0;">
            <div style="font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;">
                Total Laba Kotor (Gross Profit)
            </div>
            <div style="font-size: 22px; font-weight: 700; color: #059669; margin-top: 4px;">
                Rp {{ number_format($totalGrossProfit, 0, ',', '.') }}
            </div>
            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 2px;">
                Selisih omset dikurangi HPP
            </div>
        </div>

        <div class="card" style="border-left: 4px solid #8b5cf6; padding: 16px; margin: 0;">
            <div style="font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;">
                Rata-rata Gross Margin %
            </div>
            <div style="font-size: 22px; font-weight: 700; color: #7c3aed; margin-top: 4px;">
                {{ $avgMarginPct }}%
            </div>
            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 2px;">
                Tingkat margin kotor rata-rata
            </div>
        </div>
    </div>

    {{-- Chart.js Visual Trend --}}
    <div class="card" style="padding: 20px; margin: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-chart-area" style="color: var(--primary);"></i>
                Visualisasi Tren Omset, HPP, & Margin % ({{ $periodMonths }} Bulan Terakhir)
            </h3>
        </div>
        <div style="position: relative; height: 320px; width: 100%;">
            <canvas id="grossProfitChart"></canvas>
        </div>
    </div>

    {{-- Detail Tabel: Tren Bulanan & Breakdown Kategori --}}
    <div class="grid grid-2" style="gap: 20px;">
        {{-- Tabel 1: Bulanan --}}
        <div class="card" style="padding: 0; overflow: hidden; margin: 0;">
            <div class="card-header" style="padding: 14px 18px; border-bottom: 1px solid var(--border);">
                <h3 style="font-size: 15px; font-weight: 700; color: var(--text-primary); margin: 0;">
                    <i class="fa-regular fa-calendar-days" style="margin-right: 6px; color: var(--primary);"></i>
                    Rincian Kinerja Bulanan
                </h3>
            </div>
            <div class="table-responsive" style="margin: 0;">
                <table class="erp-table" style="margin: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th style="text-align: right;">Omset Bersih</th>
                            <th style="text-align: right;">HPP (COGS)</th>
                            <th style="text-align: right;">Laba Kotor</th>
                            <th style="text-align: right;">Margin %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($monthlyTrend as $m)
                        <tr>
                            <td>
                                <strong>{{ $m->label }}</strong>
                            </td>
                            <td style="text-align: right; color: #0284c7;">
                                Rp {{ number_format($m->revenue, 0, ',', '.') }}
                            </td>
                            <td style="text-align: right; color: #dc2626;">
                                Rp {{ number_format($m->cogs, 0, ',', '.') }}
                            </td>
                            <td style="text-align: right; font-weight: 700; color: #059669;">
                                Rp {{ number_format($m->gross_profit, 0, ',', '.') }}
                            </td>
                            <td style="text-align: right;">
                                <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 11.5px; font-weight: 700; background: {{ $m->margin_pct >= 20 ? '#d1fae5' : ($m->margin_pct > 0 ? '#fef3c7' : '#fee2e2') }}; color: {{ $m->margin_pct >= 20 ? '#059669' : ($m->margin_pct > 0 ? '#d97706' : '#dc2626') }};">
                                    {{ $m->margin_pct }}%
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 24px; color: var(--text-secondary);">
                                Belum ada data penjualan pada periode ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tabel 2: Kategori Produk --}}
        <div class="card" style="padding: 0; overflow: hidden; margin: 0;">
            <div class="card-header" style="padding: 14px 18px; border-bottom: 1px solid var(--border);">
                <h3 style="font-size: 15px; font-weight: 700; color: var(--text-primary); margin: 0;">
                    <i class="fa-solid fa-layer-group" style="margin-right: 6px; color: var(--primary);"></i>
                    Kontribusi Laba per Kategori Produk
                </h3>
            </div>
            <div class="table-responsive" style="margin: 0;">
                <table class="erp-table" style="margin: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Kategori Produk</th>
                            <th style="text-align: right;">Omset Bersih</th>
                            <th style="text-align: right;">HPP</th>
                            <th style="text-align: right;">Laba Kotor</th>
                            <th style="text-align: right;">Margin %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categoryBreakdown as $c)
                        <tr>
                            <td>
                                <strong>{{ $c->name }}</strong>
                            </td>
                            <td style="text-align: right; color: #0284c7;">
                                Rp {{ number_format($c->revenue, 0, ',', '.') }}
                            </td>
                            <td style="text-align: right; color: #dc2626;">
                                Rp {{ number_format($c->cogs, 0, ',', '.') }}
                            </td>
                            <td style="text-align: right; font-weight: 700; color: #059669;">
                                Rp {{ number_format($c->gross_profit, 0, ',', '.') }}
                            </td>
                            <td style="text-align: right;">
                                <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 11.5px; font-weight: 700; background: {{ $c->margin_pct >= 20 ? '#d1fae5' : ($c->margin_pct > 0 ? '#fef3c7' : '#fee2e2') }}; color: {{ $c->margin_pct >= 20 ? '#059669' : ($c->margin_pct > 0 ? '#d97706' : '#dc2626') }};">
                                    {{ $c->margin_pct }}%
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 24px; color: var(--text-secondary);">
                                Belum ada data kategori pada periode ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Load Chart.js from CDN for interactive visualization --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('grossProfitChart');
    if (!ctx) return;

    const labels = {!! json_encode($monthlyTrend->pluck('label')) !!};
    const revenueData = {!! json_encode($monthlyTrend->pluck('revenue')) !!};
    const cogsData = {!! json_encode($monthlyTrend->pluck('cogs')) !!};
    const profitData = {!! json_encode($monthlyTrend->pluck('gross_profit')) !!};
    const marginData = {!! json_encode($monthlyTrend->pluck('margin_pct')) !!};

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Omset (Revenue)',
                    data: revenueData,
                    backgroundColor: 'rgba(2, 132, 199, 0.75)',
                    borderColor: '#0284c7',
                    borderWidth: 1,
                    order: 2,
                    yAxisID: 'y',
                },
                {
                    label: 'HPP (COGS)',
                    data: cogsData,
                    backgroundColor: 'rgba(239, 68, 68, 0.65)',
                    borderColor: '#ef4444',
                    borderWidth: 1,
                    order: 3,
                    yAxisID: 'y',
                },
                {
                    label: 'Laba Kotor',
                    data: profitData,
                    backgroundColor: 'rgba(16, 185, 129, 0.75)',
                    borderColor: '#10b981',
                    borderWidth: 1,
                    order: 4,
                    yAxisID: 'y',
                },
                {
                    label: 'Margin %',
                    data: marginData,
                    type: 'line',
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.2)',
                    borderWidth: 3,
                    pointBackgroundColor: '#7c3aed',
                    pointRadius: 4,
                    tension: 0.25,
                    order: 1,
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + (value / 1000000).toLocaleString('id-ID') + ' jt';
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    min: 0,
                    max: 100,
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.dataset.yAxisID === 'y1') {
                                label += context.parsed.y + '%';
                            } else {
                                label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
});
</script>
@endsection
