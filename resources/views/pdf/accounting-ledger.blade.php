@extends('layouts.pdf')

@section('title', 'Buku Besar - ' . ($selectedAccount->code ?? ''))
@section('doc-title', 'LAPORAN BUKU BESAR')
@section('doc-subtitle', 'Periode ' . \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') . ' s/d ' . \Carbon\Carbon::parse($dateTo)->format('d/m/Y'))

@section('extra_css')
<style>
    @page {
        margin: 12mm 10mm 12mm 10mm;
        size: a4 landscape;
    }

    body {
        padding: 24px 34px 30px 34px;
        font-size: 8pt;
    }

    .doc-header {
        margin-bottom: 9px;
    }

    .doc-title {
        font-size: 17pt;
    }

    .header-divider {
        margin: 10px 0 12px 0;
    }

    .ledger-summary {
        width: 100%;
        margin-bottom: 12px;
        border: 1px solid #d6d6d6;
    }

    .ledger-summary td {
        width: 25%;
        padding: 6px 8px;
        border-right: 1px solid #d6d6d6;
        vertical-align: top;
    }

    .ledger-summary td:last-child {
        border-right: none;
    }

    .summary-label {
        display: block;
        font-size: 7pt;
        color: #666666;
        font-weight: 700;
        letter-spacing: .4px;
        text-transform: uppercase;
        margin-bottom: 2px;
    }

    .summary-value {
        display: block;
        font-size: 8.2pt;
        color: #111111;
        font-weight: 800;
        line-height: 1.25;
    }

    .ledger-table {
        width: 100%;
        table-layout: fixed;
    }

    .ledger-table thead th {
        background: #f2f2f2;
        color: #111111;
        font-size: 7.4pt;
        font-weight: 800;
        padding: 6px 6px;
        border-top: 1px solid #dcdcdc;
        border-bottom: 1px solid #dcdcdc;
        text-transform: uppercase;
    }

    .ledger-table tbody td,
    .ledger-table tfoot td {
        font-size: 7.6pt;
        color: #222222;
        padding: 6px 6px;
        border-bottom: 1px solid #eeeeee;
        vertical-align: top;
    }

    .ledger-table tfoot td {
        background: #f7f7f7;
        border-top: 1.5px solid #222222;
        border-bottom: 1px solid #dcdcdc;
        font-weight: 800;
    }

    .opening-row td {
        background: #fafafa;
        font-weight: 700;
    }

    .col-date { width: 9%; }
    .col-ref { width: 14%; }
    .col-desc { width: 35%; }
    .col-money { width: 14%; }
    .money { text-align: right; white-space: nowrap; }
    .ref-text { font-weight: 700; white-space: nowrap; }
    .muted { color: #777777; }
</style>
@endsection

@section('content')
    @php
        $periodDebit = $lines->sum('debit');
        $periodCredit = $lines->sum('credit');
        $endingBalance = $lines->isNotEmpty() ? $lines->last()->running_balance : $openingBalance;
    @endphp

    <table class="ledger-summary">
        <tr>
            <td>
                <span class="summary-label">Akun COA</span>
                <span class="summary-value">{{ $selectedAccount->code ?? '-' }} - {{ $selectedAccount->name ?? '-' }}</span>
            </td>
            <td>
                <span class="summary-label">Tipe / Saldo Normal</span>
                <span class="summary-value">{{ strtoupper($selectedAccount->type ?? '-') }} / {{ strtoupper($selectedAccount->normal_balance ?? '-') }}</span>
            </td>
            <td>
                <span class="summary-label">Saldo Awal</span>
                <span class="summary-value">Rp {{ number_format($openingBalance, 0, ',', '.') }}</span>
            </td>
            <td>
                <span class="summary-label">Saldo Akhir</span>
                <span class="summary-value">Rp {{ number_format($endingBalance, 0, ',', '.') }}</span>
            </td>
        </tr>
    </table>

    <div class="items-section">
        <div class="section-heading">RINCIAN MUTASI AKUN</div>
        <table class="ledger-table">
            <thead>
                <tr>
                    <th class="text-left col-date">Tanggal</th>
                    <th class="text-left col-ref">No. Referensi</th>
                    <th class="text-left col-desc">Keterangan Transaksi</th>
                    <th class="text-right col-money">Debit</th>
                    <th class="text-right col-money">Kredit</th>
                    <th class="text-right col-money">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <tr class="opening-row">
                    <td>{{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}</td>
                    <td class="muted">-</td>
                    <td><em>Saldo Awal Periode</em></td>
                    <td class="money muted">-</td>
                    <td class="money muted">-</td>
                    <td class="money">Rp {{ number_format($openingBalance, 0, ',', '.') }}</td>
                </tr>

                @forelse($lines as $line)
                <tr>
                    <td>{{ $line->journalEntry->entry_date->format('d/m/Y') }}</td>
                    <td class="ref-text">{{ $line->journalEntry->entry_number }}</td>
                    <td>{{ $line->description ?? $line->journalEntry->description }}</td>
                    <td class="money">{{ $line->debit > 0 ? 'Rp ' . number_format($line->debit, 0, ',', '.') : '-' }}</td>
                    <td class="money">{{ $line->credit > 0 ? 'Rp ' . number_format($line->credit, 0, ',', '.') : '-' }}</td>
                    <td class="money" style="font-weight:700;">Rp {{ number_format($line->running_balance, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 16px; color: #777777;">
                        Tidak ada transaksi mutasi pada rentang tanggal ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right">Total Mutasi Periode</td>
                    <td class="money">Rp {{ number_format($periodDebit, 0, ',', '.') }}</td>
                    <td class="money">Rp {{ number_format($periodCredit, 0, ',', '.') }}</td>
                    <td class="money">Rp {{ number_format($endingBalance, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
@endsection
