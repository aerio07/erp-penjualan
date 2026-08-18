@extends('layouts.app')
@section('title', 'Laporan Hutang & AP Aging')
@section('page-title', 'Laporan Hutang & AP Aging Report')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Laporan Hutang & AP Aging</h1>
            <p>Detail kewajiban pembayaran ke supplier dikelompokkan berdasarkan umur jatuh tempo (aging)</p>
        </div>
    </div>

    {{-- Stats Cards Summary --}}
    <div class="grid grid-5" style="margin-bottom:24px;">
        <div class="stat-card">
            <div class="stat-icon" style="background:#e0f2fe; color:#0284c7;"><i class="fa-solid fa-clock"></i></div>
            <div class="stat-info">
                <div class="stat-label">Belum Jatuh Tempo</div>
                <div class="stat-value" style="font-size:15px;">Rp {{ number_format($bucketCurrent, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#fef3c7; color:#d97706;"><i class="fa-solid fa-calendar-day"></i></div>
            <div class="stat-info">
                <div class="stat-label">1 - 30 Hari</div>
                <div class="stat-value" style="font-size:15px; color:#d97706;">Rp {{ number_format($bucket1to30, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#ffedd5; color:#ea580c;"><i class="fa-solid fa-calendar-days"></i></div>
            <div class="stat-info">
                <div class="stat-label">31 - 60 Hari</div>
                <div class="stat-value" style="font-size:15px; color:#ea580c;">Rp {{ number_format($bucket31to60, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#fee2e2; color:#dc2626;"><i class="fa-solid fa-calendar-minus"></i></div>
            <div class="stat-info">
                <div class="stat-label">61 - 90 Hari</div>
                <div class="stat-value" style="font-size:15px; color:#dc2626;">Rp {{ number_format($bucket61to90, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#450a0a; color:#f87171;"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="stat-info">
                <div class="stat-label">> 90 Hari</div>
                <div class="stat-value" style="font-size:15px; color:#b91c1c;">Rp {{ number_format($bucketOver90, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <h3>Daftar Hutang Beredar</h3>
            <div style="font-size:15px; font-weight:800; color:var(--danger);">
                Total Hutang: Rp {{ number_format($totalOutstanding, 0, ',', '.') }}
            </div>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>No. Invoice</th>
                        <th>Supplier</th>
                        <th>Tgl Invoice</th>
                        <th>Jatuh Tempo</th>
                        <th style="text-align:center;">Umur (Aging)</th>
                        <th style="text-align:right;">Total Tagihan</th>
                        <th style="text-align:right;">Sisa Hutang</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($openInvoices as $inv)
                    <tr>
                        <td>
                            <a href="{{ route('purchase.invoices.show', $inv) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                                {{ $inv->invoice_number }}
                            </a>
                        </td>
                        <td style="font-weight:500;">{{ $inv->purchaseOrder->supplier->name ?? '-' }}</td>
                        <td>{{ $inv->invoice_date->format('d/m/Y') }}</td>
                        <td>{{ $inv->due_date->format('d/m/Y') }}</td>
                        <td style="text-align:center;">
                            @if($inv->aging_bucket === 'current')
                                <span class="badge badge-done" style="background:#e0f2fe; color:#0369a1;">Belum Jatuh Tempo</span>
                            @elseif($inv->aging_bucket === '1_30')
                                <span class="badge" style="background:#fef3c7; color:#b45309;">1 - 30 Hari</span>
                            @elseif($inv->aging_bucket === '31_60')
                                <span class="badge" style="background:#ffedd5; color:#c2410c;">31 - 60 Hari</span>
                            @elseif($inv->aging_bucket === '61_90')
                                <span class="badge badge-cancelled" style="background:#fee2e2; color:#b91c1c;">61 - 90 Hari</span>
                            @else
                                <span class="badge badge-cancelled" style="background:#7f1d1d; color:#ffffff;">> 90 Hari</span>
                            @endif
                        </td>
                        <td style="text-align:right;">Rp {{ number_format($inv->total_amount, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:700; color:var(--danger);">
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
                        <td colspan="8" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            <i class="fa-solid fa-circle-check" style="font-size:32px; color:var(--success); display:block; margin-bottom:12px;"></i>
                            Tidak ada hutang beredar. Semua invoice supplier sudah lunas!
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
