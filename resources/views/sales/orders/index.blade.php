@extends('layouts.app')
@section('title', 'Sales Order')
@section('page-title', 'Sales Order')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Sales Order</h1>
            <p>Kelola pesanan penjualan dari customer</p>
        </div>
        <a href="{{ route('sales.orders.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Buat SO Baru
        </a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>No. SO</th>
                        <th>Customer</th>
                        <th>Tanggal Order</th>
                        <th>Exp. Kirim</th>
                        <th style="text-align:right;">Total Total</th>
                        <th style="text-align:center;">Status</th>
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
                        <td>{{ $order->order_date->format('d/m/Y') }}</td>
                        <td>{{ $order->expected_delivery_date ? $order->expected_delivery_date->format('d/m/Y') : '-' }}</td>
                        <td style="text-align:right; font-weight:600;">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                        <td style="text-align:center;">
                            <span class="badge badge-{{ $order->status }}">
                                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
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
                            Belum ada Sales Order
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
