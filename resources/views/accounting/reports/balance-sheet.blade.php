@extends('layouts.app')
@section('title', 'Neraca (Balance Sheet)')
@section('page-title', 'Neraca Keuangan (Balance Sheet)')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Neraca Keuangan (Balance Sheet)</h1>
            <p>Posisi Aset, Kewajiban, dan Modal perusahaan pada titik waktu tertentu</p>
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-body" style="padding:16px;">
            <form method="GET" action="{{ route('accounting.reports.balance-sheet') }}" style="display:flex; gap:12px; align-items:flex-end;">
                <div style="width:200px;">
                    <label class="form-label">Per Tanggal (As of Date)</label>
                    <input type="date" name="as_of_date" value="{{ $asOfDate }}" class="form-control" onchange="this.form.submit()">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    <div style="margin-bottom:20px; text-align:right;">
        @if($isBalanced)
            <span class="badge badge-done" style="font-size:14px; font-weight:700; background:#d1fae5; color:#065f46; padding:8px 16px;">
                <i class="fa-solid fa-circle-check"></i> PERSAMAAN AKUNTANSI BALANCED (Aset = Kewajiban + Ekuitas)
            </span>
        @else
            <span class="badge badge-cancelled" style="font-size:14px; font-weight:700; padding:8px 16px;">
                <i class="fa-solid fa-triangle-exclamation"></i> UNBALANCED! Selisih: Rp {{ number_format(abs($totalAssets - $totalLiabilitiesAndEquity), 0, ',', '.') }}
            </span>
        @endif
    </div>

    <div class="grid grid-2" style="align-items:start;">
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
