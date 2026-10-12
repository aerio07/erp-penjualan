@extends('layouts.app')
@section('title', 'Cash Bank Expense')
@section('page-title', 'Cash Bank Expense')

@section('content')
<div class="animate-in">
    <x-page-header title="Cash Bank Expense" subtitle="Pencatatan pengeluaran kas dan bank">
        <x-slot:actions>
            <a href="{{ route('accounting.cash-bank-expenses.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Buat Baru
            </a>
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:20px; display:flex; align-items:center; gap:10px;">
        <i class="fa-solid fa-circle-check" style="font-size:18px;"></i>
        <div>{{ session('success') }}</div>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger" style="margin-bottom:20px; display:flex; align-items:center; gap:10px;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size:18px;"></i>
        <div>{{ session('error') }}</div>
    </div>
    @endif

    <x-list-filter-bar 
        :action="route('accounting.cash-bank-expenses.index')" 
        placeholder="Cari No. Pengeluaran, No. Bukti, Company, Catatan..." 
        :showDateFilter="true" 
        dateFromParam="date_from" 
        dateToParam="date_to"
    >
        <select name="status" class="form-control filter-select">
            <option value="">Semua Status</option>
            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
            <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>Posted</option>
        </select>

        <select name="type" class="form-control filter-select">
            <option value="">Semua Tipe</option>
            <option value="KAS" {{ request('type') === 'KAS' ? 'selected' : '' }}>KAS</option>
            <option value="BANK" {{ request('type') === 'BANK' ? 'selected' : '' }}>BANK</option>
            <option value="DP" {{ request('type') === 'DP' ? 'selected' : '' }}>DP</option>
        </select>
    </x-list-filter-bar>

    <x-data-table :paginator="$expenses">
        <x-slot:head>
            <tr>
                <th style="width:50px;">No</th>
                <x-sortable-header column="expense_number" title="No. Pengeluaran" />
                <th>No. Bukti</th>
                <th style="text-align:center;">Tipe</th>
                <x-sortable-header column="transaction_date" title="Tanggal" :isDefault="true" />
                <th>Company</th>
                <th>Akun</th>
                <x-sortable-header column="amount" title="Jumlah" align="right" />
                <x-sortable-header column="status" title="Status" align="center" />
                <th style="text-align:center;" class="col-actions">Aksi</th>
            </tr>
        </x-slot:head>

        @forelse($expenses as $i => $expense)
        <tr>
            <td style="color:var(--text-secondary); font-size:12px;">{{ $expenses->firstItem() + $i }}</td>
            <td>
                <a href="{{ route('accounting.cash-bank-expenses.show', $expense) }}" style="color:var(--primary); font-weight:700; text-decoration:none; font-family:monospace;">
                    {{ $expense->expense_number }}
                </a>
            </td>
            <td style="font-family:monospace; font-size:13px;">{{ $expense->voucher_number ?? '-' }}</td>
            <td style="text-align:center;">
                <span class="badge badge-{{ $expense->type === 'KAS' ? 'confirmed' : ($expense->type === 'BANK' ? 'primary' : 'warning') }}">
                    {{ $expense->type }}
                </span>
            </td>
            <td>{{ $expense->transaction_date ? $expense->transaction_date->format('d/m/Y') : '-' }}</td>
            <td>{{ $expense->company_name }}</td>
            <td style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $expense->account->name ?? '-' }}">
                {{ $expense->account->name ?? '-' }}
            </td>
            <td style="text-align:right; font-weight:700; color:var(--danger);">
                Rp {{ number_format($expense->amount, 0, ',', '.') }}
            </td>
            <td style="text-align:center;">
                <span class="badge badge-{{ $expense->status === 'posted' ? 'done' : 'secondary' }}">
                    {{ ucfirst($expense->status) }}
                </span>
            </td>
            <td class="col-actions text-center">
                <div style="display:flex; gap:5px; justify-content:center;">
                    <a href="{{ route('accounting.cash-bank-expenses.show', $expense) }}" class="btn btn-secondary btn-sm btn-icon" title="Detail"><i class="fa-solid fa-eye"></i></a>
                    @if($expense->isDraft())
                    <a href="{{ route('accounting.cash-bank-expenses.edit', $expense) }}" class="btn btn-warning btn-sm btn-icon" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                    <form method="POST" action="{{ route('accounting.cash-bank-expenses.post', $expense) }}" style="display:inline;" onsubmit="return confirm('Posting {{ $expense->expense_number }}?')">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-success btn-sm btn-icon" title="Posting"><i class="fa-solid fa-check"></i></button>
                    </form>
                    <form method="POST" action="{{ route('accounting.cash-bank-expenses.destroy', $expense) }}" style="display:inline;" onsubmit="return confirm('Hapus {{ $expense->expense_number }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                    </form>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="10" style="text-align:center; padding:48px; color:var(--text-secondary);">
                <i class="fa-solid fa-money-bill-wave" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                Belum ada data Cash Bank Expense
            </td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection
