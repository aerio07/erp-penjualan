@extends('layouts.app')
@section('title', 'Neraca (Balance Sheet)')
@section('page-title', 'Neraca Keuangan (Balance Sheet)')

@section('content')
<div class="animate-in flex flex-col gap-6">
    <div class="page-header">
        <div>
            <h1>Neraca Keuangan (Balance Sheet)</h1>
            <p>Posisi Aset, Kewajiban, dan Modal perusahaan pada titik waktu tertentu</p>
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="card mb-0">
        <div class="card-body p-0 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <form method="GET" action="{{ route('accounting.reports.balance-sheet') }}" class="flex flex-col sm:flex-row sm:items-end gap-3 w-full sm:w-auto">
                <div class="w-full sm:w-56">
                    <label class="form-label">Per Tanggal (As of Date)</label>
                    <input type="date" name="as_of_date" value="{{ $asOfDate }}" class="form-control" onchange="this.form.submit()">
                </div>
                <div class="w-full sm:w-auto">
                    <button type="submit" class="btn btn-primary w-full sm:w-auto">
                        <i class="fa-solid fa-filter"></i> Tampilkan
                    </button>
                </div>
            </form>

            <div>
                @if($isBalanced)
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-100 border border-emerald-300 text-emerald-800 text-sm font-bold shadow-sm">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i> BALANCED (Aset = Kewajiban + Ekuitas)
                    </span>
                @else
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-rose-100 border border-rose-300 text-rose-800 text-sm font-bold shadow-sm">
                        <i class="fa-solid fa-triangle-exclamation text-rose-600 text-base"></i> UNBALANCED! Selisih: Rp {{ number_format(abs($totalAssets - $totalLiabilitiesAndEquity), 0, ',', '.') }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        {{-- SISI ASET --}}
        <div class="card">
            <div class="card-header" style="background:#f8fafc; border-bottom:2px solid var(--primary);">
                <h3 style="color:var(--primary);"><i class="fa-solid fa-building-columns"></i> ASET (ASSETS)</h3>
            </div>
            <div class="table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Kode & Nama Akun</th>
                            <th style="text-align:right;">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assets as $ast)
                        <tr>
                            <td>
                                <div style="font-weight:600;">{{ $ast['code'] }} - {{ $ast['name'] }}</div>
                            </td>
                            <td style="text-align:right; font-weight:600;">
                                Rp {{ number_format($ast['balance'], 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background:#eff6ff; font-size:15px; font-weight:800; border-top:2px solid var(--primary);">
                            <td>TOTAL ASET:</td>
                            <td style="text-align:right; color:var(--primary);">
                                Rp {{ number_format($totalAssets, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- SISI KEWAJIBAN & EKUITAS --}}
        <div>
            {{-- Kewajiban --}}
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header" style="background:#f8fafc; border-bottom:2px solid var(--danger);">
                    <h3 style="color:var(--danger);"><i class="fa-solid fa-hand-holding-dollar"></i> KEWAJIBAN (LIABILITIES)</h3>
                </div>
                <div class="table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>Kode & Nama Akun</th>
                                <th style="text-align:right;">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($liabilities as $lia)
                            <tr>
                                <td>
                                    <div style="font-weight:600;">{{ $lia['code'] }} - {{ $lia['name'] }}</div>
                                </td>
                                <td style="text-align:right; font-weight:600;">
                                    Rp {{ number_format($lia['balance'], 0, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background:#fef2f2; font-size:14px; font-weight:700;">
                                <td>TOTAL KEWAJIBAN:</td>
                                <td style="text-align:right; color:var(--danger);">
                                    Rp {{ number_format($totalLiabilities, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Ekuitas --}}
            <div class="card">
                <div class="card-header" style="background:#f8fafc; border-bottom:2px solid var(--success);">
                    <h3 style="color:var(--success);"><i class="fa-solid fa-piggy-bank"></i> EKUITAS (EQUITY)</h3>
                </div>
                <div class="table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>Komponen Ekuitas</th>
                                <th style="text-align:right;">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><div style="font-weight:600;">3-1100 - Modal Pemilik</div></td>
                                <td style="text-align:right; font-weight:600;">Rp {{ number_format($ownerCapital, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td><div style="font-weight:600;">3-1200 - Laba Ditahan</div></td>
                                <td style="text-align:right; font-weight:600;">Rp {{ number_format($retainedEarnings, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>
                                    <div style="font-weight:600; color:var(--primary);">Laba / (Rugi) Berjalan</div>
                                    <div style="font-size:11px; color:var(--text-secondary);">Akumulasi Laba/Rugi hingga {{ Carbon\Carbon::parse($asOfDate)->format('d/m/Y') }}</div>
                                </td>
                                <td style="text-align:right; font-weight:700; color:var(--primary);">
                                    Rp {{ number_format($currentProfit, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr style="background:#f0fdf4; font-size:14px; font-weight:700;">
                                <td>TOTAL EKUITAS:</td>
                                <td style="text-align:right; color:var(--success);">
                                    Rp {{ number_format($totalEquity, 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr style="background:#e0f2fe; font-size:15px; font-weight:800; border-top:2px solid var(--primary);">
                                <td>TOTAL KEWAJIBAN + EKUITAS:</td>
                                <td style="text-align:right; color:var(--primary);">
                                    Rp {{ number_format($totalLiabilitiesAndEquity, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
