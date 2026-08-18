@extends('layouts.app')
@section('title', 'Gudang')
@section('page-title', 'Manajemen Gudang')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Gudang</h1>
            <p>Kelola semua lokasi penyimpanan barang</p>
        </div>
        <a href="{{ route('master.warehouses.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Tambah Gudang
        </a>
    </div>

    <div class="grid grid-3" style="margin-bottom:24px;">
        @forelse($warehouses as $wh)
        <div class="card" style="transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='none';this.style.boxShadow='';">
            <div class="card-body">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:16px;">
                    <div style="width:48px; height:48px; border-radius:12px; background:linear-gradient(135deg,#6366f1,#4f46e5); display:flex; align-items:center; justify-content:center; color:white; font-size:20px;">
                        <i class="fa-solid fa-warehouse"></i>
                    </div>
                    <span class="badge {{ $wh->is_active ? 'badge-done' : 'badge-cancelled' }}">
                        {{ $wh->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
                <div style="font-weight:700; font-size:16px; margin-bottom:4px;">{{ $wh->name }}</div>
                <div style="font-size:12px; color:var(--text-secondary); margin-bottom:8px;">Kode: {{ $wh->code }}</div>
                @if($wh->address)
                <div style="font-size:13px; color:var(--text-secondary); margin-bottom:12px;">
                    <i class="fa-solid fa-location-dot" style="width:14px;"></i> {{ Str::limit($wh->address, 60) }}
                </div>
                @endif
                <div style="display:flex; align-items:center; justify-content:space-between; padding-top:12px; border-top:1px solid var(--border);">
                    <span style="font-size:12px; color:var(--text-secondary);">
                        <i class="fa-solid fa-box"></i> {{ $wh->stock_movements_count }} transaksi
                    </span>
                    <div style="display:flex; gap:6px;">
                        <a href="{{ route('master.warehouses.show', $wh) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Stok">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <a href="{{ route('master.warehouses.edit', $wh) }}" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        @if($wh->stock_movements_count == 0)
                        <button data-confirm-delete="del-wh-{{ $wh->id }}" class="btn btn-danger btn-sm btn-icon" title="Hapus">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                        <form id="del-wh-{{ $wh->id }}" method="POST" action="{{ route('master.warehouses.destroy', $wh) }}" style="display:none;">@csrf @method('DELETE')</form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="card" style="grid-column:span 3;">
            <div class="card-body" style="text-align:center; padding:48px; color:var(--text-secondary);">
                <i class="fa-solid fa-warehouse" style="font-size:36px; opacity:0.3; display:block; margin-bottom:12px;"></i>
                Belum ada data gudang. <a href="{{ route('master.warehouses.create') }}" style="color:var(--primary);">Tambah sekarang</a>
            </div>
        </div>
        @endforelse
    </div>
</div>
@endsection
