@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="animate-in">

    {{-- ===== KPI CARDS ===== --}}
    <div class="grid grid-4" style="margin-bottom: 24px;">
        <div class="stat-card">
            <div class="icon" style="background: #ede9fe; color: #7c3aed;">
                <i class="fa-solid fa-cart-shopping"></i>
            </div>
            <div class="value">{{ $stats['po_confirmed'] }}</div>
            <div class="label">PO Aktif</div>
            @if($stats['po_waiting_approval'] > 0)
            <div style="margin-top:8px; font-size:12px; color: var(--warning);">
                <i class="fa-solid fa-clock"></i> {{ $stats['po_waiting_approval'] }} menunggu approval
            </div>
            @endif
        </div>

        <div class="stat-card">
            <div class="icon" style="background: #dbeafe; color: #1d4ed8;">
                <i class="fa-solid fa-store"></i>
            </div>
            <div class="value">{{ $stats['so_confirmed'] }}</div>
            <div class="label">SO Aktif</div>
        </div>

        <div class="stat-card">
            <div class="icon" style="background: #fee2e2; color: #991b1b;">
                <i class="fa-solid fa-file-invoice"></i>
            </div>
            <div class="value">{{ number_format($stats['purchase_payable'], 0, ',', '.') }}</div>
            <div class="label">Total Hutang Beredar (Rp)</div>
            @if($stats['overdue_payables'] > 0)
            <div style="margin-top:8px; font-size:12px; color: var(--danger);">
                <i class="fa-solid fa-triangle-exclamation"></i> Rp {{ number_format($stats['overdue_payables'], 0, ',', '.') }} jatuh tempo
            </div>
            @endif
        </div>

        <div class="stat-card">
            <div class="icon" style="background: #d1fae5; color: #065f46;">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
            <div class="value">{{ number_format($stats['sales_receivable'], 0, ',', '.') }}</div>
            <div class="label">Total Piutang Beredar (Rp)</div>
            @if($stats['overdue_receivables'] > 0)
            <div style="margin-top:8px; font-size:12px; color: var(--danger);">
                <i class="fa-solid fa-triangle-exclamation"></i> Rp {{ number_format($stats['overdue_receivables'], 0, ',', '.') }} jatuh tempo
            </div>
            @endif
        </div>
    </div>

    {{-- ===== ROW 2 ===== --}}
    <div class="grid grid-3" style="margin-bottom: 24px;">

        {{-- Grafik Penjualan --}}
        <div class="card" style="grid-column: span 2;">
            <div class="card-header">
                <h3><i class="fa-solid fa-chart-line" style="color:var(--primary); margin-right:8px;"></i> Penjualan 6 Bulan Terakhir</h3>
            </div>
            <div class="card-body" style="padding:16px;">
                <div id="salesChart" style="min-height: 280px;"></div>
            </div>
        </div>

        {{-- Low Stock Alert --}}
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-triangle-exclamation" style="color:var(--warning); margin-right:8px;"></i> Stok Hampir Habis</h3>
                <span class="badge badge-cancelled">{{ $stats['low_stock_count'] }} produk</span>
            </div>
            <div class="card-body" style="padding:0;">
                @php
                    $stockService = app(\App\Services\StockService::class);
                    $lowStockProducts = $stockService->getLowStockProducts()->take(8);
                @endphp
                @forelse($lowStockProducts as $product)
                <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 20px; border-bottom:1px solid #f1f5f9;">
                    <div>
                        <div style="font-size:13.5px; font-weight:500;">{{ $product->name }}</div>
                        <div style="font-size:12px; color:var(--text-secondary);">{{ $product->sku }}</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:16px; font-weight:700; color:var(--danger);">{{ $product->current_stock }}</div>
                        <div style="font-size:11px; color:var(--text-secondary);">min: {{ $product->min_stock }}</div>
                    </div>
                </div>
                @empty
                <div style="padding:24px; text-align:center; color:var(--text-secondary); font-size:13.5px;">
                    <i class="fa-solid fa-circle-check" style="font-size:24px; color:var(--success); margin-bottom:8px; display:block;"></i>
                    Semua stok mencukupi
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ===== ROW 3: Jatuh Tempo ===== --}}
    <div class="grid grid-2">
        {{-- Piutang Jatuh Tempo --}}
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-clock" style="color:var(--info); margin-right:8px;"></i> Piutang Jatuh Tempo (7 Hari)</h3>
                <a href="{{ route('accounting.reports.receivables') }}" class="btn btn-secondary btn-sm">Lihat Semua</a>
            </div>
            <div class="card-body" style="padding:0;">
                @forelse($upcomingReceivables as $inv)
                <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 20px; border-bottom:1px solid #f1f5f9;">
                    <div>
                        <div style="font-size:13.5px; font-weight:500;">{{ $inv->salesOrder->customer->name ?? '-' }}</div>
                        <div style="font-size:12px; color:var(--text-secondary);">{{ $inv->invoice_number }} · Jatuh tempo: {{ $inv->due_date->format('d/m/Y') }}</div>
                    </div>
                    <div style="font-weight:600; font-size:14px; color:var(--text-primary);">
                        Rp {{ number_format($inv->outstanding_amount, 0, ',', '.') }}
                    </div>
                </div>
                @empty
                <div style="padding:24px; text-align:center; color:var(--text-secondary); font-size:13.5px;">
                    <i class="fa-solid fa-check-circle" style="display:block; font-size:24px; color:var(--success); margin-bottom:8px;"></i>
                    Tidak ada piutang jatuh tempo
                </div>
                @endforelse
            </div>
        </div>

        {{-- Hutang Jatuh Tempo --}}
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-clock" style="color:var(--warning); margin-right:8px;"></i> Hutang Jatuh Tempo (7 Hari)</h3>
                <a href="{{ route('accounting.reports.payables') }}" class="btn btn-secondary btn-sm">Lihat Semua</a>
            </div>
            <div class="card-body" style="padding:0;">
                @forelse($upcomingPayables as $inv)
                <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 20px; border-bottom:1px solid #f1f5f9;">
                    <div>
                        <div style="font-size:13.5px; font-weight:500;">{{ $inv->purchaseOrder->supplier->name ?? '-' }}</div>
                        <div style="font-size:12px; color:var(--text-secondary);">{{ $inv->invoice_number }} · Jatuh tempo: {{ $inv->due_date->format('d/m/Y') }}</div>
                    </div>
                    <div style="font-weight:600; font-size:14px; color:var(--text-primary);">
                        Rp {{ number_format($inv->outstanding_amount, 0, ',', '.') }}
                    </div>
                </div>
                @empty
                <div style="padding:24px; text-align:center; color:var(--text-secondary); font-size:13.5px;">
                    <i class="fa-solid fa-check-circle" style="display:block; font-size:24px; color:var(--success); margin-bottom:8px;"></i>
                    Tidak ada hutang jatuh tempo
                </div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
var salesChartData = @json($salesChart);
var months = Object.keys(salesChartData);
var values = Object.values(salesChartData);

var options = {
    series: [{ name: 'Penjualan', data: values }],
    chart: { type: 'area', height: 280, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
    colors: ['#6366f1'],
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 } },
    stroke: { curve: 'smooth', width: 3 },
    xaxis: { categories: months, labels: { style: { colors: '#64748b', fontSize: '12px' } } },
    yaxis: { labels: { style: { colors: '#64748b' }, formatter: (v) => 'Rp ' + (v/1000000).toFixed(1) + 'M' } },
    grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
    dataLabels: { enabled: false },
    tooltip: { y: { formatter: (v) => 'Rp ' + new Intl.NumberFormat('id-ID').format(v) } }
};

if (months.length > 0) {
    var chart = new ApexCharts(document.querySelector("#salesChart"), options);
    chart.render();
} else {
    document.querySelector("#salesChart").innerHTML = '<div style="display:flex; align-items:center; justify-content:center; height:280px; color:#94a3b8; font-size:14px;">Belum ada data penjualan</div>';
}
</script>
@endpush
