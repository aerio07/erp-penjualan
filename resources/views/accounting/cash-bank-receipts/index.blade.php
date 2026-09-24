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

    {{-- Filter Tab --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; gap:12px; flex-wrap:wrap;">
        <div style="display:flex; gap:8px;">
            @foreach([''=>'All','draft'=>'Draft','posted'=>'Posted'] as $val=>$label)
            <a href="{{ request()->fullUrlWithQuery(['status' => $val]) }}"
               style="padding:6px 16px; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; {{ request('status','')===$val ? 'background:var(--primary); color:#fff;' : 'background:#f1f5f9; color:var(--text-secondary);' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('accounting.cash-bank-receipts.index') }}" style="display:flex; gap:8px; align-items:center;">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search cash bank receipt..." style="height:36px; font-size:13px; width:220px;">
            <button type="submit" class="btn btn-secondary btn-sm"><i class="fa-solid fa-search"></i></button>
        </form>
    </div>

    <x-data-table :paginator="$receipts">
        <x-slot:head>
            <tr>
                <th>No</th>
                <th>Receipt No.</th>
                <th>Voucher No.</th>
                <th style="text-align:center;">Type</th>
                <th>Date</th>
                <th>Company</th>
                <th>Account</th>
                <th style="text-align:right;">Jumlah</th>
                <th style="text-align:center;">Status</th>
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
