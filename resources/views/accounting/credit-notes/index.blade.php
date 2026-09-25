@extends('layouts.app')
@section('title', 'Kredit Note')
@section('page-title', 'Kredit Note')

@section('content')
<div class="animate-in">
    <x-page-header title="Kredit Note" subtitle="Pencatatan penyesuaian potongan piutang pelanggan (AR) dan hutang supplier (AP)">
        <x-slot:actions>
            <a href="{{ route('accounting.credit-notes.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Buat Kredit Note
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
    <x-list-filter-bar :action="route('accounting.credit-notes.index')" placeholder="Cari No. CN, Draft, Partner, No. Invoice, Catatan..." :showDateFilter="true">
        <select name="status" class="form-control filter-select" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
            <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>Posted</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
        </select>
        <select name="cn_type" class="form-control filter-select" onchange="this.form.submit()">
            <option value="">Semua Tipe</option>
            <option value="AR" {{ request('cn_type') === 'AR' ? 'selected' : '' }}>AR (Piutang Customer)</option>
            <option value="AP" {{ request('cn_type') === 'AP' ? 'selected' : '' }}>AP (Hutang Vendor)</option>
        </select>
    </x-list-filter-bar>

    <x-data-table :paginator="$creditNotes">
        <x-slot:head>
            <tr>
                <th style="width:40px;">No</th>
                <th>No. CN</th>
                <th style="text-align:center;">No. Draft</th>
                <th style="text-align:center;">Tipe</th>
                <th>Tanggal</th>
                <th>Cust / Supp</th>
                <th>No. Invoice</th>
                <th>COA Kredit</th>
                <th style="text-align:right;">Total CN</th>
                <th style="text-align:center;">Status</th>
                <th style="text-align:center;" class="col-actions">Aksi</th>
            </tr>
        </x-slot:head>

        @forelse($creditNotes as $i => $cn)
        <tr>
            <td style="color:var(--text-secondary); font-size:12px;">{{ $creditNotes->firstItem() + $i }}</td>
            <td>
                <a href="{{ route('accounting.credit-notes.show', $cn) }}" style="color:var(--primary); font-weight:700; text-decoration:none; font-family:monospace;">
                    {{ $cn->cn_number }}
                </a>
            </td>
            <td style="text-align:center; color:var(--text-secondary); font-family:monospace; font-size:12px;">
                {{ $cn->draft_number ?: $cn->id }}
            </td>
            <td style="text-align:center;">
                <span class="badge badge-{{ $cn->cn_type === 'AR' ? 'primary' : 'warning' }}">
                    {{ $cn->cn_type }}
                </span>
            </td>
            <td>{{ $cn->cn_date ? $cn->cn_date->format('d/m/Y') : '-' }}</td>
            <td style="font-weight:600; color:var(--text-primary);">
                {{ $cn->partner_name }}
            </td>
            <td style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-family:monospace; font-size:12px;" title="{{ $cn->invoice_numbers_summary }}">
                {{ $cn->invoice_numbers_summary }}
            </td>
            <td style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:12px;" title="{{ $cn->credit_account_summary }}">
                {{ $cn->credit_account_summary }}
            </td>
            <td style="text-align:right; font-weight:700; color:var(--primary);">
                Rp {{ number_format($cn->total_amount, 0, ',', '.') }}
            </td>
            <td style="text-align:center;">
                <span class="badge badge-{{ $cn->status === 'posted' ? 'done' : ($cn->status === 'cancelled' ? 'danger' : 'secondary') }}">
                    {{ ucfirst($cn->status) }}
                </span>
            </td>
            <td class="col-actions text-center">
                <x-table-actions 
                    :show-url="route('accounting.credit-notes.show', $cn)"
                    :edit-url="$cn->isDraft() ? route('accounting.credit-notes.edit', $cn) : null"
                    :pdf-url="$cn->isPosted() ? route('accounting.credit-notes.print', $cn) : null"
                    :delete-url="$cn->isDraft() ? route('accounting.credit-notes.destroy', $cn) : null"
                    delete-confirm="Yakin ingin menghapus Kredit Note {{ $cn->cn_number }}?"
                />
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="11" style="text-align:center; padding:32px; color:var(--text-secondary);">
                <i class="fa-solid fa-receipt" style="font-size:32px; margin-bottom:8px; opacity:0.3; display:block;"></i>
                Tidak ada data Kredit Note ditemukan
            </td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection
