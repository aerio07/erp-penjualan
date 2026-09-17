@extends('layouts.app')
@section('title', 'Purchase Request')
@section('page-title', 'Purchase Request')

@section('content')
<div class="animate-in">
    <x-page-header 
        title="Purchase Request" 
        subtitle="Daftar pengajuan pengadaan barang dari divisi internal sebelum diterbitkan Purchase Order (PO)"
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
            <option value="waiting_approval" {{ $status === 'waiting_approval' ? 'selected' : '' }}>Menunggu Approval</option>
            <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved (Siap PO)</option>
            <option value="ordered" {{ $status === 'ordered' ? 'selected' : '' }}>Sudah di-PO</option>
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
                <th style="text-align:center;">Jumlah Item</th>
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
                <span style="font-weight:600; {{ $pr->required_date && $pr->required_date->isPast() && in_array($pr->status, ['draft','waiting_approval']) ? 'color:var(--danger);' : '' }}">
                    {{ $pr->required_date ? $pr->required_date->format('d/m/Y') : '-' }}
                </span>
            </td>
            <td style="text-align:center; font-weight:600;">
                {{ $pr->items->count() }} Item
            </td>
            <td style="text-align:center;">
                <span class="badge {{ $pr->status_badge }}">
                    {{ $pr->status_label }}
                </span>
            </td>
            <td class="text-center">
                <div style="display:inline-flex; gap:6px;">
                    <a href="{{ route('purchase.requests.show', $pr) }}" class="btn btn-sm btn-secondary" title="Lihat Detail">
                        <i class="fa-solid fa-eye"></i>
                    </a>

                    @if($pr->status === 'approved')
                    <a href="{{ route('purchase.orders.create', ['purchase_request_id' => $pr->id]) }}" class="btn btn-sm btn-primary" title="Terbitkan PO dari PR ini" style="background:#2563eb;">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </a>
                    @endif

                    @if(in_array($pr->status, ['draft', 'rejected']))
                    <a href="{{ route('purchase.requests.edit', $pr) }}" class="btn btn-sm btn-secondary" title="Edit PR">
                        <i class="fa-solid fa-pen"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-primary" title="Ajukan Persetujuan"
                        data-confirm-swal="submit-pr-{{ $pr->id }}"
                        data-swal-title="Ajukan Purchase Request"
                        data-swal-html="Ajukan Purchase Request <strong>{{ $pr->pr_number }}</strong> untuk persetujuan (approval)?<br><span style='font-size:12.5px; color:#64748b;'>Status PR akan diubah menjadi Waiting Approval.</span>"
                        data-swal-icon="question"
                        data-swal-icon-color="#2563eb"
                        data-swal-btn-text="Ya, Ajukan"
                        data-swal-btn-icon="fa-solid fa-paper-plane"
                        data-swal-btn-class="btn btn-primary"
                    >
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                    <form id="submit-pr-{{ $pr->id }}" method="POST" action="{{ route('purchase.requests.submit', $pr) }}" style="display:none;">
                        @csrf
                        @method('PATCH')
                    </form>

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