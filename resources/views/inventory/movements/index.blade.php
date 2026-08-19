@extends('layouts.app')
@section('title', 'Log Mutasi Stok')
@section('page-title', 'Log Audit Mutasi Stok')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Log Audit Mutasi Stok</h1>
            <p>Jejak histori mutasi barang di seluruh gudang</p>
        </div>
    </div>

    <x-list-filter-bar :action="route('inventory.movements.index')" placeholder="Cari SKU, Nama Produk, Catatan..." :showDateFilter="true">
        <select name="product_id" class="form-control" style="height:38px; font-size:13px; min-width:160px; border-radius:6px;">
            <option value="">Semua Produk</option>
            @foreach($products as $p)
            <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>

        <select name="warehouse_id" class="form-control" style="height:38px; font-size:13px; min-width:150px; border-radius:6px;">
            <option value="">Semua Gudang</option>
            @foreach($warehouses as $wh)
            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
            @endforeach
        </select>

        <select name="type" class="form-control" style="height:38px; font-size:13px; min-width:150px; border-radius:6px;">
            <option value="">Semua Tipe Mutasi</option>
            <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>Masuk (IN)</option>
            <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>Keluar (OUT)</option>
            <option value="transfer_in" {{ request('type') === 'transfer_in' ? 'selected' : '' }}>Transfer In</option>
            <option value="transfer_out" {{ request('type') === 'transfer_out' ? 'selected' : '' }}>Transfer Out</option>
            <option value="return_in" {{ request('type') === 'return_in' ? 'selected' : '' }}>Retur In (Baik)</option>
            <option value="return_in_damaged" {{ request('type') === 'return_in_damaged' ? 'selected' : '' }}>Retur Karantina (Rusak)</option>
            <option value="return_out" {{ request('type') === 'return_out' ? 'selected' : '' }}>Retur Out</option>
            <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>Adjustment Opname</option>
            <option value="write_off" {{ request('type') === 'write_off' ? 'selected' : '' }}>Write Off (Pemusnahan)</option>
            <option value="reject_out" {{ request('type') === 'reject_out' ? 'selected' : '' }}>Jual Reject (Reject Out)</option>
        </select>
    </x-list-filter-bar>

    <div class="card">
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <x-sortable-header column="movement_date" title="Tgl Mutasi" />
                        <th>Produk</th>
                        <th>Gudang</th>
                        <x-sortable-header column="type" title="Tipe" />
                        <x-sortable-header column="quantity" title="Jumlah" align="right" />
                        <x-sortable-header column="unit_cost" title="Biaya Satuan" align="right" />
                        <th>Catatan / Ref</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $m)
                    @php 
                        $isDamaged = ($m->type === 'return_in_damaged');
                        $isIncoming = in_array($m->type, ['in', 'return_in', 'transfer_in']); 
                    @endphp
                    <tr>
                        <td>{{ $m->movement_date ? $m->movement_date->format('d/m/Y') : '-' }}</td>
                        <td>
                            <div style="font-weight:600;">{{ $m->product->name ?? '-' }}</div>
                            <div style="font-size:12px; color:var(--text-secondary);">{{ $m->product->sku ?? '-' }}</div>
                        </td>
                        <td>{{ $m->warehouse->name ?? '-' }}</td>
                        <td>
                            @if($isDamaged)
                                <span class="badge badge-warning" style="background:#fef3c7; color:#92400e; font-weight:700;">
                                    <i class="fa-solid fa-triangle-exclamation"></i> RETUR KARANTINA
                                </span>
                            @else
                                <span class="badge {{ $isIncoming ? 'badge-done' : 'badge-cancelled' }}">
                                    {{ strtoupper(str_replace('_', ' ', $m->type)) }}
                                </span>
                            @endif
                        </td>
                        <td style="text-align:right; font-weight:700; color:{{ $isDamaged ? '#92400e' : ($isIncoming ? 'var(--success)' : 'var(--danger)') }};">
                            {{ $isIncoming || $isDamaged ? '+' : '-' }}{{ number_format($m->quantity) }} {{ $m->product->unit ?? 'pcs' }}
                        </td>
                        <td style="text-align:right;">Rp {{ number_format($m->unit_cost, 0, ',', '.') }}</td>
                        <td>{{ $m->notes ?? '-' }}</td>
                        <td>{{ $m->user->name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            Belum ada riwayat mutasi stok yang sesuai filter
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movements->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border);">
            {{ $movements->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
