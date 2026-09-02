@extends('layouts.app')

@section('title', 'Laporan Monitoring Fulfillment Sales Order')

@section('content')
<div class="animate-in" x-data="{ activeRow: null }">
    {{-- Header Halaman --}}
    <div class="page-header" style="margin-bottom: 20px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">
                <i class="fa-solid fa-chart-line" style="color: var(--primary); margin-right: 8px;"></i>
                Laporan Monitoring Fulfillment Sales Order
            </h1>
            <p style="color: var(--text-secondary); font-size: 13px; margin: 0;">
                Pantau progres siklus lengkap: pesanan (SO) &rarr; pengiriman (Surat Jalan) &rarr; retur penjualan &rarr; penagihan (Invoice) &rarr; pembayaran piutang.
            </p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('sales.orders.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-cart-shopping"></i> Kelola Sales Order
            </a>
            <a href="{{ route('sales.returns.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-rotate-left"></i> Kelola Retur
            </a>
            <a href="{{ route('sales.invoices.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-file-invoice-dollar"></i> Kelola Invoice
            </a>
        </div>
    </div>

    {{-- Executive Summary KPI Cards --}}
    <div class="grid grid-4" style="gap: 16px; margin-bottom: 20px;">
        <div class="card" style="border-left: 4px solid var(--primary); padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Total Sales Order</div>
            <div style="font-size: 24px; font-weight: 700; color: var(--text-primary); margin-top: 4px;">
                {{ number_format($totalOrdersCount) }} <span style="font-size: 13px; font-weight: 400; color: var(--text-secondary);">Pesanan</span>
            </div>
            <div style="font-size: 12px; color: var(--primary); margin-top: 4px; font-weight: 600;">
                Rp {{ number_format($totalOrdersAmount, 0, ',', '.') }}
            </div>
        </div>

        <div class="card" style="border-left: 4px solid #0284c7; padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Total Ter-Invoice (Bersih)</div>
            <div style="font-size: 24px; font-weight: 700; color: #0369a1; margin-top: 4px;">
                Rp {{ number_format($totalInvoicedAmount, 0, ',', '.') }}
            </div>
            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">
                {{ $totalOrdersAmount > 0 ? round(($totalInvoicedAmount / $totalOrdersAmount) * 100, 1) : 0 }}% setelah penyesuaian retur
            </div>
        </div>

        <div class="card" style="border-left: 4px solid #10b981; padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Piutang Terbayar</div>
            <div style="font-size: 24px; font-weight: 700; color: #059669; margin-top: 4px;">
                Rp {{ number_format($totalPaidAmount, 0, ',', '.') }}
            </div>
            <div style="font-size: 12px; color: #059669; margin-top: 4px; font-weight: 500;">
                <i class="fa-solid fa-circle-check"></i> Kas Masuk Diterima
            </div>
        </div>

        <div class="card" style="border-left: 4px solid #ef4444; padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Sisa Piutang (Outstanding)</div>
            <div style="font-size: 24px; font-weight: 700; color: #dc2626; margin-top: 4px;">
                Rp {{ number_format($totalOutstandingAmount, 0, ',', '.') }}
            </div>
            <div style="font-size: 12px; color: #dc2626; margin-top: 4px; font-weight: 500;">
                <i class="fa-solid fa-clock"></i> Belum Tertagih / Belum Lunas
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <x-list-filter-bar :action="route('sales.reports.fulfillment')" placeholder="Cari No. SO, Customer..." :showDateFilter="true" dateFromParam="date_from" dateToParam="date_to">
        <!-- Customer Dropdown -->
        <select name="customer_id" class="form-control" style="height: 38px; font-size: 13px; border-radius: 6px; padding: 0 10px; width: 170px; margin-bottom: 0;">
            <option value="">-- Semua Customer --</option>
            @foreach($customers as $c)
            <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>
                {{ $c->name }}
            </option>
            @endforeach
        </select>

        <!-- Status SO Dropdown -->
        <select name="status" class="form-control" style="height: 38px; font-size: 13px; border-radius: 6px; padding: 0 10px; width: 150px; margin-bottom: 0;">
            <option value="">-- Status SO --</option>
            <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
            <option value="partially_delivered" {{ request('status') == 'partially_delivered' ? 'selected' : '' }}>Sebagian Dikirim</option>
            <option value="done" {{ request('status') == 'done' ? 'selected' : '' }}>Done / Selesai</option>
            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
        </select>

        <!-- Status Bayar Dropdown -->
        <select name="payment_status" class="form-control" style="height: 38px; font-size: 13px; border-radius: 6px; padding: 0 10px; width: 160px; margin-bottom: 0;">
            <option value="">-- Status Bayar --</option>
            <option value="no_invoice" {{ request('payment_status') == 'no_invoice' ? 'selected' : '' }}>Belum Ada Invoice</option>
            <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Belum Dibayar</option>
            <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Sebagian</option>
            <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Lunas</option>
        </select>

        <!-- Status Penagihan Dropdown -->
        <select name="billing_status" class="form-control" style="height: 38px; font-size: 13px; border-radius: 6px; padding: 0 10px; width: 175px; margin-bottom: 0;">
            <option value="">-- Status Penagihan --</option>
            <option value="unbilled" {{ request('billing_status') == 'unbilled' ? 'selected' : '' }}>Ada SJ Belum Di-Invoice</option>
            <option value="partial_billed" {{ request('billing_status') == 'partial_billed' ? 'selected' : '' }}>Sebagian SJ Di-Invoice</option>
            <option value="fully_billed" {{ request('billing_status') == 'fully_billed' ? 'selected' : '' }}>Semua SJ Sudah Di-Invoice</option>
        </select>
    </x-list-filter-bar>

    {{-- Tabel Monitoring Fulfillment --}}
    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-responsive">
            <table class="erp-table" style="margin: 0; width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 32px; text-align: center;"></th>
                        <th>No. SO & Tanggal</th>
                        <th>Customer</th>
                        <th style="text-align: center;">Status SO</th>
                        <th style="text-align: right;">Nilai Pesanan</th>
                        <th style="text-align: center;">Pengiriman (SJ)</th>
                        <th style="text-align: center;">Penagihan (Invoice)</th>
                        <th style="text-align: right;">Total Tagihan</th>
                        <th style="text-align: right;">Sudah Dibayar</th>
                        <th style="text-align: right;">Sisa Piutang</th>
                        <th style="text-align: center;">Status Bayar</th>
                        <th style="text-align: center; width: 80px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    @php
                        $qtyOrdered = (int) $order->qty_ordered_sum;
                        $netDelivered = (int) $order->net_qty_delivered;
                        $netInvoiced = (int) $order->net_qty_invoiced;
                        $qtyReturned = (int) $order->qty_returned_sum;
                        $qtyReversed = (int) $order->qty_reversed_sum;
                        $totalInvGross = (float) $order->total_invoice_sum;
                        $totalInvEffective = (float) $order->effective_total_invoice;
                        $totalReversed = (float) $order->total_reversed_amount_sum;
                        $totalPaid = (float) $order->total_paid_sum;
                        $remaining = (float) $order->remaining_balance;
                        $hasUnbilledSJ = $netDelivered > $netInvoiced;
                    @endphp
                    <tr style="cursor: pointer; transition: background-color 0.15s;" 
                        :style="activeRow === {{ $order->id }} ? 'background-color: #f8fafc;' : ''"
                        @click="activeRow = activeRow === {{ $order->id }} ? null : {{ $order->id }}">
                        {{-- Chevron expand indicator --}}
                        <td style="text-align: center; color: var(--text-secondary); font-size: 11px;">
                            <i class="fa-solid fa-chevron-right transition-transform" 
                               :class="activeRow === {{ $order->id }} ? 'rotate-90 text-primary' : ''"></i>
                        </td>

                        {{-- No SO & Tanggal --}}
                        <td>
                            <div style="font-family: monospace; font-weight: 700; color: var(--primary);">
                                {{ $order->so_number }}
                            </div>
                            <div style="font-size: 11.5px; color: var(--text-secondary);">
                                {{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}
                            </div>
                        </td>

                        {{-- Customer --}}
                        <td>
                            <div style="font-weight: 600; color: var(--text-primary);">
                                {{ $order->customer->name ?? '-' }}
                            </div>
                            @if($order->customer?->isPkp())
                            <span style="display: inline-block; font-size: 10px; font-weight: 700; background: #e0f2fe; color: #0369a1; padding: 1px 6px; border-radius: 4px;">PKP</span>
                            @endif
                        </td>

                        {{-- Status SO --}}
                        <td style="text-align: center;">
                            <span class="badge badge-{{ $order->status }}">
                                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                            </span>
                        </td>

                        {{-- Nilai Pesanan --}}
                        <td style="text-align: right; font-weight: 600;">
                            Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                        </td>

                        {{-- Pengiriman (SJ) X/Y (Net setelah retur) --}}
                        <td style="text-align: center;">
                            <div style="display: inline-flex; align-items: center; gap: 6px;">
                                <span style="font-weight: 700; font-size: 13px; font-family: monospace;">
                                    {{ number_format($netDelivered) }} / {{ number_format($qtyOrdered) }}
                                </span>
                                @if($netDelivered >= $qtyOrdered && $qtyOrdered > 0)
                                    <i class="fa-solid fa-circle-check" style="color: #10b981; font-size: 13px;" title="Terkirim Penuh (100%)"></i>
                                @elseif($netDelivered > 0)
                                    <span style="font-size: 10px; font-weight: 700; background: #fef3c7; color: #b45309; padding: 2px 6px; border-radius: 9999px;" title="Terkirim Sebagian">
                                        {{ $order->delivery_progress_percent }}%
                                    </span>
                                @else
                                    <span style="font-size: 10px; font-weight: 600; background: #f1f5f9; color: #64748b; padding: 2px 6px; border-radius: 9999px;">
                                        Belum SJ
                                    </span>
                                @endif
                            </div>
                            @if($qtyReturned > 0)
                            <div style="font-size: 10px; font-weight: 600; color: #b91c1c; margin-top: 2px;">
                                <i class="fa-solid fa-rotate-left"></i> Retur: {{ number_format($qtyReturned) }} item
                            </div>
                            @endif
                        </td>

                        {{-- Penagihan (Invoice) X/Y (Net setelah reversed retur) --}}
                        <td style="text-align: center;">
                            @if($netDelivered > 0)
                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                    <span style="font-weight: 700; font-size: 13px; font-family: monospace;">
                                        {{ number_format($netInvoiced) }} / {{ number_format($netDelivered) }}
                                    </span>
                                    @if($netInvoiced >= $netDelivered)
                                        <i class="fa-solid fa-circle-check" style="color: #10b981; font-size: 13px;" title="Semua SJ Sudah Ditagih (100%)"></i>
                                    @else
                                        <span style="font-size: 10px; font-weight: 700; background: #fee2e2; color: #b91c1c; padding: 2px 6px; border-radius: 9999px;" title="Ada SJ Belum Dibuatkan Invoice">
                                            Kurang {{ $netDelivered - $netInvoiced }} unit
                                        </span>
                                    @endif
                                </div>
                                @if($qtyReversed > 0)
                                <div style="font-size: 10px; font-weight: 600; color: #b91c1c; margin-top: 2px;">
                                    <i class="fa-solid fa-rotate-left"></i> Batal Retur: {{ number_format($qtyReversed) }} item
                                </div>
                                @endif
                            @elseif($order->qty_delivered_sum > 0 && $netDelivered == 0)
                                <span style="font-size: 10px; font-weight: 600; background: #fee2e2; color: #b91c1c; padding: 2px 6px; border-radius: 9999px;">
                                    Diretur Penuh
                                </span>
                            @else
                                <span style="color: var(--text-secondary); font-size: 12px;">-</span>
                            @endif
                        </td>

                        {{-- Total Tagihan (Net setelah penyesuaian retur) --}}
                        <td style="text-align: right; font-weight: 600;">
                            @if($totalInvGross > 0)
                                Rp {{ number_format($totalInvEffective, 0, ',', '.') }}
                                @if($totalReversed > 0)
                                <div style="font-size: 10px; color: #b91c1c; font-weight: 500; margin-top: 2px;">
                                    (Retur: -Rp {{ number_format($totalReversed, 0, ',', '.') }})
                                </div>
                                @endif
                            @else
                                <span style="color: var(--text-secondary);">-</span>
                            @endif
                        </td>

                        {{-- Sudah Dibayar --}}
                        <td style="text-align: right; font-weight: 600; color: {{ $totalPaid > 0 ? '#059669' : 'var(--text-secondary)' }};">
                            @if($totalPaid > 0)
                                Rp {{ number_format($totalPaid, 0, ',', '.') }}
                            @else
                                <span style="color: var(--text-secondary);">-</span>
                            @endif
                        </td>

                        {{-- Sisa Piutang --}}
                        <td style="text-align: right; font-weight: 700; color: {{ $remaining > 0 ? '#dc2626' : 'var(--text-secondary)' }};">
                            @if($totalInvGross > 0)
                                Rp {{ number_format($remaining, 0, ',', '.') }}
                            @else
                                <span style="color: var(--text-secondary);">-</span>
                            @endif
                        </td>

                        {{-- Status Bayar --}}
                        <td style="text-align: center;">
                            <span class="badge badge-{{ $order->payment_status_badge }}">
                                {{ $order->payment_status_label }}
                            </span>
                        </td>

                        {{-- Aksi --}}
                        <td style="text-align: center;" @click.stop>
                            <a href="{{ route('sales.orders.show', $order) }}" class="btn btn-secondary btn-sm" title="Lihat Detail Sales Order">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </a>
                        </td>
                    </tr>

                    {{-- EXPANDED ACCORDION ROW: Breakdown Dokumen Surat Jalan, Retur, & Invoice --}}
                    <tr x-show="activeRow === {{ $order->id }}" x-cloak style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <td colspan="12" style="padding: 16px 24px;">
                            <div style="display: grid; grid-template-columns: 1.1fr 0.9fr 1.2fr; gap: 16px;">
                                {{-- Sub-tabel 1: Surat Jalan Terkait --}}
                                <div style="background: #ffffff; border: 1px solid var(--border); border-radius: 8px; padding: 14px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                        <div style="font-size: 12.5px; font-weight: 700; color: var(--text-primary);">
                                            <i class="fa-solid fa-truck" style="color: var(--primary); margin-right: 6px;"></i>
                                            Daftar Surat Jalan ({{ $order->deliveries->count() }})
                                        </div>
                                        @if(in_array($order->status, ['confirmed', 'partially_delivered']) && $netDelivered < $qtyOrdered)
                                        <a href="{{ route('sales.deliveries.create', ['so_id' => $order->id]) }}" class="btn btn-primary btn-sm" style="font-size: 11px; padding: 2px 8px;">
                                            + Buat SJ
                                        </a>
                                        @endif
                                    </div>
                                    @if($order->deliveries->isNotEmpty())
                                    <table class="erp-table" style="font-size: 12px; margin: 0;">
                                        <thead>
                                            <tr>
                                                <th>No. SJ</th>
                                                <th>Tanggal</th>
                                                <th style="text-align: center;">Item</th>
                                                <th style="text-align: center;">Status Invoice</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($order->deliveries as $del)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('sales.deliveries.show', $del) }}" style="font-family: monospace; font-weight: 600; color: var(--primary); text-decoration: none;">
                                                        {{ $del->delivery_number }}
                                                    </a>
                                                </td>
                                                <td>{{ $del->delivery_date->format('d/m/Y') }}</td>
                                                <td style="text-align: center;">{{ $del->items->count() }} item</td>
                                                <td style="text-align: center;">
                                                    @if($del->is_invoiced)
                                                        <span class="badge badge-done" style="font-size: 10px;">
                                                            <i class="fa-solid fa-check"></i> Sudah Di-Invoice
                                                        </span>
                                                    @else
                                                        <a href="{{ route('sales.invoices.create', ['so_id' => $order->id, 'delivery_id' => $del->id]) }}" 
                                                           class="badge badge-warning" style="font-size: 10px; text-decoration: none;" title="Klik untuk terbitkan invoice">
                                                            <i class="fa-solid fa-clock"></i> Belum Di-Invoice &rarr;
                                                        </a>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @else
                                    <div style="text-align: center; padding: 16px; color: var(--text-secondary); font-size: 12px;">
                                        Belum ada Surat Jalan.
                                    </div>
                                    @endif
                                </div>

                                {{-- Sub-tabel 2: Retur Penjualan Terkait --}}
                                <div style="background: #ffffff; border: 1px solid var(--border); border-radius: 8px; padding: 14px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                        <div style="font-size: 12.5px; font-weight: 700; color: var(--text-primary);">
                                            <i class="fa-solid fa-rotate-left" style="color: #dc2626; margin-right: 6px;"></i>
                                            Retur Penjualan ({{ $order->returns->count() }})
                                        </div>
                                    </div>
                                    @if($order->returns->isNotEmpty())
                                    <table class="erp-table" style="font-size: 12px; margin: 0;">
                                        <thead>
                                            <tr>
                                                <th>No. Retur</th>
                                                <th>Ref. SJ</th>
                                                <th style="text-align: center;">Qty</th>
                                                <th style="text-align: center;">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($order->returns as $ret)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('sales.returns.show', $ret) }}" style="font-family: monospace; font-weight: 600; color: #dc2626; text-decoration: none;">
                                                        {{ $ret->return_number }}
                                                    </a>
                                                </td>
                                                <td>
                                                    <span style="font-family: monospace; font-size: 11px; color: var(--text-secondary);">
                                                        {{ $ret->delivery->delivery_number ?? '-' }}
                                                    </span>
                                                </td>
                                                <td style="text-align: center; font-weight: 600;">
                                                    {{ $ret->items->sum('qty') }} item
                                                </td>
                                                <td style="text-align: center;">
                                                    <span class="badge badge-{{ $ret->status }}" style="font-size: 10px;">
                                                        {{ ucfirst($ret->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @else
                                    <div style="text-align: center; padding: 16px; color: var(--text-secondary); font-size: 12px;">
                                        Tidak ada retur untuk SO ini.
                                    </div>
                                    @endif
                                </div>

                                {{-- Sub-tabel 3: Invoice Penjualan Terkait --}}
                                <div style="background: #ffffff; border: 1px solid var(--border); border-radius: 8px; padding: 14px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                        <div style="font-size: 12.5px; font-weight: 700; color: var(--text-primary);">
                                            <i class="fa-solid fa-file-invoice-dollar" style="color: #0284c7; margin-right: 6px;"></i>
                                            Daftar Invoice ({{ $order->invoices->count() }})
                                        </div>
                                        @if($hasUnbilledSJ)
                                        <a href="{{ route('sales.invoices.create', ['so_id' => $order->id]) }}" class="btn btn-primary btn-sm" style="font-size: 11px; padding: 2px 8px;">
                                            + Terbitkan Invoice
                                        </a>
                                        @endif
                                    </div>
                                    @if($order->invoices->isNotEmpty())
                                    <table class="erp-table" style="font-size: 12px; margin: 0;">
                                        <thead>
                                            <tr>
                                                <th>No. Invoice</th>
                                                <th>Ref. SJ</th>
                                                <th style="text-align: right;">Tagihan Bersih</th>
                                                <th style="text-align: right;">Sisa</th>
                                                <th style="text-align: center;">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($order->invoices as $inv)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('sales.invoices.show', $inv) }}" style="font-family: monospace; font-weight: 600; color: var(--primary); text-decoration: none;">
                                                        {{ $inv->invoice_number }}
                                                    </a>
                                                </td>
                                                <td>
                                                    @if($inv->delivery)
                                                        <span style="font-family: monospace; font-size: 11px; color: var(--text-secondary);">
                                                            {{ $inv->delivery->delivery_number }}
                                                        </span>
                                                    @else
                                                        <span style="color: var(--text-secondary);">-</span>
                                                    @endif
                                                </td>
                                                <td style="text-align: right; font-weight: 600;">
                                                    Rp {{ number_format($inv->effective_total_amount, 0, ',', '.') }}
                                                    @if($inv->total_reversed_amount > 0)
                                                    <div style="font-size: 9.5px; color: #b91c1c; font-weight: normal;">
                                                        (Retur: -{{ number_format($inv->total_reversed_amount, 0, ',', '.') }})
                                                    </div>
                                                    @endif
                                                </td>
                                                <td style="text-align: right; font-weight: 600; color: {{ $inv->outstanding_amount > 0 ? '#dc2626' : 'var(--text-secondary)' }};">
                                                    Rp {{ number_format($inv->outstanding_amount, 0, ',', '.') }}
                                                </td>
                                                <td style="text-align: center;">
                                                    <span class="badge badge-{{ $inv->status }}" style="font-size: 10px;">
                                                        {{ ucfirst($inv->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @else
                                    <div style="text-align: center; padding: 16px; color: var(--text-secondary); font-size: 12px;">
                                        Belum ada Invoice Penjualan.
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" style="text-align: center; padding: 36px; color: var(--text-secondary);">
                            <i class="fa-solid fa-folder-open" style="font-size: 28px; margin-bottom: 8px; display: block; opacity: 0.4;"></i>
                            Tidak ada data Sales Order yang sesuai dengan filter.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($orders->hasPages())
        <div style="padding: 16px 20px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 13px; color: var(--text-secondary);">
                Menampilkan {{ $orders->firstItem() ?? 0 }} - {{ $orders->lastItem() ?? 0 }} dari {{ $orders->total() }} Sales Order
            </div>
            <div>
                {{ $orders->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
