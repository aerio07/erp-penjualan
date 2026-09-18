@extends('layouts.app')
@section('title', 'Sales Order')
@section('page-title', 'Sales Order')

@section('content')
<div class="animate-in">
    <x-page-header
        title="Sales Order"
        subtitle="Kelola pesanan penjualan customer, alokasi stok, dan pelacakan status pemenuhan pengiriman.">
        <x-slot:actions>
            <a href="{{ route('sales.orders.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Buat Sales Order
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-list-filter-bar :action="route('sales.orders.index')" placeholder="Cari no. SO, customer, catatan...">
        <select name="customer_id" class="form-control filter-select" onchange="this.form.submit()">
            <option value="">-- Semua Customer --</option>
            @foreach($customers as $c)
            <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>
                {{ $c->name }}
            </option>
            @endforeach
        </select>

        <select name="status" class="form-control filter-select" onchange="this.form.submit()">
            <option value="">-- Semua Status Order --</option>
            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
            <option value="waiting_approval" {{ request('status') == 'waiting_approval' ? 'selected' : '' }}>Menunggu Approval</option>
            <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Dikonfirmasi</option>
            <option value="done" {{ request('status') == 'done' ? 'selected' : '' }}>Selesai</option>
            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
        </select>

        <select name="fulfillment_status" class="form-control filter-select" onchange="this.form.submit()">
            <option value="">-- Semua Status Pemenuhan --</option>
            <option value="ready_to_ship" {{ request('fulfillment_status') == 'ready_to_ship' ? 'selected' : '' }}>Siap Kirim</option>
            <option value="partially_available" {{ request('fulfillment_status') == 'partially_available' ? 'selected' : '' }}>Tersedia Sebagian</option>
            <option value="backorder" {{ request('fulfillment_status') == 'backorder' ? 'selected' : '' }}>Backorder</option>
            <option value="partially_delivered" {{ request('fulfillment_status') == 'partially_delivered' ? 'selected' : '' }}>Terkirim Sebagian</option>
            <option value="delivered" {{ request('fulfillment_status') == 'delivered' ? 'selected' : '' }}>Terkirim</option>
        </select>

        <select name="channel" class="form-control filter-select" onchange="this.form.submit()">
            <option value="">-- Semua Channel --</option>
            <option value="offline" {{ request('channel') == 'offline' ? 'selected' : '' }}>Offline</option>
            <option value="online" {{ request('channel') == 'online' ? 'selected' : '' }}>Online</option>
        </select>
    </x-list-filter-bar>

    {{-- Quick Filter Channel Pills --}}
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider mr-1">
            <i class="fa-solid fa-route text-slate-400 mr-1"></i> Channel:
        </span>
        <a href="{{ request()->fullUrlWithQuery(['channel' => '']) }}"
           class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all no-underline inline-flex items-center gap-1.5 border shadow-xs {{ empty(request('channel')) ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
            Semua Channel
        </a>
        <a href="{{ request()->fullUrlWithQuery(['channel' => 'offline']) }}"
           class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all no-underline inline-flex items-center gap-1.5 border shadow-xs {{ request('channel') === 'offline' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
            <i class="fa-solid fa-store text-xs {{ request('channel') === 'offline' ? 'text-white' : 'text-slate-500' }}"></i> Offline
        </a>
        <a href="{{ request()->fullUrlWithQuery(['channel' => 'online']) }}"
           class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all no-underline inline-flex items-center gap-1.5 border shadow-xs {{ request('channel') === 'online' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
            <i class="fa-solid fa-globe text-xs {{ request('channel') === 'online' ? 'text-white' : 'text-slate-500' }}"></i> Online
        </a>
    </div>

    <x-data-table :paginator="$orders">
        <x-slot:head>
            <tr>
                <x-sortable-header column="so_number" title="No. SO" />
                <th>Customer</th>
                <x-sortable-header column="order_date" title="Tgl Order" :isDefault="true" />
                <x-sortable-header column="total_amount" title="Total" align="right" />
                <x-sortable-header column="status" title="Status Order" align="center" />
                <th style="text-align:center;">Status Pemenuhan</th>
                <th class="text-center col-actions">Aksi</th>
            </tr>
        </x-slot:head>

        @forelse($orders as $order)
        <tr>
            <td style="white-space:nowrap;">
                <div style="display:flex; align-items:center; gap:6px;">
                    <a href="{{ route('sales.orders.show', $order) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                        {{ $order->so_number }}
                    </a>
                    @if($order->channel === 'online')
                        <span class="badge" style="background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe; font-size:10px; padding:2px 6px;">
                            <i class="fa-solid fa-globe" style="font-size:9px;"></i> Online
                        </span>
                    @else
                        <span class="badge" style="background:#dcfce7; color:#166534; border:1px solid #bbf7d0; font-size:10px; padding:2px 6px;">
                            <i class="fa-solid fa-store" style="font-size:9px;"></i> Offline
                        </span>
                    @endif
                </div>
            </td>
            <td>{{ $order->customer->name ?? '-' }}</td>
            <td>{{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}</td>
            <td style="text-align:right; font-weight:600;">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
            <td style="text-align:center;">
                <x-status-badge :status="$order->status" />
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
                @php
                    $whCount = $order->items->flatMap->reservations->where('status', 'active')->filter(fn($r) => ($r->qty_reserved - $r->qty_delivered) > 0)->pluck('warehouse_id')->unique()->count();
                @endphp
                @if($whCount > 1)
                <div>
                    <span style="display:inline-block; font-size:10px; font-weight:600; background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; border-radius:4px; padding:1px 5px; margin-top:3px;">
                        <i class="fa-solid fa-boxes-stacked" style="color:var(--primary);"></i> {{ $whCount }} Gudang
                    </span>
                </div>
                @endif
            </td>
            <td class="col-actions text-center">
                <div style="display:flex; gap:6px; justify-content:center;">
                    <a href="{{ route('sales.orders.show', $order) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                        <i class="fa-solid fa-eye"></i>
                    </a>
                    @if($order->status === 'draft')
                    <a href="{{ route('sales.orders.edit', $order) }}" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                        <i class="fa-solid fa-pen"></i>
                    </a>
                    <button data-confirm-delete="del-so-{{ $order->id }}" class="btn btn-danger btn-sm btn-icon" title="Hapus">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                    <form id="del-so-{{ $order->id }}" method="POST" action="{{ route('sales.orders.destroy', $order) }}" style="display:none;">
                        @csrf @method('DELETE')
                    </form>
                    @endif
                    @if(in_array($order->status, ['confirmed', 'partially_delivered', 'done']))
                    <a href="{{ route('pdf.sales-order', $order) }}" class="btn btn-secondary btn-sm btn-icon" title="Export PDF" target="_blank">
                        <i class="fa-solid fa-file-pdf"></i>
                    </a>
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
    </x-data-table>
</div>
@endsection
