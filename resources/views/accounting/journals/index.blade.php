@extends('layouts.app')
@section('title', 'Jurnal Umum')
@section('page-title', 'Jurnal Umum (General Journal)')

@section('content')
<div class="animate-in">
    <x-page-header title="Jurnal Umum (General Journal)" subtitle="Pencatatan transaksi akuntansi double-entry (Debit / Kredit)">
        <x-slot:actions>
            <a href="{{ route('accounting.journals.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Buat Jurnal Manual
            </a>
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
    <div class="alert alert-success" style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-circle-check" style="font-size: 18px;"></i>
        <div>{{ session('success') }}</div>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger" style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size: 18px;"></i>
        <div>{{ session('error') }}</div>
    </div>
    @endif

    <x-list-filter-bar :action="route('accounting.journals.index')" placeholder="Cari No. Entri, Keterangan..." :showDateFilter="true" dateFromParam="date_from" dateToParam="date_to">
        <select name="status" class="form-control filter-select">
            <option value="">Semua Status</option>
            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
            <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>Posted</option>
        </select>
    </x-list-filter-bar>

    <x-data-table :paginator="$entries">
        <x-slot:head>
            <tr>
                <x-sortable-header column="entry_number" title="No. Entri" />
                <x-sortable-header column="entry_date" title="Tanggal" :isDefault="true" />
                <th>Keterangan / Referensi</th>
                <th style="text-align: right;">Total Nominal</th>
                <th>Dibuat Oleh</th>
                <x-sortable-header column="status" title="Status" align="center" />
                <th class="text-center col-actions">Aksi</th>
            </tr>
        </x-slot:head>

        @forelse($entries as $entry)
        <tr>
            <td>
                <a href="{{ route('accounting.journals.show', $entry) }}" style="color:var(--primary); font-weight:600; font-family:monospace; text-decoration:none;">
                    {{ $entry->entry_number }}
                </a>
            </td>
            <td>{{ $entry->entry_date ? $entry->entry_date->format('d/m/Y') : '-' }}</td>
            <td class="col-notes" title="{{ $entry->description ?? '-' }}">{{ $entry->description ?? '-' }}</td>
            <td style="text-align: right; font-weight: 600; font-family: 'Inter', monospace; color: var(--primary);">
                Rp {{ number_format($entry->total_debit, 0, ',', '.') }}
            </td>
            <td>{{ $entry->creator->name ?? '-' }}</td>
            <td style="text-align:center;">
                <span class="badge badge-{{ $entry->status === 'posted' ? 'posted' : 'draft' }}">
                    {{ ucfirst($entry->status) }}
                </span>
            </td>
            <td class="col-actions text-center">
                <div style="display:flex; gap:6px; justify-content:center;">
                    <a href="{{ route('accounting.journals.show', $entry) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail Jurnal">
                        <i class="fa-solid fa-eye"></i>
                    </a>
                    @if($entry->status === 'draft')
                    <a href="{{ route('accounting.journals.edit', $entry) }}" class="btn btn-warning btn-sm btn-icon" title="Edit Jurnal">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                    <form method="POST" action="{{ route('accounting.journals.post', $entry) }}" style="display:inline;" onsubmit="return confirm('Posting jurnal {{ $entry->entry_number }}?')">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-success btn-sm btn-icon" title="Posting Jurnal">
                            <i class="fa-solid fa-check"></i>
                        </button>
                    </form>
                    <form method="POST" action="{{ route('accounting.journals.destroy', $entry) }}" style="display:inline;" onsubmit="return confirm('Hapus jurnal draft {{ $entry->entry_number }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Hapus Jurnal Draft">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align:center; padding:48px; color:var(--text-secondary);">
                <i class="fa-solid fa-book-open" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                Belum ada entri jurnal akuntansi yang sesuai filter
            </td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection
