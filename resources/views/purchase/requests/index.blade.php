@extends('layouts.app')
@section('title', 'Purchase Request')
@section('page-title', 'Purchase Request')

@section('content')
<div class="animate-in">
    <x-page-header 
        title="Purchase Request" 
        subtitle="Daftar pengajuan pengadaan barang dari seluruh divisi internal perusahaan"
    >
        <x-slot:actions>
            <a href="{{ route('purchase.requests.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Buat PR Baru
            </a>
        </x-slot:actions>
    </x-page-header>

    {{-- Filter Search & Status --}}
    <x-list-filter-bar :action="route('purchase.requests.index')" placeholder="Cari No. PR, Divisi, Pemohon, Catatan..." :showDateFilter="true">
        <select name="status" class="form-control filter-select">
            <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua Status</option>
            <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
            <option value="revised" {{ $status === 'revised' ? 'selected' : '' }}>Revisi</option>
            <option value="waiting_approval" {{ $status === 'waiting_approval' ? 'selected' : '' }}>Menunggu Approval</option>
            <option value="confirmed" {{ in_array($status, ['confirmed', 'approved']) ? 'selected' : '' }}>Confirmed (Siap PO)</option>
            <option value="done" {{ in_array($status, ['done', 'ordered']) ? 'selected' : '' }}>Done (Sudah di-PO)</option>
            <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Ditolak</option>
            <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
        </select>
    </x-list-filter-bar>

    <x-data-table :paginator="$purchaseRequests">
        <x-slot:head>
            <tr>
                <th>No. PR</th>
                <th>Pemohon & Divisi</th>
                <th>Tanggal PR</th>
                <th>Tgl Dibutuhkan</th>
                <th>Catatan</th>
                <th style="text-align:center;">Status</th>
                <th class="text-center col-actions">Aksi</th>
            </tr>
        </x-slot:head>

        @forelse($purchaseRequests as $pr)
        <tr>
            <td>
                <a href="{{ route('purchase.requests.show', $pr) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                    {{ $pr->pr_number }}
                </a>
            </td>
            <td>
                <div style="font-weight:600; color:var(--text-primary);">{{ $pr->user->name ?? '-' }}</div>
                <div style="font-size:12px; color:var(--text-secondary);">
                    <i class="fa-solid fa-building-user"></i> {{ $pr->division ?? 'Umum' }}
                </div>
            </td>
            <td>{{ $pr->request_date ? $pr->request_date->format('d/m/Y') : '-' }}</td>
            <td>
                <span style="font-weight:600; {{ $pr->required_date && $pr->required_date->isPast() && ($pr->isDraft() || $pr->isRevised() || $pr->isWaitingApproval()) ? 'color:var(--danger);' : '' }}">
                    {{ $pr->required_date ? $pr->required_date->format('d/m/Y') : '-' }}
                </span>
            </td>
            <td>
                <div style="font-size:13px; color:var(--text-secondary); max-width:240px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $pr->notes }}">
                    {{ $pr->notes ?: '-' }}
                </div>
            </td>
            <td style="text-align:center;">
                <x-status-badge :status="$pr->status" />
            </td>
            <td class="text-center">
                <div style="display:inline-flex; gap:6px;">
                    <a href="{{ route('purchase.requests.show', $pr) }}" class="btn btn-sm btn-secondary" title="Lihat Detail">
                        <i class="fa-solid fa-eye"></i>
                    </a>

                    @if($pr->isConfirmed() || $pr->isDone())
                    <a href="{{ route('pdf.purchase-request', $pr) }}" class="btn btn-sm btn-secondary" title="Export PDF" target="_blank">
                        <i class="fa-solid fa-file-pdf"></i>
                    </a>
                    @endif

                    @if($pr->isDraft() || $pr->isRevised() || $pr->isRejected())
                    <a href="{{ route('purchase.requests.edit', $pr) }}" class="btn btn-sm btn-secondary" title="Edit PR">
                        <i class="fa-solid fa-pen"></i>
                    </a>
                    @endif

                    @if($pr->isDraft() || $pr->isRevised())
                    <button type="button" class="btn btn-sm btn-danger" title="Hapus"
                        data-confirm-delete="delete-pr-{{ $pr->id }}"
                        data-name="Purchase Request {{ $pr->pr_number }}"
                    >
                        <i class="fa-solid fa-trash"></i>
                    </button>
                    <form id="delete-pr-{{ $pr->id }}" method="POST" action="{{ route('purchase.requests.destroy', $pr) }}" style="display:none;">
                        @csrf
                        @method('DELETE')
                    </form>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align:center; padding:48px; color:var(--text-secondary);">
                <i class="fa-solid fa-file-invoice" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                Belum ada dokumen Purchase Request yang sesuai filter.
            </td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection