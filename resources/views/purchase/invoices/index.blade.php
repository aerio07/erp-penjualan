@extends('layouts.app')
@section('title', 'Invoice Pembelian')
@section('page-title', 'Invoice Pembelian')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Invoice Pembelian</h1>
            <p>Kelola tagihan dari supplier dan status pelunasan hutang</p>
        </div>
        <a href="{{ route('purchase.invoices.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Terbitkan Invoice
        </a>
    </div>

    <x-list-filter-bar :action="route('purchase.invoices.index')" placeholder="Cari No. Invoice, Ref. PO, Supplier..." :showDateFilter="true">
        <select name="supplier_id" class="form-control" style="height:38px; font-size:13px; min-width:170px; border-radius:6px;">
            <option value="">Semua Supplier</option>
            @foreach($suppliers as $sup)
            <option value="{{ $sup->id }}" {{ request('supplier_id') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
            @endforeach
        </select>

        <select name="status" class="form-control" style="height:38px; font-size:13px; min-width:150px; border-radius:6px;">
            <option value="">Semua Status</option>
            <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Belum Dibayar (Unpaid)</option>
            <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Dibayar Sebagian (Partial)</option>
            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Lunas (Paid)</option>
        </select>
    </x-list-filter-bar>

    <div class="card">
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <x-sortable-header column="invoice_number" title="No. Invoice" />
                        <th>Supplier</th>
                        <th>Ref. PO</th>
                        <x-sortable-header column="invoice_date" title="Tgl Invoice" />
                        <x-sortable-header column="due_date" title="Jatuh Tempo" />
                        <x-sortable-header column="total_amount" title="Total Tagihan" align="right" />
                        <th style="text-align:right;">Sisa Hutang</th>
                        <x-sortable-header column="status" title="Status" align="center" />
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                    <tr>
                        <td>
                            <a href="{{ route('purchase.invoices.show', $inv) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                                {{ $inv->invoice_number }}
                            </a>
                        </td>
                        <td>{{ $inv->purchaseOrder->supplier->name ?? '-' }}</td>
                        <td>
                            @if($inv->purchaseOrder)
                            <a href="{{ route('purchase.orders.show', $inv->purchaseOrder) }}" style="color:var(--text-primary); text-decoration:none;">
                                {{ $inv->purchaseOrder->po_number }}
                            </a>
                            @else
                            -
                            @endif
                        </td>
                        <td>{{ $inv->invoice_date ? $inv->invoice_date->format('d/m/Y') : '-' }}</td>
                        <td>
                            <span style="{{ $inv->status !== 'paid' && $inv->due_date < today() ? 'color:var(--danger); font-weight:600;' : '' }}">
                                {{ $inv->due_date ? $inv->due_date->format('d/m/Y') : '-' }}
                            </span>
                        </td>
                        <td style="text-align:right; font-weight:600;">Rp {{ number_format($inv->total_amount, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:600; color:{{ $inv->outstanding_amount > 0 ? 'var(--danger)' : 'var(--success)' }};">
                            Rp {{ number_format($inv->outstanding_amount, 0, ',', '.') }}
                        </td>
                        <td style="text-align:center;">
                            <span class="badge badge-{{ $inv->status }}">
                                {{ ucfirst($inv->status) }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                <a href="{{ route('purchase.invoices.show', $inv) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                @if($inv->outstanding_amount > 0)
                                <a href="{{ route('purchase.payments.create') }}?invoice_id={{ $inv->id }}" class="btn btn-success btn-sm btn-icon" title="Bayar Hutang">
                                    <i class="fa-solid fa-money-check-dollar"></i>
                                </a>
                                @endif
                                <a href="{{ route('pdf.purchase-invoice', $inv) }}" class="btn btn-secondary btn-sm btn-icon" title="Export PDF" target="_blank">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            <i class="fa-solid fa-file-invoice-dollar" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                            Belum ada invoice pembelian yang sesuai filter
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border);">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
