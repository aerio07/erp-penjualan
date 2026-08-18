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

    <div class="card">
        <div class="card-header">
            <h3>Daftar Penerimaan Barang</h3>
            <span style="font-size:13px; color:var(--text-secondary);">{{ $receipts->total() }} penerimaan</span>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>No. GRN</th>
                        <th>No. PO</th>
                        <th>Supplier</th>
                        <th>Gudang Tujuan</th>
                        <th>Tgl Terima</th>
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
                            <a href="{{ route('purchase.orders.show', $receipt->purchaseOrder) }}" style="color:var(--text-primary); text-decoration:none; font-weight:500;">
                                {{ $receipt->purchaseOrder->po_number }}
                            </a>
                        </td>
                        <td>{{ $receipt->purchaseOrder->supplier->name ?? '-' }}</td>
                        <td>
                            <span class="badge badge-confirmed">
                                <i class="fa-solid fa-warehouse"></i> {{ $receipt->warehouse_names }}
                            </span>
                        </td>
                        <td>{{ $receipt->received_date ? $receipt->received_date->format('d/m/Y') : '-' }}</td>
                        <td>{{ $receipt->user->name ?? '-' }}</td>
                        <td style="text-align:center;">
                            <a href="{{ route('purchase.goods-receipts.show', $receipt) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            <i class="fa-solid fa-boxes-stacked" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                            Belum ada catatan penerimaan barang
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
