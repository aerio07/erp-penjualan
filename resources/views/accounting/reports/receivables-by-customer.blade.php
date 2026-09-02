@extends('layouts.app')

@section('title', 'Rekap Piutang by Customer')

@section('content')
<div class="animate-in">
    {{-- Header --}}
    <div class="page-header" style="margin-bottom: 20px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">
                <i class="fa-solid fa-users" style="color: var(--primary); margin-right: 8px;"></i>
                Rekap Piutang by Customer
            </h1>
            <p style="color: var(--text-secondary); font-size: 13px; margin: 0;">
                Ringkasan total tagihan piutang usaha yang dikelompokkan per customer beserta umur jatuh tempo invoice. Klik nama customer untuk melihat <strong>Kartu Piutang</strong> detail.
            </p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('accounting.reports.receivables') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-clock-rotate-left"></i> AR Aging Piutang
            </a>
            <a href="{{ route('sales.payments.create') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-hand-holding-dollar"></i> Terima Piutang
            </a>
        </div>
    </div>

    {{-- Executive Summary KPI Cards --}}
    <div class="grid grid-3" style="gap: 16px; margin-bottom: 20px;">
        <div class="card" style="border-left: 4px solid var(--primary); padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                Customer dengan Piutang Aktif
            </div>
            <div style="font-size: 24px; font-weight: 700; color: var(--text-primary); margin-top: 4px;">
                {{ number_format($totalCustomers) }} <span style="font-size: 13px; font-weight: 400; color: var(--text-secondary);">Pelanggan</span>
            </div>
            <div style="font-size: 12px; color: var(--primary); margin-top: 4px; font-weight: 600;">
                {{ number_format($totalOpenInvoices) }} Invoice Belum Lunas
            </div>
        </div>

        <div class="card" style="border-left: 4px solid #0284c7; padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                Total Saldo Piutang Usaha
            </div>
            <div style="font-size: 24px; font-weight: 700; color: #0284c7; margin-top: 4px;">
                Rp {{ number_format($totalAllReceivable, 0, ',', '.') }}
            </div>
            <div style="font-size: 12px; color: #0284c7; margin-top: 4px; font-weight: 500;">
                <i class="fa-solid fa-clock"></i> Tagihan Belum Tertagih dari Customer
            </div>
        </div>

        <div class="card" style="border-left: 4px solid #10b981; padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                Rata-rata Piutang per Customer
            </div>
            <div style="font-size: 24px; font-weight: 700; color: #059669; margin-top: 4px;">
                Rp {{ number_format($totalCustomers > 0 ? ($totalAllReceivable / $totalCustomers) : 0, 0, ',', '.') }}
            </div>
            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">
                Eksposur piutang per pelanggan
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <form method="GET" action="{{ route('accounting.reports.receivables-by-customer') }}" class="card" style="padding: 14px 20px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama atau kode customer..." class="form-control" style="height: 38px; font-size: 13px; border-radius: 6px; padding: 0 12px; width: 260px; margin-bottom: 0;">

            <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--text-primary); cursor: pointer; margin-bottom: 0;">
                <input type="checkbox" name="only_outstanding" value="1" {{ $onlyOutstanding ? 'checked' : '' }} onchange="this.form.submit()">
                Hanya customer dengan sisa piutang > 0
            </label>

            <button type="submit" class="btn btn-primary btn-sm" style="height: 38px; padding: 0 16px;">
                <i class="fa-solid fa-magnifying-glass"></i> Cari
            </button>
            @if($search || !$onlyOutstanding)
            <a href="{{ route('accounting.reports.receivables-by-customer') }}" class="btn btn-secondary btn-sm" style="height: 38px; padding: 0 12px;" title="Reset Filter">
                Reset
            </a>
            @endif
        </div>
        <div style="font-size: 12px; color: var(--text-secondary);">
            Menampilkan <strong>{{ $customers->count() }}</strong> customer
        </div>
    </form>

    {{-- Tabel Rekap Customer --}}
    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-responsive">
            <table class="erp-table" style="margin: 0; width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 250px;">Customer / Pelanggan</th>
                        <th style="text-align: center; width: 120px;">Invoice Aktif</th>
                        <th style="text-align: right; width: 180px;">Total Piutang</th>
                        <th style="width: 170px;">Invoice Tertua</th>
                        <th style="width: 150px;">Jatuh Tempo</th>
                        <th style="text-align: center; width: 140px;">Keterlambatan</th>
                        <th style="text-align: center; width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $c)
                    <tr>
                        {{-- Nama Customer (Drill-down ke Kartu Piutang) --}}
                        <td>
                            <a href="{{ route('accounting.reports.ledger-receivable', $c) }}" style="font-weight: 700; color: var(--primary); text-decoration: none; font-size: 13.5px;" title="Klik untuk lihat Kartu Piutang">
                                {{ $c->name }} &rarr;
                            </a>
                            <div style="font-size: 11.5px; color: var(--text-secondary); font-family: monospace;">
                                Kode: {{ $c->code }} · Telp: {{ $c->phone ?? '-' }}
                            </div>
                        </td>

                        {{-- Jumlah Invoice Belum Lunas --}}
                        <td style="text-align: center;">
                            @if($c->open_invoices_count > 0)
                                <span class="badge badge-warning" style="font-size: 11px; font-weight: 700;">
                                    {{ $c->open_invoices_count }} Inv
                                </span>
                            @else
                                <span class="badge badge-done" style="font-size: 10px;">Lunas</span>
                            @endif
                        </td>

                        {{-- Total Sisa Piutang --}}
                        <td style="text-align: right; font-weight: 700; color: {{ $c->total_receivable > 0 ? '#0284c7' : '#059669' }}; font-size: 13.5px;">
                            Rp {{ number_format($c->total_receivable, 0, ',', '.') }}
                        </td>

                        {{-- Invoice Tertua --}}
                        <td>
                            @if($c->oldest_invoice_number)
                                <div style="font-family: monospace; font-weight: 600; font-size: 12px; color: var(--text-primary);">
                                    {{ $c->oldest_invoice_number }}
                                </div>
                                <div style="font-size: 11px; color: var(--text-secondary);">
                                    Tgl: {{ $c->oldest_invoice_date ? date('d/m/Y', strtotime($c->oldest_invoice_date)) : '-' }}
                                </div>
                            @else
                                <span style="color: var(--text-secondary); font-size: 12px;">-</span>
                            @endif
                        </td>

                        {{-- Jatuh Tempo --}}
                        <td>
                            @if($c->oldest_due_date)
                                <span style="font-size: 12px; font-weight: 500;">
                                    {{ date('d/m/Y', strtotime($c->oldest_due_date)) }}
                                </span>
                            @else
                                <span style="color: var(--text-secondary); font-size: 12px;">-</span>
                            @endif
                        </td>

                        {{-- Status Terlambat (Overdue) --}}
                        <td style="text-align: center;">
                            @if($c->max_overdue_days > 0)
                                <span class="badge badge-danger" style="font-size: 11px; font-weight: 700;">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Lewat {{ $c->max_overdue_days }} hari
                                </span>
                            @elseif($c->total_receivable > 0)
                                <span class="badge badge-primary" style="font-size: 10px;">
                                    <i class="fa-solid fa-clock"></i> Belum Jatuh Tempo
                                </span>
                            @else
                                <span style="color: var(--text-secondary); font-size: 12px;">-</span>
                            @endif
                        </td>

                        {{-- Aksi --}}
                        <td style="text-align: center;">
                            <a href="{{ route('accounting.reports.ledger-receivable', $c) }}" class="btn btn-secondary btn-sm" style="font-size: 11px; padding: 4px 10px; color: var(--primary);" title="Buka Kartu Piutang Customer">
                                <i class="fa-solid fa-book"></i> Kartu Piutang
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 36px; color: var(--text-secondary);">
                            <i class="fa-solid fa-circle-check" style="font-size: 28px; margin-bottom: 8px; display: block; color: #10b981; opacity: 0.6;"></i>
                            Tidak ada customer dengan saldo piutang aktif sesuai filter.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background: #f1f5f9; font-weight: 700; border-top: 2px solid #cbd5e1;">
                        <td>TOTAL KESELURUHAN ({{ $customers->count() }} CUSTOMER)</td>
                        <td style="text-align: center;">{{ number_format($totalOpenInvoices) }} Inv</td>
                        <td style="text-align: right; color: #0284c7; font-size: 14px;">Rp {{ number_format($totalAllReceivable, 0, ',', '.') }}</td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
