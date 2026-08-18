@extends('layouts.app')
@section('title', 'Detail Jurnal - ' . $journal->entry_number)
@section('page-title', 'Detail Jurnal Umum')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>{{ $journal->entry_number }}</h1>
            <p>Tanggal: {{ $journal->entry_date->format('d F Y') }}</p>
        </div>
        <div style="display:flex; gap:8px;">
            @if($journal->status === 'draft')
            <form method="POST" action="{{ route('accounting.journals.post', $journal) }}" style="display:inline;">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-success">
                    <i class="fa-solid fa-check"></i> Posting Jurnal
                </button>
            </form>
            @endif
            <a href="{{ route('accounting.journals.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <h3>Informasi Entri Jurnal</h3>
            <span class="badge badge-{{ $journal->status === 'posted' ? 'posted' : 'draft' }}">
                {{ ucfirst($journal->status) }}
            </span>
        </div>
        <div class="card-body">
            <div class="form-row form-row-2">
                <div>
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Keterangan Transaksi</div>
                    <div style="font-weight:600;">{{ $journal->description ?? '-' }}</div>
                </div>
                <div>
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Dibuat Oleh</div>
                    <div style="font-weight:600;">{{ $journal->creator->name ?? '-' }} ({{ $journal->created_at->format('d/m/Y H:i') }})</div>
                </div>
                @if($journal->posted_at)
                <div>
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Diposting Oleh</div>
                    <div style="font-weight:600; color:var(--success);">{{ $journal->poster->name ?? '-' }} ({{ $journal->posted_at->format('d/m/Y H:i') }})</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Journal Lines Table --}}
    <div class="card">
        <div class="card-header"><h3>Postingan Akun (Double-Entry)</h3></div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Kode Akun</th>
                        <th>Nama Akun COA</th>
                        <th>Tipe Akun</th>
                        <th>Keterangan Line</th>
                        <th style="text-align:right;">Debit</th>
                        <th style="text-align:right;">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($journal->lines as $line)
                    <tr>
                        <td style="font-weight:700; font-family:monospace; color:var(--primary);">
                            {{ $line->chartOfAccount->code }}
                        </td>
                        <td style="font-weight:600;">{{ $line->chartOfAccount->name }}</td>
                        <td><span class="badge badge-confirmed">{{ ucfirst($line->chartOfAccount->type) }}</span></td>
                        <td>{{ $line->description ?? '-' }}</td>
                        <td style="text-align:right; font-weight:600; color:{{ $line->debit > 0 ? 'var(--text-primary)' : 'var(--text-secondary)' }};">
                            {{ $line->debit > 0 ? 'Rp '.number_format($line->debit, 0, ',', '.') : '-' }}
                        </td>
                        <td style="text-align:right; font-weight:600; color:{{ $line->credit > 0 ? 'var(--text-primary)' : 'var(--text-secondary)' }};">
                            {{ $line->credit > 0 ? 'Rp '.number_format($line->credit, 0, ',', '.') : '-' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#f8fafc; font-weight:700; font-size:14.5px;">
                        <td colspan="4" style="text-align:right;">TOTAL:</td>
                        <td style="text-align:right; color:var(--primary);">Rp {{ number_format($journal->lines->sum('debit'), 0, ',', '.') }}</td>
                        <td style="text-align:right; color:var(--primary);">Rp {{ number_format($journal->lines->sum('credit'), 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
