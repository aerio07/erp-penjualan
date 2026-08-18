@extends('layouts.app')
@section('title', 'Laporan Laba Rugi')
@section('page-title', 'Laporan Laba Rugi (Profit & Loss)')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Laporan Laba Rugi</h1>
            <p>Ringkasan kinerja keuangan (Pendapatan - Beban) dari jurnal yang diposting</p>
        </div>
    </div>

    {{-- Filter Periode --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-body" style="padding:16px;">
            <form method="GET" action="{{ route('accounting.reports.profit-loss') }}" style="display:flex; gap:12px; align-items:flex-end;">
                <div style="width:140px;">
                    <label class="form-label">Tahun</label>
                    <select name="year" class="form-control" onchange="this.form.submit()">
                        @foreach(range(date('Y')-2, date('Y')+1) as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="width:180px;">
                    <label class="form-label">Bulan</label>
                    <select name="month" class="form-control" onchange="this.form.submit()">
                        <option value="">Semua Bulan (Tahunan)</option>
                        @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 10)) }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="grid grid-4" style="margin-bottom:24px;">
        <div class="stat-card">
            <div class="icon" style="background:#d1fae5; color:#065f46;"><i class="fa-solid fa-arrow-trend-up"></i></div>
            <div class="value">Rp {{ number_format($revenue, 0, ',', '.') }}</div>
            <div class="label">Total Pendapatan</div>
        </div>

        <div class="stat-card">
            <div class="icon" style="background:#fee2e2; color:#991b1b;"><i class="fa-solid fa-boxes-packing"></i></div>
            <div class="value">Rp {{ number_format($cogs, 0, ',', '.') }}</div>
            <div class="label">Total HPP / COGS</div>
        </div>

        <div class="stat-card">
            <div class="icon" style="background:#ede9fe; color:#6d28d9;"><i class="fa-solid fa-chart-pie"></i></div>
            <div class="value">Rp {{ number_format($grossProfit, 0, ',', '.') }}</div>
            <div class="label">Laba Kotor</div>
        </div>

        <div class="stat-card">
            <div class="icon" style="background:{{ $netProfit >= 0 ? '#d1fae5' : '#fee2e2' }}; color:{{ $netProfit >= 0 ? '#065f46' : '#991b1b' }};">
                <i class="fa-solid fa-calculator"></i>
            </div>
            <div class="value" style="color:{{ $netProfit >= 0 ? 'var(--success)' : 'var(--danger)' }};">
                Rp {{ number_format($netProfit, 0, ',', '.') }}
            </div>
            <div class="label">Laba (Rugi) Bersih</div>
        </div>
    </div>

    {{-- Detail Table --}}
    <div class="card">
        <div class="card-header"><h3>Rincian Akun Laba Rugi</h3></div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Kode Akun</th>
                        <th>Nama Akun COA</th>
                        <th style="text-align:right;">Debit</th>
                        <th style="text-align:right;">Kredit</th>
                        <th style="text-align:right;">Saldo Akhir</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Revenue --}}
                    <tr style="background:#f8fafc; font-weight:700;">
                        <td colspan="5" style="color:#065f46;"><i class="fa-solid fa-arrow-trend-up"></i> PENDAPATAN</td>
                    </tr>
                    @forelse($byType->get('revenue', []) as $item)
                    <tr>
                        <td style="font-family:monospace; font-weight:600; padding-left:24px;">{{ $item->code }}</td>
                        <td>{{ $item->name }}</td>
                        <td style="text-align:right;">Rp {{ number_format($item->total_debit, 0, ',', '.') }}</td>
                        <td style="text-align:right;">Rp {{ number_format($item->total_credit, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:600; color:var(--success);">Rp {{ number_format($item->total_credit - $item->total_debit, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center; color:var(--text-secondary); padding-left:24px;">Belum ada entri pendapatan</td></tr>
                    @endforelse

                    {{-- Expenses --}}
                    <tr style="background:#f8fafc; font-weight:700;">
                        <td colspan="5" style="color:#991b1b;"><i class="fa-solid fa-arrow-trend-down"></i> BEBAN & HPP</td>
                    </tr>
                    @forelse($byType->get('expense', []) as $item)
                    <tr>
                        <td style="font-family:monospace; font-weight:600; padding-left:24px;">{{ $item->code }}</td>
                        <td>{{ $item->name }}</td>
                        <td style="text-align:right;">Rp {{ number_format($item->total_debit, 0, ',', '.') }}</td>
                        <td style="text-align:right;">Rp {{ number_format($item->total_credit, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:600; color:var(--danger);">Rp {{ number_format($item->total_debit - $item->total_credit, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center; color:var(--text-secondary); padding-left:24px;">Belum ada entri beban</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
