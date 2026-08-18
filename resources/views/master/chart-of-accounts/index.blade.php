@extends('layouts.app')
@section('title', 'Chart of Accounts')
@section('page-title', 'Chart of Accounts')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Chart of Accounts</h1>
            <p>Daftar akun standar akuntansi perusahaan</p>
        </div>
        <a href="{{ route('master.chart-of-accounts.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Tambah Akun
        </a>
    </div>

    @php
    $typeLabels = [
        'asset'     => ['label' => 'Aset',         'icon' => 'fa-building-columns', 'color' => '#3b82f6', 'bg' => '#dbeafe'],
        'liability' => ['label' => 'Kewajiban',     'icon' => 'fa-file-invoice',     'color' => '#ef4444', 'bg' => '#fee2e2'],
        'equity'    => ['label' => 'Ekuitas',       'icon' => 'fa-chart-pie',        'color' => '#8b5cf6', 'bg' => '#ede9fe'],
        'revenue'   => ['label' => 'Pendapatan',    'icon' => 'fa-arrow-trend-up',   'color' => '#10b981', 'bg' => '#d1fae5'],
        'expense'   => ['label' => 'Beban/Biaya',   'icon' => 'fa-arrow-trend-down', 'color' => '#f59e0b', 'bg' => '#fef3c7'],
    ];
    @endphp

    @foreach($typeLabels as $type => $meta)
    @if(isset($accounts[$type]) && $accounts[$type]->count() > 0)
    <div class="card" style="margin-bottom:16px;">
        <div class="card-header">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:36px; height:36px; border-radius:10px; background:{{ $meta['bg'] }}; color:{{ $meta['color'] }}; display:flex; align-items:center; justify-content:center; font-size:16px;">
                    <i class="fa-solid {{ $meta['icon'] }}"></i>
                </div>
                <div>
                    <h3>{{ $meta['label'] }}</h3>
                    <span style="font-size:12px; color:var(--text-secondary);">{{ $accounts[$type]->count() }} akun</span>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Akun</th>
                        <th>Normal Balance</th>
                        <th>Keterangan</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accounts[$type] as $acc)
                    <tr>
                        <td style="font-weight:700; color:{{ $meta['color'] }}; font-family:monospace; font-size:14px;">{{ $acc->code }}</td>
                        <td style="font-weight:500;">{{ $acc->name }}</td>
                        <td>
                            <span class="badge {{ $acc->normal_balance === 'debit' ? 'badge-confirmed' : 'badge-pending' }}">
                                {{ ucfirst($acc->normal_balance) }}
                            </span>
                        </td>
                        <td style="color:var(--text-secondary); font-size:13px;">{{ Str::limit($acc->description ?? '', 60) }}</td>
                        <td style="text-align:center;">
                            <span class="badge {{ $acc->is_active ? 'badge-done' : 'badge-cancelled' }}">
                                {{ $acc->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                <a href="{{ route('master.chart-of-accounts.edit', $acc) }}" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <button data-confirm-delete="del-coa-{{ $acc->id }}" class="btn btn-danger btn-sm btn-icon" title="Hapus">
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
</div>
@endsection
