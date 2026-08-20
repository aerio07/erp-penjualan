@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Overview')

@section('content')
<div class="flex flex-col gap-6">

    <!-- ========================================================= -->
    <!-- ALERT BARS (PO Menunggu Approval / Stok Kritis) -->
    <!-- ========================================================= -->
    @if(isset($alerts['po_waiting_approval']) && $alerts['po_waiting_approval'] > 0)
    <div class="bg-[#FBEBD2] border-l-4 border-[#92640B] p-4 rounded-lg flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3 text-[#92640B]">
            <span class="material-symbols-outlined">warning</span>
            <span class="font-medium text-sm">{{ $alerts['po_waiting_approval'] }} Purchase Order Menunggu Approval Limit</span>
        </div>
        <a href="{{ route('approvals.index') }}" class="bg-[#03193c] text-white font-medium px-4 py-2 rounded hover:bg-[#1b2e52] transition-colors text-xs font-bold shadow-sm">
            Review Sekarang
        </a>
    </div>
    @endif

    @if(isset($alerts['low_stock']) && $alerts['low_stock'] > 0)
    <div class="bg-[#FBEBD2] border-l-4 border-[#92640B] p-4 rounded-lg flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3 text-[#92640B]">
            <span class="material-symbols-outlined">inventory_2</span>
            <span class="font-medium text-sm">Stok Kritis: {{ $alerts['low_stock'] }} Produk Perlu Re-order</span>
        </div>
        <a href="{{ route('inventory.stock-summary') }}" class="bg-[#03193c] text-white font-medium px-4 py-2 rounded hover:bg-[#1b2e52] transition-colors text-xs font-bold shadow-sm">
            Lihat Stok
        </a>
    </div>
    @endif

    <!-- ========================================================= -->
    <!-- STAT CARDS GRID (4 Column Tailwind Paper Cards) -->
    <!-- ========================================================= -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Total Piutang -->
        <div class="bg-white rounded-lg p-5 border-l-4 border-[#d97706] shadow-sm flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition-shadow border border-[#E2E8F0]">
            <div class="flex justify-between items-start mb-2">
                <span class="text-[#44474e] text-xs font-bold uppercase tracking-wider font-sans">Total Piutang</span>
                <span class="material-symbols-outlined text-[#44474e] text-[20px]">account_balance_wallet</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold text-[#0e1b35] tabular-nums font-sans">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</span>
            </div>
            <div class="flex items-center justify-between mt-4">
                <span class="flex items-center gap-1 text-[#166534] bg-[#DCFCE3] px-2.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">
                    <span class="material-symbols-outlined text-[14px]">trending_up</span> Sales Receivables
                </span>
                <a href="{{ route('accounting.reports.receivables') }}" class="text-xs text-[#03193c] font-bold hover:underline">Rincian &rarr;</a>
            </div>
        </div>

        <!-- Card 2: Total Hutang -->
        <div class="bg-white rounded-lg p-5 border-l-4 border-[#64748b] shadow-sm flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition-shadow border border-[#E2E8F0]">
            <div class="flex justify-between items-start mb-2">
                <span class="text-[#44474e] text-xs font-bold uppercase tracking-wider font-sans">Total Hutang</span>
                <span class="material-symbols-outlined text-[#44474e] text-[20px]">receipt_long</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold text-[#0e1b35] tabular-nums font-sans">Rp {{ number_format($totalHutang, 0, ',', '.') }}</span>
            </div>
            <div class="flex items-center justify-between mt-4">
                <span class="text-[#6B7280] text-xs font-medium">Due Payables</span>
                <a href="{{ route('accounting.reports.payables') }}" class="text-xs text-[#03193c] font-bold hover:underline">Rincian &rarr;</a>
            </div>
        </div>

        <!-- Card 3: Total Laba -->
        <div class="bg-white rounded-lg p-5 border-l-4 border-[#16a34a] shadow-sm flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition-shadow border border-[#E2E8F0]">
            <div class="flex justify-between items-start mb-2">
                <span class="text-[#44474e] text-xs font-bold uppercase tracking-wider font-sans">Total Laba (Bulan Ini)</span>
                <span class="material-symbols-outlined text-[#44474e] text-[20px]">payments</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold tabular-nums font-sans {{ $labaBulanIni >= 0 ? 'text-[#166534]' : 'text-[#B91C1C]' }}">
                    Rp {{ number_format($labaBulanIni, 0, ',', '.') }}
                </span>
            </div>
            <div class="flex items-center justify-between mt-4">
                <span class="flex items-center gap-1 text-[#166534] bg-[#DCFCE3] px-2.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">
                    <span class="material-symbols-outlined text-[14px]">trending_up</span> Net Income
                </span>
                <a href="{{ route('accounting.reports.profit-loss') }}" class="text-xs text-[#03193c] font-bold hover:underline">Rapor &rarr;</a>
            </div>
        </div>

        <!-- Card 4: Kas & Bank -->
        <div class="bg-white rounded-lg p-5 border-l-4 border-[#03193c] shadow-sm flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition-shadow border border-[#E2E8F0]">
            <div class="flex justify-between items-start mb-2">
                <span class="text-[#44474e] text-xs font-bold uppercase tracking-wider font-sans">Kas & Bank</span>
                <span class="material-symbols-outlined text-[#44474e] text-[20px]">account_balance</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold text-[#0e1b35] tabular-nums font-sans">Rp {{ number_format($saldoKas, 0, ',', '.') }}</span>
            </div>
            <div class="flex items-center justify-between mt-4">
                <span class="text-[#6B7280] text-xs font-medium">Available Liquidity</span>
                <a href="{{ route('accounting.reports.ledger') }}" class="text-xs text-[#03193c] font-bold hover:underline">Buku Besar &rarr;</a>
            </div>
        </div>

    </div>

    <!-- ========================================================= -->
    <!-- CHART & RECENT ACTIVITIES GRID -->
    <!-- ========================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Tren Penjualan (2 Cols) -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-6 flex flex-col border border-[#E2E8F0]">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h2 class="font-bold text-[#0e1b35] text-lg font-sans">Tren Penjualan 30 Hari Terakhir</h2>
                    <p class="text-xs text-[#44474e]">Agregasi Nilai Sales Invoice Harian</p>
                </div>
                <div class="flex gap-1">
                    <span class="px-2.5 py-1 bg-[#03193c] text-white text-xs font-bold rounded">30D</span>
                </div>
            </div>
            <div class="flex-1 relative w-full min-h-[260px]">
                <div id="salesTrendChart" class="w-full h-full"></div>
            </div>
        </div>

        <!-- Recent Activities (1 Col) -->
        <div class="bg-white rounded-lg shadow-sm flex flex-col overflow-hidden border border-[#E2E8F0]">
            <div class="p-4 border-b border-[#E2E8F0] bg-slate-50 flex justify-between items-center">
                <h2 class="font-bold text-[#0e1b35] text-base font-sans">Recent Activities</h2>
                <span class="text-xs font-bold text-[#44474e]">7 Terbaru</span>
            </div>
            <div class="flex-1 overflow-y-auto max-h-[340px]">
                <table class="w-full text-left border-collapse">
                    <tbody class="font-sans text-[#0e1b35]">
                        @forelse($aktivitas as $act)
                        <tr class="border-b border-[#E2E8F0] hover:bg-[#F5F6F8] transition-colors">
                            <td class="py-3 px-4 border-l-[3px]" style="border-color: {{ $act['color'] }};">
                                <a href="{{ $act['url'] }}" class="text-[#03193c] hover:underline text-xs font-bold block">{{ $act['ref'] }}</a>
                                <div class="text-[#44474e] text-[12px] truncate max-w-[150px]">{{ $act['desc'] }}</div>
                            </td>
                            <td class="py-3 px-4 text-right tabular-nums text-xs font-semibold">
                                @if($act['amount'] !== null)
                                    Rp {{ number_format($act['amount'], 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <x-status-badge :status="$act['status']" />
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="py-8 text-center text-[#44474e] text-xs">
                                Belum ada aktivitas transaksi.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var salesTrendData = @json($trenPenjualan);
    var salesOptions = {
        series: [{
            name: 'Total Penjualan',
            data: salesTrendData.data || []
        }],
        chart: {
            type: 'area',
            height: 260,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        colors: ['#03193c'],
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.02 }
        },
        stroke: { curve: 'smooth', width: 2 },
        xaxis: {
            categories: salesTrendData.categories || [],
            labels: {
                rotate: -45,
                rotateAlways: false,
                hideOverlappingLabels: true,
                style: { colors: '#44474e', fontSize: '10px' }
            }
        },
        yaxis: {
            labels: {
                style: { colors: '#44474e', fontSize: '10px' },
                formatter: (v) => 'Rp ' + new Intl.NumberFormat('id-ID').format(v)
            }
        },
        grid: { borderColor: '#E2E8F0', strokeDashArray: 3 },
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
});
</script>
@endpush
