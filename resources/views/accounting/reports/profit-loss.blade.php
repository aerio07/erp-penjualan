@extends('layouts.app')
@section('title', 'Laporan Laba Rugi')
@section('page-title', 'Laporan Laba Rugi (Profit & Loss)')

@section('content')
<div class="animate-in flex flex-col gap-6">
    <div class="page-header">
        <div>
            <h1>Laporan Laba Rugi (Profit & Loss)</h1>
            <p>Ringkasan kinerja pendapatan, HPP, beban operasional, dan laba bersih perusahaan</p>
        </div>
    </div>

    {{-- Filter Periode --}}
    <div class="card mb-0">
        <div class="card-body p-0">
            <form method="GET" action="{{ route('accounting.reports.profit-loss') }}" class="flex flex-col sm:flex-row flex-wrap sm:items-end gap-3">
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
        <div class="stat-card border-l-4 border-emerald-500">
            <div class="stat-icon" style="background:#d1fae5; color:#065f46;">
                <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Pendapatan Bersih</div>
                <div class="stat-value text-emerald-600">Rp {{ number_format($netRevenue, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card border-l-4 border-rose-500">
            <div class="stat-icon" style="background:#fee2e2; color:#991b1b;">
                <i class="fa-solid fa-boxes-packing"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Total HPP (COGS)</div>
                <div class="stat-value text-rose-600">Rp {{ number_format($totalCogs, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card border-l-4 border-purple-500">
            <div class="stat-icon" style="background:#ede9fe; color:#6d28d9;">
                <i class="fa-solid fa-chart-pie"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Laba Kotor</div>
                <div class="stat-value text-purple-700">Rp {{ number_format($grossProfit, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card border-l-4 {{ $netProfit >= 0 ? 'border-emerald-600' : 'border-rose-600' }}">
            <div class="stat-icon" style="background:{{ $netProfit >= 0 ? '#d1fae5' : '#fee2e2' }}; color:{{ $netProfit >= 0 ? '#065f46' : '#991b1b' }};">
                <i class="fa-solid fa-calculator"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Laba / (Rugi) Bersih</div>
                <div class="stat-value {{ $netProfit >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                    Rp {{ number_format($netProfit, 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Detail Financial Statement Layout --}}
    <div class="card mb-0">
        <div class="card-header">
            <h3>Struktur Laporan Laba Rugi</h3>
        </div>
        <div class="table-responsive mb-0">
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
                        <td colspan="3" class="text-emerald-700"><i class="fa-solid fa-arrow-trend-up"></i> 1. PENDAPATAN OPERASIONAL</td>
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
                        <td style="padding-left:32px;" class="text-rose-600">4-1200 Dikurangi: Retur Penjualan</td>
                        <td style="text-align:right;" class="text-rose-600">(Rp {{ number_format($salesReturn, 0, ',', '.') }})</td>
                        <td></td>
                    </tr>
                    @endif
                    @if($salesDiscount > 0)
                    <tr>
                        <td style="padding-left:32px;" class="text-rose-600">4-1300 Dikurangi: Potongan Penjualan</td>
                        <td style="text-align:right;" class="text-rose-600">(Rp {{ number_format($salesDiscount, 0, ',', '.') }})</td>
                        <td></td>
                    </tr>
                    @endif
                    <tr class="font-bold bg-emerald-50">
                        <td style="padding-left:32px;">TOTAL PENDAPATAN BERSIH:</td>
                        <td></td>
                        <td style="text-align:right;" class="text-emerald-700 text-sm font-bold">Rp {{ number_format($netRevenue, 0, ',', '.') }}</td>
                    </tr>

                    {{-- 2. HPP --}}
                    <tr style="background:#f8fafc; font-weight:700;">
                        <td colspan="3" class="text-rose-700"><i class="fa-solid fa-boxes-packing"></i> 2. BEBAN POKOK PENJUALAN (HPP)</td>
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
                    <tr class="font-bold bg-rose-50">
                        <td style="padding-left:32px;">TOTAL BEBAN POKOK PENJUALAN:</td>
                        <td></td>
                        <td style="text-align:right;" class="text-rose-700 text-sm font-bold">(Rp {{ number_format($totalCogs, 0, ',', '.') }})</td>
                    </tr>

                    {{-- LABA KOTOR --}}
                    <tr class="bg-purple-100 text-base font-extrabold">
                        <td colspan="2" class="text-purple-900">LABA KOTOR (GROSS PROFIT):</td>
                        <td style="text-align:right;" class="text-purple-800">Rp {{ number_format($grossProfit, 0, ',', '.') }}</td>
                    </tr>

                    {{-- 3. BEBAN OPERASIONAL --}}
                    <tr style="background:#f8fafc; font-weight:700;">
                        <td colspan="3" class="text-rose-700"><i class="fa-solid fa-file-invoice-dollar"></i> 3. BEBAN OPERASIONAL & LAIN-LAIN</td>
                    </tr>
                    <tr>
                        <td style="padding-left:32px;">5-1300 Kerugian Persediaan Rusak (Write-off)</td>
                        <td style="text-align:right;">Rp {{ number_format($damagedExpense, 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                    @if($purchaseReturn > 0)
                    <tr>
                        <td style="padding-left:32px;" class="text-emerald-700">5-1200 Pengurang Beban: Retur Pembelian</td>
                        <td style="text-align:right;" class="text-emerald-700">(Rp {{ number_format($purchaseReturn, 0, ',', '.') }})</td>
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
                    <tr class="font-bold bg-rose-50">
                        <td style="padding-left:32px;">TOTAL BEBAN OPERASIONAL:</td>
                        <td></td>
                        <td style="text-align:right;" class="text-rose-700 text-sm font-bold">(Rp {{ number_format($totalOperatingExpense, 0, ',', '.') }})</td>
                    </tr>

                    {{-- LABA/RUGI BERSIH --}}
                    <tr style="background:{{ $netProfit >= 0 ? '#d1fae5' : '#fee2e2' }};" class="text-base font-extrabold border-t-2 border-slate-300">
                        <td colspan="2" style="color:{{ $netProfit >= 0 ? '#065f46' : '#991b1b' }};">LABA / (RUGI) BERSIH:</td>
                        <td style="text-align:right; color:{{ $netProfit >= 0 ? '#166534' : '#991b1b' }}; font-size:1.125rem;">
                            Rp {{ number_format($netProfit, 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
