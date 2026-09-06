@extends('layouts.app')
@section('title', 'Penerimaan Barang')
@section('page-title', 'Penerimaan Barang (GRN)')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Penerimaan Barang (GRN)</h1>
            <p>Catat penerimaan barang dari supplier berdasarkan Purchase Order</p>
        </div>
        <a href="{{ route('purchase.goods-receipts.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Catat Penerimaan
        </a>
    </div>

    <x-list-filter-bar :action="route('purchase.goods-receipts.index')" placeholder="Cari GRN, No. PO, Supplier..." :showDateFilter="true">
        <select name="is_invoiced" class="form-control" style="height:38px; font-size:13px; min-width:160px; border-radius:6px;">
            <option value="">Semua Status Faktur</option>
            <option value="0" {{ request('is_invoiced') === '0' ? 'selected' : '' }}>Siap Ditagih</option>
            <option value="1" {{ request('is_invoiced') === '1' ? 'selected' : '' }}>Sudah Di-Invoice</option>
        </select>

        <select name="warehouse_id" class="form-control" style="height:38px; font-size:13px; min-width:160px; border-radius:6px;">
            <option value="">Semua Gudang</option>
            @foreach($warehouses as $wh)
            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
            @endforeach
        </select>

        <select name="qc_status" class="form-control" style="height:38px; font-size:13px; min-width:150px; border-radius:6px;">
            <option value="">Semua Status QC</option>
            <option value="pending" {{ request('qc_status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="passed" {{ request('qc_status') === 'passed' ? 'selected' : '' }}>Passed (Lolos)</option>
            <option value="failed" {{ request('qc_status') === 'failed' ? 'selected' : '' }}>Failed (Gagal)</option>
            <option value="partial" {{ request('qc_status') === 'partial' ? 'selected' : '' }}>Partial</option>
        </select>
    </x-list-filter-bar>

    <div class="card">
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <x-sortable-header column="receipt_number" title="No. GRN" />
                        <th>No. PO</th>
                        <th>Supplier</th>
                        <th>Gudang Tujuan</th>
                        <x-sortable-header column="received_date" title="Tgl Terima" :isDefault="true" />
                        <th>Status Faktur</th>
                        <th>Diterima Oleh</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $receipt)
                    <tr>
                        <td>
                            <a href="{{ route('purchase.goods-receipts.show', $receipt) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                                {{ $receipt->receipt_number }}
                            </a>
                        </td>
                        <td>
                            @if($receipt->purchaseOrder)
                            <a href="{{ route('purchase.orders.show', $receipt->purchaseOrder) }}" style="color:var(--text-primary); text-decoration:none; font-weight:500;">
                                {{ $receipt->purchaseOrder->po_number }}
                            </a>
                            @else
                            -
                            @endif
                        </td>
                        <td>{{ $receipt->purchaseOrder->supplier->name ?? '-' }}</td>
                        <td>
                            <span class="badge badge-confirmed">
                                <i class="fa-solid fa-warehouse"></i> {{ $receipt->warehouse_names }}
                            </span>
                        </td>
                        <td>{{ $receipt->received_date ? $receipt->received_date->format('d/m/Y') : '-' }}</td>
                        <td>
                            @if($receipt->is_invoiced && $receipt->purchaseInvoice)
                                <a href="{{ route('purchase.invoices.show', $receipt->purchaseInvoice) }}" class="badge badge-info" style="text-decoration:none;" title="Lihat Faktur Pembelian">
                                    <i class="fa-solid fa-file-invoice-dollar"></i> Invoiced
                                </a>
                            @else
                                <a href="{{ route('purchase.invoices.create', ['po_id' => $receipt->purchase_order_id, 'grn_id' => $receipt->id]) }}" class="badge badge-pending" style="text-decoration:none;" title="Klik untuk terbitkan tagihan supplier">
                                    <i class="fa-solid fa-plus"></i> Siap Ditagih
                                </a>
                            @endif
                        </td>
                        <td>{{ $receipt->user->name ?? '-' }}</td>
                        <td style="text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                @if(!$receipt->is_invoiced)
                                <a href="{{ route('purchase.invoices.create', ['po_id' => $receipt->purchase_order_id, 'grn_id' => $receipt->id]) }}" class="btn btn-primary btn-sm btn-icon" title="Lempar ke Invoice / Buat Tagihan">
                                    <i class="fa-solid fa-file-invoice-dollar"></i>
                                </a>
                                @endif
                                <a href="{{ route('purchase.goods-receipts.show', $receipt) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            <i class="fa-solid fa-boxes-stacked" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                            Belum ada catatan penerimaan barang yang sesuai filter
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($receipts->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border);">
            {{ $receipts->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
