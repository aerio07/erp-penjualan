@extends('layouts.app')
@section('title', 'Invoice Penjualan')
@section('page-title', 'Invoice Penjualan')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Invoice Penjualan</h1>
            <p>Kelola faktur tagihan ke customer dan status pelunasan piutang</p>
        </div>
        <a href="{{ route('sales.invoices.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Terbitkan Invoice
        </a>
    </div>

    <x-list-filter-bar :action="route('sales.invoices.index')" placeholder="Cari No. Invoice, Ref. SO, Customer..." :showDateFilter="true">
        <select name="customer_id" class="form-control" style="height:38px; font-size:13px; min-width:170px; border-radius:6px;">
            <option value="">Semua Customer</option>
            @foreach($customers as $cust)
            <option value="{{ $cust->id }}" {{ request('customer_id') == $cust->id ? 'selected' : '' }}>{{ $cust->name }}</option>
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
                        <th>Customer</th>
                        <th>Ref. SO</th>
                        <th>Ref. Surat Jalan</th>
                        <x-sortable-header column="invoice_date" title="Tgl Invoice" />
                        <x-sortable-header column="due_date" title="Jatuh Tempo" />
                        <x-sortable-header column="total_amount" title="Tagihan Efektif" align="right" />
                        <th style="text-align:right;">Sisa Piutang</th>
                        <x-sortable-header column="status" title="Status" align="center" />
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                    <tr>
                        <td>
                            <a href="{{ route('sales.invoices.show', $inv) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                                {{ $inv->invoice_number }}
                            </a>
                        </td>
                        <td>{{ $inv->salesOrder->customer->name ?? '-' }}</td>
                        <td>
                            @if($inv->salesOrder)
                            <a href="{{ route('sales.orders.show', $inv->salesOrder) }}" style="color:var(--text-primary); text-decoration:none;">
                                {{ $inv->salesOrder->so_number }}
                            </a>
                            @else
                            -
                            @endif
                        </td>
                        <td>
                            @if($inv->delivery)
                            <a href="{{ route('sales.deliveries.show', $inv->delivery) }}" style="color:var(--primary); text-decoration:none; font-weight:500;">
                                {{ $inv->delivery->delivery_number }}
                            </a>
                            @else
                            <span style="color:var(--text-secondary);">-</span>
                            @endif
                        </td>
                        <td>{{ $inv->invoice_date ? $inv->invoice_date->format('d/m/Y') : '-' }}</td>
                        <td>
                            <span style="{{ $inv->status !== 'paid' && $inv->due_date < today() ? 'color:var(--danger); font-weight:600;' : '' }}">
                                {{ $inv->due_date ? $inv->due_date->format('d/m/Y') : '-' }}
                            </span>
                        </td>
                        <td style="text-align:right; font-weight:600;">
                            Rp {{ number_format($inv->effective_total_amount, 0, ',', '.') }}
                            @if($inv->total_reversed_amount > 0)
                                <div style="font-size:11px; color:var(--text-secondary); font-weight:400;">
                                    Retur: Rp {{ number_format($inv->total_reversed_amount, 0, ',', '.') }}
                                </div>
                            @endif
                        </td>
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
                                <a href="{{ route('sales.invoices.show', $inv) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                @if($inv->outstanding_amount > 0)
                                <a href="{{ route('sales.payments.create') }}?invoice_id={{ $inv->id }}" class="btn btn-success btn-sm btn-icon" title="Terima Pembayaran">
                                    <i class="fa-solid fa-hand-holding-dollar"></i>
                                </a>
                                @endif
                                <a href="{{ route('pdf.sales-invoice', $inv) }}" class="btn btn-secondary btn-sm btn-icon" title="Export PDF" target="_blank">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            <i class="fa-solid fa-file-invoice-dollar" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                            Belum ada invoice penjualan yang sesuai filter
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
