@extends('layouts.app')
@section('title', 'Buku Besar')
@section('page-title', 'Buku Besar (General Ledger)')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Buku Besar (General Ledger)</h1>
            <p>Riwayat mutasi debet/kredit dan saldo berjalan per akun akuntansi</p>
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-body" style="padding:16px;">
            <form method="GET" action="{{ route('accounting.reports.ledger') }}" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
                <div style="flex:1; min-width:240px;">
                    <label class="form-label">Pilih Akun COA <span style="color:var(--danger);">*</span></label>
                    <select name="chart_of_account_id" class="form-control" onchange="this.form.submit()" required>
                        @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ $accountId == $acc->id ? 'selected' : '' }}>
                            {{ $acc->code }} - {{ $acc->name }} ({{ ucfirst($acc->type) }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div style="width:160px;">
                    <label class="form-label">Dari Tgl</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
                </div>

                <div style="width:160px;">
                    <label class="form-label">Sampai Tgl</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
                </div>

                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    @if($selectedAccount)
    <div class="card">
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
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Tgl Transaksi</th>
                        <th>No. Referensi</th>
                        <th>Keterangan Transaksi</th>
                        <th style="text-align:right;">Debit</th>
                        <th style="text-align:right;">Kredit</th>
                        <th style="text-align:right;">Saldo Berjalan</th>
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
                            <a href="{{ route('accounting.journals.show', $line->journalEntry->id) }}" style="color:var(--primary); text-decoration:none;">
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
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
