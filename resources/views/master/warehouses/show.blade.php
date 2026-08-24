@extends('layouts.app')
@section('title', 'Gudang — ' . $warehouse->name)
@section('page-title', 'Detail Gudang')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>{{ $warehouse->name }}</h1>
            <p>Kode: <strong style="color:var(--primary);">{{ $warehouse->code }}</strong> &nbsp;·&nbsp;
                <span class="badge {{ $warehouse->is_active ? 'badge-done' : 'badge-cancelled' }}">
                    {{ $warehouse->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </p>
        </div>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <form method="POST" action="{{ route('master.warehouses.toggle-status', $warehouse) }}" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin {{ $warehouse->is_active ? 'menonaktifkan' : 'mengaktifkan' }} gudang ini?');">
                @csrf
                @method('PATCH')
                @if($warehouse->is_active)
                    <button type="submit" class="btn btn-secondary" style="color:#b91c1c; border-color:#fca5a5;" title="Nonaktifkan Gudang">
                        <i class="fa-solid fa-ban"></i> Nonaktifkan
                    </button>
                @else
                    <button type="submit" class="btn btn-primary" style="background:#16a34a; border-color:#16a34a;" title="Aktifkan Gudang">
                        <i class="fa-solid fa-check"></i> Aktifkan
                    </button>
                @endif
            </form>

            <a href="{{ route('master.warehouses.edit', $warehouse) }}" class="btn btn-secondary">
                <i class="fa-solid fa-pen"></i> Edit
            </a>

            <button type="button" data-confirm-delete="del-wh-show" data-name="{{ $warehouse->name }} ({{ $warehouse->code }})" class="btn btn-danger" title="Hapus Gudang">
                <i class="fa-solid fa-trash"></i> Hapus
            </button>
            <form id="del-wh-show" method="POST" action="{{ route('master.warehouses.destroy', $warehouse) }}" style="display:none;">
                @csrf
                @method('DELETE')
            </form>

            <a href="{{ route('master.warehouses.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    @if($warehouse->address)
    <div class="card" style="margin-bottom:20px;">
        <div class="card-body" style="display:flex; align-items:center; gap:12px;">
            <i class="fa-solid fa-location-dot" style="color:var(--primary); font-size:18px;"></i>
            <span style="color:var(--text-secondary);">{{ $warehouse->address }}</span>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-cubes" style="color:var(--primary); margin-right:8px;"></i> Ringkasan Stok</h3>
            <a href="{{ route('inventory.stock-card') }}?warehouse_id={{ $warehouse->id }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-rectangle-list"></i> Kartu Stok
            </a>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Produk</th>
                        <th>Unit</th>
                        <th style="text-align:right;">Stok</th>
                        <th style="text-align:right;">Nilai (HPP)</th>
                        <th style="text-align:center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stockSummary as $item)
                    <tr>
                        <td style="font-weight:600; color:var(--primary);">{{ $item->sku }}</td>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->unit }}</td>
                        <td style="text-align:right; font-weight:600;">{{ number_format($item->current_stock) }}</td>
                        <td style="text-align:right;">Rp {{ number_format($item->stock_value ?? 0, 0, ',', '.') }}</td>
                        <td style="text-align:center;">
                            @if($item->current_stock <= 0)
                                <span class="badge badge-cancelled">Habis</span>
                            @elseif($item->current_stock <= $item->min_stock)
                                <span class="badge badge-pending">Hampir Habis</span>
                            @else
                                <span class="badge badge-done">Aman</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:36px; color:var(--text-secondary);">
                            Belum ada stok di gudang ini
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
