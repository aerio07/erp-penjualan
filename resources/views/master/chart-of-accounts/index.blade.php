@extends('layouts.app')
@section('title', 'Chart of Accounts')
@section('page-title', 'Chart of Accounts (Bagan Akun)')

@section('content')
<div class="animate-in">
    <div class="page-header" style="margin-bottom: 24px;">
        <div>
            <h1>Chart of Accounts (CoA)</h1>
            <p>Bagan akun standar akuntansi dan struktur pembukuan keuangan</p>
        </div>
        <div style="display: flex; gap: 12px; align-items: center;">
            <a href="{{ route('accounting.reports.trial-balance') }}" class="btn btn-secondary">
                <i class="fa-solid fa-scale-balanced"></i> Neraca Saldo
            </a>
            <a href="{{ route('master.chart-of-accounts.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Tambah Akun
            </a>
        </div>
    </div>

    @php
    $typeMeta = [
        'asset'     => ['label' => 'Aset (Aktiva)',         'short' => 'Aset',       'icon' => 'fa-building-columns', 'color' => '#2563eb', 'bg' => '#eff6ff', 'border' => '#bfdbfe'],
        'liability' => ['label' => 'Kewajiban (Hutang)',    'short' => 'Kewajiban',  'icon' => 'fa-file-invoice-dollar', 'color' => '#dc2626', 'bg' => '#fef2f2', 'border' => '#fecaca'],
        'equity'    => ['label' => 'Ekuitas (Modal)',       'short' => 'Ekuitas',    'icon' => 'fa-chart-pie',        'color' => '#7c3aed', 'bg' => '#f5f3ff', 'border' => '#ddd6fe'],
        'revenue'   => ['label' => 'Pendapatan (Penjualan)', 'short' => 'Pendapatan', 'icon' => 'fa-arrow-trend-up', 'color' => '#16a34a', 'bg' => '#f0fdf4', 'border' => '#bbf7d0'],
        'expense'   => ['label' => 'Beban & Biaya',         'short' => 'Beban',      'icon' => 'fa-arrow-trend-down', 'color' => '#d97706', 'bg' => '#fffbeb', 'border' => '#fde68a'],
    ];
    $totalAccounts = $allAccounts->count();
    @endphp

    {{-- Standard ERP Filter Bar --}}
    <x-list-filter-bar :action="route('master.chart-of-accounts.index')" placeholder="Cari Kode Akun, Nama Akun, Deskripsi...">
        <select name="type" class="form-control" style="height: 38px; font-size: 13px; min-width: 170px; border-radius: 6px;">
            <option value="">Semua Kategori</option>
            <option value="asset" {{ request('type') === 'asset' ? 'selected' : '' }}>Aset (Aktiva)</option>
            <option value="liability" {{ request('type') === 'liability' ? 'selected' : '' }}>Kewajiban (Hutang)</option>
            <option value="equity" {{ request('type') === 'equity' ? 'selected' : '' }}>Ekuitas (Modal)</option>
            <option value="revenue" {{ request('type') === 'revenue' ? 'selected' : '' }}>Pendapatan (Penjualan)</option>
            <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Beban (Biaya)</option>
        </select>

        <select name="is_active" class="form-control" style="height: 38px; font-size: 13px; min-width: 140px; border-radius: 6px;">
            <option value="">Semua Status</option>
            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
        </select>
    </x-list-filter-bar>

    {{-- Categories Listing --}}
    @php $hasResults = false; @endphp

    @foreach($typeMeta as $type => $meta)
    @if(isset($accounts[$type]) && $accounts[$type]->count() > 0)
    @php $hasResults = true; @endphp
    <div class="card" style="margin-bottom: 28px; overflow: hidden; border-top: 3px solid {{ $meta['color'] }};">
        <div class="card-header" style="background: #fafbfc; border-bottom: 1px solid var(--border); padding: 14px 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: {{ $meta['bg'] }}; color: {{ $meta['color'] }}; display: flex; align-items: center; justify-content: center; font-size: 16px; border: 1px solid {{ $meta['border'] }}; flex-shrink: 0;">
                        <i class="fa-solid {{ $meta['icon'] }}"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 16px; color: var(--primary); font-weight: 700;">{{ $meta['label'] }}</h3>
                        <span style="font-size: 12px; color: var(--text-secondary);">{{ $accounts[$type]->count() }} akun terdaftar</span>
                    </div>
                </div>
                <div>
                    <a href="{{ route('master.chart-of-accounts.create') }}?type={{ $type }}" class="btn btn-secondary btn-sm" style="font-size: 12px;">
                        <i class="fa-solid fa-plus"></i> Tambah Akun {{ $meta['short'] }}
                    </a>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr style="background: #f8fafc;">
                        <th style="width: 140px;">Kode Akun</th>
                        <th>Nama Akun</th>
                        <th style="width: 210px;">Akun Induk (Parent)</th>
                        <th style="width: 140px; text-align: center;">Normal Balance</th>
                        <th>Keterangan</th>
                        <th style="text-align: center; width: 110px;">Status</th>
                        <th style="text-align: center; width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accounts[$type] as $acc)
                    @php $isChild = !empty($acc->parent_id); @endphp
                    <tr style="{{ $isChild ? 'background: #fafbfc;' : 'background: #ffffff;' }}">
                        <td style="font-family: monospace; font-size: 13px; font-weight: 700; white-space: nowrap;">
                            <a href="{{ route('master.chart-of-accounts.show', $acc) }}" style="color: {{ $meta['color'] }}; text-decoration: none;" title="Lihat Mutasi">
                                {{ $acc->code }}
                            </a>
                        </td>
                        <td style="font-weight: 600; {{ $isChild ? 'padding-left: 28px;' : '' }}">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                @if($isChild)
                                    <i class="fa-solid fa-turn-up fa-rotate-90" style="color: #94a3b8; font-size: 11px; flex-shrink: 0;"></i>
                                    <a href="{{ route('master.chart-of-accounts.show', $acc) }}" style="color: #334155; text-decoration: none;">
                                        {{ $acc->name }}
                                    </a>
                                @else
                                    <i class="fa-solid fa-folder-tree" style="color: {{ $meta['color'] }}; font-size: 13px; opacity: 0.8; flex-shrink: 0;"></i>
                                    <a href="{{ route('master.chart-of-accounts.show', $acc) }}" style="color: var(--primary); text-decoration: none; font-weight: 700;">
                                        {{ $acc->name }}
                                    </a>
                                @endif

                                @if($acc->children->count() > 0)
                                    <span class="badge" style="background: #e0e7ff; color: #4338ca; font-size: 10px; font-weight: 600; margin-left: 4px;" title="{{ $acc->children->count() }} sub-akun">
                                        {{ $acc->children->count() }} Sub-Akun
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($acc->parent)
                                <a href="{{ route('master.chart-of-accounts.show', $acc->parent) }}" style="text-decoration: none;" title="Akun Induk">
                                    <span class="badge" style="background: #f1f5f9; color: #475569; font-size: 11px; font-weight: 500; border: 1px solid #e2e8f0;">
                                        <i class="fa-solid fa-sitemap" style="font-size: 10px; margin-right: 4px; color: #64748b;"></i>[{{ $acc->parent->code }}] {{ Str::limit($acc->parent->name, 16) }}
                                    </span>
                                </a>
                            @else
                                <span style="font-size: 11px; color: #94a3b8; font-style: italic;">Induk Utama (Level 1)</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <span class="badge {{ $acc->normal_balance === 'debit' ? 'badge-confirmed' : 'badge-pending' }}" style="font-size: 11px; text-transform: uppercase;">
                                {{ $acc->normal_balance }}
                            </span>
                        </td>
                        <td style="color: var(--text-secondary); font-size: 13px;">
                            {{ $acc->description ?: '-' }}
                        </td>
                        <td style="text-align: center;">
                            <form method="POST" action="{{ route('master.chart-of-accounts.toggle-status', $acc) }}" style="display: inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" style="background: none; border: none; padding: 0; cursor: pointer;" title="Klik untuk {{ $acc->is_active ? 'menonaktifkan' : 'mengaktifkan' }} akun">
                                    <span class="badge {{ $acc->is_active ? 'badge-done' : 'badge-cancelled' }}" style="cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                                        <i class="fa-solid {{ $acc->is_active ? 'fa-check' : 'fa-xmark' }}" style="font-size: 10px; margin-right: 3px;"></i>
                                        {{ $acc->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </button>
                            </form>
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                                <a href="{{ route('master.chart-of-accounts.show', $acc) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Mutasi Jurnal">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="{{ route('master.chart-of-accounts.edit', $acc) }}" class="btn btn-secondary btn-sm btn-icon" title="Edit Akun">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <button type="button" data-confirm-delete="del-coa-{{ $acc->id }}" data-name="{{ $acc->name }} ({{ $acc->code }})" class="btn btn-danger btn-sm btn-icon" title="Hapus Akun">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <form id="del-coa-{{ $acc->id }}" method="POST" action="{{ route('master.chart-of-accounts.destroy', $acc) }}" style="display: none;">
                                    @csrf @method('DELETE')
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    @endforeach

    @if(!$hasResults)
    <div class="card" style="padding: 48px 20px; text-align: center; color: var(--text-secondary);">
        <i class="fa-solid fa-book-open" style="font-size: 40px; margin-bottom: 12px; display: block; opacity: 0.3;"></i>
        <div style="font-size: 16px; font-weight: 600; color: var(--primary); margin-bottom: 6px;">Tidak ada akun CoA yang ditemukan</div>
        <p style="font-size: 13px; margin: 0;">Coba sesuaikan kata kunci pencarian atau filter kategori yang dipilih.</p>
    </div>
    @endif
</div>
@endsection

