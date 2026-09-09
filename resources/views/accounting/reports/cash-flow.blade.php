@extends('layouts.app')
@section('title', 'Laporan Arus Kas')
@section('page-title', 'Laporan Arus Kas')

@section('content')
<div class="animate-in flex flex-col gap-6">
    <div class="page-header">
        <div>
            <h1>Laporan Arus Kas</h1>
            <p>Pelacakan rinci sumber penerimaan dan pengeluaran kas operasional perusahaan</p>
        </div>
    </div>

    {{-- ================================================================ --}}
    {{-- SECTION 1: ARUS KAS HISTORIS --}}
    {{-- ================================================================ --}}

    {{-- Filter Form --}}
    <div class="card mb-0">
        <div class="card-body p-0">
            <form method="GET" action="{{ route('accounting.reports.cash-flow') }}" class="flex flex-col sm:flex-row flex-wrap sm:items-end gap-3">
                {{-- Preserve forecast_period --}}
                <input type="hidden" name="forecast_period" value="{{ $forecastPeriod }}">
                <div class="w-full sm:w-48">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
                </div>
                <div class="w-full sm:w-48">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
                </div>
                <div class="w-full sm:w-auto">
                    <button type="submit" class="btn btn-primary w-full sm:w-auto">
                        <i class="fa-solid fa-filter"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Stats Cards Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="stat-card border-l-4 border-[#0284c7]">
            <div class="stat-icon" style="background:#e0f2fe; color:#0284c7;">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Saldo Kas Awal</div>
                <div class="stat-value">Rp {{ number_format($openingBalance, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card border-l-4 border-[#16a34a]">
            <div class="stat-icon" style="background:#d1fae5; color:#059669;">
                <i class="fa-solid fa-circle-arrow-down"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Total Kas Masuk</div>
                <div class="stat-value" style="color:var(--success);">+ Rp {{ number_format($totalInflow, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card border-l-4 border-[#dc2626]">
            <div class="stat-icon" style="background:#fee2e2; color:#dc2626;">
                <i class="fa-solid fa-circle-arrow-up"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Total Kas Keluar</div>
                <div class="stat-value" style="color:var(--danger);">- Rp {{ number_format($totalOutflow, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card border-l-4 border-[#7e22ce]">
            <div class="stat-icon" style="background:#f3e8ff; color:#7e22ce;">
                <i class="fa-solid fa-sack-dollar"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Saldo Kas Akhir</div>
                <div class="stat-value" style="color:var(--primary);">Rp {{ number_format($closingBalance, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    {{-- Details Inflow & Outflow --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Kas Masuk --}}
        <div class="card mb-0">
            <div class="card-header border-l-4 border-emerald-500 pl-3">
                <h3 class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-down text-emerald-600"></i> Penerimaan Kas (Inflows)
                </h3>
                <span class="font-bold text-emerald-600">Rp {{ number_format($totalInflow, 0, ',', '.') }}</span>
            </div>
            <div class="table-responsive mb-0">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Sumber Dana</th>
                            <th style="text-align:right;">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inflows as $source => $amount)
                        <tr>
                            <td class="font-medium">{{ $source }}</td>
                            <td style="text-align:right;" class="font-bold text-emerald-600">
                                Rp {{ number_format($amount, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="text-center py-6 text-slate-400 font-sans">
                                Tidak ada penerimaan kas pada periode ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Kas Keluar --}}
        <div class="card mb-0">
            <div class="card-header border-l-4 border-rose-500 pl-3">
                <h3 class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-up text-rose-600"></i> Pengeluaran Kas (Outflows)
                </h3>
                <span class="font-bold text-rose-600">Rp {{ number_format($totalOutflow, 0, ',', '.') }}</span>
            </div>
            <div class="table-responsive mb-0">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Tujuan Pengeluaran</th>
                            <th style="text-align:right;">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($outflows as $target => $amount)
                        <tr>
                            <td class="font-medium">{{ $target }}</td>
                            <td style="text-align:right;" class="font-bold text-rose-600">
                                Rp {{ number_format($amount, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="text-center py-6 text-slate-400 font-sans">
                                Tidak ada pengeluaran kas pada periode ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ================================================================ --}}
    {{-- ================================================================ --}}
    {{-- SECTION 2: PROYEKSI KAS (CASH FLOW FORECAST) --}}
    {{-- ================================================================ --}}
    @php
        $badgeColors = [
            'success' => ['bg' => '#dcfce7', 'text' => '#166534', 'border' => '#16a34a', 'icon' => 'fa-circle-check'],
            'warning' => ['bg' => '#fef9c3', 'text' => '#854d0e', 'border' => '#eab308', 'icon' => 'fa-triangle-exclamation'],
            'danger'  => ['bg' => '#fee2e2', 'text' => '#991b1b', 'border' => '#dc2626', 'icon' => 'fa-circle-xmark'],
        ];
        $bc = $badgeColors[$fcHealthBadge];

        $recCountTotal   = $fcReceivableByCustomer->sum('count');
        $recCountCurrent = $fcReceivableByCustomer->sum('count_current');
        $recCountPrior   = $fcReceivableByCustomer->sum('count_prior');

        $payCountTotal   = $fcPayableBySupplier->sum('count');
        $payCountCurrent = $fcPayableBySupplier->sum('count_current');
        $payCountPrior   = $fcPayableBySupplier->sum('count_prior');
    @endphp

    <div x-data="{
        activeBox: 5,
        searchQuery: '',
        filterTab: 'all',
        isAllOpen: false,
        openGroups: {},

        toggleGroup(id) {
            if (this.isAllOpen) {
                this.openGroups[id] = false;
            } else {
                this.openGroups[id] = !this.openGroups[id];
            }
        },
        isGroupOpen(id) {
            if (this.searchQuery.trim() !== '') return true;
            if (this.isAllOpen) return true;
            return !!this.openGroups[id];
        },
        toggleAll() {
            this.isAllOpen = !this.isAllOpen;
            this.openGroups = {};
        },
        selectBox(boxNum) {
            this.activeBox = boxNum;
            this.searchQuery = '';
            this.filterTab = 'all';
            this.isAllOpen = false;
            this.openGroups = {};
        },
        matchesParty(name, code, invArray) {
            if (!this.searchQuery.trim()) return true;
            const q = this.searchQuery.toLowerCase();
            if (name.toLowerCase().includes(q) || (code && code.toLowerCase().includes(q))) return true;
            return invArray.some(inv => inv.toLowerCase().includes(q));
        },
        matchesInvoice(classification, invNum, partyName) {
            if (this.filterTab === 'current' && classification !== 'current') return false;
            if (this.filterTab === 'prior' && classification !== 'prior') return false;
            if (!this.searchQuery.trim()) return true;
            const q = this.searchQuery.toLowerCase();
            return invNum.toLowerCase().includes(q) || partyName.toLowerCase().includes(q);
        }
    }" class="card mt-2 border-t-4 border-[var(--primary)] mb-0">
        {{-- Header & Selector Periode (Anti-Overlap) --}}
        <div class="card-header flex flex-col md:flex-row md:items-center justify-between gap-4 py-4 px-5">
            <div class="flex-1 min-w-0">
                <h2 class="text-base sm:text-lg font-bold text-slate-800 m-0 flex items-center gap-2">
                    <i class="fa-solid fa-chart-line text-[var(--primary)]"></i>
                    <span>Proyeksi Kas (Cash Flow Forecast)</span>
                </h2>
                <p class="text-xs text-slate-500 m-0 mt-1">
                    Estimasi posisi kas periode <strong class="text-slate-700">{{ $fcLabel }}</strong> berdasarkan piutang & hutang belum lunas — bersih setelah retur.
                </p>
            </div>
            {{-- Periode Selector --}}
            <div class="shrink-0 flex items-center gap-2 self-start md:self-auto">
                <form method="GET" action="{{ route('accounting.reports.cash-flow') }}" class="flex items-center gap-2 m-0 flex-wrap">
                    <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                    <input type="hidden" name="date_to" value="{{ $dateTo }}">
                    <label class="text-xs font-semibold text-slate-600 whitespace-nowrap m-0">Pilih Periode:</label>
                    <select name="forecast_period" class="form-control text-xs w-auto min-w-[240px]" style="height: 36px;" onchange="this.form.submit()">
                        @foreach($fcPeriodOptions as $key => $label)
                            <option value="{{ $key }}" {{ $forecastPeriod === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        <div class="p-4 sm:p-5">
            {{-- Info Banner --}}
            @if(!$fcIsCurrent)
            <div class="bg-amber-50 border-l-4 border-amber-500 p-3 rounded text-xs text-amber-900 mb-4 flex items-start gap-2.5">
                <i class="fa-solid fa-triangle-exclamation text-amber-600 text-sm mt-0.5 shrink-0"></i>
                <div>
                    <strong>Periode Masa Depan ({{ $fcLabel }}):</strong> Saldo Awal merupakan estimasi berantai dari proyeksi saldo akhir bulan sebelumnya.
                    Tagihan lampau yang belum lunas (overdue) tetap ditampilkan lengkap di breakdown dengan penanda khusus, dan tidak dihitung ganda pada Saldo Akhir.
                </div>
            </div>
            @else
            <div class="bg-blue-50 border-l-4 border-blue-500 p-3 rounded text-xs text-blue-900 mb-4 flex items-start gap-2.5">
                <i class="fa-solid fa-circle-info text-blue-600 text-sm mt-0.5 shrink-0"></i>
                <div>
                    <strong>Bulan Berjalan ({{ $fcLabel }}):</strong> Saldo Awal menggunakan saldo riil jurnal akun kas & bank s/d hari ini. Estimasi penerimaan & pengeluaran mencakup seluruh tagihan belum lunas yang jatuh tempo s/d {{ $fcEnd->format('d/m/Y') }}.
                </div>
            </div>
            @endif

            {{-- 4 INTERACTIVE SUMMARY BOXES (Identik, Seragam, Anti-Overlap, Responsif) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">

                {{-- BOX 2 — Saldo Awal Kas & Bank --}}
                <div @click="selectBox(2)"
                     class="card p-4 m-0 cursor-pointer transition-all duration-150 border-l-4 border-[#0284c7] select-none flex flex-col justify-between h-full group"
                     :class="activeBox === 2 ? 'ring-2 ring-[#0284c7] shadow-md bg-sky-50/70 border-sky-500' : 'hover:border-sky-400 hover:shadow-xs bg-white'">
                    <div>
                        {{-- Row 1: Header Category & Icon --}}
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block leading-tight">
                                Saldo Awal Kas & Bank
                            </span>
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs shrink-0 shadow-xs"
                                 style="background: #e0f2fe; color: #0284c7;">
                                <i class="fa-solid fa-wallet"></i>
                            </div>
                        </div>

                        {{-- Row 2: Value --}}
                        <div class="text-xl sm:text-2xl font-bold font-mono tracking-tight text-[#0284c7] my-1">
                            Rp {{ number_format($fcSaldoAwal, 0, ',', '.') }}
                        </div>

                        {{-- Row 3: Sub-info --}}
                        <div class="min-h-[44px] flex flex-col justify-center text-xs">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="badge text-[10px] font-bold px-2 py-0.5 rounded {{ $fcIsCurrent ? 'bg-emerald-100 text-emerald-800 border border-emerald-200/60' : 'bg-amber-100 text-amber-800 border border-amber-200/60' }}">
                                    {{ $fcSaldoAwalLabel }}
                                </span>
                            </div>
                            <span class="text-[11px] text-slate-400 mt-1 block truncate">
                                {{ $cashAccountsList->count() }} Akun Kas & Bank Aktif
                            </span>
                        </div>
                    </div>

                    {{-- Row 4: Status Footer --}}
                    <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs font-semibold">
                        <span class="flex items-center gap-1.5" :class="activeBox === 2 ? 'text-[#0284c7]' : 'text-slate-400'">
                            <i class="fa-solid" :class="activeBox === 2 ? 'fa-circle-check text-sky-600' : 'fa-circle text-slate-300 text-[8px]'"></i>
                            <span x-text="activeBox === 2 ? 'Aktif Ditampilkan' : 'Lihat Rincian Akun'"></span>
                        </span>
                        <i class="fa-solid fa-chevron-right text-[10px] transition-transform" :class="activeBox === 2 ? 'rotate-90 text-sky-600' : 'text-slate-300'"></i>
                    </div>
                </div>

                {{-- BOX 3 — Est. Penerimaan dari Piutang --}}
                <div @click="selectBox(3)"
                     class="card p-4 m-0 cursor-pointer transition-all duration-150 border-l-4 border-[#16a34a] select-none flex flex-col justify-between h-full group"
                     :class="activeBox === 3 ? 'ring-2 ring-[#16a34a] shadow-md bg-emerald-50/70 border-emerald-500' : 'hover:border-emerald-400 hover:shadow-xs bg-white'">
                    <div>
                        {{-- Row 1: Header Category & Icon --}}
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block leading-tight">
                                Est. Penerimaan Piutang
                            </span>
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs shrink-0 shadow-xs"
                                 style="background: #dcfce7; color: #16a34a;">
                                <i class="fa-solid fa-circle-arrow-down"></i>
                            </div>
                        </div>

                        {{-- Row 2: Value --}}
                        <div class="text-xl sm:text-2xl font-bold font-mono tracking-tight text-[#059669] my-1">
                            + Rp {{ number_format($fcTotalReceivablePeriod, 0, ',', '.') }}
                        </div>

                        {{-- Row 3: Sub-info --}}
                        <div class="min-h-[44px] flex flex-col justify-center text-xs">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="badge text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-200/60">
                                    {{ $recCountTotal }} Nota ({{ $fcReceivableByCustomer->count() }} Pihak)
                                </span>
                            </div>
                            <span class="text-[11px] text-slate-400 mt-1 block truncate">
                                Jatuh tempo s/d {{ $fcEnd->format('d/m/Y') }}
                            </span>
                        </div>
                    </div>

                    {{-- Row 4: Status Footer --}}
                    <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs font-semibold">
                        <span class="flex items-center gap-1.5" :class="activeBox === 3 ? 'text-[#16a34a]' : 'text-slate-400'">
                            <i class="fa-solid" :class="activeBox === 3 ? 'fa-circle-check text-emerald-600' : 'fa-circle text-slate-300 text-[8px]'"></i>
                            <span x-text="activeBox === 3 ? 'Aktif Ditampilkan' : 'Lihat Rincian Customer'"></span>
                        </span>
                        <i class="fa-solid fa-chevron-right text-[10px] transition-transform" :class="activeBox === 3 ? 'rotate-90 text-emerald-600' : 'text-slate-300'"></i>
                    </div>
                </div>

                {{-- BOX 4 — Est. Pengeluaran dari Hutang --}}
                <div @click="selectBox(4)"
                     class="card p-4 m-0 cursor-pointer transition-all duration-150 border-l-4 border-[#dc2626] select-none flex flex-col justify-between h-full group"
                     :class="activeBox === 4 ? 'ring-2 ring-[#dc2626] shadow-md bg-rose-50/70 border-rose-500' : 'hover:border-rose-400 hover:shadow-xs bg-white'">
                    <div>
                        {{-- Row 1: Header Category & Icon --}}
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block leading-tight">
                                Est. Pengeluaran Hutang
                            </span>
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs shrink-0 shadow-xs"
                                 style="background: #fee2e2; color: #dc2626;">
                                <i class="fa-solid fa-circle-arrow-up"></i>
                            </div>
                        </div>

                        {{-- Row 2: Value --}}
                        <div class="text-xl sm:text-2xl font-bold font-mono tracking-tight text-[#dc2626] my-1">
                            - Rp {{ number_format($fcTotalPayablePeriod, 0, ',', '.') }}
                        </div>

                        {{-- Row 3: Sub-info --}}
                        <div class="min-h-[44px] flex flex-col justify-center text-xs">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="badge text-[10px] font-bold px-2 py-0.5 rounded bg-rose-100 text-rose-800 border border-rose-200/60">
                                    {{ $payCountTotal }} Nota ({{ $fcPayableBySupplier->count() }} Pihak)
                                </span>
                            </div>
                            <span class="text-[11px] text-slate-400 mt-1 block truncate">
                                Jatuh tempo s/d {{ $fcEnd->format('d/m/Y') }}
                            </span>
                        </div>
                    </div>

                    {{-- Row 4: Status Footer --}}
                    <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs font-semibold">
                        <span class="flex items-center gap-1.5" :class="activeBox === 4 ? 'text-[#dc2626]' : 'text-slate-400'">
                            <i class="fa-solid" :class="activeBox === 4 ? 'fa-circle-check text-rose-600' : 'fa-circle text-slate-300 text-[8px]'"></i>
                            <span x-text="activeBox === 4 ? 'Aktif Ditampilkan' : 'Lihat Rincian Supplier'"></span>
                        </span>
                        <i class="fa-solid fa-chevron-right text-[10px] transition-transform" :class="activeBox === 4 ? 'rotate-90 text-rose-600' : 'text-slate-300'"></i>
                    </div>
                </div>

                {{-- BOX 5 — Saldo Akhir Proyeksi (Default) --}}
                <div @click="selectBox(5)"
                     class="card p-4 m-0 cursor-pointer transition-all duration-150 border-l-4 select-none flex flex-col justify-between h-full group"
                     style="border-left-color: {{ $bc['border'] }};"
                     :class="activeBox === 5 ? 'ring-2 ring-purple-600 shadow-md bg-purple-50/70 border-purple-500' : 'hover:border-purple-400 hover:shadow-xs bg-white'">
                    <div>
                        {{-- Row 1: Header Category & Icon --}}
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block leading-tight">
                                Saldo Akhir Proyeksi
                            </span>
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs shrink-0 shadow-xs"
                                 style="background: #f3e8ff; color: #7e22ce;">
                                <i class="fa-solid fa-sack-dollar"></i>
                            </div>
                        </div>

                        {{-- Row 2: Value --}}
                        <div class="text-xl sm:text-2xl font-bold font-mono tracking-tight my-1" style="color: {{ $bc['text'] }};">
                            Rp {{ number_format($fcSaldoAkhir, 0, ',', '.') }}
                        </div>

                        {{-- Row 3: Sub-info --}}
                        <div class="min-h-[44px] flex flex-col justify-center text-xs">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="badge text-[10px] font-bold px-2 py-0.5 rounded"
                                      style="background: {{ $bc['bg'] }}; color: {{ $bc['text'] }}; border: 1px solid {{ $bc['border'] }};">
                                    <i class="fa-solid {{ $bc['icon'] }} mr-1"></i>{{ $fcHealthLabel }}
                                </span>
                            </div>
                            <span class="text-[11px] text-slate-400 mt-1 block truncate">
                                = Saldo Awal + Piutang − Hutang
                            </span>
                        </div>
                    </div>

                    {{-- Row 4: Status Footer --}}
                    <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs font-semibold">
                        <span class="flex items-center gap-1.5" :class="activeBox === 5 ? 'text-purple-700' : 'text-slate-400'">
                            <i class="fa-solid" :class="activeBox === 5 ? 'fa-circle-check text-purple-600' : 'fa-circle text-slate-300 text-[8px]'"></i>
                            <span x-text="activeBox === 5 ? 'Aktif Ditampilkan' : 'Lihat Ringkasan Formula'"></span>
                        </span>
                        <i class="fa-solid fa-chevron-right text-[10px] transition-transform" :class="activeBox === 5 ? 'rotate-90 text-purple-600' : 'text-slate-300'"></i>
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- DYNAMIC MASTER-DETAIL PANEL --}}
            {{-- ============================================================ --}}

            {{-- PANEL 5: FORMULA SALDO AKHIR & ANALISIS KESEHATAN KAS (DEFAULT) --}}
            <div x-show="activeBox === 5" x-cloak class="space-y-4">
                <div class="flex items-center justify-between border-b pb-2 mb-3">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2 m-0">
                        <i class="fa-solid fa-calculator text-purple-600"></i>
                        Rekapitulasi Formula & Analisis Posisi Kas — {{ $fcLabel }}
                    </h3>
                    <span class="badge bg-purple-100 text-purple-800 text-[10px] font-bold px-2 py-0.5 rounded">Default View</span>
                </div>

                <div class="table-responsive border border-slate-200 rounded-lg overflow-hidden">
                    <table class="erp-table w-full mb-0">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 text-xs font-semibold">
                                <th>Komponen Perhitungan</th>
                                <th style="text-align: right; width: 220px;">Nominal (Rp)</th>
                                <th>Keterangan & Rincian</th>
                                <th style="text-align: center; width: 170px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <tr>
                                <td class="font-medium text-slate-800">
                                    <i class="fa-solid fa-wallet text-[#0284c7] mr-2"></i>Saldo Awal Kas & Bank
                                </td>
                                <td style="text-align: right;" class="font-bold text-[#0284c7]">
                                    Rp {{ number_format($fcSaldoAwal, 0, ',', '.') }}
                                </td>
                                <td class="text-xs text-slate-500">
                                    {{ $fcSaldoAwalLabel }} — bersumber dari {{ $cashAccountsList->count() }} akun kas & bank aktif
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" @click="selectBox(2)" class="btn btn-secondary btn-sm flex items-center gap-1.5 mx-auto" style="height: 32px; font-size: 12px; padding: 0 12px; border-radius: 6px;">
                                        <span>Rincian Akun</span>
                                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td class="font-medium text-slate-800">
                                    <i class="fa-solid fa-plus text-[#059669] mr-2"></i>Est. Penerimaan Piutang
                                </td>
                                <td style="text-align: right;" class="font-bold text-[#059669]">
                                    + Rp {{ number_format($fcTotalReceivablePeriod, 0, ',', '.') }}
                                </td>
                                <td class="text-xs text-slate-500">
                                    @if(!$fcIsCurrent)
                                        Tagihan jatuh tempo pada <strong>{{ $fcLabel }}</strong>.
                                        Total akumulasi s/d {{ $fcEnd->format('d/m/Y') }} adalah <strong>Rp {{ number_format($fcTotalReceivableCumulative, 0, ',', '.') }}</strong>
                                        @if($fcTotalReceivablePrior > 0)
                                            (Rp {{ number_format($fcTotalReceivablePrior, 0, ',', '.') }} nota lampau sudah terserap dalam Saldo Awal).
                                        @endif
                                    @else
                                        Penerimaan dari {{ $recCountTotal }} invoice jatuh tempo s/d {{ $fcEnd->format('d/m/Y') }}.
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" @click="selectBox(3)" class="btn btn-secondary btn-sm flex items-center gap-1.5 mx-auto" style="height: 32px; font-size: 12px; padding: 0 12px; border-radius: 6px;">
                                        <span>Rincian Customer</span>
                                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td class="font-medium text-slate-800">
                                    <i class="fa-solid fa-minus text-[#dc2626] mr-2"></i>Est. Pengeluaran Hutang
                                </td>
                                <td style="text-align: right;" class="font-bold text-[#dc2626]">
                                    - Rp {{ number_format($fcTotalPayablePeriod, 0, ',', '.') }}
                                </td>
                                <td class="text-xs text-slate-500">
                                    @if(!$fcIsCurrent)
                                        Kewajiban jatuh tempo pada <strong>{{ $fcLabel }}</strong>.
                                        Total akumulasi s/d {{ $fcEnd->format('d/m/Y') }} adalah <strong>Rp {{ number_format($fcTotalPayableCumulative, 0, ',', '.') }}</strong>
                                        @if($fcTotalPayablePrior > 0)
                                            (Rp {{ number_format($fcTotalPayablePrior, 0, ',', '.') }} kewajiban lampau sudah terserap dalam Saldo Awal).
                                        @endif
                                    @else
                                        Kewajiban atas {{ $payCountTotal }} invoice jatuh tempo s/d {{ $fcEnd->format('d/m/Y') }}.
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" @click="selectBox(4)" class="btn btn-secondary btn-sm flex items-center gap-1.5 mx-auto" style="height: 32px; font-size: 12px; padding: 0 12px; border-radius: 6px;">
                                        <span>Rincian Supplier</span>
                                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr style="background: {{ $bc['bg'] }}; border-top: 2px solid {{ $bc['border'] }};">
                                <td class="font-bold" style="color: {{ $bc['text'] }};">
                                    <i class="fa-solid fa-equals mr-2"></i>Saldo Kas Akhir Estimasi
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border ml-2"
                                          style="background: {{ $bc['bg'] }}; color: {{ $bc['text'] }}; border-color: {{ $bc['border'] }};">
                                        <i class="fa-solid {{ $bc['icon'] }}"></i> {{ $fcHealthLabel }}
                                    </span>
                                </td>
                                <td style="text-align: right; font-size: 16px; color: {{ $bc['text'] }};" class="font-bold">
                                    Rp {{ number_format($fcSaldoAkhir, 0, ',', '.') }}
                                </td>
                                <td colspan="2" class="text-xs font-medium" style="color: {{ $bc['text'] }};">
                                    @if($fcHealthBadge === 'danger')
                                        <strong>Peringatan Defisit:</strong> Proyeksi kas berada di bawah nol. Segera lakukan penagihan intensif dan jadwalkan ulang pelunasan hutang non-kritis.
                                    @elseif($fcHealthBadge === 'warning')
                                        <strong>Kas Menipis:</strong> Proyeksi kas akhir kurang dari batas aman likuiditas (20% pengeluaran). Pantau arus kas masuk harian secara ketat.
                                    @else
                                        <strong>Kas Sehat:</strong> Buffer likuiditas aman untuk mendukung operasional dan melunasi kewajiban jatuh tempo.
                                    @endif
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Health Badge Legend --}}
                <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 pt-1">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                        <strong>Sehat:</strong> Saldo akhir ≥ 20% kewajiban pengeluaran
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span>
                        <strong>Waspada:</strong> Saldo akhir 0 – 20% pengeluaran
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-600 inline-block"></span>
                        <strong>Defisit:</strong> Saldo akhir < 0
                    </span>
                </div>
            </div>

            {{-- PANEL 2: SALDO KAS & BANK PER AKUN --}}
            <div x-show="activeBox === 2" x-cloak class="space-y-4">
                {{-- Header Panel 2 --}}
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b pb-3 mb-3">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2 m-0">
                        <i class="fa-solid fa-wallet text-[#0284c7]"></i>
                        <span>Rincian Saldo Akun Kas & Bank ({{ $fcSaldoAwalLabel }})</span>
                    </h3>
                    <button type="button" @click="selectBox(5)" class="btn btn-secondary btn-sm flex items-center gap-1.5 shrink-0 self-start md:self-auto" style="height: 34px; font-size: 12px; padding: 0 12px; border-radius: 6px;">
                        <i class="fa-solid fa-arrow-left text-[10px]"></i>
                        <span>Kembali ke Ringkasan</span>
                    </button>
                </div>

                @if(!$fcIsCurrent)
                <div class="bg-amber-50 p-2.5 rounded border border-amber-200 text-xs text-amber-900">
                    <i class="fa-solid fa-info-circle mr-1 text-amber-700"></i>
                    Angka per akun di bawah merupakan saldo buku historis sebelum periode <strong>{{ $fcLabel }}</strong>. Nilai total saldo awal proyeksi berantai di atas dihitung dari estimasi saldo akhir bulan sebelumnya.
                </div>
                @endif

                <div class="table-responsive border border-slate-200 rounded-lg overflow-hidden">
                    <table class="erp-table w-full mb-0">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 text-xs font-semibold">
                                <th>Kode Akun</th>
                                <th>Nama Akun Rekening</th>
                                <th style="text-align: right; width: 220px;">Saldo Kas (Rp)</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            @php $sumSaldoBreakdown = 0; @endphp
                            @forelse($fcSaldoBreakdown as $akun)
                            @php $sumSaldoBreakdown += $akun->balance; @endphp
                            <tr>
                                <td class="font-mono text-slate-600 font-medium">{{ $akun->code }}</td>
                                <td class="font-semibold text-slate-800">{{ $akun->name }}</td>
                                <td style="text-align: right;" class="font-mono font-bold {{ $akun->balance < 0 ? 'text-rose-600' : 'text-slate-800' }}">
                                    Rp {{ number_format($akun->balance, 0, ',', '.') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center py-6 text-slate-400">Tidak ada akun kas/bank yang terdaftar.</td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-100 font-bold border-t border-slate-300 text-sm">
                                <td colspan="2">Total Saldo Kas & Bank Aktif</td>
                                <td style="text-align: right;" class="text-[#0284c7]">
                                    Rp {{ number_format($sumSaldoBreakdown, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- PANEL 3 & 4: GROUPED MASTER-DETAIL PIUTANG (BOX 3) & HUTANG (BOX 4) --}}
            <div x-show="activeBox === 3 || activeBox === 4" x-cloak class="space-y-4">
                {{-- Header Panel --}}
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b pb-3 mb-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="w-6 h-6 rounded-md flex items-center justify-center text-xs shrink-0"
                                  :style="activeBox === 3 ? 'background: #dcfce7; color: #16a34a;' : 'background: #fee2e2; color: #dc2626;'">
                                <i class="fa-solid" :class="activeBox === 3 ? 'fa-circle-arrow-down' : 'fa-circle-arrow-up'"></i>
                            </span>
                            <h3 class="text-sm font-bold text-slate-800 m-0">
                                <span x-show="activeBox === 3">Rincian Estimasi Penerimaan Piutang</span>
                                <span x-show="activeBox === 4">Rincian Estimasi Pengeluaran Hutang</span>
                            </h3>
                            <span class="badge text-[10px] font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">
                                Jatuh Tempo s/d {{ $fcEnd->format('d/m/Y') }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 m-0 mt-1">
                            Menampilkan seluruh tagihan belum lunas (termasuk tagihan tertunggak lampau).
                        </p>
                    </div>
                    <button type="button" @click="selectBox(5)" class="btn btn-secondary btn-sm flex items-center gap-1.5 shrink-0 self-start md:self-auto" style="height: 34px; font-size: 12px; padding: 0 12px; border-radius: 6px;">
                        <i class="fa-solid fa-arrow-left text-[10px]"></i>
                        <span>Kembali ke Ringkasan</span>
                    </button>
                </div>

                {{-- Toolbar Filter, Search, dan Toggle Expand --}}
                <div class="card p-3 mb-4 bg-white border border-slate-200 rounded-lg shadow-xs" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;">
                    {{-- Left: Search & Filter Tabs --}}
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 12px; flex: 1 1 auto; min-width: 0;">
                        {{-- Search Box --}}
                        <div style="position: relative; flex: 1 1 220px; min-width: 200px; max-width: 340px;">
                            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; pointer-events: none; z-index: 2;"></i>
                            <input type="text"
                                   x-model="searchQuery"
                                   placeholder="Cari pihak / invoice..."
                                   class="form-control search-input"
                                   style="padding-left: 2.75rem !important; padding-right: 2rem !important; height: 38px !important; font-size: 13px !important; border-radius: 6px !important; width: 100% !important; margin: 0 !important; border: 1px solid #cbd5e1 !important;">
                            <button type="button"
                                    x-show="searchQuery.trim().length > 0"
                                    @click="searchQuery = ''"
                                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 0; z-index: 2;"
                                    class="hover:text-slate-600">
                                <i class="fa-solid fa-xmark text-xs"></i>
                            </button>
                        </div>

                        {{-- Filter Tabs --}}
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" @click="filterTab = 'all'"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                                    :class="filterTab === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                    x-text="'Semua Nota (' + (activeBox === 3 ? '{{ $recCountTotal }}' : '{{ $payCountTotal }}') + ')'">
                            </button>
                            <button type="button" @click="filterTab = 'current'"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                                    :class="filterTab === 'current'
                                        ? (activeBox === 3 ? 'bg-emerald-600 text-white shadow-xs' : 'bg-rose-600 text-white shadow-xs')
                                        : (activeBox === 3 ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 hover:bg-rose-100')"
                                    x-text="'Periode Ini (' + (activeBox === 3 ? '{{ $recCountCurrent }}' : '{{ $payCountCurrent }}') + ')'">
                            </button>
                            @if(!$fcIsCurrent || ($recCountPrior > 0 || $payCountPrior > 0))
                            <button type="button" @click="filterTab = 'prior'"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                                    :class="filterTab === 'prior' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 text-amber-700 hover:bg-amber-100'"
                                    x-text="'Bawaan (' + (activeBox === 3 ? '{{ $recCountPrior }}' : '{{ $payCountPrior }}') + ')'">
                            </button>
                            @endif
                        </div>
                    </div>

                    {{-- Right: Expand / Collapse All Button --}}
                    <div style="flex: 0 0 auto;">
                        <button type="button"
                                @click="toggleAll()"
                                class="btn btn-secondary btn-sm"
                                style="height: 36px; padding: 0 14px; font-size: 12px; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; border-radius: 6px;">
                            <i class="fa-solid text-[10px]" :class="isAllOpen ? 'fa-compress' : 'fa-expand'"></i>
                            <span x-text="isAllOpen ? 'Tutup Semua' : 'Buka Semua'"></span>
                        </button>
                    </div>
                </div>

                {{-- GROUPED TABLE UNTUK PIUTANG (activeBox === 3) --}}
                <div x-show="activeBox === 3" class="space-y-2">
                    @forelse($fcReceivableByCustomer as $cust)
                        @php
                            $groupId = 'c_' . $cust->customer_id;
                            $invListJson = json_encode($cust->invoices->pluck('invoice_number')->all());
                        @endphp
                        <div x-show="matchesParty('{{ addslashes($cust->customer_name) }}', '{{ $cust->customer_code }}', {{ $invListJson }})"
                             class="border border-slate-200 rounded-lg overflow-hidden bg-white shadow-xs">
                            {{-- Master Row / Customer Header --}}
                            <div @click="toggleGroup('{{ $groupId }}')"
                                 class="p-3 bg-slate-50 hover:bg-slate-100/90 cursor-pointer flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 select-none transition-colors">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-6 h-6 flex items-center justify-center rounded-md bg-white border border-slate-200 text-slate-500 transition-transform duration-200"
                                          :class="isGroupOpen('{{ $groupId }}') ? 'rotate-90 bg-emerald-50 text-emerald-700 border-emerald-300' : ''">
                                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                    </span>
                                    <div>
                                        <span class="font-bold text-slate-800 text-sm">{{ $cust->customer_name }}</span>
                                        <span class="text-xs text-slate-400 font-mono ml-1">({{ $cust->customer_code }})</span>
                                    </div>
                                    <span class="badge text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        {{ $cust->count }} Invoice
                                    </span>
                                </div>
                                <div class="flex items-center gap-4 self-end sm:self-auto">
                                    <div class="text-right">
                                        <div class="text-[11px] text-slate-400 uppercase font-semibold">Total Piutang</div>
                                        <div class="text-sm font-bold text-emerald-700 font-mono">Rp {{ number_format($cust->total, 0, ',', '.') }}</div>
                                    </div>
                                    <span class="text-xs text-slate-400">
                                        <i class="fa-solid" :class="isGroupOpen('{{ $groupId }}') ? 'fa-angle-up' : 'fa-angle-down'"></i>
                                    </span>
                                </div>
                            </div>

                            {{-- Detail Sub-Table (Invoices) --}}
                            <div x-show="isGroupOpen('{{ $groupId }}')" class="p-0 bg-white">
                                <div class="table-responsive m-0">
                                    <table class="erp-table w-full mb-0 text-xs">
                                        <thead>
                                            <tr class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                                                <th style="width: 170px;">No. Invoice</th>
                                                <th style="width: 110px;">Tgl Invoice</th>
                                                <th style="width: 120px;">Tgl Jatuh Tempo</th>
                                                <th>Klasifikasi Periode</th>
                                                <th>Status Keterlambatan</th>
                                                <th style="text-align: right; width: 160px;">Sisa Piutang</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($cust->invoices as $inv)
                                            <tr x-show="matchesInvoice('{{ $inv->classification }}', '{{ $inv->invoice_number }}', '{{ addslashes($cust->customer_name) }}')"
                                                class="hover:bg-emerald-50/40 transition-colors">
                                                <td class="font-mono font-bold text-slate-800">
                                                    {{ $inv->invoice_number }}
                                                </td>
                                                <td class="text-slate-500">
                                                    {{ $inv->invoice_date_formatted ?? \Carbon\Carbon::parse($inv->invoice_date)->format('d/m/Y') }}
                                                </td>
                                                <td class="font-medium text-slate-700">
                                                    {{ $inv->due_date_formatted }}
                                                </td>
                                                <td>
                                                    @if($inv->is_prior)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-900 border border-amber-300">
                                                            <i class="fa-solid fa-clock-rotate-left text-[9px]"></i> Bawaan Saldo Awal
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-900 border border-emerald-300">
                                                            <i class="fa-solid fa-calendar-check text-[9px]"></i> Periode Ini
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($inv->is_overdue)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                                            <i class="fa-solid fa-triangle-exclamation text-[9px]"></i> OVERDUE (+{{ $inv->days_overdue }} hr)
                                                        </span>
                                                    @else
                                                        <span class="text-slate-400 font-medium">Tepat Waktu</span>
                                                    @endif
                                                </td>
                                                <td style="text-align: right;" class="font-mono font-bold text-emerald-700">
                                                    Rp {{ number_format($inv->outstanding, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 bg-slate-50 rounded-lg border border-dashed border-slate-200 text-slate-400 text-sm">
                            <i class="fa-solid fa-circle-check text-2xl mb-2 text-emerald-500"></i>
                            <div>Tidak ada tagihan piutang belum lunas pada periode ini.</div>
                        </div>
                    @endforelse
                </div>

                {{-- GROUPED TABLE UNTUK HUTANG (activeBox === 4) --}}
                <div x-show="activeBox === 4" class="space-y-2">
                    @forelse($fcPayableBySupplier as $supp)
                        @php
                            $groupId = 's_' . $supp->supplier_id;
                            $suppInvListJson = json_encode($supp->invoices->pluck('invoice_number')->all());
                        @endphp
                        <div x-show="matchesParty('{{ addslashes($supp->supplier_name) }}', '{{ $supp->supplier_code }}', {{ $suppInvListJson }})"
                             class="border border-slate-200 rounded-lg overflow-hidden bg-white shadow-xs">
                            {{-- Master Row / Supplier Header --}}
                            <div @click="toggleGroup('{{ $groupId }}')"
                                 class="p-3 bg-slate-50 hover:bg-slate-100/90 cursor-pointer flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 select-none transition-colors">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-6 h-6 flex items-center justify-center rounded-md bg-white border border-slate-200 text-slate-500 transition-transform duration-200"
                                          :class="isGroupOpen('{{ $groupId }}') ? 'rotate-90 bg-rose-50 text-rose-700 border-rose-300' : ''">
                                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                    </span>
                                    <div>
                                        <span class="font-bold text-slate-800 text-sm">{{ $supp->supplier_name }}</span>
                                        <span class="text-xs text-slate-400 font-mono ml-1">({{ $supp->supplier_code }})</span>
                                    </div>
                                    <span class="badge text-[10px] font-bold px-2 py-0.5 rounded bg-rose-100 text-rose-800 border border-rose-200">
                                        {{ $supp->count }} Invoice
                                    </span>
                                </div>
                                <div class="flex items-center gap-4 self-end sm:self-auto">
                                    <div class="text-right">
                                        <div class="text-[11px] text-slate-400 uppercase font-semibold">Total Hutang</div>
                                        <div class="text-sm font-bold text-rose-700 font-mono">Rp {{ number_format($supp->total, 0, ',', '.') }}</div>
                                    </div>
                                    <span class="text-xs text-slate-400">
                                        <i class="fa-solid" :class="isGroupOpen('{{ $groupId }}') ? 'fa-angle-up' : 'fa-angle-down'"></i>
                                    </span>
                                </div>
                            </div>

                            {{-- Detail Sub-Table (Invoices) --}}
                            <div x-show="isGroupOpen('{{ $groupId }}')" class="p-0 bg-white">
                                <div class="table-responsive m-0">
                                    <table class="erp-table w-full mb-0 text-xs">
                                        <thead>
                                            <tr class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                                                <th style="width: 170px;">No. Invoice</th>
                                                <th style="width: 110px;">Tgl Invoice</th>
                                                <th style="width: 120px;">Tgl Jatuh Tempo</th>
                                                <th>Klasifikasi Periode</th>
                                                <th>Status Keterlambatan</th>
                                                <th style="text-align: right; width: 160px;">Sisa Hutang</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($supp->invoices as $inv)
                                            <tr x-show="matchesInvoice('{{ $inv->classification }}', '{{ $inv->invoice_number }}', '{{ addslashes($supp->supplier_name) }}')"
                                                class="hover:bg-rose-50/40 transition-colors">
                                                <td class="font-mono font-bold text-slate-800">
                                                    {{ $inv->invoice_number }}
                                                </td>
                                                <td class="text-slate-500">
                                                    {{ $inv->invoice_date_formatted ?? \Carbon\Carbon::parse($inv->invoice_date)->format('d/m/Y') }}
                                                </td>
                                                <td class="font-medium text-slate-700">
                                                    {{ $inv->due_date_formatted }}
                                                </td>
                                                <td>
                                                    @if($inv->is_prior)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-900 border border-amber-300">
                                                            <i class="fa-solid fa-clock-rotate-left text-[9px]"></i> Bawaan Saldo Awal
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-900 border border-emerald-300">
                                                            <i class="fa-solid fa-calendar-check text-[9px]"></i> Periode Ini
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($inv->is_overdue)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                                            <i class="fa-solid fa-triangle-exclamation text-[9px]"></i> OVERDUE (+{{ $inv->days_overdue }} hr)
                                                        </span>
                                                    @else
                                                        <span class="text-slate-400 font-medium">Tepat Waktu</span>
                                                    @endif
                                                </td>
                                                <td style="text-align: right;" class="font-mono font-bold text-rose-700">
                                                    Rp {{ number_format($inv->outstanding, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 bg-slate-50 rounded-lg border border-dashed border-slate-200 text-slate-400 text-sm">
                            <i class="fa-solid fa-circle-check text-2xl mb-2 text-rose-500"></i>
                            <div>Tidak ada kewajiban hutang belum lunas pada periode ini.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
