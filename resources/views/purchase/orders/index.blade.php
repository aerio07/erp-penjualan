@extends('layouts.app')
@section('title', 'Purchase Order')
@section('page-title', 'Purchase Order')

@section('content')
<div class="animate-in">
    <x-page-header 
        title="Purchase Order" 
        subtitle="Kelola semua purchase order ke supplier"
    >
        <x-slot:actions>
            <a href="{{ route('purchase.orders.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Buat PO Baru
            </a>
        </x-slot:actions>
    </x-page-header>

    {{-- Filters --}}
    <x-list-filter-bar :action="route('purchase.orders.index')" placeholder="Cari No. PO, Supplier, Catatan..." :showDateFilter="true">
        <select name="supplier_id" class="form-control filter-select">
            <option value="">Semua Supplier</option>
            @foreach($suppliers as $sup)
            <option value="{{ $sup->id }}" {{ request('supplier_id') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
            @endforeach
        </select>

        <select name="status" class="form-control filter-select">
            <option value="">Semua Status</option>
            @php
                $statusLabels = [
                    'draft' => 'Draft',
                    'waiting_approval' => 'Menunggu Approval',
                    'confirmed' => 'Dikonfirmasi',
                    'partially_received' => 'Diterima Sebagian',
                    'done' => 'Selesai',
                    'cancelled' => 'Dibatalkan',
                ];
            @endphp
            @foreach($statusLabels as $s => $lbl)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $lbl }}</option>
            @endforeach
        </select>
    </x-list-filter-bar>

    <x-data-table :paginator="$orders">
        <x-slot:head>
            <tr>
                <x-sortable-header column="po_number" title="No. PO" />
                <th>Supplier</th>
                <x-sortable-header column="order_date" title="Tanggal" :isDefault="true" />
                <x-sortable-header column="expected_date" title="Estimasi Kirim" />
                <th style="text-align:right;">Diskon</th>
                <x-sortable-header column="total_amount" title="Total" align="right" />
                <x-sortable-header column="status" title="Status" align="center" />
                <th class="text-center col-actions">Aksi</th>
            </tr>
        </x-slot:head>

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
            <td style="text-align:right;">
                @php
                    $itemDiscountTotal = $order->items->sum(function($item) {
                        return ($item->qty_ordered * $item->unit_price) * (($item->discount_percent ?? 0) / 100);
                    });
                    $totalDiscount = ($order->discount_amount ?? 0) + $itemDiscountTotal;
                @endphp
                @if($totalDiscount > 0)
                    <span style="font-weight:600; color:var(--danger); font-size:13px;" title="Diskon Item + Header">
                        -Rp {{ number_format($totalDiscount, 0, ',', '.') }}
                    </span>
                @else
                    <span style="color:var(--text-secondary); font-size:13px;">-</span>
                @endif
            </td>
            <td style="text-align:right;">
                <div style="font-weight:600;">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</div>
            </td>
            <td style="text-align:center;">
                @php
                    $isRevised = ($order->status === 'draft' && $order->approvalRequests->sortByDesc('id')->first()?->status === 'revised');
                @endphp
                @if($isRevised)
                    <span class="badge badge-warning">Perlu Revisi</span>
                @else
                    <span class="badge badge-{{ $order->status }}">
                        {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                    </span>
                @endif
            </td>
            <td class="col-actions text-center">
                <div style="display:flex; gap:6px; justify-content:center;">
                    <a href="{{ route('purchase.orders.show', $order) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                        <i class="fa-solid fa-eye"></i>
                    </a>
                    @if($order->status === 'draft')
                    <a href="{{ route('purchase.orders.edit', $order) }}" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                        <i class="fa-solid fa-pen"></i>
                    </a>
                    <button data-confirm-delete="delete-po-{{ $order->id }}" class="btn btn-danger btn-sm btn-icon" title="Hapus">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                    <form id="delete-po-{{ $order->id }}" method="POST" action="{{ route('purchase.orders.destroy', $order) }}" style="display:none;">
                        @csrf @method('DELETE')
                    </form>
                    @endif
                    @if(in_array($order->status, ['confirmed', 'partially_received', 'done']))
                    <a href="{{ route('pdf.purchase-order', $order) }}" class="btn btn-secondary btn-sm btn-icon" title="Export PDF" target="_blank">
                        <i class="fa-solid fa-file-pdf"></i>
                    </a>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" style="text-align:center; padding:48px; color:var(--text-secondary);">
                <i class="fa-solid fa-inbox" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                Belum ada Purchase Order yang sesuai filter
            </td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection
