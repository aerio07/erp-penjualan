@extends('layouts.app')
@section('title', 'Disposisi Stok Karantina')
@section('page-title', 'Penyelesaian Barang Karantina')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Disposisi Stok Karantina</h1>
            <p>Riwayat penyelesaian barang rusak (stok karantina) melalui Write Off atau Jual Sebagai Reject</p>
        </div>
        <div>
            <a href="{{ route('inventory.dispositions.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Disposisi Baru
            </a>
        </div>
    </div>

    {{-- Filter Bar --}}
    <x-list-filter-bar :action="route('inventory.dispositions.index')" placeholder="Cari No. Disposisi, Produk, Catatan..." :showDateFilter="true">
        <select name="product_id" class="form-control" style="height:38px; font-size:13px; min-width:160px; border-radius:6px;">
            <option value="">Semua Produk</option>
            @foreach($products as $p)
            <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>

        <select name="warehouse_id" class="form-control" style="height:38px; font-size:13px; min-width:160px; border-radius:6px;">
            <option value="">Semua Gudang</option>
            @foreach($warehouses as $wh)
            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
            @endforeach
        </select>

        <select name="resolution_type" class="form-control" style="height:38px; font-size:13px; min-width:160px; border-radius:6px;">
            <option value="">Semua Jenis</option>
            <option value="write_off" {{ request('resolution_type') == 'write_off' ? 'selected' : '' }}>Write Off (Penghapusan)</option>
            <option value="sold_as_reject" {{ request('resolution_type') == 'sold_as_reject' ? 'selected' : '' }}>Sold as Reject (Jual Reject)</option>
        </select>
    </x-list-filter-bar>

    <div class="card">
        <div class="card-header">
            <h3>Daftar Dokumen Disposisi</h3>
            <span style="font-size:13px; color:var(--text-secondary);">Total {{ $dispositions->total() }} Dokumen</span>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <x-sortable-header column="disposition_number" title="No. Disposisi" />
                        <x-sortable-header column="disposed_at" title="Tgl Disposisi" />
                        <th>Produk</th>
                        <th>Gudang</th>
                        <x-sortable-header column="qty" title="Qty" align="center" />
                        <x-sortable-header column="resolution_type" title="Jenis Penyelesaian" align="center" />
                        <th style="text-align:right;">Beban / Nilai HPP</th>
                        <th style="text-align:right;">Hasil Penjualan</th>
                        <th>Jurnal Umum</th>
                        <th>Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dispositions as $disp)
                    @php
                        $totalCost = $disp->qty * $disp->unit_cost;
                        $saleAmount = $disp->resolution_type === 'sold_as_reject' ? ($disp->qty * $disp->sale_price) : 0;
                    @endphp
                    <tr>
                        <td style="font-weight:700; color:var(--primary);">
                            {{ $disp->disposition_number }}
                        </td>
                        <td>{{ $disp->disposed_at ? $disp->disposed_at->format('d/m/Y') : '-' }}</td>
                        <td>
                            <div style="font-weight:600;">{{ $disp->product->name ?? '-' }}</div>
                            <div style="font-size:11px; color:var(--text-secondary);">SKU: {{ $disp->product->sku ?? '-' }}</div>
                        </td>
                        <td>{{ $disp->warehouse->name ?? '-' }}</td>
                        <td style="text-align:center; font-weight:700;">
                            {{ number_format($disp->qty) }} {{ $disp->product->unit ?? 'pcs' }}
                        </td>
                        <td style="text-align:center;">
                            @if($disp->resolution_type === 'write_off')
                                <span class="badge badge-cancelled" style="font-weight:700;">
                                    <i class="fa-solid fa-trash-can"></i> WRITE OFF
                                </span>
                            @else
                                <span class="badge badge-done" style="font-weight:700; background:#dcfce7; color:#15803d;">
                                    <i class="fa-solid fa-hand-holding-dollar"></i> SOLD AS REJECT
                                </span>
                            @endif
                        </td>
                        <td style="text-align:right; font-weight:600; color:var(--danger);">
                            Rp {{ number_format($totalCost, 0, ',', '.') }}
                            <div style="font-size:10.5px; color:var(--text-secondary);">@ Rp {{ number_format($disp->unit_cost, 0, ',', '.') }}</div>
                        </td>
                        <td style="text-align:right; font-weight:600; color:var(--success);">
                            @if($disp->resolution_type === 'sold_as_reject')
                                Rp {{ number_format($saleAmount, 0, ',', '.') }}
                                <div style="font-size:10.5px; color:var(--text-secondary);">@ Rp {{ number_format($disp->sale_price, 0, ',', '.') }}</div>
                            @else
                                <span style="color:var(--text-secondary);">-</span>
                            @endif
                        </td>
                        <td>
                            @if($disp->journalEntry)
                                <a href="{{ route('accounting.journals.show', $disp->journalEntry->id) }}" class="badge badge-confirmed" style="text-decoration:none;">
                                    <i class="fa-solid fa-book"></i> {{ $disp->journalEntry->entry_number }}
                                </a>
                            @else
                                <span class="badge badge-draft">-</span>
                            @endif
                        </td>
                        <td>{{ $disp->user->name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            <i class="fa-solid fa-box-archive" style="font-size:32px; margin-bottom:12px; opacity:0.3; display:block;"></i>
                            Belum ada transaksi penyelesaian barang karantina yang sesuai filter.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($dispositions->hasPages())
        <div style="padding:16px;">
            {{ $dispositions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
