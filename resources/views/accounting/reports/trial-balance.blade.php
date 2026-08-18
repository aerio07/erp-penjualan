@extends('layouts.app')
@section('title', 'Neraca Saldo')
@section('page-title', 'Neraca Saldo (Trial Balance)')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Neraca Saldo (Trial Balance)</h1>
            <p>Rekapitulasi total Debit & Kredit semua akun untuk verifikasi keseimbangan pembukuan</p>
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-body" style="padding:16px;">
            <form method="GET" action="{{ route('accounting.reports.trial-balance') }}" style="display:flex; gap:12px; align-items:flex-end;">
                <div style="width:200px;">
                    <label class="form-label">Per Tanggal (As of Date)</label>
                    <input type="date" name="as_of_date" value="{{ $asOfDate }}" class="form-control" onchange="this.form.submit()">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Daftar Saldo Akun (Per {{ Carbon\Carbon::parse($asOfDate)->format('d F Y') }})</h3>
            <div>
                @if($isBalanced)
                    <span class="badge badge-done" style="font-size:13px; font-weight:700; background:#d1fae5; color:#065f46; padding:6px 12px;">
                        <i class="fa-solid fa-circle-check"></i> DEBIT & KREDIT BALANCED
                    </span>
                @else
                    <span class="badge badge-cancelled" style="font-size:13px; font-weight:700; padding:6px 12px;">
                        <i class="fa-solid fa-triangle-exclamation"></i> UNBALANCED (Selisih: Rp {{ number_format(abs($grandTotalDebit - $grandTotalCredit), 0, ',', '.') }})
                    </span>
                @endif
            </div>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th style="width:120px;">Kode COA</th>
                        <th>Nama Akun</th>
                        <th>Tipe</th>
                        <th style="text-align:right;">Total Debit</th>
                        <th style="text-align:right;">Total Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accounts as $acc)
                    <tr {{ ($acc->total_debit == 0 && $acc->total_credit == 0) ? 'style=opacity:0.5;' : '' }}>
                        <td style="font-weight:700; color:var(--primary);">{{ $acc->code }}</td>
                        <td style="font-weight:500;">
                            <a href="{{ route('accounting.reports.ledger') }}?chart_of_account_id={{ $acc->id }}" style="color:inherit; text-decoration:none;">
                                {{ $acc->name }}
                            </a>
                        </td>
                        <td><span class="badge badge-draft">{{ ucfirst($acc->type) }}</span></td>
                        <td style="text-align:right; font-weight:600; {{ $acc->total_debit > 0 ? 'color:var(--success);' : '' }}">
                            {{ $acc->total_debit > 0 ? 'Rp ' . number_format($acc->total_debit, 0, ',', '.') : '-' }}
                        </td>
                        <td style="text-align:right; font-weight:600; {{ $acc->total_credit > 0 ? 'color:var(--danger);' : '' }}">
                            {{ $acc->total_credit > 0 ? 'Rp ' . number_format($acc->total_credit, 0, ',', '.') : '-' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#f8fafc; font-size:15px; font-weight:800; border-top:2px solid var(--border);">
                        <td colspan="3" style="text-align:right;">TOTAL KESELURUHAN:</td>
                        <td style="text-align:right; color: {{ $isBalanced ? 'var(--success)' : 'var(--danger)' }};">
                            Rp {{ number_format($grandTotalDebit, 0, ',', '.') }}
                        </td>
                        <td style="text-align:right; color: {{ $isBalanced ? 'var(--success)' : 'var(--danger)' }};">
                            Rp {{ number_format($grandTotalCredit, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
