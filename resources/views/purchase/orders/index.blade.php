@extends('layouts.app')
@section('title', 'Purchase Orders')
@section('page-title', 'Purchase Order')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Purchase Order</h1>
            <p>Kelola semua purchase order ke supplier</p>
        </div>
        <a href="{{ route('purchase.orders.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Buat PO Baru
        </a>
    </div>

    {{-- Filters --}}
    <x-list-filter-bar :action="route('purchase.orders.index')" placeholder="Cari No. PO, Supplier, Catatan..." :showDateFilter="true">
        <select name="supplier_id" class="form-control" style="height:38px; font-size:13px; min-width:170px; border-radius:6px;">
            <option value="">Semua Supplier</option>
            @foreach($suppliers as $sup)
            <option value="{{ $sup->id }}" {{ request('supplier_id') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
            @endforeach
        </select>

        <select name="status" class="form-control" style="height:38px; font-size:13px; min-width:160px; border-radius:6px;">
            <option value="">Semua Status</option>
            @foreach(['draft','waiting_approval','confirmed','partially_received','done','cancelled'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
            @endforeach
        </select>
    </x-list-filter-bar>

    <div class="card">
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <x-sortable-header column="po_number" title="No. PO" />
                        <th>Supplier</th>
                        <x-sortable-header column="order_date" title="Tanggal" />
                        <x-sortable-header column="expected_date" title="Exp. Delivery" />
                        <x-sortable-header column="total_amount" title="Total" align="right" />
                        <x-sortable-header column="status" title="Status" align="center" />
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('purchase.orders.show', $order) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                                {{ $order->po_number }}
                            </a>
                        </td>
                        <td>{{ $order->supplier->name ?? '-' }}</td>
                        <td>{{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}</td>
                        <td>{{ $order->expected_date ? $order->expected_date->format('d/m/Y') : '-' }}</td>
                        <td style="text-align:right; font-weight:600;">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                        <td style="text-align:center;">
                            <span class="badge badge-{{ $order->status }}">
                                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                <a href="{{ route('purchase.orders.show', $order) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                @if($order->status === 'draft')
                                <a href="{{ route('purchase.orders.edit', $order) }}" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                @endif
                                <a href="{{ route('pdf.purchase-order', $order) }}" class="btn btn-secondary btn-sm btn-icon" title="Export PDF" target="_blank">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </a>
                                @if($order->status === 'draft')
                                <button data-confirm-delete="delete-po-{{ $order->id }}" class="btn btn-danger btn-sm btn-icon" title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <form id="delete-po-{{ $order->id }}" method="POST" action="{{ route('purchase.orders.destroy', $order) }}" style="display:none;">
                                    @csrf @method('DELETE')
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            <i class="fa-solid fa-inbox" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                            Belum ada Purchase Order yang sesuai filter
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border);">
            {{ $orders->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
