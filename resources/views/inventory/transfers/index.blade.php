@extends('layouts.app')
@section('title', 'Transfer Gudang')
@section('page-title', 'Transfer antar Gudang')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Transfer antar Gudang</h1>
            <p>Pindahkan stok produk antar lokasi gudang perusahaan</p>
        </div>
        <a href="{{ route('inventory.transfers.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Buat Transfer Baru
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Daftar Transfer Gudang</h3>
            <span style="font-size:13px; color:var(--text-secondary);">{{ $transfers->total() }} transfer</span>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>No. Transfer</th>
                        <th>Gudang Asal</th>
                        <th>Gudang Tujuan</th>
                        <th>Tgl Kirim</th>
                        <th>Tgl Terima</th>
                        <th>Pemohon</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $trf)
                    <tr>
                        <td>
                            <a href="{{ route('inventory.transfers.show', $trf) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                                {{ $trf->transfer_number }}
                            </a>
                        </td>
                        <td>{{ $trf->fromWarehouse->name ?? '-' }}</td>
                        <td>{{ $trf->toWarehouse->name ?? '-' }}</td>
                        <td>{{ $trf->transfer_date->format('d/m/Y') }}</td>
                        <td>{{ $trf->received_date ? $trf->received_date->format('d/m/Y') : '-' }}</td>
                        <td>{{ $trf->user->name ?? '-' }}</td>
                        <td style="text-align:center;">
                            <span class="badge badge-{{ $trf->status === 'received' ? 'done' : ($trf->status === 'in_transit' ? 'confirmed' : 'pending') }}">
                                {{ ucfirst(str_replace('_', ' ', $trf->status)) }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <a href="{{ route('inventory.transfers.show', $trf) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            <i class="fa-solid fa-arrow-right-arrow-left" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                            Belum ada riwayat transfer barang antar gudang
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transfers->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border);">
            {{ $transfers->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
