@extends('layouts.app')
@section('title', 'Buku Besar')
@section('page-title', 'Buku Besar (General Ledger)')

@section('content')
@php
    $periodDebit = $lines->sum('debit');
    $periodCredit = $lines->sum('credit');
    $endingBalance = $lines->isNotEmpty() ? $lines->last()->running_balance : $openingBalance;
@endphp

<div class="animate-in flex flex-col gap-6 ledger-report-page">
    <x-page-header title="Buku Besar (General Ledger)" subtitle="Riwayat mutasi debet/kredit dan saldo berjalan per akun akuntansi">
        <x-slot:actions>
            <a href="{{ route('accounting.reports.ledger.pdf', request()->query()) }}" target="_blank" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-file-pdf"></i> PDF Laporan
            </a>
        </x-slot:actions>
    </x-page-header>

    {{-- Filter Form --}}
    <div class="card mb-0 ledger-filter-card">
        <div class="card-body p-0">
            <form method="GET" action="{{ route('accounting.reports.ledger') }}" class="flex flex-col sm:flex-row flex-wrap sm:items-end gap-3">
                <div class="w-full sm:flex-1 sm:min-w-[260px]">
                    <label class="form-label">Pilih Akun COA <span class="text-rose-600">*</span></label>
                    <select name="chart_of_account_id" class="form-control" onchange="this.form.submit()" required>
                        @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ $accountId == $acc->id ? 'selected' : '' }}>
                            {{ $acc->code }} - {{ $acc->name }} ({{ ucfirst($acc->type) }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full sm:w-44">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
                </div>

                <div class="w-full sm:w-44">
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

    @if($selectedAccount)
    <div class="card ledger-report-card">
        <div class="card-header">
            <div>
                <h3>Akun: {{ $selectedAccount->code }} - {{ $selectedAccount->name }}</h3>
                <div style="font-size:12.5px; color:var(--text-secondary); margin-top:2px;">
                    Tipe: <strong>{{ strtoupper($selectedAccount->type) }}</strong> · Saldo Normal: <strong>{{ strtoupper($selectedAccount->normal_balance) }}</strong>
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:12px; color:var(--text-secondary);">Saldo Awal (sebelum {{ Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}):</div>
                <div style="font-size:16px; font-weight:700; color:var(--primary);">
                    Rp {{ number_format($openingBalance, 0, ',', '.') }}
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="erp-table ledger-table">
                <thead>
                    <tr>
                        <th class="ledger-date-col">Tgl Transaksi</th>
                        <th class="ledger-ref-col">No. Referensi</th>
                        <th>Keterangan Transaksi</th>
                        <th class="ledger-money-col" style="text-align:right;">Debit</th>
                        <th class="ledger-money-col" style="text-align:right;">Kredit</th>
                        <th class="ledger-money-col" style="text-align:right;">Saldo Berjalan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="background:#f8fafc; font-weight:600;">
                        <td>{{ Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}</td>
                        <td>-</td>
                        <td><em>Saldo Awal Periode</em></td>
                        <td style="text-align:right;">-</td>
                        <td style="text-align:right;">-</td>
                        <td style="text-align:right; font-weight:700; color:var(--primary);">
                            Rp {{ number_format($openingBalance, 0, ',', '.') }}
                        </td>
                    </tr>

                    @forelse($lines as $line)
                    <tr>
                        <td>{{ $line->journalEntry->entry_date->format('d/m/Y') }}</td>
                        <td style="font-weight:600;">
                            <a href="{{ route('accounting.journals.show', $line->journalEntry->id) }}" class="ledger-ref-link" style="color:var(--primary); text-decoration:none;">
                                {{ $line->journalEntry->entry_number }}
                            </a>
                        </td>
                        <td>
                            <div>{{ $line->description ?? $line->journalEntry->description }}</div>
                        </td>
                        <td style="text-align:right; font-weight:600; {{ $line->debit > 0 ? 'color:var(--success);' : '' }}">
                            {{ $line->debit > 0 ? 'Rp ' . number_format($line->debit, 0, ',', '.') : '-' }}
                        </td>
                        <td style="text-align:right; font-weight:600; {{ $line->credit > 0 ? 'color:var(--danger);' : '' }}">
                            {{ $line->credit > 0 ? 'Rp ' . number_format($line->credit, 0, ',', '.') : '-' }}
                        </td>
                        <td style="text-align:right; font-weight:700; font-size:14px; color:var(--text-primary);">
                            Rp {{ number_format($line->running_balance, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:36px; color:var(--text-secondary);">
                            Tidak ada transaksi mutasi pada rentang tanggal ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align:right; font-weight:800;">Total Mutasi Periode</td>
                        <td style="text-align:right; font-weight:800;">Rp {{ number_format($periodDebit, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:800;">Rp {{ number_format($periodCredit, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:800;">Rp {{ number_format($endingBalance, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
