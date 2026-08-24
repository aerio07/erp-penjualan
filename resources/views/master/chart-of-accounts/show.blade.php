@extends('layouts.app')
@section('title', $chartOfAccount->name)
@section('page-title', 'Detail Akun CoA')

@section('content')
<div class="animate-in">
    @php
    $typeMeta = [
        'asset'     => ['label' => 'Aset',         'icon' => 'fa-building-columns', 'color' => '#3b82f6', 'bg' => '#dbeafe'],
        'liability' => ['label' => 'Kewajiban',     'icon' => 'fa-file-invoice',     'color' => '#ef4444', 'bg' => '#fee2e2'],
        'equity'    => ['label' => 'Ekuitas',       'icon' => 'fa-chart-pie',        'color' => '#8b5cf6', 'bg' => '#ede9fe'],
        'revenue'   => ['label' => 'Pendapatan',    'icon' => 'fa-arrow-trend-up',   'color' => '#10b981', 'bg' => '#d1fae5'],
        'expense'   => ['label' => 'Beban/Biaya',   'icon' => 'fa-arrow-trend-down', 'color' => '#f59e0b', 'bg' => '#fef3c7'],
    ][$chartOfAccount->type] ?? ['label' => ucfirst($chartOfAccount->type), 'icon' => 'fa-book', 'color' => '#6b7280', 'bg' => '#f3f4f6'];
    @endphp

    <div class="page-header">
        <div>
            <h1>{{ $chartOfAccount->name }}</h1>
            <p>
                Kode: <strong style="color:{{ $typeMeta['color'] }}; font-family:monospace; font-size:15px;">{{ $chartOfAccount->code }}</strong> &nbsp;·&nbsp;
                <span class="badge" style="background:{{ $typeMeta['bg'] }}; color:{{ $typeMeta['color'] }};">
                    <i class="fa-solid {{ $typeMeta['icon'] }}" style="margin-right:4px;"></i> {{ $typeMeta['label'] }}
                </span> &nbsp;·&nbsp;
                <span class="badge {{ $chartOfAccount->is_active ? 'badge-done' : 'badge-cancelled' }}">
                    {{ $chartOfAccount->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </p>
        </div>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <form method="POST" action="{{ route('master.chart-of-accounts.toggle-status', $chartOfAccount) }}" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin {{ $chartOfAccount->is_active ? 'menonaktifkan' : 'mengaktifkan' }} akun ini?');">
                @csrf
                @method('PATCH')
                @if($chartOfAccount->is_active)
                    <button type="submit" class="btn btn-secondary" style="color:#b91c1c; border-color:#fca5a5;" title="Nonaktifkan Akun">
                        <i class="fa-solid fa-ban"></i> Nonaktifkan
                    </button>
                @else
                    <button type="submit" class="btn btn-primary" style="background:#16a34a; border-color:#16a34a;" title="Aktifkan Akun">
                        <i class="fa-solid fa-check"></i> Aktifkan
                    </button>
                @endif
            </form>

            <a href="{{ route('master.chart-of-accounts.edit', $chartOfAccount) }}" class="btn btn-secondary">
                <i class="fa-solid fa-pen"></i> Edit
            </a>

            <button type="button" data-confirm-delete="del-coa-show" data-name="{{ $chartOfAccount->name }} ({{ $chartOfAccount->code }})" class="btn btn-danger" title="Hapus Akun">
                <i class="fa-solid fa-trash"></i> Hapus
            </button>
            <form id="del-coa-show" method="POST" action="{{ route('master.chart-of-accounts.destroy', $chartOfAccount) }}" style="display:none;">
                @csrf
                @method('DELETE')
            </form>

            <a href="{{ route('master.chart-of-accounts.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    {{-- Metrics Cards --}}
    <div class="grid grid-4" style="margin-bottom:24px;">
        <div class="card" style="padding:16px;">
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Normal Balance</div>
            <div style="font-size:18px; font-weight:700;">
                <span class="badge {{ $chartOfAccount->normal_balance === 'debit' ? 'badge-confirmed' : 'badge-pending' }}" style="font-size:14px; padding:6px 12px;">
                    {{ strtoupper($chartOfAccount->normal_balance) }}
                </span>
            </div>
        </div>

        <div class="card" style="padding:16px;">
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Total Debit</div>
            <div style="font-size:18px; font-weight:700; color:#2563eb;">
                Rp {{ number_format($totalDebit, 0, ',', '.') }}
            </div>
        </div>

        <div class="card" style="padding:16px;">
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Total Kredit</div>
            <div style="font-size:18px; font-weight:700; color:#dc2626;">
                Rp {{ number_format($totalCredit, 0, ',', '.') }}
            </div>
        </div>

        <div class="card" style="padding:16px; background:#f8fafc; border-left:4px solid {{ $typeMeta['color'] }};">
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Saldo Akhir (Estimasi)</div>
            <div style="font-size:18px; font-weight:800; color:var(--primary);">
                Rp {{ number_format($endingBalance, 0, ',', '.') }}
            </div>
        </div>
    </div>

    {{-- Account Info --}}
    @if($chartOfAccount->description)
    <div class="card" style="margin-bottom:24px;">
        <div class="card-header"><h3>Keterangan Akun</h3></div>
        <div class="card-body">
            <p style="margin:0; color:var(--text-secondary); line-height:1.6;">{{ $chartOfAccount->description }}</p>
        </div>
    </div>
    @endif

    {{-- Journal Lines History --}}
    <div class="card">
        <div class="card-header">
            <h3>Mutasi Jurnal Terakhir</h3>
            <span style="font-size:12px; color:var(--text-secondary);">Maks. 50 mutasi transaksi terbaru</span>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>No. Jurnal</th>
                        <th>Tanggal</th>
                        <th>Keterangan / Memo</th>
                        <th style="text-align:right;">Debit</th>
                        <th style="text-align:right;">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($chartOfAccount->journalLines as $line)
                    <tr>
                        <td>
                            @if($line->journalEntry)
                            <a href="{{ route('accounting.journals.show', $line->journalEntry) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                                {{ $line->journalEntry->entry_number }}
                            </a>
                            @else
                            <span style="color:var(--text-secondary);">-</span>
                            @endif
                        </td>
                        <td>{{ $line->journalEntry ? $line->journalEntry->date->format('d/m/Y') : '-' }}</td>
                        <td>{{ $line->memo ?: ($line->journalEntry ? $line->journalEntry->description : '-') }}</td>
                        <td style="text-align:right; font-family:monospace; font-weight:{{ $line->debit > 0 ? '600' : '400' }}; color:{{ $line->debit > 0 ? '#2563eb' : 'inherit' }};">
                            {{ $line->debit > 0 ? 'Rp ' . number_format($line->debit, 0, ',', '.') : '-' }}
                        </td>
                        <td style="text-align:right; font-family:monospace; font-weight:{{ $line->credit > 0 ? '600' : '400' }}; color:{{ $line->credit > 0 ? '#dc2626' : 'inherit' }};">
                            {{ $line->credit > 0 ? 'Rp ' . number_format($line->credit, 0, ',', '.') : '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align:center; padding:40px; color:var(--text-secondary);">
                            <i class="fa-solid fa-book-open" style="font-size:32px; opacity:0.3; display:block; margin-bottom:12px;"></i>
                            Belum ada riwayat mutasi transaksi pada akun ini
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
