@extends('layouts.app')
@section('title', 'Detail GRN - ' . $goodsReceipt->receipt_number)
@section('page-title', 'Detail Penerimaan Barang (GRN)')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>{{ $goodsReceipt->receipt_number }}</h1>
            <p>Penerimaan untuk PO: <a href="{{ route('purchase.orders.show', $goodsReceipt->purchaseOrder) }}" style="color:var(--primary); font-weight:600;">{{ $goodsReceipt->purchaseOrder->po_number }}</a></p>
        </div>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('purchase.goods-receipts.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar
            </a>
            @if($goodsReceipt->items->sum('qty_rejected') > 0 || $goodsReceipt->items->sum('qty_received') > 0)
            <a href="{{ route('purchase.returns.create', ['grn_id' => $goodsReceipt->id]) }}" class="btn btn-warning">
                <i class="fa-solid fa-rotate-left"></i> Buat Retur Pembelian
            </a>
            @endif
        </div>
    </div>

    <div class="grid grid-3" style="margin-bottom:20px;">
        <div class="card" style="grid-column:span 2;">
            <div class="card-header">
                <h3>Informasi Penerimaan</h3>
                <span class="badge badge-done">Passed QC</span>
            </div>
            <div class="card-body">
                <div class="form-row form-row-2">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Supplier</div>
                        <div style="font-weight:600;">{{ $goodsReceipt->purchaseOrder->supplier->name ?? '-' }}</div>
                        <div style="font-size:13px; color:var(--text-secondary);">{{ $goodsReceipt->purchaseOrder->supplier->phone ?? '' }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Gudang Penyimpanan</div>
                        <div style="font-weight:600; color:var(--primary);">
                            <i class="fa-solid fa-warehouse"></i> {{ $goodsReceipt->warehouse_names }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Tanggal Diterima</div>
                        <div style="font-weight:600;">{{ $goodsReceipt->received_date ? $goodsReceipt->received_date->format('d F Y') : '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Diterima Oleh</div>
                        <div style="font-weight:600;">{{ $goodsReceipt->user->name ?? '-' }}</div>
                    </div>
                </div>

                @if($goodsReceipt->notes)
                <div style="margin-top:16px; padding:12px; background:#f8fafc; border-radius:10px; font-size:14px; color:var(--text-secondary);">
                    <i class="fa-solid fa-note-sticky" style="margin-right:6px;"></i> {{ $goodsReceipt->notes }}
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Ringkasan Fisik</h3></div>
            <div class="card-body">
                <div style="display:flex; flex-direction:column; gap:16px;">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary);">Total Fisik Datang</div>
                        <div style="font-size:24px; font-weight:700;">
                            {{ number_format($goodsReceipt->items->sum('qty_received') + $goodsReceipt->items->sum('qty_rejected')) }} pcs
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary);">Kondisi Baik (Masuk Stok)</div>
                        <div style="font-size:20px; font-weight:700; color:var(--success);">
                            <i class="fa-solid fa-circle-check"></i> {{ number_format($goodsReceipt->items->sum('qty_received')) }} pcs
                        </div>
                    </div>
                    @if($goodsReceipt->items->sum('qty_rejected') > 0)
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary);">Kondisi Rusak / Ditolak (Reject)</div>
                        <div style="font-size:20px; font-weight:700; color:var(--danger);">
                            <i class="fa-solid fa-triangle-exclamation"></i> {{ number_format($goodsReceipt->items->sum('qty_rejected')) }} pcs
                        </div>
                    </div>
                    @endif

                    @if($goodsReceipt->purchaseReturns->count() > 0)
                    <hr style="border:none; border-top:1px solid var(--border); margin:0;">
                    <div style="padding:10px; background:#fff1f2; border-radius:8px; border:1px solid #fecdd3;">
                        <div style="font-size:12px; font-weight:600; color:#e11d48; margin-bottom:6px;">
                            <i class="fa-solid fa-rotate-left"></i> Dokumen Retur Terkait:
                        </div>
                        @foreach($goodsReceipt->purchaseReturns as $ret)
                        <a href="{{ route('purchase.returns.show', $ret) }}" style="display:flex; justify-content:space-between; align-items:center; font-size:13px; color:#be123c; font-weight:600; text-decoration:none; margin-bottom:4px;">
                            <span>{{ $ret->return_number }}</span>
                            <span class="badge badge-{{ $ret->status === 'completed' ? 'done' : ($ret->status === 'sent' ? 'confirmed' : 'pending') }}">{{ ucfirst($ret->status) }}</span>
                        </a>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Item Table --}}
    <div class="card">
        <div class="card-header"><h3>Detail Item Diterima & Alokasi Gudang</h3></div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Produk</th>
                        <th>Gudang Tujuan</th>
                        <th style="text-align:center;">Qty Baik (Stok Masuk)</th>
                        <th style="text-align:center;">Qty Rusak (Reject)</th>
                        <th style="text-align:right;">Harga Acuan HPP</th>
                        <th style="text-align:center;">Kondisi</th>
                        <th style="text-align:center;">Alasan Sisa / Rusak</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($goodsReceipt->items as $idx => $item)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <div style="font-weight:600;">{{ $item->purchaseOrderItem->product->name ?? '-' }}</div>
                            <div style="font-size:12px; color:var(--text-secondary);">{{ $item->purchaseOrderItem->product->sku ?? '-' }}</div>
                        </td>
                        <td>
                            <span class="badge badge-confirmed">
                                <i class="fa-solid fa-warehouse"></i> {{ $item->warehouse->name ?? '-' }}
                            </span>
                        </td>
                        <td style="text-align:center; font-weight:600; color:var(--success);">
                            {{ $item->qty_received }} {{ $item->purchaseOrderItem->product->unit ?? 'pcs' }}
                        </td>
                        <td style="text-align:center; {{ $item->qty_rejected > 0 ? 'color:var(--danger); font-weight:600;' : 'color:var(--text-secondary);' }}">
                            {{ $item->qty_rejected }}
                        </td>
                        <td style="text-align:right;">
                            Rp {{ number_format($item->unit_cost, 0, ',', '.') }}
                        </td>
                        <td style="text-align:center;">
                            <span class="badge {{ $item->condition === 'Good' ? 'badge-done' : 'badge-cancelled' }}">
                                {{ $item->condition }}
                            </span>
                        </td>
                        <td style="text-align:center; font-size:12px;">
                            @if($item->shortage_reason === 'damaged_in_transit')
                                <span style="color:var(--danger); font-weight:600;"><i class="fa-solid fa-truck-burst"></i> Rusak Saat Kirim</span>
                            @elseif($item->shortage_reason === 'not_shipped')
                                <span style="color:var(--warning); font-weight:600;"><i class="fa-solid fa-box-open"></i> Belum Dikirim (Parsial)</span>
                            @else
                                <span style="color:var(--text-secondary);">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
