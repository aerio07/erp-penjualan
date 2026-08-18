@extends('layouts.app')
@section('title', 'Jurnal Umum')
@section('page-title', 'Jurnal Umum (General Journal)')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Jurnal Umum (General Journal)</h1>
            <p>Pencatatan transaksi akuntansi double-entry (Debit / Kredit)</p>
        </div>
        <a href="{{ route('accounting.journals.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Buat Jurnal Manual
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Daftar Jurnal Entries</h3>
            <span style="font-size:13px; color:var(--text-secondary);">{{ $entries->total() }} entri</span>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>No. Entri</th>
                        <th>Tanggal</th>
                        <th>Keterangan / Referensi</th>
                        <th>Dibuat Oleh</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                    <tr>
                        <td>
                            <a href="{{ route('accounting.journals.show', $entry) }}" style="color:var(--primary); font-weight:600; font-family:monospace; text-decoration:none;">
                                {{ $entry->entry_number }}
                            </a>
                        </td>
                        <td>{{ $entry->entry_date->format('d/m/Y') }}</td>
                        <td>{{ $entry->description ?? '-' }}</td>
                        <td>{{ $entry->creator->name ?? '-' }}</td>
                        <td style="text-align:center;">
                            <span class="badge badge-{{ $entry->status === 'posted' ? 'posted' : 'draft' }}">
                                {{ ucfirst($entry->status) }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                <a href="{{ route('accounting.journals.show', $entry) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Jurnal">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                @if($entry->status === 'draft')
                                <form method="POST" action="{{ route('accounting.journals.post', $entry) }}" style="display:inline;">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-success btn-sm btn-icon" title="Posting Jurnal">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            <i class="fa-solid fa-book-open" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                            Belum ada entri jurnal akuntansi
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($entries->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border);">
            {{ $entries->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
