@extends('layouts.app')
@section('title', 'Cash Bank Receipt')
@section('page-title', 'Cash Bank Receipt')

@section('content')
<div class="animate-in">
    <x-page-header title="Cash Bank Receipt" subtitle="Pencatatan penerimaan kas dan bank">
        <x-slot:actions>
            <a href="{{ route('accounting.cash-bank-receipts.create') }}" class="btn btn-primary">
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
        :action="route('accounting.cash-bank-receipts.index')" 
        placeholder="Cari No. Receipt, Voucher, Company, Catatan..." 
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

    <x-data-table :paginator="$receipts">
        <x-slot:head>
            <tr>
                <th style="width:50px;">No</th>
                <x-sortable-header column="receipt_number" title="Receipt No." />
                <th>Voucher No.</th>
                <th style="text-align:center;">Type</th>
                <x-sortable-header column="transaction_date" title="Date" :isDefault="true" />
                <th>Company</th>
                <th>Account</th>
                <x-sortable-header column="amount" title="Jumlah" align="right" />
                <x-sortable-header column="status" title="Status" align="center" />
                <th style="text-align:center;" class="col-actions">Aksi</th>
            </tr>
        </x-slot:head>

        @forelse($receipts as $i => $receipt)
        <tr>
            <td style="color:var(--text-secondary); font-size:12px;">{{ $receipts->firstItem() + $i }}</td>
            <td>
                <a href="{{ route('accounting.cash-bank-receipts.show', $receipt) }}" style="color:var(--primary); font-weight:700; text-decoration:none; font-family:monospace;">
                    {{ $receipt->receipt_number }}
                </a>
            </td>
            <td style="font-family:monospace; font-size:13px;">{{ $receipt->voucher_number ?? '-' }}</td>
            <td style="text-align:center;">
                <span class="badge badge-{{ $receipt->type === 'KAS' ? 'confirmed' : ($receipt->type === 'BANK' ? 'primary' : 'warning') }}">
                    {{ $receipt->type }}
                </span>
            </td>
            <td>{{ $receipt->transaction_date ? $receipt->transaction_date->format('d/m/Y') : '-' }}</td>
            <td>{{ $receipt->company_name }}</td>
            <td style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $receipt->account->name ?? '-' }}">
                {{ $receipt->account->name ?? '-' }}
            </td>
            <td style="text-align:right; font-weight:700; color:var(--success);">
                Rp {{ number_format($receipt->amount, 0, ',', '.') }}
            </td>
            <td style="text-align:center;">
                <span class="badge badge-{{ $receipt->status === 'posted' ? 'done' : 'secondary' }}">
                    {{ ucfirst($receipt->status) }}
                </span>
            </td>
            <td class="col-actions text-center">
                <div style="display:flex; gap:5px; justify-content:center;">
                    <a href="{{ route('accounting.cash-bank-receipts.show', $receipt) }}" class="btn btn-secondary btn-sm btn-icon" title="Detail"><i class="fa-solid fa-eye"></i></a>
                    @if($receipt->isDraft())
                    <a href="{{ route('accounting.cash-bank-receipts.edit', $receipt) }}" class="btn btn-warning btn-sm btn-icon" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                    <form method="POST" action="{{ route('accounting.cash-bank-receipts.post', $receipt) }}" style="display:inline;" onsubmit="return confirm('Posting {{ $receipt->receipt_number }}?')">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-success btn-sm btn-icon" title="Posting"><i class="fa-solid fa-check"></i></button>
                    </form>
                    <form method="POST" action="{{ route('accounting.cash-bank-receipts.destroy', $receipt) }}" style="display:inline;" onsubmit="return confirm('Hapus {{ $receipt->receipt_number }}?')">
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
                <i class="fa-solid fa-money-bill-transfer" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                Belum ada data Cash Bank Receipt
            </td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection
