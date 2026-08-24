@extends('layouts.app')
@section('title', 'Chart of Accounts')
@section('page-title', 'Chart of Accounts (Bagan Akun)')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Chart of Accounts (CoA)</h1>
            <p>Daftar bagan akun standar akuntansi dan pembukuan keuangan</p>
        </div>
        <a href="{{ route('master.chart-of-accounts.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Tambah Akun
        </a>
    </div>

    {{-- Filter Bar --}}
    <x-list-filter-bar :action="route('master.chart-of-accounts.index')" placeholder="Cari Kode Akun, Nama Akun, Keterangan...">
        <select name="type" class="form-control" style="height:38px; font-size:13px; min-width:140px; border-radius:6px;">
            <option value="">Semua Tipe Akun</option>
            <option value="asset" {{ request('type') === 'asset' ? 'selected' : '' }}>Aset</option>
            <option value="liability" {{ request('type') === 'liability' ? 'selected' : '' }}>Kewajiban</option>
            <option value="equity" {{ request('type') === 'equity' ? 'selected' : '' }}>Ekuitas</option>
            <option value="revenue" {{ request('type') === 'revenue' ? 'selected' : '' }}>Pendapatan</option>
            <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Beban/Biaya</option>
        </select>
        <select name="is_active" class="form-control" style="height:38px; font-size:13px; min-width:130px; border-radius:6px;">
            <option value="">Semua Status</option>
            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
        </select>
    </x-list-filter-bar>

    @php
    $typeLabels = [
        'asset'     => ['label' => 'Aset',         'icon' => 'fa-building-columns', 'color' => '#3b82f6', 'bg' => '#dbeafe'],
        'liability' => ['label' => 'Kewajiban',     'icon' => 'fa-file-invoice',     'color' => '#ef4444', 'bg' => '#fee2e2'],
        'equity'    => ['label' => 'Ekuitas',       'icon' => 'fa-chart-pie',        'color' => '#8b5cf6', 'bg' => '#ede9fe'],
        'revenue'   => ['label' => 'Pendapatan',    'icon' => 'fa-arrow-trend-up',   'color' => '#10b981', 'bg' => '#d1fae5'],
        'expense'   => ['label' => 'Beban/Biaya',   'icon' => 'fa-arrow-trend-down', 'color' => '#f59e0b', 'bg' => '#fef3c7'],
    ];
    $hasResults = false;
    @endphp

    @foreach($typeLabels as $type => $meta)
    @if(isset($accounts[$type]) && $accounts[$type]->count() > 0)
    @php $hasResults = true; @endphp
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header" style="background:#f8fafc; border-bottom:1px solid var(--border); padding:12px 20px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:34px; height:34px; border-radius:8px; background:{{ $meta['bg'] }}; color:{{ $meta['color'] }}; display:flex; align-items:center; justify-content:center; font-size:15px;">
                    <i class="fa-solid {{ $meta['icon'] }}"></i>
                </div>
                <div>
                    <h3 style="margin:0; font-size:15px;">{{ $meta['label'] }}</h3>
                    <span style="font-size:12px; color:var(--text-secondary);">{{ $accounts[$type]->count() }} akun terdaftar</span>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th style="width:140px;">Kode Akun</th>
                        <th>Nama Akun</th>
                        <th style="width:150px;">Normal Balance</th>
                        <th>Keterangan</th>
                        <th style="text-align:center; width:100px;">Status</th>
                        <th style="text-align:center; width:140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accounts[$type] as $acc)
                    <tr>
                        <td style="font-weight:700; font-family:monospace; font-size:13px;">
                            <a href="{{ route('master.chart-of-accounts.show', $acc) }}" style="color:{{ $meta['color'] }}; text-decoration:none;" title="Lihat Mutasi Jurnal">
                                {{ $acc->code }}
                            </a>
                        </td>
                        <td style="font-weight:600;">
                            <a href="{{ route('master.chart-of-accounts.show', $acc) }}" style="color:inherit; text-decoration:none;" title="Lihat Mutasi Jurnal">
                                {{ $acc->name }}
                            </a>
                        </td>
                        <td>
                            <span class="badge {{ $acc->normal_balance === 'debit' ? 'badge-confirmed' : 'badge-pending' }}" style="font-size:11px;">
                                {{ strtoupper($acc->normal_balance) }}
                            </span>
                        </td>
                        <td style="color:var(--text-secondary); font-size:13px;">{{ Str::limit($acc->description ?? '-', 60) }}</td>
                        <td style="text-align:center;">
                            <form method="POST" action="{{ route('master.chart-of-accounts.toggle-status', $acc) }}" style="display:inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" style="background:none; border:none; padding:0; cursor:pointer;" title="Klik untuk {{ $acc->is_active ? 'menonaktifkan' : 'mengaktifkan' }} akun ini">
                                    <span class="badge {{ $acc->is_active ? 'badge-done' : 'badge-cancelled' }}" style="cursor:pointer; transition:opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                                        <i class="fa-solid {{ $acc->is_active ? 'fa-check' : 'fa-xmark' }}" style="font-size:10px; margin-right:3px;"></i>
                                        {{ $acc->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </button>
                            </form>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                <a href="{{ route('master.chart-of-accounts.show', $acc) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Mutasi">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="{{ route('master.chart-of-accounts.edit', $acc) }}" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form method="POST" action="{{ route('master.chart-of-accounts.toggle-status', $acc) }}" style="display:inline;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-icon {{ $acc->is_active ? 'btn-secondary' : 'btn-primary' }}" style="{{ $acc->is_active ? 'color:#dc2626;' : 'background:#16a34a; border-color:#16a34a;' }}" title="{{ $acc->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}">
                                        <i class="fa-solid {{ $acc->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                                    </button>
                                </form>
                                <button type="button" data-confirm-delete="del-coa-{{ $acc->id }}" data-name="{{ $acc->name }} ({{ $acc->code }})" class="btn btn-danger btn-sm btn-icon" title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <form id="del-coa-{{ $acc->id }}" method="POST" action="{{ route('master.chart-of-accounts.destroy', $acc) }}" style="display:none;">
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
    <div class="card" style="text-align:center; padding:48px; color:var(--text-secondary);">
        <i class="fa-solid fa-book" style="font-size:36px; opacity:0.3; display:block; margin-bottom:12px;"></i>
        Tidak ada data akun CoA yang sesuai dengan pencarian / filter
    </div>
    @endif
</div>
@endsection
