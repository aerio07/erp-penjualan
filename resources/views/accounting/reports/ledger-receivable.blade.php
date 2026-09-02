@extends('layouts.app')

@section('title', 'Kartu Piutang - ' . $customer->name)

@section('content')
<div class="animate-in">
    {{-- Header --}}
    <div class="page-header" style="margin-bottom: 20px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">
                <i class="fa-solid fa-book" style="color: var(--primary); margin-right: 8px;"></i>
                Kartu Piutang: {{ $customer->name }}
            </h1>
            <p style="color: var(--text-secondary); font-size: 13px; margin: 0;">
                Buku Pembantu Piutang Usaha (Subsidiary Ledger) · Kode Customer: <strong style="color: var(--primary);">{{ $customer->code }}</strong>
            </p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('accounting.reports.receivables-by-customer') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Rekap Customer
            </a>
            <a href="{{ route('master.customers.show', $customer) }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-user-tag"></i> Detail Customer
            </a>
            <button onclick="window.print()" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-print"></i> Cetak
            </button>
        </div>
    </div>

    {{-- Executive Summary KPI Cards --}}
    <div class="grid grid-4" style="gap: 16px; margin-bottom: 20px;">
        <div class="card" style="border-left: 4px solid #64748b; padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                Saldo Awal {{ $dateFrom ? '(' . date('d/m/Y', strtotime($dateFrom)) . ')' : '' }}
            </div>
            <div style="font-size: 22px; font-weight: 700; color: var(--text-primary); margin-top: 4px;">
                Rp {{ number_format($beginningBalance, 0, ',', '.') }}
            </div>
            <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 4px;">
                Saldo piutang sebelum periode filter
            </div>
        </div>

        <div class="card" style="border-left: 4px solid #0284c7; padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                Penambahan Piutang (+)
            </div>
            <div style="font-size: 22px; font-weight: 700; color: #0284c7; margin-top: 4px;">
                Rp {{ number_format($totalDebit, 0, ',', '.') }}
            </div>
            <div style="font-size: 11.5px; color: #0284c7; margin-top: 4px; font-weight: 500;">
                <i class="fa-solid fa-file-invoice-dollar"></i> Tagihan Invoice Baru
            </div>
        </div>

        <div class="card" style="border-left: 4px solid #10b981; padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                Pengurangan Piutang (-)
            </div>
            <div style="font-size: 22px; font-weight: 700; color: #059669; margin-top: 4px;">
                Rp {{ number_format($totalCredit, 0, ',', '.') }}
            </div>
            <div style="font-size: 11.5px; color: #059669; margin-top: 4px; font-weight: 500;">
                <i class="fa-solid fa-circle-check"></i> Pembayaran & Retur Jual
            </div>
        </div>

        <div class="card" style="border-left: 4px solid var(--primary); padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                Saldo Akhir Piutang
            </div>
            <div style="font-size: 22px; font-weight: 700; color: {{ $endingBalance > 0 ? '#0284c7' : '#059669' }}; margin-top: 4px;">
                Rp {{ number_format($endingBalance, 0, ',', '.') }}
            </div>
            <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 4px;">
                Sisa tagihan piutang berjalan
            </div>
        </div>
    </div>

    {{-- Filter Rentang Tanggal --}}
    <form method="GET" action="{{ route('accounting.reports.ledger-receivable', $customer) }}" class="card" style="padding: 14px 20px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <span style="font-size: 13px; font-weight: 600; color: var(--text-primary);">
                <i class="fa-solid fa-filter" style="color: var(--primary); margin-right: 4px;"></i> Filter Periode:
            </span>
            <div style="display: flex; align-items: center; gap: 6px;">
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control" style="height: 36px; font-size: 13px; border-radius: 6px; padding: 0 10px; width: 150px; margin-bottom: 0;">
                <span style="color: var(--text-secondary); font-size: 13px;">s/d</span>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control" style="height: 36px; font-size: 13px; border-radius: 6px; padding: 0 10px; width: 150px; margin-bottom: 0;">
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="height: 36px; padding: 0 16px;">
                Terapkan Filter
            </button>
            @if($dateFrom || $dateTo)
            <a href="{{ route('accounting.reports.ledger-receivable', $customer) }}" class="btn btn-secondary btn-sm" style="height: 36px; padding: 0 12px;" title="Reset Filter">
                Reset
            </a>
            @endif
        </div>
        <div style="font-size: 12px; color: var(--text-secondary);">
            Menampilkan <strong>{{ $filteredTransactions->count() }}</strong> transaksi
        </div>
    </form>

    {{-- Tabel Mutasi Saldo Berjalan (Ledger) --}}
    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-responsive">
            <table class="erp-table" style="margin: 0; width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 100px;">Tanggal</th>
                        <th style="width: 140px;">No. Dokumen</th>
                        <th style="width: 100px; text-align: center;">Tipe</th>
                        <th style="width: 130px;">Ref. Dokumen</th>
                        <th>Keterangan</th>
                        <th style="text-align: right; width: 140px; color: #0284c7;">Tambah Piutang (+)</th>
                        <th style="text-align: right; width: 140px; color: #059669;">Kurang Piutang (-)</th>
                        <th style="text-align: right; width: 160px;">Saldo Berjalan</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Baris Saldo Awal --}}
                    <tr style="background: #f8fafc; font-weight: 600; border-bottom: 2px solid #e2e8f0;">
                        <td>{{ $dateFrom ? date('d/m/Y', strtotime($dateFrom)) : '-' }}</td>
                        <td style="color: var(--text-secondary); font-family: monospace;">-</td>
                        <td style="text-align: center;">
                            <span class="badge" style="background: #e2e8f0; color: #475569; font-size: 10px;">SALDO AWAL</span>
                        </td>
                        <td style="color: var(--text-secondary);">-</td>
                        <td style="color: var(--text-secondary);">
                            Saldo Awal Piutang {{ $dateFrom ? 'per ' . date('d/m/Y', strtotime($dateFrom)) : 'Awal Pembukuan' }}
                        </td>
                        <td style="text-align: right; color: var(--text-secondary);">-</td>
                        <td style="text-align: right; color: var(--text-secondary);">-</td>
                        <td style="text-align: right; font-weight: 700; color: {{ $beginningBalance > 0 ? '#0284c7' : 'var(--text-primary)' }};">
                            Rp {{ number_format($beginningBalance, 0, ',', '.') }}
                        </td>
                    </tr>

                    {{-- Baris Mutasi Transaksi --}}
                    @forelse($filteredTransactions as $t)
                    <tr>
                        <td>{{ date('d/m/Y', strtotime($t->date)) }}</td>
                        <td>
                            @if($t->link)
                                <a href="{{ $t->link }}" style="font-family: monospace; font-weight: 700; color: var(--primary); text-decoration: none;">
                                    {{ $t->document_number }}
                                </a>
                            @else
                                <span style="font-family: monospace; font-weight: 600;">{{ $t->document_number }}</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <span class="badge badge-{{ $t->type_badge }}" style="font-size: 10px;">
                                {{ $t->type_label }}
                            </span>
                        </td>
                        <td>
                            <span style="font-family: monospace; font-size: 11.5px; color: var(--text-secondary);">
                                {{ $t->reference_info }}
                            </span>
                        </td>
                        <td>
                            <div style="font-size: 12.5px; color: var(--text-primary);">
                                {{ $t->description }}
                            </div>
                        </td>
                        <td style="text-align: right; font-weight: 600; color: {{ $t->debit > 0 ? '#0284c7' : 'var(--text-secondary)' }};">
                            {{ $t->debit > 0 ? 'Rp ' . number_format($t->debit, 0, ',', '.') : '-' }}
                        </td>
                        <td style="text-align: right; font-weight: 600; color: {{ $t->credit > 0 ? '#059669' : 'var(--text-secondary)' }};">
                            {{ $t->credit > 0 ? 'Rp ' . number_format($t->credit, 0, ',', '.') : '-' }}
                        </td>
                        <td style="text-align: right; font-weight: 700; color: {{ $t->running_balance > 0 ? '#0284c7' : '#059669' }};">
                            Rp {{ number_format($t->running_balance, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 28px; color: var(--text-secondary);">
                            <i class="fa-solid fa-receipt" style="font-size: 24px; margin-bottom: 6px; display: block; opacity: 0.4;"></i>
                            Tidak ada transaksi piutang pada periode filter ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background: #f1f5f9; font-weight: 700; border-top: 2px solid #cbd5e1;">
                        <td colspan="5" style="text-align: right;">TOTAL MUTASI PERIODE INI:</td>
                        <td style="text-align: right; color: #0284c7;">Rp {{ number_format($totalDebit, 0, ',', '.') }}</td>
                        <td style="text-align: right; color: #059669;">Rp {{ number_format($totalCredit, 0, ',', '.') }}</td>
                        <td style="text-align: right; color: {{ $endingBalance > 0 ? '#0284c7' : '#059669' }}; font-size: 14px;">
                            Rp {{ number_format($endingBalance, 0, ',', '.') }}
                        </td>
                    </tr>
                    <tr style="background: #ffffff; font-size: 12px; color: var(--text-secondary);">
                        <td colspan="8" style="padding: 10px 16px; text-align: right;">
                            Formula: <strong>Saldo Akhir</strong> = Saldo Awal (Rp {{ number_format($beginningBalance, 0, ',', '.') }}) + Penambahan (Rp {{ number_format($totalDebit, 0, ',', '.') }}) − Pengurangan (Rp {{ number_format($totalCredit, 0, ',', '.') }}) = <strong>Rp {{ number_format($endingBalance, 0, ',', '.') }}</strong>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
