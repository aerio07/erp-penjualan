@extends('layouts.app')
@section('title', 'Laporan Piutang & AR Aging')
@section('page-title', 'Laporan Piutang & AR Aging Report')

@section('content')
<div class="animate-in flex flex-col gap-6">
    <div class="page-header">
        <div>
            <h1>Laporan Piutang & AR Aging</h1>
            <p>Detail piutang penjualan per customer dikelompokkan berdasarkan umur penagihan (aging)</p>
        </div>
    </div>

    {{-- Stats Cards Summary (5 Aging Buckets) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <div class="stat-card border-l-4 border-[#0284c7]">
            <div class="stat-icon" style="background:#e0f2fe; color:#0284c7;">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Belum Jatuh Tempo</div>
                <div class="stat-value text-base">Rp {{ number_format($bucketCurrent, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card border-l-4 border-[#d97706]">
            <div class="stat-icon" style="background:#fef3c7; color:#d97706;">
                <i class="fa-solid fa-calendar-day"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">1 - 30 Hari</div>
                <div class="stat-value text-base text-[#d97706]">Rp {{ number_format($bucket1to30, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card border-l-4 border-[#ea580c]">
            <div class="stat-icon" style="background:#ffedd5; color:#ea580c;">
                <i class="fa-solid fa-calendar-days"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">31 - 60 Hari</div>
                <div class="stat-value text-base text-[#ea580c]">Rp {{ number_format($bucket31to60, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card border-l-4 border-[#dc2626]">
            <div class="stat-icon" style="background:#fee2e2; color:#dc2626;">
                <i class="fa-solid fa-calendar-minus"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">61 - 90 Hari</div>
                <div class="stat-value text-base text-[#dc2626]">Rp {{ number_format($bucket61to90, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card border-l-4 border-[#991b1b]">
            <div class="stat-icon" style="background:#fee2e2; color:#991b1b;">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">> 90 Hari</div>
                <div class="stat-value text-base text-[#991b1b]">Rp {{ number_format($bucketOver90, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="card mb-0">
        <div class="card-header flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h3>Daftar Piutang Beredar</h3>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm font-bold">
                <span>Total Piutang:</span>
                <span class="text-base text-rose-800 font-extrabold">Rp {{ number_format($totalOutstanding, 0, ',', '.') }}</span>
            </div>
        </div>
        <div class="table-responsive mb-0">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>No. Invoice</th>
                        <th>Customer</th>
                        <th>Tgl Invoice</th>
                        <th>Jatuh Tempo</th>
                        <th style="text-align:center;">Umur (Aging)</th>
                        <th style="text-align:right;">Tagihan Efektif</th>
                        <th style="text-align:right;">Sisa Piutang</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($openInvoices as $inv)
                    <tr>
                        <td>
                            <a href="{{ route('sales.invoices.show', $inv) }}" class="text-[#03193c] font-semibold hover:underline">
                                {{ $inv->invoice_number }}
                            </a>
                        </td>
                        <td class="font-medium">{{ $inv->salesOrder->customer->name ?? '-' }}</td>
                        <td>{{ $inv->invoice_date->format('d/m/Y') }}</td>
                        <td>{{ $inv->due_date->format('d/m/Y') }}</td>
                        <td style="text-align:center;">
                            @if($inv->aging_bucket === 'current')
                                <span class="badge badge-done" style="background:#e0f2fe; color:#0369a1; border-color:#bae6fd;">Belum Jatuh Tempo</span>
                            @elseif($inv->aging_bucket === '1_30')
                                <span class="badge" style="background:#fef3c7; color:#b45309; border-color:#fde68a;">1 - 30 Hari</span>
                            @elseif($inv->aging_bucket === '31_60')
                                <span class="badge" style="background:#ffedd5; color:#c2410c; border-color:#fed7aa;">31 - 60 Hari</span>
                            @elseif($inv->aging_bucket === '61_90')
                                <span class="badge badge-cancelled" style="background:#fee2e2; color:#b91c1c; border-color:#fecaca;">61 - 90 Hari</span>
                            @else
                                <span class="badge badge-cancelled" style="background:#7f1d1d; color:#ffffff; border-color:#991b1b;">> 90 Hari</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            Rp {{ number_format($inv->effective_total_amount, 0, ',', '.') }}
                            @if($inv->total_reversed_amount > 0)
                                <div class="text-[11px] text-slate-400">
                                    Retur: Rp {{ number_format($inv->total_reversed_amount, 0, ',', '.') }}
                                </div>
                            @endif
                        </td>
                        <td style="text-align:right;" class="font-bold text-rose-600">
                            Rp {{ number_format($inv->outstanding_amount, 0, ',', '.') }}
                        </td>
                        <td style="text-align:center;">
                            <a href="{{ route('sales.payments.create') }}?invoice_id={{ $inv->id }}" class="btn btn-success btn-sm">
                                <i class="fa-solid fa-hand-holding-dollar"></i> Terima Bayar
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-12 text-slate-400 font-sans">
                            <i class="fa-solid fa-circle-check text-emerald-500 text-3xl block mb-3"></i>
                            Tidak ada piutang beredar. Semua invoice sudah lunas!
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
