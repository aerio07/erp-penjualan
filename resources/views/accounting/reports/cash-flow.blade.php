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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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
    {{-- SECTION 2: PROYEKSI KAS (CASH FLOW FORECAST) --}}
    {{-- ================================================================ --}}
    @php
        $badgeColors = [
            'success' => ['bg' => '#dcfce7', 'text' => '#166534', 'border' => '#16a34a', 'icon' => 'fa-circle-check'],
            'warning' => ['bg' => '#fef9c3', 'text' => '#854d0e', 'border' => '#eab308', 'icon' => 'fa-triangle-exclamation'],
            'danger'  => ['bg' => '#fee2e2', 'text' => '#991b1b', 'border' => '#dc2626', 'icon' => 'fa-circle-xmark'],
        ];
        $bc = $badgeColors[$fcHealthBadge];
    @endphp

    <div class="card" style="margin-top: 10px; border-top: 4px solid var(--primary);">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div>
                <h2 style="font-size: 18px; font-weight: 700; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-chart-line" style="color: var(--primary);"></i>
                    Proyeksi Kas (Cash Flow Forecast)
                </h2>
                <p style="font-size: 12.5px; color: var(--text-secondary); margin: 4px 0 0 0;">
                    Estimasi posisi kas di masa depan berdasarkan piutang & hutang belum lunas — bersih setelah retur.
                </p>
            </div>
            {{-- Periode Selector --}}
            <form method="GET" action="{{ route('accounting.reports.cash-flow') }}" style="display: flex; gap: 8px; align-items: center;">
                <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                <input type="hidden" name="date_to" value="{{ $dateTo }}">
                <select name="forecast_period" class="form-control" style="width: auto; min-width: 220px; font-size: 13px;" onchange="this.form.submit()">
                    @foreach($fcPeriodOptions as $key => $label)
                        <option value="{{ $key }}" {{ $forecastPeriod === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div style="padding: 16px 20px;">
            {{-- Info Banner --}}
            @if(!$fcIsCurrent)
            <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 10px 14px; border-radius: 4px; font-size: 12.5px; color: #92400e; margin-bottom: 16px;">
                <i class="fa-solid fa-triangle-exclamation" style="margin-right: 6px;"></i>
                <strong>Periode Masa Depan:</strong> Saldo Awal merupakan estimasi berantai (saldo aktual + proyeksi piutang/hutang bulan-bulan sebelumnya).
                Angka ini adalah <em>best-case scenario</em> dengan asumsi semua pihak membayar tepat waktu.
            </div>
            @else
            <div style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 10px 14px; border-radius: 4px; font-size: 12.5px; color: #1e40af; margin-bottom: 16px;">
                <i class="fa-solid fa-circle-info" style="margin-right: 6px;"></i>
                <strong>Proyeksi {{ $fcLabel }}:</strong> Saldo Awal menggunakan data jurnal aktual. Estimasi penerimaan dan pengeluaran berdasarkan tagihan belum lunas yang jatuh tempo s/d {{ $fcEnd->format('d/m/Y') }}.
            </div>
            @endif

            {{-- 4 BOXES --}}
            <div class="grid grid-4" style="gap: 14px; margin-bottom: 20px;">

                {{-- BOX 2 — Saldo Awal Kas & Bank --}}
                <div x-data="{ expanded: false }" class="card" style="border-left: 4px solid #0284c7; padding: 14px 16px; margin: 0; cursor: pointer;" @click="expanded = !expanded">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <div style="font-size: 11px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;">
                                Saldo Awal Kas & Bank
                            </div>
                            <div style="font-size: 20px; font-weight: 700; color: #0284c7; margin-top: 4px;">
                                Rp {{ number_format($fcSaldoAwal, 0, ',', '.') }}
                            </div>
                            <div style="font-size: 11px; margin-top: 4px;">
                                <span style="background: {{ $fcIsCurrent ? '#dcfce7' : '#fef9c3' }}; color: {{ $fcIsCurrent ? '#166534' : '#854d0e' }}; padding: 1px 6px; border-radius: 3px; font-weight: 600;">
                                    {{ $fcSaldoAwalLabel }}
                                </span>
                            </div>
                        </div>
                        <span class="material-symbols-outlined" style="font-size: 18px; color: var(--text-secondary); transition: transform 0.2s;" :style="expanded ? 'transform: rotate(180deg)' : ''">expand_more</span>
                    </div>
                    {{-- Breakdown per akun --}}
                    <div x-show="expanded" x-cloak @click.stop style="margin-top: 12px; border-top: 1px solid var(--border); padding-top: 8px;">
                        <div style="font-size: 10px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 4px;">Per Akun</div>
                        @foreach($fcSaldoBreakdown as $akun)
                        <div style="display: flex; justify-content: space-between; padding: 3px 0; font-size: 12px; border-bottom: 1px dashed var(--border);">
                            <span style="color: var(--text-secondary);">{{ $akun->code }} {{ $akun->name }}</span>
                            <span style="font-weight: 600;">Rp {{ number_format($akun->balance, 0, ',', '.') }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- BOX 3 — Est. Penerimaan dari Piutang --}}
                <div x-data="{ expanded: false }" class="card" style="border-left: 4px solid #16a34a; padding: 14px 16px; margin: 0; cursor: pointer;" @click="expanded = !expanded">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <div style="font-size: 11px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;">
                                Est. Penerimaan Piutang
                            </div>
                            <div style="font-size: 20px; font-weight: 700; color: #059669; margin-top: 4px;">
                                + Rp {{ number_format($fcTotalReceivable, 0, ',', '.') }}
                            </div>
                            <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px;">
                                {{ $fcReceivableByCustomer->sum('count') }} nota · {{ $fcReceivableByCustomer->count() }} customer
                            </div>
                        </div>
                        <span class="material-symbols-outlined" style="font-size: 18px; color: var(--text-secondary); transition: transform 0.2s;" :style="expanded ? 'transform: rotate(180deg)' : ''">expand_more</span>
                    </div>
                    {{-- Breakdown per customer → per nota --}}
                    <div x-show="expanded" x-cloak @click.stop style="margin-top: 12px; border-top: 1px solid var(--border); padding-top: 8px;">
                        <div style="font-size: 10px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 4px;">Per Customer</div>
                        @forelse($fcReceivableByCustomer as $cust)
                        <div x-data="{ showNotes: false }" style="margin-bottom: 4px;">
                            <div @click.stop="showNotes = !showNotes" style="display: flex; justify-content: space-between; align-items: center; padding: 4px 0; font-size: 12px; border-bottom: 1px solid var(--border); cursor: pointer;">
                                <div>
                                    <span style="font-weight: 600;">{{ $cust->customer_name }}</span>
                                    <span style="font-size: 10px; color: var(--text-secondary);">({{ $cust->count }})</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 4px;">
                                    <span style="font-weight: 600; color: #059669;">Rp {{ number_format($cust->total, 0, ',', '.') }}</span>
                                    <span class="material-symbols-outlined" style="font-size: 14px; color: var(--text-secondary); transition: transform 0.2s;" :style="showNotes ? 'transform: rotate(180deg)' : ''">expand_more</span>
                                </div>
                            </div>
                            <div x-show="showNotes" x-cloak style="padding-left: 8px; background: #f0fdf4; border-radius: 0 0 4px 4px;">
                                @foreach($cust->invoices as $inv)
                                <div style="display: flex; justify-content: space-between; padding: 3px 4px; font-size: 11px; border-bottom: 1px dashed #bbf7d0;">
                                    <div>
                                        <span style="font-weight: 600;">{{ $inv->invoice_number }}</span>
                                        <span style="color: var(--text-secondary); margin-left: 3px;">JT: {{ \Carbon\Carbon::parse($inv->due_date)->format('d/m/Y') }}</span>
                                        @if($inv->is_overdue)
                                            <span style="background: #fee2e2; color: #991b1b; padding: 0 3px; border-radius: 2px; font-size: 9px; font-weight: 600;">OVERDUE</span>
                                        @endif
                                    </div>
                                    <span style="font-weight: 600; color: #059669;">Rp {{ number_format($inv->outstanding, 0, ',', '.') }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @empty
                        <div style="text-align: center; padding: 8px; font-size: 11px; color: var(--text-secondary);">Tidak ada piutang belum lunas.</div>
                        @endforelse
                    </div>
                </div>

                {{-- BOX 4 — Est. Pengeluaran dari Hutang --}}
                <div x-data="{ expanded: false }" class="card" style="border-left: 4px solid #dc2626; padding: 14px 16px; margin: 0; cursor: pointer;" @click="expanded = !expanded">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <div style="font-size: 11px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;">
                                Est. Pengeluaran Hutang
                            </div>
                            <div style="font-size: 20px; font-weight: 700; color: #dc2626; margin-top: 4px;">
                                - Rp {{ number_format($fcTotalPayable, 0, ',', '.') }}
                            </div>
                            <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px;">
                                {{ $fcPayableBySupplier->sum('count') }} nota · {{ $fcPayableBySupplier->count() }} supplier
                            </div>
                        </div>
                        <span class="material-symbols-outlined" style="font-size: 18px; color: var(--text-secondary); transition: transform 0.2s;" :style="expanded ? 'transform: rotate(180deg)' : ''">expand_more</span>
                    </div>
                    {{-- Breakdown per supplier → per nota --}}
                    <div x-show="expanded" x-cloak @click.stop style="margin-top: 12px; border-top: 1px solid var(--border); padding-top: 8px;">
                        <div style="font-size: 10px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 4px;">Per Supplier</div>
                        @forelse($fcPayableBySupplier as $supp)
                        <div x-data="{ showNotes: false }" style="margin-bottom: 4px;">
                            <div @click.stop="showNotes = !showNotes" style="display: flex; justify-content: space-between; align-items: center; padding: 4px 0; font-size: 12px; border-bottom: 1px solid var(--border); cursor: pointer;">
                                <div>
                                    <span style="font-weight: 600;">{{ $supp->supplier_name }}</span>
                                    <span style="font-size: 10px; color: var(--text-secondary);">({{ $supp->count }})</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 4px;">
                                    <span style="font-weight: 600; color: #dc2626;">Rp {{ number_format($supp->total, 0, ',', '.') }}</span>
                                    <span class="material-symbols-outlined" style="font-size: 14px; color: var(--text-secondary); transition: transform 0.2s;" :style="showNotes ? 'transform: rotate(180deg)' : ''">expand_more</span>
                                </div>
                            </div>
                            <div x-show="showNotes" x-cloak style="padding-left: 8px; background: #fef2f2; border-radius: 0 0 4px 4px;">
                                @foreach($supp->invoices as $inv)
                                <div style="display: flex; justify-content: space-between; padding: 3px 4px; font-size: 11px; border-bottom: 1px dashed #fecaca;">
                                    <div>
                                        <span style="font-weight: 600;">{{ $inv->invoice_number }}</span>
                                        <span style="color: var(--text-secondary); margin-left: 3px;">JT: {{ \Carbon\Carbon::parse($inv->due_date)->format('d/m/Y') }}</span>
                                        @if($inv->is_overdue)
                                            <span style="background: #fee2e2; color: #991b1b; padding: 0 3px; border-radius: 2px; font-size: 9px; font-weight: 600;">OVERDUE</span>
                                        @endif
                                    </div>
                                    <span style="font-weight: 600; color: #dc2626;">Rp {{ number_format($inv->outstanding, 0, ',', '.') }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @empty
                        <div style="text-align: center; padding: 8px; font-size: 11px; color: var(--text-secondary);">Tidak ada hutang belum lunas.</div>
                        @endforelse
                    </div>
                </div>

                {{-- BOX 5 — Saldo Akhir + Health Badge --}}
                <div class="card" style="border-left: 4px solid {{ $bc['border'] }}; padding: 14px 16px; margin: 0;">
                    <div style="font-size: 11px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;">
                        Saldo Akhir Estimasi
                    </div>
                    <div style="font-size: 20px; font-weight: 700; color: {{ $bc['text'] }}; margin-top: 4px;">
                        Rp {{ number_format($fcSaldoAkhir, 0, ',', '.') }}
                    </div>
                    <div style="margin-top: 6px;">
                        <span style="display: inline-flex; align-items: center; gap: 4px; background: {{ $bc['bg'] }}; color: {{ $bc['text'] }}; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; border: 1px solid {{ $bc['border'] }};">
                            <i class="fa-solid {{ $bc['icon'] }}" style="font-size: 11px;"></i>
                            {{ $fcHealthLabel }}
                        </span>
                    </div>
                    <div style="font-size: 10px; color: var(--text-secondary); margin-top: 6px;">
                        = Awal + Penerimaan − Pengeluaran
                    </div>
                </div>
            </div>

            {{-- Formula Breakdown Table --}}
            <div class="table-responsive" style="margin: 0; border: 1px solid var(--border); border-radius: 6px; overflow: hidden;">
                <table class="erp-table" style="margin: 0; width: 100%;">
                    <thead>
                        <tr style="background: #f8fafc;">
                            <th>Komponen</th>
                            <th style="text-align: right; width: 180px;">Jumlah</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-medium">
                                <i class="fa-solid fa-wallet" style="color: #0284c7; margin-right: 6px;"></i>Saldo Awal Kas & Bank
                            </td>
                            <td style="text-align: right; font-weight: 600; color: #0284c7;">Rp {{ number_format($fcSaldoAwal, 0, ',', '.') }}</td>
                            <td style="font-size: 12px; color: var(--text-secondary);">{{ $fcSaldoAwalLabel }} — {{ $cashAccountsList->count() }} akun aktif</td>
                        </tr>
                        <tr>
                            <td class="font-medium">
                                <i class="fa-solid fa-plus" style="color: #059669; margin-right: 6px;"></i>Est. Penerimaan Piutang
                            </td>
                            <td style="text-align: right; font-weight: 600; color: #059669;">+ Rp {{ number_format($fcTotalReceivable, 0, ',', '.') }}</td>
                            <td style="font-size: 12px; color: var(--text-secondary);">{{ $fcReceivableByCustomer->sum('count') }} invoice (JT s/d {{ $fcEnd->format('d/m/Y') }})</td>
                        </tr>
                        <tr>
                            <td class="font-medium">
                                <i class="fa-solid fa-minus" style="color: #dc2626; margin-right: 6px;"></i>Est. Pengeluaran Hutang
                            </td>
                            <td style="text-align: right; font-weight: 600; color: #dc2626;">- Rp {{ number_format($fcTotalPayable, 0, ',', '.') }}</td>
                            <td style="font-size: 12px; color: var(--text-secondary);">{{ $fcPayableBySupplier->sum('count') }} invoice (JT s/d {{ $fcEnd->format('d/m/Y') }})</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr style="background: {{ $bc['bg'] }}; border-top: 2px solid {{ $bc['border'] }};">
                            <td style="font-weight: 700; color: {{ $bc['text'] }};">
                                <i class="fa-solid fa-equals" style="margin-right: 6px;"></i>Saldo Akhir
                                <span style="display: inline-flex; align-items: center; gap: 3px; background: {{ $bc['bg'] }}; color: {{ $bc['text'] }}; padding: 1px 8px; border-radius: 10px; font-size: 10px; font-weight: 700; border: 1px solid {{ $bc['border'] }}; margin-left: 6px;">
                                    <i class="fa-solid {{ $bc['icon'] }}" style="font-size: 9px;"></i> {{ $fcHealthLabel }}
                                </span>
                            </td>
                            <td style="text-align: right; font-weight: 700; font-size: 15px; color: {{ $bc['text'] }};">Rp {{ number_format($fcSaldoAkhir, 0, ',', '.') }}</td>
                            <td style="font-size: 12px; color: {{ $bc['text'] }}; font-weight: 600;">
                                @if($fcHealthBadge === 'danger')
                                    Potensi defisit kas — perlu tindakan segera!
                                @elseif($fcHealthBadge === 'warning')
                                    Kas menipis — perhatikan arus kas
                                @else
                                    Posisi kas sehat, buffer likuiditas memadai
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Health Badge Legend --}}
            <div style="margin-top: 12px; font-size: 11px; display: flex; flex-wrap: wrap; gap: 14px; color: var(--text-secondary);">
                <span style="display: flex; align-items: center; gap: 3px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #16a34a; display: inline-block;"></span>
                    <strong>Sehat:</strong> ≥ 20% pengeluaran
                </span>
                <span style="display: flex; align-items: center; gap: 3px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #eab308; display: inline-block;"></span>
                    <strong>Waspada:</strong> 0–20%
                </span>
                <span style="display: flex; align-items: center; gap: 3px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #dc2626; display: inline-block;"></span>
                    <strong>Defisit:</strong> < 0
                </span>
            </div>
        </div>
    </div>
</div>
@endsection
