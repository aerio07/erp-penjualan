@extends('layouts.app')
@section('title', 'Stock Opname')
@section('page-title', 'Stock Opname')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Stock Opname</h1>
            <p>Pemeriksaan dan penyesuaian fisik persediaan di gudang</p>
        </div>
        <a href="{{ route('inventory.opname.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Mulai Stock Opname
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Daftar Stock Opname</h3>
            <span style="font-size:13px; color:var(--text-secondary);">{{ $opnames->total() }} dokumen</span>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>No. Opname</th>
                        <th>Gudang</th>
                        <th>Tgl Opname</th>
                        <th>Petugas</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($opnames as $op)
                    <tr>
                        <td>
                            <a href="{{ route('inventory.opname.show', $op) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                                {{ $op->opname_number }}
                            </a>
                        </td>
                        <td>
                            <span class="badge badge-confirmed">
                                <i class="fa-solid fa-warehouse"></i> {{ $op->warehouse->name ?? '-' }}
                            </span>
                        </td>
                        <td>{{ $op->opname_date->format('d/m/Y') }}</td>
                        <td>{{ $op->user->name ?? '-' }}</td>
                        <td style="text-align:center;">
                            <span class="badge badge-{{ $op->status === 'completed' ? 'done' : 'pending' }}">
                                {{ ucfirst($op->status) }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <a href="{{ route('inventory.opname.show', $op) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            <i class="fa-solid fa-clipboard-check" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                            Belum ada dokumen Stock Opname
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($opnames->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border);">
            {{ $opnames->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
