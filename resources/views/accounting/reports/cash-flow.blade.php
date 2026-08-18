@extends('layouts.app')
@section('title', 'Laporan Arus Kas')
@section('page-title', 'Laporan Arus Kas (Cash Flow)')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Laporan Arus Kas (Cash Flow)</h1>
            <p>Pelacakan rinci sumber penerimaan dan pengeluaran kas operasional perusahaan</p>
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-body" style="padding:16px;">
            <form method="GET" action="{{ route('accounting.reports.cash-flow') }}" style="display:flex; gap:12px; align-items:flex-end;">
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

    {{-- Stats Cards Summary --}}
    <div class="grid grid-4" style="margin-bottom:20px;">
        <div class="stat-card">
            <div class="stat-icon" style="background:#e0f2fe; color:#0284c7;"><i class="fa-solid fa-wallet"></i></div>
            <div class="stat-info">
                <div class="stat-label">Saldo Kas Awal</div>
                <div class="stat-value">Rp {{ number_format($openingBalance, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#d1fae5; color:#059669;"><i class="fa-solid fa-arrow-down-left"></i></div>
            <div class="stat-info">
                <div class="stat-label">Total Kas Masuk</div>
                <div class="stat-value" style="color:var(--success);">+ Rp {{ number_format($totalInflow, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#fee2e2; color:#dc2626;"><i class="fa-solid fa-arrow-up-right"></i></div>
            <div class="stat-info">
                <div class="stat-label">Total Kas Keluar</div>
                <div class="stat-value" style="color:var(--danger);">- Rp {{ number_format($totalOutflow, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#f3e8ff; color:#7e22ce;"><i class="fa-solid fa-sack-dollar"></i></div>
            <div class="stat-info">
                <div class="stat-label">Saldo Kas Akhir</div>
                <div class="stat-value" style="color:var(--primary);">Rp {{ number_format($closingBalance, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    {{-- Details Inflow & Outflow --}}
    <div class="grid grid-2" style="margin-bottom:20px;">
        {{-- Kas Masuk --}}
        <div class="card">
            <div class="card-header" style="border-left:4px solid var(--success);">
                <h3><i class="fa-solid fa-circle-down" style="color:var(--success);"></i> Penerimaan Kas (Inflows)</h3>
                <span style="font-weight:700; color:var(--success);">Rp {{ number_format($totalInflow, 0, ',', '.') }}</span>
            </div>
            <div class="table-responsive">
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
                            <td style="font-weight:500;">{{ $source }}</td>
                            <td style="text-align:right; font-weight:700; color:var(--success);">
                                Rp {{ number_format($amount, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" style="text-align:center; padding:24px; color:var(--text-secondary);">
                                Tidak ada penerimaan kas pada periode ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Kas Keluar --}}
        <div class="card">
            <div class="card-header" style="border-left:4px solid var(--danger);">
                <h3><i class="fa-solid fa-circle-up" style="color:var(--danger);"></i> Pengeluaran Kas (Outflows)</h3>
                <span style="font-weight:700; color:var(--danger);">Rp {{ number_format($totalOutflow, 0, ',', '.') }}</span>
            </div>
            <div class="table-responsive">
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
                            <td style="font-weight:500;">{{ $target }}</td>
                            <td style="text-align:right; font-weight:700; color:var(--danger);">
                                Rp {{ number_format($amount, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" style="text-align:center; padding:24px; color:var(--text-secondary);">
                                Tidak ada pengeluaran kas pada periode ini.
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
