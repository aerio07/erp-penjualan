@extends('layouts.app')
@section('title', 'Laporan Laba Rugi')
@section('page-title', 'Laporan Laba Rugi (Profit & Loss)')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Laporan Laba Rugi (Profit & Loss)</h1>
            <p>Ringkasan kinerja pendapatan, HPP, beban operasional, dan laba bersih perusahaan</p>
        </div>
    </div>

    {{-- Filter Periode --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-body" style="padding:16px;">
            <form method="GET" action="{{ route('accounting.reports.profit-loss') }}" style="display:flex; gap:12px; align-items:flex-end;">
                <div style="width:160px;">
                    <label class="form-label">Dari Tgl</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
                </div>
                <div style="width:160px;">
                    <label class="form-label">Sampai Tgl</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-4" style="margin-bottom:24px;">
        <div class="stat-card">
            <div class="stat-icon" style="background:#d1fae5; color:#065f46;"><i class="fa-solid fa-arrow-trend-up"></i></div>
            <div class="stat-info">
                <div class="stat-label">Pendapatan Bersih</div>
                <div class="stat-value" style="font-size:16px; color:var(--success);">Rp {{ number_format($netRevenue, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#fee2e2; color:#991b1b;"><i class="fa-solid fa-boxes-packing"></i></div>
            <div class="stat-info">
                <div class="stat-label">Total HPP (COGS)</div>
                <div class="stat-value" style="font-size:16px; color:var(--danger);">Rp {{ number_format($totalCogs, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#ede9fe; color:#6d28d9;"><i class="fa-solid fa-chart-pie"></i></div>
            <div class="stat-info">
                <div class="stat-label">Laba Kotor</div>
                <div class="stat-value" style="font-size:16px; color:#6d28d9;">Rp {{ number_format($grossProfit, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:{{ $netProfit >= 0 ? '#d1fae5' : '#fee2e2' }}; color:{{ $netProfit >= 0 ? '#065f46' : '#991b1b' }};">
                <i class="fa-solid fa-calculator"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Laba / (Rugi) Bersih</div>
                <div class="stat-value" style="font-size:16px; color:{{ $netProfit >= 0 ? 'var(--success)' : 'var(--danger)' }};">
                    Rp {{ number_format($netProfit, 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Detail Financial Statement Layout --}}
    <div class="card">
        <div class="card-header"><h3>Struktur Laporan Laba Rugi</h3></div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Komponen Laporan</th>
                        <th style="text-align:right;">Subtotal (Rp)</th>
                        <th style="text-align:right;">Total (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- 1. PENDAPATAN --}}
                    <tr style="background:#f8fafc; font-weight:700;">
                        <td colspan="3" style="color:var(--success);"><i class="fa-solid fa-arrow-trend-up"></i> 1. PENDAPATAN OPERASIONAL</td>
                    </tr>
                    <tr>
                        <td style="padding-left:32px;">4-1100 Penjualan Barang</td>
                        <td style="text-align:right;">Rp {{ number_format($salesRevenue, 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td style="padding-left:32px;">4-1400 Pendapatan Penjualan Reject</td>
                        <td style="text-align:right;">Rp {{ number_format($rejectRevenue, 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                    @if($otherRevenue > 0)
                    <tr>
                        <td style="padding-left:32px;">4-9100 Pendapatan Lain-lain</td>
                        <td style="text-align:right;">Rp {{ number_format($otherRevenue, 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                    @endif
                    @if($salesReturn > 0)
                    <tr>
                        <td style="padding-left:32px; color:var(--danger);">4-1200 Dikurangi: Retur Penjualan</td>
                        <td style="text-align:right; color:var(--danger);">(Rp {{ number_format($salesReturn, 0, ',', '.') }})</td>
                        <td></td>
                    </tr>
                    @endif
                    @if($salesDiscount > 0)
                    <tr>
                        <td style="padding-left:32px; color:var(--danger);">4-1300 Dikurangi: Potongan Penjualan</td>
                        <td style="text-align:right; color:var(--danger);">(Rp {{ number_format($salesDiscount, 0, ',', '.') }})</td>
                        <td></td>
                    </tr>
                    @endif
                    <tr style="font-weight:700; background:#f0fdf4;">
                        <td style="padding-left:32px;">TOTAL PENDAPATAN BERSIH:</td>
                        <td></td>
                        <td style="text-align:right; color:var(--success); font-size:14.5px;">Rp {{ number_format($netRevenue, 0, ',', '.') }}</td>
                    </tr>

                    {{-- 2. HPP --}}
                    <tr style="background:#f8fafc; font-weight:700;">
                        <td colspan="3" style="color:var(--danger);"><i class="fa-solid fa-boxes-packing"></i> 2. BEBAN POKOK PENJUALAN (HPP)</td>
                    </tr>
                    <tr>
                        <td style="padding-left:32px;">5-1100 HPP Penjualan Utama</td>
                        <td style="text-align:right;">Rp {{ number_format($cogsNormal, 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td style="padding-left:32px;">5-1400 HPP Penjualan Reject</td>
                        <td style="text-align:right;">Rp {{ number_format($cogsReject, 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                    <tr style="font-weight:700; background:#fef2f2;">
                        <td style="padding-left:32px;">TOTAL BEBAN POKOK PENJUALAN:</td>
                        <td></td>
                        <td style="text-align:right; color:var(--danger); font-size:14.5px;">(Rp {{ number_format($totalCogs, 0, ',', '.') }})</td>
                    </tr>

                    {{-- LABA KOTOR --}}
                    <tr style="background:#ede9fe; font-size:15px; font-weight:800;">
                        <td colspan="2">LABA KOTOR (GROSS PROFIT):</td>
                        <td style="text-align:right; color:#6d28d9;">Rp {{ number_format($grossProfit, 0, ',', '.') }}</td>
                    </tr>

                    {{-- 3. BEBAN OPERASIONAL --}}
                    <tr style="background:#f8fafc; font-weight:700;">
                        <td colspan="3" style="color:var(--danger);"><i class="fa-solid fa-file-invoice-dollar"></i> 3. BEBAN OPERASIONAL & LAIN-LAIN</td>
                    </tr>
                    <tr>
                        <td style="padding-left:32px;">5-1300 Kerugian Persediaan Rusak (Write-off)</td>
                        <td style="text-align:right;">Rp {{ number_format($damagedExpense, 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                    @if($purchaseReturn > 0)
                    <tr>
                        <td style="padding-left:32px; color:var(--success);">5-1200 Pengurang Beban: Retur Pembelian</td>
                        <td style="text-align:right; color:var(--success);">(Rp {{ number_format($purchaseReturn, 0, ',', '.') }})</td>
                        <td></td>
                    </tr>
                    @endif
                    @if($otherExpenses > 0)
                    <tr>
                        <td style="padding-left:32px;">Beban Operasional Lainnya (Gaji/Sewa/Listrik/Lain-lain)</td>
                        <td style="text-align:right;">Rp {{ number_format($otherExpenses, 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                    @endif
                    <tr style="font-weight:700; background:#fef2f2;">
                        <td style="padding-left:32px;">TOTAL BEBAN OPERASIONAL:</td>
                        <td></td>
                        <td style="text-align:right; color:var(--danger); font-size:14.5px;">(Rp {{ number_format($totalOperatingExpense, 0, ',', '.') }})</td>
                    </tr>

                    {{-- LABA/RUGI BERSIH --}}
                    <tr style="background:{{ $netProfit >= 0 ? '#d1fae5' : '#fee2e2' }}; font-size:16px; font-weight:800; border-top:2px solid var(--border);">
                        <td colspan="2" style="color:{{ $netProfit >= 0 ? '#065f46' : '#991b1b' }};">LABA / (RUGI) BERSIH:</td>
                        <td style="text-align:right; color:{{ $netProfit >= 0 ? 'var(--success)' : 'var(--danger)' }}; font-size:17px;">
                            Rp {{ number_format($netProfit, 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
