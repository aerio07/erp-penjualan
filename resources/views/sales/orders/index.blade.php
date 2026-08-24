@extends('layouts.app')
@section('title', 'Sales Order')
@section('page-title', 'Sales Order (Pesanan Penjualan)')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Daftar Sales Order</h1>
            <p>Kelola pesanan penjualan customer, alokasi stok, dan pelacakan status pemenuhan pengiriman.</p>
        </div>
        <a href="{{ route('sales.orders.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Buat Sales Order
        </a>
    </div>

    <x-list-filter-bar :action="route('sales.orders.index')" searchPlaceholder="Cari no. SO, customer, catatan...">
        <select name="customer_id" class="form-control" onchange="this.form.submit()">
            <option value="">-- Semua Customer --</option>
            @foreach($customers as $c)
            <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>
                {{ $c->name }}
            </option>
            @endforeach
        </select>

        <select name="status" class="form-control" onchange="this.form.submit()">
            <option value="">-- Semua Status Order --</option>
            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
            <option value="waiting_approval" {{ request('status') == 'waiting_approval' ? 'selected' : '' }}>Menunggu Approval</option>
            <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
            <option value="done" {{ request('status') == 'done' ? 'selected' : '' }}>Done</option>
            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
        </select>

        <select name="fulfillment_status" class="form-control" onchange="this.form.submit()">
            <option value="">-- Semua Status Pemenuhan --</option>
            <option value="ready_to_ship" {{ request('fulfillment_status') == 'ready_to_ship' ? 'selected' : '' }}>Ready to Ship</option>
            <option value="partially_available" {{ request('fulfillment_status') == 'partially_available' ? 'selected' : '' }}>Partially Available</option>
            <option value="backorder" {{ request('fulfillment_status') == 'backorder' ? 'selected' : '' }}>Backorder</option>
            <option value="partially_delivered" {{ request('fulfillment_status') == 'partially_delivered' ? 'selected' : '' }}>Partially Delivered</option>
            <option value="delivered" {{ request('fulfillment_status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
        </select>
    </x-list-filter-bar>

    <div class="card">
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <x-sortable-header column="so_number" title="No. SO" />
                        <th>Customer</th>
                        <x-sortable-header column="order_date" title="Tgl Order" />
                        <x-sortable-header column="total_amount" title="Total" align="right" />
                        <x-sortable-header column="status" title="Status Order" align="center" />
                        <th style="text-align:center;">Status Pemenuhan</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('sales.orders.show', $order) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                                {{ $order->so_number }}
                            </a>
                        </td>
                        <td>{{ $order->customer->name ?? '-' }}</td>
                        <td>{{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}</td>
                        <td style="text-align:right; font-weight:600;">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                        <td style="text-align:center;">
                            <span class="badge badge-{{ $order->status }}">
                                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            @php
                                $fBadge = match($order->fulfillment_status) {
                                    'ready_to_ship'       => 'background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;',
                                    'partially_available' => 'background:#fef3c7; color:#b45309; border:1px solid #fde68a;',
                                    'backorder'           => 'background:#fee2e2; color:#b91c1c; border:1px solid #fecaca;',
                                    'partially_delivered' => 'background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;',
                                    'delivered'           => 'background:#d1fae5; color:#047857; border:1px solid #a7f3d0;',
                                    default               => 'background:#f1f5f9; color:#475569; border:1px solid #e2e8f0;'
                                };
                                $fLabel = match($order->fulfillment_status) {
                                    'ready_to_ship'       => 'Ready to Ship',
                                    'partially_available' => 'Partial Available',
                                    'backorder'           => 'Backorder (PO)',
                                    'partially_delivered' => 'Partially Delivered',
                                    'delivered'           => 'Delivered',
                                    default               => 'Pending'
                                };
                            @endphp
                            <span style="display:inline-block; font-size:11px; font-weight:700; text-transform:uppercase; padding:3px 8px; border-radius:9999px; {{ $fBadge }}">
                                {{ $fLabel }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                <a href="{{ route('sales.orders.show', $order) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                @if($order->status === 'draft')
                                <button data-confirm-delete="del-so-{{ $order->id }}" class="btn btn-danger btn-sm btn-icon" title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <form id="del-so-{{ $order->id }}" method="POST" action="{{ route('sales.orders.destroy', $order) }}" style="display:none;">
                                    @csrf @method('DELETE')
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            <i class="fa-solid fa-store" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                            Belum ada Sales Order yang sesuai filter
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
