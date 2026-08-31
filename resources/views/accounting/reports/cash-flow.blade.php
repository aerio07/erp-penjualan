@extends('layouts.app')
@section('title', 'Laporan Arus Kas')
@section('page-title', 'Laporan Arus Kas (Cash Flow)')

@section('content')
<div class="animate-in flex flex-col gap-6">
    <div class="page-header">
        <div>
            <h1>Laporan Arus Kas (Cash Flow)</h1>
            <p>Pelacakan rinci sumber penerimaan dan pengeluaran kas operasional perusahaan</p>
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="card mb-0">
        <div class="card-body p-0">
            <form method="GET" action="{{ route('accounting.reports.cash-flow') }}" class="flex flex-col sm:flex-row flex-wrap sm:items-end gap-3">
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
</div>
@endsection
