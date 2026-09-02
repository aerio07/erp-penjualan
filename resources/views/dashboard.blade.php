@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Overview')

@section('content')
<div class="flex flex-col gap-6">

    <!-- ========================================================= -->
    <!-- 1. ALERT BARS (Action Items & Early Warnings) -->
    <!-- ========================================================= -->
    <div class="flex flex-col gap-3">
        {{-- PO Waiting Approval (Khusus Admin) --}}
        @if(auth()->user()->isAdmin() && isset($alerts['po_waiting_approval']) && $alerts['po_waiting_approval'] > 0)
        <div class="bg-[#FBEBD2] border-l-4 border-[#92640B] p-3.5 rounded-lg flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3 text-[#92640B]">
                <span class="material-symbols-outlined text-[20px]">warning</span>
                <span class="font-medium text-sm"><strong>{{ $alerts['po_waiting_approval'] }} Purchase Order</strong> menunggu approval limit otorisasi</span>
            </div>
            <a href="{{ route('approvals.index') }}" class="bg-[#03193c] text-white px-3.5 py-1.5 rounded hover:bg-[#1b2e52] transition-colors text-xs font-bold shadow-sm">
                Review Approval &rarr;
            </a>
        </div>
        @endif

        {{-- Piutang Mendekati Jatuh Tempo --}}
        @if(isset($alerts['due_receivables']) && $alerts['due_receivables'] > 0)
        <div class="bg-[#e0f2fe] border-l-4 border-[#0284c7] p-3.5 rounded-lg flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3 text-[#0369a1]">
                <span class="material-symbols-outlined text-[20px]">notifications_active</span>
                <span class="font-medium text-sm">
                    <strong>{{ $alerts['due_receivables'] }} Invoice Piutang</strong> (Rp {{ number_format($totalUpcomingReceivables, 0, ',', '.') }}) jatuh tempo dalam 7 hari ke depan
                </span>
            </div>
            <a href="{{ route('accounting.reports.receivables-upcoming') }}" class="bg-[#0284c7] text-white px-3.5 py-1.5 rounded hover:bg-[#0369a1] transition-colors text-xs font-bold shadow-sm">
                Tagih Sekarang &rarr;
            </a>
        </div>
        @endif

        {{-- Hutang Mendekati Jatuh Tempo --}}
        @if(isset($alerts['due_payables']) && $alerts['due_payables'] > 0)
        <div class="bg-[#fee2e2] border-l-4 border-[#dc2626] p-3.5 rounded-lg flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3 text-[#b91c1c]">
                <span class="material-symbols-outlined text-[20px]">schedule</span>
                <span class="font-medium text-sm">
                    <strong>{{ $alerts['due_payables'] }} Invoice Hutang Supplier</strong> (Rp {{ number_format($totalUpcomingPayables, 0, ',', '.') }}) jatuh tempo dalam 7 hari ke depan
                </span>
            </div>
            <a href="{{ route('accounting.reports.payables-upcoming') }}" class="bg-[#dc2626] text-white px-3.5 py-1.5 rounded hover:bg-[#b91c1c] transition-colors text-xs font-bold shadow-sm">
                Alokasi Bayar &rarr;
            </a>
        </div>
        @endif

        {{-- Stok Kritis & Karantina --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @if(isset($alerts['low_stock']) && $alerts['low_stock'] > 0)
            <div class="bg-[#fff7ed] border-l-4 border-[#ea580c] p-3 rounded-lg flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2.5 text-[#c2410c]">
                    <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                    <span class="font-medium text-xs">Stok Kritis: <strong>{{ $alerts['low_stock'] }} Produk</strong> menipis</span>
                </div>
                <a href="{{ route('inventory.stock-summary') }}" class="text-xs text-[#c2410c] font-bold hover:underline">
                    Lihat Stok &rarr;
                </a>
            </div>
            @endif

            @if(isset($alerts['quarantine_pending']) && $alerts['quarantine_pending'] > 0)
            <div class="bg-[#faf5ff] border-l-4 border-[#9333ea] p-3 rounded-lg flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2.5 text-[#7e22ce]">
                    <span class="material-symbols-outlined text-[18px]">healing</span>
                    <span class="font-medium text-xs">Karantina: <strong>{{ $alerts['quarantine_pending'] }} Produk</strong> rusak perlu disposisi</span>
                </div>
                <a href="{{ route('inventory.dispositions.index') }}" class="text-xs text-[#7e22ce] font-bold hover:underline">
                    Disposisi &rarr;
                </a>
            </div>
            @endif
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- 2. STAT CARDS (4 Main Financial KPIs) -->
    <!-- ========================================================= -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Total Piutang -->
        <div class="bg-white rounded-lg p-5 border-l-4 border-[#0284c7] shadow-sm flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition-shadow border border-[#E2E8F0]">
            <div class="flex justify-between items-start mb-2">
                <span class="text-[#44474e] text-xs font-bold uppercase tracking-wider font-sans">Total Piutang Berjalan</span>
                <span class="material-symbols-outlined text-[#0284c7] text-[20px]">account_balance_wallet</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold text-[#0e1b35] tabular-nums font-sans">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</span>
            </div>
            <div class="flex items-center justify-between mt-3 pt-2 border-t border-[#f1f5f9]">
                <span class="text-[11px] text-[#0284c7] font-semibold truncate max-w-[140px]">
                    {{ $alerts['due_receivables'] }} due ≤ 7 hari
                </span>
                <a href="{{ route('accounting.reports.receivables-upcoming') }}" class="text-xs text-[#0284c7] font-bold hover:underline">Jatuh Tempo &rarr;</a>
            </div>
        </div>

        <!-- Card 2: Total Hutang -->
        <div class="bg-white rounded-lg p-5 border-l-4 border-[#dc2626] shadow-sm flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition-shadow border border-[#E2E8F0]">
            <div class="flex justify-between items-start mb-2">
                <span class="text-[#44474e] text-xs font-bold uppercase tracking-wider font-sans">Total Hutang Supplier</span>
                <span class="material-symbols-outlined text-[#dc2626] text-[20px]">receipt_long</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold text-[#0e1b35] tabular-nums font-sans">Rp {{ number_format($totalHutang, 0, ',', '.') }}</span>
            </div>
            <div class="flex items-center justify-between mt-3 pt-2 border-t border-[#f1f5f9]">
                <span class="text-[11px] text-[#dc2626] font-semibold truncate max-w-[140px]">
                    {{ $alerts['due_payables'] }} due ≤ 7 hari
                </span>
                <a href="{{ route('accounting.reports.payables-upcoming') }}" class="text-xs text-[#dc2626] font-bold hover:underline">Jatuh Tempo &rarr;</a>
            </div>
        </div>

        <!-- Card 3: Total Laba (Bulan Ini) -->
        <div class="bg-white rounded-lg p-5 border-l-4 border-[#16a34a] shadow-sm flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition-shadow border border-[#E2E8F0]">
            <div class="flex justify-between items-start mb-2">
                <span class="text-[#44474e] text-xs font-bold uppercase tracking-wider font-sans">Laba Bersih (Bulan Ini)</span>
                <span class="material-symbols-outlined text-[#16a34a] text-[20px]">payments</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold tabular-nums font-sans {{ $labaBulanIni >= 0 ? 'text-[#166534]' : 'text-[#B91C1C]' }}">
                    Rp {{ number_format($labaBulanIni, 0, ',', '.') }}
                </span>
            </div>
            <div class="flex items-center justify-between mt-3 pt-2 border-t border-[#f1f5f9]">
                <span class="text-[11px] text-[#166534] font-semibold">
                    Net Income
                </span>
                <a href="{{ route('accounting.reports.gross-profit') }}" class="text-xs text-[#16a34a] font-bold hover:underline">Tren Margin &rarr;</a>
            </div>
        </div>

        <!-- Card 4: Kas & Bank -->
        <div class="bg-white rounded-lg p-5 border-l-4 border-[#7e22ce] shadow-sm flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition-shadow border border-[#E2E8F0]">
            <div class="flex justify-between items-start mb-2">
                <span class="text-[#44474e] text-xs font-bold uppercase tracking-wider font-sans">Kas & Bank Tersedia</span>
                <span class="material-symbols-outlined text-[#7e22ce] text-[20px]">account_balance</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold text-[#0e1b35] tabular-nums font-sans">Rp {{ number_format($saldoKas, 0, ',', '.') }}</span>
            </div>
            <div class="flex items-center justify-between mt-3 pt-2 border-t border-[#f1f5f9]">
                <span class="text-[11px] text-[#7e22ce] font-semibold">
                    Likuiditas Riil
                </span>
                <a href="{{ route('accounting.reports.cash-flow') }}" class="text-xs text-[#7e22ce] font-bold hover:underline">Forecast 30H &rarr;</a>
            </div>
        </div>

    </div>

    <!-- ========================================================= -->
    <!-- 3. CHARTS GRID (Tren Penjualan & Top 5 Produk) -->
    <!-- ========================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Tren Penjualan 30 Hari (2 Cols) -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-6 flex flex-col border border-[#E2E8F0]">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h2 class="font-bold text-[#0e1b35] text-lg font-sans">Tren Penjualan 30 Hari Terakhir</h2>
                    <p class="text-xs text-[#44474e]">Total nominal Sales Invoice per hari</p>
                </div>
                <span class="px-2.5 py-1 bg-[#03193c] text-white text-xs font-bold rounded">30 Hari</span>
            </div>
            <div class="flex-1 relative w-full min-h-[260px]">
                <div id="salesTrendChart" class="w-full h-full"></div>
            </div>
        </div>

        <!-- Top 5 Produk Terlaris (1 Col) -->
        <div class="bg-white rounded-lg shadow-sm p-6 flex flex-col border border-[#E2E8F0]">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h2 class="font-bold text-[#0e1b35] text-base font-sans">Top 5 Produk Terlaris</h2>
                    <p class="text-xs text-[#44474e]">Kuantitas terjual 30 hari terakhir</p>
                </div>
                <a href="{{ route('sales.reports.recap-by-product') }}" class="text-xs text-[#03193c] font-bold hover:underline">Rekap &rarr;</a>
            </div>
            <div class="flex-1 relative w-full min-h-[260px] flex items-center justify-center">
                @if(!empty($topProduk['series']) && array_sum($topProduk['series']) > 0)
                    <div id="topProductChart" class="w-full h-full"></div>
                @else
                    <div class="text-center text-slate-400 text-xs py-8">
                        <span class="material-symbols-outlined text-3xl mb-1 text-slate-300">pie_chart</span>
                        <p>Belum ada data penjualan 30 hari ini</p>
                    </div>
                @endif
            </div>
        </div>

    </div>

    <!-- ========================================================= -->
    <!-- 4. OPERATIONAL DASHBOARD: JATUH TEMPO & LOGISTIK QUEUE -->
    <!-- ========================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" x-data="{ upcomingTab: 'receivables' }">

        <!-- Kolom Kiri: Tagihan Mendekati Jatuh Tempo (≤ 7 Hari) -->
        <div class="bg-white rounded-lg shadow-sm flex flex-col border border-[#E2E8F0] overflow-hidden">
            <div class="p-4 border-b border-[#E2E8F0] bg-slate-50 flex flex-wrap justify-between items-center gap-2">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#0e1b35] text-[20px]">calendar_clock</span>
                    <h2 class="font-bold text-[#0e1b35] text-base font-sans">Jatuh Tempo (≤ 7 Hari)</h2>
                </div>
                {{-- Tabs Toggle --}}
                <div class="flex bg-slate-200/80 p-0.5 rounded text-xs font-semibold">
                    <button type="button" @click="upcomingTab = 'receivables'" 
                            :class="upcomingTab === 'receivables' ? 'bg-white text-[#0284c7] shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2.5 py-1 rounded transition-all">
                        Piutang ({{ $upcomingReceivables->count() }})
                    </button>
                    <button type="button" @click="upcomingTab = 'payables'" 
                            :class="upcomingTab === 'payables' ? 'bg-white text-[#dc2626] shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2.5 py-1 rounded transition-all">
                        Hutang ({{ $upcomingPayables->count() }})
                    </button>
                </div>
            </div>

            {{-- Tab Content: Piutang --}}
            <div x-show="upcomingTab === 'receivables'" class="flex-1 overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-3">No. Invoice</th>
                            <th class="py-2.5 px-3">Customer</th>
                            <th class="py-2.5 px-3 text-center">Jatuh Tempo</th>
                            <th class="py-2.5 px-3 text-right">Sisa Tagihan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-sans">
                        @forelse($upcomingReceivables as $inv)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-2.5 px-3">
                                <a href="{{ route('sales.invoices.show', $inv->id) }}" class="font-bold text-[#0284c7] hover:underline">
                                    {{ $inv->invoice_number }}
                                </a>
                            </td>
                            <td class="py-2.5 px-3 font-medium text-slate-800 truncate max-w-[140px]">
                                {{ $inv->salesOrder?->customer?->name ?? 'Customer Umum' }}
                            </td>
                            <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $inv->days_remaining <= 3 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $inv->days_remaining == 0 ? 'Hari Ini' : $inv->days_remaining . ' hr lagi' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-right font-bold text-[#0284c7] whitespace-nowrap">
                                Rp {{ number_format($inv->outstanding_amount, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-400">
                                <span class="material-symbols-outlined text-2xl text-emerald-500 mb-1 block">check_circle</span>
                                Tidak ada piutang jatuh tempo dalam 7 hari ke depan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($upcomingReceivables->isNotEmpty())
                <div class="p-2.5 bg-slate-50 border-t border-slate-200 text-right">
                    <a href="{{ route('accounting.reports.receivables-upcoming') }}" class="text-xs text-[#0284c7] font-bold hover:underline">
                        Lihat Semua Piutang Jatuh Tempo &rarr;
                    </a>
                </div>
                @endif
            </div>

            {{-- Tab Content: Hutang --}}
            <div x-show="upcomingTab === 'payables'" x-cloak class="flex-1 overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-3">No. Invoice</th>
                            <th class="py-2.5 px-3">Supplier</th>
                            <th class="py-2.5 px-3 text-center">Jatuh Tempo</th>
                            <th class="py-2.5 px-3 text-right">Sisa Hutang</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-sans">
                        @forelse($upcomingPayables as $inv)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-2.5 px-3">
                                <a href="{{ route('purchase.invoices.show', $inv->id) }}" class="font-bold text-[#dc2626] hover:underline">
                                    {{ $inv->invoice_number }}
                                </a>
                            </td>
                            <td class="py-2.5 px-3 font-medium text-slate-800 truncate max-w-[140px]">
                                {{ $inv->purchaseOrder?->supplier?->name ?? 'Supplier Umum' }}
                            </td>
                            <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $inv->days_remaining <= 3 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $inv->days_remaining == 0 ? 'Hari Ini' : $inv->days_remaining . ' hr lagi' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-right font-bold text-[#dc2626] whitespace-nowrap">
                                Rp {{ number_format($inv->outstanding_amount, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-400">
                                <span class="material-symbols-outlined text-2xl text-emerald-500 mb-1 block">check_circle</span>
                                Tidak ada hutang supplier jatuh tempo dalam 7 hari ke depan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($upcomingPayables->isNotEmpty())
                <div class="p-2.5 bg-slate-50 border-t border-slate-200 text-right">
                    <a href="{{ route('accounting.reports.payables-upcoming') }}" class="text-xs text-[#dc2626] font-bold hover:underline">
                        Lihat Semua Hutang Jatuh Tempo &rarr;
                    </a>
                </div>
                @endif
            </div>
        </div>

        <!-- Kolom Kanan: Status Antrian Operasional & Stok Menipis -->
        <div class="bg-white rounded-lg shadow-sm flex flex-col border border-[#E2E8F0] overflow-hidden">
            <div class="p-4 border-b border-[#E2E8F0] bg-slate-50 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#0e1b35] text-[20px]">conveyor_belt</span>
                    <h2 class="font-bold text-[#0e1b35] text-base font-sans">Antrian Operasional & Logistik</h2>
                </div>
                <a href="{{ route('inventory.stock-summary') }}" class="text-xs text-[#03193c] font-bold hover:underline">Stok &rarr;</a>
            </div>

            {{-- 3 Quick Operational Metric Badges --}}
            <div class="p-4 grid grid-cols-3 gap-3 border-b border-slate-100 bg-white">
                <a href="{{ route('sales.orders.index') }}" class="p-2.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-sky-50 hover:border-sky-300 transition-all flex flex-col items-center text-center">
                    <span class="text-lg font-bold text-sky-700 font-sans">{{ $operationalQueues['so_ready_to_ship'] ?? 0 }}</span>
                    <span class="text-[11px] font-semibold text-slate-600 mt-0.5">SO Butuh Dikirim</span>
                </a>
                <a href="{{ route('purchase.orders.index') }}" class="p-2.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-indigo-50 hover:border-indigo-300 transition-all flex flex-col items-center text-center">
                    <span class="text-lg font-bold text-indigo-700 font-sans">{{ $operationalQueues['po_waiting_receipt'] ?? 0 }}</span>
                    <span class="text-[11px] font-semibold text-slate-600 mt-0.5">PO Menunggu Masuk</span>
                </a>
                <a href="{{ route('purchase.demands.index') }}" class="p-2.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-rose-50 hover:border-rose-300 transition-all flex flex-col items-center text-center">
                    <span class="text-lg font-bold text-rose-700 font-sans">{{ $operationalQueues['backorder_count'] ?? 0 }}</span>
                    <span class="text-[11px] font-semibold text-slate-600 mt-0.5">Backorder Defisit</span>
                </a>
            </div>

            {{-- Cuplikan 5 Produk Stok Kritis --}}
            <div class="p-3 bg-slate-50/50 border-b border-slate-100 text-xs font-bold text-slate-700 flex justify-between items-center">
                <span>Daftar Stok Kritis (Perlu Reorder)</span>
                <span class="text-[11px] text-slate-500 font-normal">Min. Stock Alert</span>
            </div>
            <div class="flex-1 overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="py-2 px-3">Produk</th>
                            <th class="py-2 px-3 text-center">Sisa Stok</th>
                            <th class="py-2 px-3 text-center">Min. Stok</th>
                            <th class="py-2 px-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-sans">
                        @forelse($lowStockProducts as $prod)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-2.5 px-3">
                                <div class="font-bold text-slate-900">{{ $prod->name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $prod->sku }}</div>
                            </td>
                            <td class="py-2.5 px-3 text-center font-bold text-rose-600">
                                {{ $prod->current_stock }} {{ $prod->unit }}
                            </td>
                            <td class="py-2.5 px-3 text-center text-slate-500">
                                {{ $prod->min_stock }} {{ $prod->unit }}
                            </td>
                            <td class="py-2.5 px-3 text-right">
                                <a href="{{ route('purchase.orders.create') }}" class="px-2 py-1 bg-sky-600 text-white font-bold rounded text-[10px] hover:bg-sky-700 transition-colors">
                                    + PO
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-slate-400">
                                <span class="material-symbols-outlined text-2xl text-emerald-500 mb-1 block">check_circle</span>
                                Seluruh stok produk saat ini dalam batas aman.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ========================================================= -->
    <!-- 5. RECENT ACTIVITIES (Riwayat Transaksi Terbaru) -->
    <!-- ========================================================= -->
    <div class="bg-white rounded-lg shadow-sm flex flex-col overflow-hidden border border-[#E2E8F0]">
        <div class="p-4 border-b border-[#E2E8F0] bg-slate-50 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[#0e1b35] text-[20px]">history</span>
                <h2 class="font-bold text-[#0e1b35] text-base font-sans">Recent Activities (Aktivitas Transaksi Terbaru)</h2>
            </div>
            <span class="text-xs font-bold text-[#44474e]">7 Terkini</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 text-xs">
                    <tr>
                        <th class="py-2.5 px-4">Tipe & Dokumen</th>
                        <th class="py-2.5 px-4">Keterangan</th>
                        <th class="py-2.5 px-4 text-right">Nilai Transaksi</th>
                        <th class="py-2.5 px-4 text-center">Status</th>
                        <th class="py-2.5 px-4 text-right">Waktu</th>
                    </tr>
                </thead>
                <tbody class="font-sans text-[#0e1b35] text-xs">
                    @forelse($aktivitas as $act)
                    <tr class="border-b border-[#E2E8F0] hover:bg-[#F5F6F8] transition-colors">
                        <td class="py-3 px-4 border-l-[3px]" style="border-color: {{ $act['color'] }};">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">{{ $act['type'] }}</span>
                            <a href="{{ $act['url'] }}" class="text-[#03193c] hover:underline text-xs font-bold">{{ $act['ref'] }}</a>
                        </td>
                        <td class="py-3 px-4 text-[#44474e] text-xs">
                            {{ $act['desc'] }}
                        </td>
                        <td class="py-3 px-4 text-right tabular-nums text-xs font-semibold">
                            @if($act['amount'] !== null)
                                Rp {{ number_format($act['amount'], 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <x-status-badge :status="$act['status']" />
                        </td>
                        <td class="py-3 px-4 text-right text-slate-400 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($act['created_at'])->diffForHumans() }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-[#44474e] text-xs">
                            Belum ada aktivitas transaksi terbaru.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Chart Tren Penjualan
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
            gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 }
        },
        stroke: { curve: 'smooth', width: 2.5 },
        xaxis: {
            categories: salesTrendData.categories || [],
            labels: {
                rotate: -45,
                rotateAlways: false,
                hideOverlappingLabels: true,
                style: { colors: '#64748b', fontSize: '10px' }
            }
        },
        yaxis: {
            labels: {
                style: { colors: '#64748b', fontSize: '10px' },
                formatter: (v) => 'Rp ' + (v / 1000000).toFixed(1) + ' jt'
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

    // 2. Chart Top 5 Produk Terlaris (Donut)
    var topProdukData = @json($topProduk);
    var topProductChartEl = document.querySelector("#topProductChart");
    if (topProductChartEl && topProdukData.series && topProdukData.series.length > 0) {
        var donutOptions = {
            series: topProdukData.series || [],
            labels: topProdukData.labels || [],
            chart: {
                type: 'donut',
                height: 260,
                fontFamily: 'Inter, sans-serif'
            },
            colors: ['#0284c7', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'],
            legend: {
                position: 'bottom',
                fontSize: '11px',
                labels: { colors: '#475569' }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val, opts) {
                    return opts.w.config.series[opts.seriesIndex] + ' pcs';
                }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val + ' pcs terjual';
                    }
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '60%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total Terjual',
                                fontSize: '11px',
                                color: '#64748b',
                                formatter: function (w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0) + ' pcs';
                                }
                            }
                        }
                    }
                }
            }
        };
        var topProductChart = new ApexCharts(topProductChartEl, donutOptions);
        topProductChart.render();
    }
});
</script>
@endpush
