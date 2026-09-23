@extends('layouts.app')
@section('title', 'Umur Hutang (AP Aging)')
@section('page-title', 'Umur Hutang (AP Aging)')

@section('content')
<div class="animate-in flex flex-col gap-6">
    <x-page-header title="Umur Hutang (AP Aging)" subtitle="Detail kewajiban pembayaran ke supplier dikelompokkan berdasarkan umur jatuh tempo (aging)">
        <x-slot:actions>
            <a href="{{ route('accounting.reports.payables-by-vendor') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-truck-field"></i> Rekap per Supplier
            </a>
            <a href="{{ route('accounting.reports.payables-upcoming') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-calendar-week"></i> Jatuh Tempo
            </a>
            <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-print"></i> Cetak
            </button>
        </x-slot:actions>
    </x-page-header>

    {{-- Stats Cards Summary (5 AP Aging Buckets) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
        <div class="stat-card border-l-4 border-[#0284c7]" style="padding: 12px 14px !important; gap: 10px !important;">
            <div class="stat-icon" style="background:#e0f2fe; color:#0284c7; width: 38px !important; height: 38px !important; min-width: 38px !important; font-size: 16px !important;">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div class="stat-info" style="min-width: 0;">
                <div class="stat-label" style="font-size: 10.5px !important; margin-bottom: 2px !important;" title="Belum Jatuh Tempo">Belum Jatuh Tempo</div>
                <div class="stat-value text-slate-900" style="font-size: 13px !important; font-weight: 700 !important; white-space: nowrap !important;" title="Rp {{ number_format($bucketCurrent, 0, ',', '.') }}">
                    Rp {{ number_format($bucketCurrent, 0, ',', '.') }}
                </div>
            </div>
        </div>

        <div class="stat-card border-l-4 border-[#d97706]" style="padding: 12px 14px !important; gap: 10px !important;">
            <div class="stat-icon" style="background:#fef3c7; color:#d97706; width: 38px !important; height: 38px !important; min-width: 38px !important; font-size: 16px !important;">
                <i class="fa-solid fa-calendar-day"></i>
            </div>
            <div class="stat-info" style="min-width: 0;">
                <div class="stat-label" style="font-size: 10.5px !important; margin-bottom: 2px !important;" title="1 - 30 Hari">1 - 30 Hari</div>
                <div class="stat-value text-[#d97706]" style="font-size: 13px !important; font-weight: 700 !important; white-space: nowrap !important;" title="Rp {{ number_format($bucket1to30, 0, ',', '.') }}">
                    Rp {{ number_format($bucket1to30, 0, ',', '.') }}
                </div>
            </div>
        </div>

        <div class="stat-card border-l-4 border-[#ea580c]" style="padding: 12px 14px !important; gap: 10px !important;">
            <div class="stat-icon" style="background:#ffedd5; color:#ea580c; width: 38px !important; height: 38px !important; min-width: 38px !important; font-size: 16px !important;">
                <i class="fa-solid fa-calendar-days"></i>
            </div>
            <div class="stat-info" style="min-width: 0;">
                <div class="stat-label" style="font-size: 10.5px !important; margin-bottom: 2px !important;" title="31 - 60 Hari">31 - 60 Hari</div>
                <div class="stat-value text-[#ea580c]" style="font-size: 13px !important; font-weight: 700 !important; white-space: nowrap !important;" title="Rp {{ number_format($bucket31to60, 0, ',', '.') }}">
                    Rp {{ number_format($bucket31to60, 0, ',', '.') }}
                </div>
            </div>
        </div>

        <div class="stat-card border-l-4 border-[#dc2626]" style="padding: 12px 14px !important; gap: 10px !important;">
            <div class="stat-icon" style="background:#fee2e2; color:#dc2626; width: 38px !important; height: 38px !important; min-width: 38px !important; font-size: 16px !important;">
                <i class="fa-solid fa-calendar-minus"></i>
            </div>
            <div class="stat-info" style="min-width: 0;">
                <div class="stat-label" style="font-size: 10.5px !important; margin-bottom: 2px !important;" title="61 - 90 Hari">61 - 90 Hari</div>
                <div class="stat-value text-[#dc2626]" style="font-size: 13px !important; font-weight: 700 !important; white-space: nowrap !important;" title="Rp {{ number_format($bucket61to90, 0, ',', '.') }}">
                    Rp {{ number_format($bucket61to90, 0, ',', '.') }}
                </div>
            </div>
        </div>

        <div class="stat-card border-l-4 border-[#991b1b]" style="padding: 12px 14px !important; gap: 10px !important;">
            <div class="stat-icon" style="background:#fee2e2; color:#991b1b; width: 38px !important; height: 38px !important; min-width: 38px !important; font-size: 16px !important;">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="stat-info" style="min-width: 0;">
                <div class="stat-label" style="font-size: 10.5px !important; margin-bottom: 2px !important;" title="> 90 Hari">> 90 Hari</div>
                <div class="stat-value text-[#991b1b]" style="font-size: 13px !important; font-weight: 700 !important; white-space: nowrap !important;" title="Rp {{ number_format($bucketOver90, 0, ',', '.') }}">
                    Rp {{ number_format($bucketOver90, 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Main Table Card --}}
    <x-data-table :paginator="$paginatedInvoices">
        <x-slot:head>
            <tr>
                <th>No. Invoice</th>
                <th>Supplier</th>
                <th>Tgl Invoice</th>
                <th>Jatuh Tempo</th>
                <th style="text-align:center;">Umur (Aging)</th>
                <th style="text-align:right;">Nominal Tagihan</th>
                <th style="text-align:right;">Sisa Hutang</th>
                <th style="text-align:center;">Aksi</th>
            </tr>
        </x-slot:head>

        @forelse($paginatedInvoices as $inv)
        <tr>
            <td>
                <a href="{{ route('purchase.invoices.show', $inv) }}" class="text-[#03193c] font-semibold hover:underline">
                    {{ $inv->invoice_number }}
                </a>
            </td>
            <td class="font-medium">{{ $inv->purchaseOrder->supplier->name ?? '-' }}</td>
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
                <a href="{{ route('purchase.payments.create') }}?invoice_id={{ $inv->id }}" class="btn btn-success btn-sm">
                    <i class="fa-solid fa-money-check-dollar"></i> Bayar Hutang
                </a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" class="text-center py-12 text-slate-400 font-sans">
                <i class="fa-solid fa-circle-check text-emerald-500 text-3xl block mb-3"></i>
                Tidak ada hutang beredar. Semua invoice supplier sudah lunas!
            </td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection
