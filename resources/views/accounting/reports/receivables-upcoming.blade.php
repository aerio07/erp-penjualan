@extends('layouts.app')

@section('title', 'Piutang Akan Jatuh Tempo')

@section('content')
<div class="content-header">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 style="font-size: 24px; font-weight: 700; color: var(--text-primary); margin: 0;">
                Piutang Akan Jatuh Tempo
            </h1>
            <p style="font-size: 13px; color: var(--text-secondary); margin: 4px 0 0 0;">
                Daftar tagihan penjualan yang mendekati jatuh tempo (belum telat) untuk tindakan penagihan proaktif
            </p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('accounting.reports.cash-flow') }}" class="btn btn-secondary">
                <i class="fa-solid fa-chart-line"></i> Cash Flow & Forecast
            </a>
            <a href="{{ route('accounting.reports.receivables') }}" class="btn btn-secondary">
                <i class="fa-solid fa-clock-rotate-left"></i> AP/AR Aging Piutang
            </a>
        </div>
    </div>
</div>

<div class="content-body" style="display: flex; flex-direction: column; gap: 20px;">
    {{-- Filter Bar --}}
    <div class="card" style="padding: 16px 20px; margin: 0;">
        <form method="GET" action="{{ route('accounting.reports.receivables-upcoming') }}" style="display: flex; align-items: flex-end; gap: 14px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 220px;">
                <label style="font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px; display: block;">
                    Cari No. Invoice / Customer
                </label>
                <input type="text" name="q" value="{{ $search }}" placeholder="Ketik nomor invoice atau nama pelanggan..." class="form-control">
            </div>

            <div style="width: 200px;">
                <label style="font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px; display: block;">
                    Rentang Jatuh Tempo
                </label>
                <select name="days" class="form-control" onchange="this.form.submit()">
                    <option value="7" {{ $days == 7 ? 'selected' : '' }}>7 Hari ke Depan (1 Minggu)</option>
                    <option value="14" {{ $days == 14 ? 'selected' : '' }}>14 Hari ke Depan (2 Minggu)</option>
                    <option value="30" {{ $days == 30 ? 'selected' : '' }}>30 Hari ke Depan (1 Bulan)</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="height: 38px;">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
            @if($search || $days != 7)
            <a href="{{ route('accounting.reports.receivables-upcoming') }}" class="btn btn-secondary" style="height: 38px;">
                Reset
            </a>
            @endif
        </form>
    </div>

    {{-- Executive Summary KPI Cards --}}
    <div class="grid grid-3" style="gap: 16px;">
        <div class="card" style="border-left: 4px solid #0284c7; padding: 16px; margin: 0;">
            <div style="font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;">
                Total Piutang Mendekati Jatuh Tempo
            </div>
            <div style="font-size: 22px; font-weight: 700; color: #0284c7; margin-top: 4px;">
                Rp {{ number_format($totalUpcomingAmount, 0, ',', '.') }}
            </div>
            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 2px;">
                Sisa tagihan bersih dalam {{ $days }} hari ke depan
            </div>
        </div>

        <div class="card" style="border-left: 4px solid #f59e0b; padding: 16px; margin: 0;">
            <div style="font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;">
                Jumlah Invoice
            </div>
            <div style="font-size: 22px; font-weight: 700; color: #d97706; margin-top: 4px;">
                {{ $totalUpcomingCount }} Dokumen
            </div>
            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 2px;">
                Menunggu pelunasan customer
            </div>
        </div>

        <div class="card" style="border-left: 4px solid #10b981; padding: 16px; margin: 0;">
            <div style="font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;">
                Status Monitoring
            </div>
            <div style="font-size: 18px; font-weight: 700; color: #059669; margin-top: 4px;">
                Aktif & Siap Ditagih
            </div>
            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 2px;">
                Potongan retur otomatis diperhitungkan
            </div>
        </div>
    </div>

    {{-- Tabel Invoice --}}
    <div class="card" style="margin: 0; padding: 0; overflow: hidden;">
        <div class="table-responsive" style="margin: 0;">
            <table class="erp-table" style="margin: 0; width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 140px;">No. Invoice</th>
                        <th style="width: 220px;">Customer</th>
                        <th style="width: 120px;">Tgl Invoice</th>
                        <th style="width: 120px;">Jatuh Tempo</th>
                        <th style="width: 130px; text-align: center;">Sisa Hari</th>
                        <th style="text-align: right; width: 140px;">Total Tagihan</th>
                        <th style="text-align: right; width: 130px;">Retur (-)</th>
                        <th style="text-align: right; width: 130px;">Dibayar (-)</th>
                        <th style="text-align: right; width: 150px;">Sisa Piutang</th>
                        <th style="text-align: center; width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                    @php
                        $badgeColor = '#10b981';
                        $badgeBg = '#d1fae5';
                        if ($inv->days_remaining <= 3) {
                            $badgeColor = '#dc2626';
                            $badgeBg = '#fee2e2';
                        } elseif ($inv->days_remaining <= 7) {
                            $badgeColor = '#d97706';
                            $badgeBg = '#fef3c7';
                        }
                    @endphp
                    <tr>
                        <td>
                            <strong style="color: var(--primary);">{{ $inv->invoice_number }}</strong>
                            <div style="font-size: 11px; color: var(--text-secondary);">
                                SO: {{ $inv->salesOrder?->so_number ?? '-' }}
                            </div>
                        </td>
                        <td>
                            <strong style="color: var(--text-primary);">
                                {{ $inv->salesOrder?->customer?->name ?? 'Pelanggan Umum' }}
                            </strong>
                            <div style="font-size: 11px; color: var(--text-secondary);">
                                {{ $inv->salesOrder?->customer?->code ?? '' }}
                            </div>
                        </td>
                        <td>
                            <span style="font-size: 12.5px; color: var(--text-secondary);">
                                {{ \Carbon\Carbon::parse($inv->invoice_date)->format('d/m/Y') }}
                            </span>
                        </td>
                        <td>
                            <strong style="color: var(--text-primary); font-size: 12.5px;">
                                {{ \Carbon\Carbon::parse($inv->due_date)->format('d/m/Y') }}
                            </strong>
                        </td>
                        <td style="text-align: center;">
                            <span style="display: inline-block; padding: 3px 8px; border-radius: 9999px; font-size: 11.5px; font-weight: 700; background: {{ $badgeBg }}; color: {{ $badgeColor }};">
                                @if($inv->days_remaining == 0)
                                    Hari Ini
                                @else
                                    {{ $inv->days_remaining }} hari lagi
                                @endif
                            </span>
                        </td>
                        <td style="text-align: right; color: var(--text-secondary);">
                            Rp {{ number_format($inv->total_amount, 0, ',', '.') }}
                        </td>
                        <td style="text-align: right; color: #dc2626;">
                            {{ $inv->total_reversed_amount > 0 ? '- Rp ' . number_format($inv->total_reversed_amount, 0, ',', '.') : '-' }}
                        </td>
                        <td style="text-align: right; color: #059669;">
                            {{ $inv->total_paid > 0 ? '- Rp ' . number_format($inv->total_paid, 0, ',', '.') : '-' }}
                        </td>
                        <td style="text-align: right; font-weight: 700; color: #0284c7; font-size: 13.5px;">
                            Rp {{ number_format($inv->outstanding_amount, 0, ',', '.') }}
                        </td>
                        <td style="text-align: center;">
                            <a href="{{ route('sales.invoices.show', $inv) }}" class="btn btn-secondary btn-sm" title="Lihat Detail Invoice">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 36px 20px; color: var(--text-secondary);">
                            <i class="fa-solid fa-circle-check" style="font-size: 32px; color: #10b981; margin-bottom: 8px; display: block;"></i>
                            Tidak ada tagihan piutang yang akan jatuh tempo dalam {{ $days }} hari ke depan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($invoices->isNotEmpty())
                <tfoot>
                    <tr style="background: #f8fafc; font-weight: 700; border-top: 2px solid #cbd5e1;">
                        <td colspan="8">TOTAL SISA PIUTANG MENDATANG</td>
                        <td style="text-align: right; color: #0284c7; font-size: 14px;">
                            Rp {{ number_format($totalUpcomingAmount, 0, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
