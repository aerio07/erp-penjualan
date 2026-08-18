@extends('layouts.app')
@section('title', 'Detail Opname - ' . $opname->opname_number)
@section('page-title', 'Detail Stock Opname')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>{{ $opname->opname_number }}</h1>
            <p>Gudang: <strong>{{ $opname->warehouse->name ?? '-' }}</strong> · Tgl Opname: {{ $opname->opname_date->format('d F Y') }}</p>
        </div>
        <div style="display:flex; gap:8px;">
            @if($opname->status === 'draft')
            <form method="POST" action="{{ route('inventory.opname.complete', $opname) }}" style="display:inline;">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-primary" onclick="return confirm('Selesaikan opname dan sesuaikan stok otomatis di sistem?')">
                    <i class="fa-solid fa-check"></i> Selesaikan Opname & Penyesuaian Stok
                </button>
            </form>
            @endif
            <a href="{{ route('inventory.opname.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-3" style="margin-bottom:20px;">
        <div class="card" style="grid-column:span 2;">
            <div class="card-header">
                <h3>Informasi Stock Opname</h3>
                <span class="badge badge-{{ $opname->status === 'completed' ? 'done' : 'pending' }}">
                    {{ ucfirst($opname->status) }}
                </span>
            </div>
            <div class="card-body">
                <div class="form-row form-row-2">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Gudang</div>
                        <div style="font-weight:600;"><i class="fa-solid fa-warehouse"></i> {{ $opname->warehouse->name ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Petugas Hitung</div>
                        <div style="font-weight:600;">{{ $opname->user->name ?? '-' }}</div>
                    </div>
                </div>

                @if($opname->notes)
                <div style="margin-top:16px; padding:12px; background:#f8fafc; border-radius:10px; font-size:13.5px; color:var(--text-secondary);">
                    <i class="fa-solid fa-note-sticky" style="margin-right:6px;"></i> {{ $opname->notes }}
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Statistik Selisih</h3></div>
            <div class="card-body">
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary);">Jumlah Produk Ber-selisih</div>
                        <div style="font-size:24px; font-weight:700; color:var(--warning);">
                            {{ $opname->items->where('difference', '!=', 0)->count() }} produk
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table Items --}}
    <div class="card">
        <div class="card-header"><h3>Hasil Perhitungan Fisik</h3></div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produk</th>
                        <th style="text-align:right;">Stok Sistem</th>
                        <th style="text-align:right;">Stok Fisik</th>
                        <th style="text-align:right;">Selisih</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($opname->items as $idx => $item)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <div style="font-weight:600;">{{ $item->product->name ?? '-' }}</div>
                            <div style="font-size:12px; color:var(--text-secondary);">{{ $item->product->sku ?? '-' }}</div>
                        </td>
                        <td style="text-align:right;">{{ number_format($item->system_qty) }}</td>
                        <td style="text-align:right; font-weight:600;">{{ number_format($item->physical_qty) }}</td>
                        <td style="text-align:right; font-weight:700; color:{{ $item->difference < 0 ? 'var(--danger)' : ($item->difference > 0 ? 'var(--success)' : 'var(--text-primary)') }};">
                            {{ $item->difference > 0 ? '+'.$item->difference : $item->difference }}
                        </td>
                        <td>{{ $item->notes ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
