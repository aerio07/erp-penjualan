@extends('layouts.app')
@section('title', '[' . $chartOfAccount->code . '] ' . $chartOfAccount->name)
@section('page-title', 'Detail Bagan Akun (CoA)')

@section('content')
<div class="animate-in">
    @php
    $typeMeta = [
        'asset'     => ['label' => 'Aset (Aktiva)',         'icon' => 'fa-building-columns', 'color' => '#2563eb', 'bg' => '#eff6ff', 'border' => '#bfdbfe'],
        'liability' => ['label' => 'Kewajiban (Hutang)',    'icon' => 'fa-file-invoice-dollar', 'color' => '#dc2626', 'bg' => '#fef2f2', 'border' => '#fecaca'],
        'equity'    => ['label' => 'Ekuitas (Modal)',       'icon' => 'fa-chart-pie',        'color' => '#7c3aed', 'bg' => '#f5f3ff', 'border' => '#ddd6fe'],
        'revenue'   => ['label' => 'Pendapatan (Penjualan)', 'icon' => 'fa-arrow-trend-up',   'color' => '#16a34a', 'bg' => '#f0fdf4', 'border' => '#bbf7d0'],
        'expense'   => ['label' => 'Beban & Biaya',         'icon' => 'fa-arrow-trend-down', 'color' => '#d97706', 'bg' => '#fffbeb', 'border' => '#fde68a'],
    ][$chartOfAccount->type] ?? ['label' => ucfirst($chartOfAccount->type), 'icon' => 'fa-book', 'color' => '#6b7280', 'bg' => '#f3f4f6', 'border' => '#e5e7eb'];
    @endphp

    {{-- Breadcrumb Navigation --}}
    <div style="display:flex; align-items:center; gap:8px; font-size:13px; color:var(--text-secondary); margin-bottom:14px;">
        <a href="{{ route('master.chart-of-accounts.index') }}" style="color:var(--text-secondary); text-decoration:none;">
            <i class="fa-solid fa-layer-group"></i> Chart of Accounts
        </a>
        <i class="fa-solid fa-chevron-right" style="font-size:10px; color:#cbd5e1;"></i>
        <span class="badge" style="background:{{ $typeMeta['bg'] }}; color:{{ $typeMeta['color'] }}; font-size:11px; font-weight:600;">
            {{ $typeMeta['label'] }}
        </span>
        @if($chartOfAccount->parent)
        <i class="fa-solid fa-chevron-right" style="font-size:10px; color:#cbd5e1;"></i>
        <a href="{{ route('master.chart-of-accounts.show', $chartOfAccount->parent) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
            [{{ $chartOfAccount->parent->code }}] {{ $chartOfAccount->parent->name }}
        </a>
        @endif
        <i class="fa-solid fa-chevron-right" style="font-size:10px; color:#cbd5e1;"></i>
        <span style="color:var(--primary); font-weight:700;">{{ $chartOfAccount->name }}</span>
    </div>

    <div class="page-header">
        <div>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:4px;">
                <span style="font-family:monospace; font-size:18px; font-weight:800; color:{{ $typeMeta['color'] }}; background:{{ $typeMeta['bg'] }}; padding:2px 10px; border-radius:6px; border:1px solid {{ $typeMeta['border'] }};">
                    {{ $chartOfAccount->code }}
                </span>
                <h1 style="margin:0; font-size:24px;">{{ $chartOfAccount->name }}</h1>
            </div>
            <p style="margin:0;">
                <span class="badge {{ $chartOfAccount->is_active ? 'badge-done' : 'badge-cancelled' }}">
                    <i class="fa-solid {{ $chartOfAccount->is_active ? 'fa-check' : 'fa-xmark' }}" style="font-size:10px; margin-right:3px;"></i>
                    {{ $chartOfAccount->is_active ? 'Akun Aktif (Dapat Dijurnal)' : 'Nonaktif' }}
                </span>
                @if($chartOfAccount->parent)
                &nbsp;·&nbsp;
                <span style="font-size:13px; color:var(--text-secondary);">
                    Sub-akun dari: <a href="{{ route('master.chart-of-accounts.show', $chartOfAccount->parent) }}" style="color:var(--primary); font-weight:600; text-decoration:none;"><i class="fa-solid fa-sitemap" style="font-size:11px; margin-right:3px;"></i>[{{ $chartOfAccount->parent->code }}] {{ $chartOfAccount->parent->name }}</a>
                </span>
                @else
                &nbsp;·&nbsp;
                <span style="font-size:13px; color:#64748b; font-style:italic;">
                    <i class="fa-solid fa-folder-tree" style="margin-right:4px;"></i> Akun Induk Tingkat 1 (Header Utama)
                </span>
                @endif
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
                <i class="fa-solid fa-pen"></i> Edit Akun
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

    {{-- Financial Metrics Cards --}}
    <div class="grid grid-4" style="margin-bottom:24px;">
        <div class="card" style="padding:16px; border-top:3px solid var(--primary);">
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px; font-weight:500;">Normal Balance</div>
            <div style="font-size:16px; font-weight:800;">
                <span class="badge {{ $chartOfAccount->normal_balance === 'debit' ? 'badge-confirmed' : 'badge-pending' }}" style="font-size:13px; padding:4px 10px;">
                    <i class="fa-solid {{ $chartOfAccount->normal_balance === 'debit' ? 'fa-arrow-down' : 'fa-arrow-up' }}" style="font-size:10px; margin-right:4px;"></i>
                    {{ strtoupper($chartOfAccount->normal_balance) }}
                </span>
            </div>
            <div style="font-size:11px; color:var(--text-secondary); margin-top:4px;">
                {{ $chartOfAccount->normal_balance === 'debit' ? 'Bertambah di Debit' : 'Bertambah di Kredit' }}
            </div>
        </div>

        <div class="card" style="padding:16px; border-top:3px solid #2563eb;">
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px; font-weight:500;">Total Mutasi Debit</div>
            <div style="font-size:18px; font-weight:800; color:#2563eb;">
                Rp {{ number_format($totalDebit, 0, ',', '.') }}
            </div>
            <div style="font-size:11px; color:var(--text-secondary); margin-top:4px;">Akumulasi debit akun ini</div>
        </div>

        <div class="card" style="padding:16px; border-top:3px solid #dc2626;">
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px; font-weight:500;">Total Mutasi Kredit</div>
            <div style="font-size:18px; font-weight:800; color:#dc2626;">
                Rp {{ number_format($totalCredit, 0, ',', '.') }}
            </div>
            <div style="font-size:11px; color:var(--text-secondary); margin-top:4px;">Akumulasi kredit akun ini</div>
        </div>

        <div class="card" style="padding:16px; background:#f8fafc; border-top:3px solid {{ $typeMeta['color'] }};">
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px; font-weight:500;">Estimasi Saldo Akhir</div>
            <div style="font-size:20px; font-weight:900; color:var(--primary);">
                Rp {{ number_format($endingBalance, 0, ',', '.') }}
            </div>
            <div style="font-size:11px; color:var(--text-secondary); margin-top:4px;">Sesuai rumus saldo normal</div>
        </div>
    </div>

    {{-- Sub-accounts Section (If this is a parent account) --}}
    @if($chartOfAccount->children->count() > 0)
    <div class="card" style="margin-bottom:24px;">
        <div class="card-header" style="background:#f8fafc;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; font-size:15px;"><i class="fa-solid fa-sitemap" style="color:var(--primary); margin-right:6px;"></i> Sub-Akun Terdaftar ({{ $chartOfAccount->children->count() }})</h3>
                <a href="{{ route('master.chart-of-accounts.create') }}?parent_id={{ $chartOfAccount->id }}" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-plus"></i> Tambah Sub-Akun
                </a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th style="width:140px;">Kode Sub-Akun</th>
                        <th>Nama Akun</th>
                        <th>Normal Balance</th>
                        <th>Keterangan</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center; width:120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($chartOfAccount->children as $child)
                    <tr>
                        <td style="font-family:monospace; font-weight:700;">
                            <a href="{{ route('master.chart-of-accounts.show', $child) }}" style="color:var(--primary); text-decoration:none;">
                                {{ $child->code }}
                            </a>
                        </td>
                        <td style="font-weight:600;">
                            <a href="{{ route('master.chart-of-accounts.show', $child) }}" style="color:inherit; text-decoration:none;">
                                {{ $child->name }}
                            </a>
                        </td>
                        <td>
                            <span class="badge {{ $child->normal_balance === 'debit' ? 'badge-confirmed' : 'badge-pending' }}" style="font-size:11px;">
                                {{ strtoupper($child->normal_balance) }}
                            </span>
                        </td>
                        <td style="font-size:13px; color:var(--text-secondary);">{{ $child->description ?: '-' }}</td>
                        <td style="text-align:center;">
                            <span class="badge {{ $child->is_active ? 'badge-done' : 'badge-cancelled' }}">
                                {{ $child->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <a href="{{ route('master.chart-of-accounts.show', $child) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('master.chart-of-accounts.edit', $child) }}" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

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
