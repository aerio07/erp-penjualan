@extends('layouts.app')
@section('title', 'Detail Retur - ' . $return->return_number)
@section('page-title', 'Detail Retur Pembelian')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>{{ $return->return_number }}</h1>
            <p>Supplier: <strong>{{ $return->supplier->name ?? '-' }}</strong> · Ref GRN: {{ $return->goodsReceipt->receipt_number ?? '-' }}</p>
        </div>
        <div style="display:flex; gap:8px;">
            @if($return->status === 'draft')
            <form method="POST" action="{{ route('purchase.returns.complete', $return) }}" style="display:inline;">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-primary" onclick="return confirm('Selesaikan retur dan kurangi stok barang di gudang?')">
                    <i class="fa-solid fa-check"></i> Selesaikan Retur & Potong Stok
                </button>
            </form>
            @endif
            <a href="{{ route('purchase.returns.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-3" style="margin-bottom:20px;">
        <div class="card" style="grid-column:span 2;">
            <div class="card-header">
                <h3>Informasi Retur</h3>
                <span class="badge badge-{{ $return->status === 'completed' ? 'done' : 'pending' }}">
                    {{ ucfirst($return->status) }}
                </span>
            </div>
            <div class="card-body">
                <div class="form-row form-row-2">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Gudang Penyimpanan Barang</div>
                        <div style="font-weight:600;"><i class="fa-solid fa-warehouse"></i> {{ $return->goodsReceipt->warehouse_names }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Tanggal Retur</div>
                        <div style="font-weight:600;">{{ $return->return_date ? $return->return_date->format('d F Y') : '-' }}</div>
                    </div>
                </div>

                @if($return->reason)
                <div style="margin-top:16px; padding:12px; background:#f8fafc; border-radius:10px; font-size:14px;">
                    <strong style="color:var(--text-secondary);">Alasan Retur:</strong> {{ $return->reason }}
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Ringkasan</h3></div>
            <div class="card-body">
                <div style="font-size:12px; color:var(--text-secondary);">Total Item Dikembalikan</div>
                <div style="font-size:24px; font-weight:700; color:var(--danger); margin-top:4px;">
                    {{ number_format($return->items->sum('qty')) }} pcs
                </div>
            </div>
        </div>
    </div>

    {{-- Items Table --}}
    <div class="card">
        <div class="card-header"><h3>Item Dikembalikan</h3></div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produk</th>
                        <th style="text-align:center;">Qty Retur</th>
                        <th style="text-align:center;">Jenis</th>
                        <th style="text-align:right;">Nilai Satuan (HPP)</th>
                        <th style="text-align:right;">Subtotal Retur</th>
                        <th>Alasan Item</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($return->items as $idx => $item)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <div style="font-weight:600;">{{ $item->product->name ?? '-' }}</div>
                            <div style="font-size:12px; color:var(--text-secondary);">{{ $item->product->sku ?? '-' }}</div>
                        </td>
                        <td style="text-align:center; font-weight:600; color:var(--danger);">
                            {{ $item->qty }} {{ $item->product->unit ?? 'pcs' }}
                        </td>
                        <td style="text-align:center;">
                            <span class="badge {{ $item->source_type === 'accepted' ? 'badge-done' : 'badge-cancelled' }}">
                                {{ $item->source_type === 'accepted' ? 'Diterima Stok' : 'Rusak / Reject' }}
                            </span>
                        </td>
                        <td style="text-align:right;">Rp {{ number_format($item->unit_cost, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:600;">Rp {{ number_format($item->qty * $item->unit_cost, 0, ',', '.') }}</td>
                        <td>{{ $item->reason ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
