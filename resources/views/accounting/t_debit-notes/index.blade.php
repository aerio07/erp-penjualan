@extends('layouts.app')
@section('title', 'Debet Note')
@section('page-title', 'Debet Note')

@section('content')
<div class="animate-in">
    <x-page-header title="Debet Note" subtitle="Pencatatan penyesuaian potongan hutang supplier (AP) dan piutang pelanggan (AR)">
        <x-slot:actions>
            <a href="{{ route('accounting.debit-notes.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Buat Debet Note
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

    {{-- Filter Bar Standar Sistem --}}
    <x-list-filter-bar :action="route('accounting.debit-notes.index')" placeholder="Cari No. DN, Draft, Partner, No. Invoice, Catatan..." :showDateFilter="true">
        <select name="status" class="form-control filter-select" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
            <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>Posted</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
        </select>
        <select name="dn_type" class="form-control filter-select" onchange="this.form.submit()">
            <option value="">Semua Tipe</option>
            <option value="AP" {{ request('dn_type') === 'AP' ? 'selected' : '' }}>AP (Hutang Vendor)</option>
            <option value="AR" {{ request('dn_type') === 'AR' ? 'selected' : '' }}>AR (Piutang Customer)</option>
        </select>
    </x-list-filter-bar>

    <x-data-table :paginator="$debitNotes">
        <x-slot:head>
            <tr>
                <th style="width:40px;">No</th>
                <th>No. DN</th>
                <th style="text-align:center;">No. Draft</th>
                <th style="text-align:center;">Tipe</th>
                <th>Tanggal</th>
                <th>Supplier / Customer</th>
                <th>No. Invoice</th>
                <th>COA Kredit</th>
                <th style="text-align:right;">Total DN</th>
                <th style="text-align:center;">Status</th>
                <th style="text-align:center;" class="col-actions">Aksi</th>
            </tr>
        </x-slot:head>

        @forelse($debitNotes as $i => $dn)
        <tr>
            <td style="color:var(--text-secondary); font-size:12px;">{{ $debitNotes->firstItem() + $i }}</td>
            <td>
                <a href="{{ route('accounting.debit-notes.show', $dn) }}" style="color:var(--primary); font-weight:700; text-decoration:none; font-family:monospace;">
                    {{ $dn->dn_number }}
                </a>
            </td>
            <td style="text-align:center; color:var(--text-secondary); font-family:monospace; font-size:12px;">
                {{ $dn->draft_number ?: $dn->id }}
            </td>
            <td style="text-align:center;">
                <span class="badge badge-{{ $dn->dn_type === 'AP' ? 'warning' : 'primary' }}">
                    {{ $dn->dn_type }}
                </span>
            </td>
            <td>{{ $dn->dn_date ? $dn->dn_date->format('d/m/Y') : '-' }}</td>
            <td style="font-weight:600; color:var(--text-primary);">
                {{ $dn->partner_name }}
            </td>
            <td style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-family:monospace; font-size:12px;" title="{{ $dn->invoice_numbers_summary }}">
                {{ $dn->invoice_numbers_summary }}
            </td>
            <td style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:12px;" title="{{ $dn->credit_account_summary }}">
                {{ $dn->credit_account_summary }}
            </td>
            <td style="text-align:right; font-weight:700; color:var(--primary);">
                Rp {{ number_format($dn->total_amount, 0, ',', '.') }}
            </td>
            <td style="text-align:center;">
                <span class="badge badge-{{ $dn->status === 'posted' ? 'done' : ($dn->status === 'cancelled' ? 'danger' : 'secondary') }}">
                    {{ ucfirst($dn->status) }}
                </span>
            </td>
            <td class="col-actions text-center">
                <x-table-actions 
                    :show-url="route('accounting.debit-notes.show', $dn)"
                    :edit-url="$dn->isDraft() ? route('accounting.debit-notes.edit', $dn) : null"
                    :pdf-url="$dn->isPosted() ? route('accounting.debit-notes.print', $dn) : null"
                    :delete-url="$dn->isDraft() ? route('accounting.debit-notes.destroy', $dn) : null"
                    delete-confirm="Yakin ingin menghapus Debet Note {{ $dn->dn_number }}?"
                />
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="11" style="text-align:center; padding:32px; color:var(--text-secondary);">
                <i class="fa-solid fa-receipt" style="font-size:32px; margin-bottom:8px; opacity:0.3; display:block;"></i>
                Tidak ada data Debet Note ditemukan
            </td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection
