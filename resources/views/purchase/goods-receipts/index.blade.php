@extends('layouts.app')
@section('title', 'Penerimaan Barang')
@section('page-title', 'Penerimaan Barang')

@section('content')
<div class="animate-in">
    <x-page-header
        title="Penerimaan Barang"
        subtitle="Catat penerimaan barang dari supplier berdasarkan Purchase Order">
        <x-slot:actions>
            <a href="{{ route('purchase.goods-receipts.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Buat PB
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-list-filter-bar :action="route('purchase.goods-receipts.index')" placeholder="Cari No Penerimaan, No. PO, Supplier..." :showDateFilter="true">
        <select name="status" class="form-control filter-select">
            <option value="">Semua Status</option>
            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
            <option value="waiting_approval" {{ request('status') === 'waiting_approval' ? 'selected' : '' }}>Menunggu Approval</option>
            <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Disetujui</option>
            <option value="revised" {{ request('status') === 'revised' ? 'selected' : '' }}>Perlu Revisi</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
        </select>

        <select name="is_invoiced" class="form-control filter-select">
            <option value="">Semua Status Faktur</option>
            <option value="0" {{ request('is_invoiced') === '0' ? 'selected' : '' }}>Siap Ditagih</option>
            <option value="1" {{ request('is_invoiced') === '1' ? 'selected' : '' }}>Sudah Di-Invoice</option>
        </select>

        <select name="warehouse_id" class="form-control filter-select">
            <option value="">Semua Gudang</option>
            @foreach($warehouses as $wh)
            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
            @endforeach
        </select>

        <select name="qc_status" class="form-control filter-select">
            <option value="">Semua Status QC</option>
            <option value="pending" {{ request('qc_status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="passed" {{ request('qc_status') === 'passed' ? 'selected' : '' }}>Passed (Lolos)</option>
            <option value="failed" {{ request('qc_status') === 'failed' ? 'selected' : '' }}>Failed (Gagal)</option>
            <option value="partial" {{ request('qc_status') === 'partial' ? 'selected' : '' }}>Partial</option>
        </select>
    </x-list-filter-bar>

    <x-data-table :paginator="$receipts">
        <x-slot:head>
            <tr>
                <x-sortable-header column="receipt_number" title="No Penerimaan" />
                <th>No. PO</th>
                <th>Supplier</th>
                <th>Gudang Penerimaan</th>
                <x-sortable-header column="received_date" title="Tgl Terima" :isDefault="true" />
                <x-sortable-header column="status" title="Status" />
                <th>Status Faktur</th>
                <th>Diterima Oleh</th>
                <th class="text-center col-actions">Aksi</th>
            </tr>
        </x-slot:head>

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
                    <i class="fa-solid fa-warehouse"></i> {{ $receipt->warehouse->name ?? $receipt->warehouse_names }}
                </span>
            </td>
            <td>{{ $receipt->received_date ? $receipt->received_date->format('d/m/Y') : '-' }}</td>
            <td>
                <span class="badge {{ $receipt->status_badge }}">
                    {{ $receipt->status_label }}
                </span>
            </td>
            <td>
                @if($receipt->status === 'confirmed')
                    @if($receipt->is_invoiced && $receipt->purchaseInvoice)
                        <a href="{{ route('purchase.invoices.show', $receipt->purchaseInvoice) }}" class="badge badge-info" style="text-decoration:none;" title="Lihat Faktur Pembelian">
                            <i class="fa-solid fa-file-invoice-dollar"></i> Invoiced
                        </a>
                    @else
                        <a href="{{ route('purchase.invoices.create', ['po_id' => $receipt->purchase_order_id, 'grn_id' => $receipt->id]) }}" class="badge badge-pending" style="text-decoration:none;" title="Klik untuk terbitkan tagihan supplier">
                            <i class="fa-solid fa-plus"></i> Siap Ditagih
                        </a>
                    @endif
                @else
                    <span style="color:var(--text-secondary); font-size:12px;">-</span>
                @endif
            </td>
            <td>{{ $receipt->user->name ?? '-' }}</td>
            <td class="col-actions text-center">
                <div style="display:flex; gap:6px; justify-content:center;">
                    <a href="{{ route('purchase.goods-receipts.show', $receipt) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                        <i class="fa-solid fa-eye"></i>
                    </a>
                    @if(in_array($receipt->status, ['draft', 'revised']))
                    <a href="{{ route('purchase.goods-receipts.edit', $receipt) }}" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                        <i class="fa-solid fa-pen"></i>
                    </a>
                    <button type="button" class="btn btn-danger btn-sm btn-icon" 
                        data-confirm-swal="delete-grn-{{ $receipt->id }}"
                        data-swal-title="Hapus Draft Penerimaan?"
                        data-swal-html="Hapus draft penerimaan <strong>{{ $receipt->receipt_number }}</strong>? Tindakan ini tidak dapat dibatalkan."
                        data-swal-icon="warning"
                        data-swal-icon-color="#ef4444"
                        data-swal-btn-text="Ya, Hapus"
                        data-swal-btn-icon="fa-solid fa-trash"
                        data-swal-btn-class="btn btn-danger"
                        title="Hapus Draft">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                    <form id="delete-grn-{{ $receipt->id }}" method="POST" action="{{ route('purchase.goods-receipts.destroy', $receipt) }}" style="display:none;">
                        @csrf
                        @method('DELETE')
                    </form>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="9" style="text-align:center; padding:48px; color:var(--text-secondary);">
                <i class="fa-solid fa-boxes-stacked" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                Belum ada catatan penerimaan barang yang sesuai filter
            </td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection
