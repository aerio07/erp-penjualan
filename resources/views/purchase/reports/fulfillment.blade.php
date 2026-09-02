@extends('layouts.app')

@section('title', 'Laporan Monitoring Fulfillment Purchase Order')

@section('content')
<div class="animate-in" x-data="{ activeRow: null }">
    {{-- Header Halaman --}}
    <div class="page-header" style="margin-bottom: 20px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">
                <i class="fa-solid fa-chart-pie" style="color: var(--primary); margin-right: 8px;"></i>
                Laporan Monitoring Fulfillment Purchase Order
            </h1>
            <p style="color: var(--text-secondary); font-size: 13px; margin: 0;">
                Pantau progres siklus lengkap pengadaan: pesanan (PO) &rarr; penerimaan gudang (LPB) &rarr; retur pembelian &rarr; penagihan (Invoice) &rarr; pelunasan hutang.
            </p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('purchase.orders.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-cart-shopping"></i> Kelola Purchase Order
            </a>
            <a href="{{ route('purchase.returns.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-rotate-left"></i> Kelola Retur
            </a>
            <a href="{{ route('purchase.invoices.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-file-invoice-dollar"></i> Kelola Invoice
            </a>
        </div>
    </div>

    {{-- Executive Summary KPI Cards --}}
    <div class="grid grid-4" style="gap: 16px; margin-bottom: 20px;">
        <div class="card" style="border-left: 4px solid var(--primary); padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Total Purchase Order</div>
            <div style="font-size: 24px; font-weight: 700; color: var(--text-primary); margin-top: 4px;">
                {{ number_format($totalOrdersCount) }} <span style="font-size: 13px; font-weight: 400; color: var(--text-secondary);">PO</span>
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
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Hutang Terbayar</div>
            <div style="font-size: 24px; font-weight: 700; color: #059669; margin-top: 4px;">
                Rp {{ number_format($totalPaidAmount, 0, ',', '.') }}
            </div>
            <div style="font-size: 12px; color: #059669; margin-top: 4px; font-weight: 500;">
                <i class="fa-solid fa-circle-check"></i> Kas Keluar Dibayarkan
            </div>
        </div>

        <div class="card" style="border-left: 4px solid #ef4444; padding: 16px 20px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Sisa Hutang (Outstanding)</div>
            <div style="font-size: 24px; font-weight: 700; color: #dc2626; margin-top: 4px;">
                Rp {{ number_format($totalOutstandingAmount, 0, ',', '.') }}
            </div>
            <div style="font-size: 12px; color: #dc2626; margin-top: 4px; font-weight: 500;">
                <i class="fa-solid fa-clock"></i> Belum Dibayarkan ke Supplier
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <x-list-filter-bar :action="route('purchase.reports.fulfillment')" placeholder="Cari No. PO, Supplier..." :showDateFilter="true" dateFromParam="date_from" dateToParam="date_to">
        <!-- Supplier Dropdown -->
        <select name="supplier_id" class="form-control" style="height: 38px; font-size: 13px; border-radius: 6px; padding: 0 10px; width: 170px; margin-bottom: 0;">
            <option value="">-- Semua Supplier --</option>
            @foreach($suppliers as $s)
            <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>
                {{ $s->name }}
            </option>
            @endforeach
        </select>

        <!-- Status PO Dropdown -->
        <select name="status" class="form-control" style="height: 38px; font-size: 13px; border-radius: 6px; padding: 0 10px; width: 150px; margin-bottom: 0;">
            <option value="">-- Status PO --</option>
            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
            <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
            <option value="partially_received" {{ request('status') == 'partially_received' ? 'selected' : '' }}>Sebagian Diterima</option>
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
        <select name="billing_status" class="form-control" style="height: 38px; font-size: 13px; border-radius: 6px; padding: 0 10px; width: 180px; margin-bottom: 0;">
            <option value="">-- Status Penagihan --</option>
            <option value="unbilled" {{ request('billing_status') == 'unbilled' ? 'selected' : '' }}>Ada LPB Belum Di-Invoice</option>
            <option value="partial_billed" {{ request('billing_status') == 'partial_billed' ? 'selected' : '' }}>Sebagian LPB Di-Invoice</option>
            <option value="fully_billed" {{ request('billing_status') == 'fully_billed' ? 'selected' : '' }}>Semua LPB Sudah Di-Invoice</option>
        </select>
    </x-list-filter-bar>

    {{-- Tabel Monitoring Fulfillment --}}
    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-responsive">
            <table class="erp-table" style="margin: 0; width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 32px; text-align: center;"></th>
                        <th>No. PO & Tanggal</th>
                        <th>Supplier</th>
                        <th style="text-align: center;">Status PO</th>
                        <th style="text-align: right;">Nilai Pesanan</th>
                        <th style="text-align: center;">Penerimaan (LPB)</th>
                        <th style="text-align: center;">Penagihan (Invoice)</th>
                        <th style="text-align: right;">Total Tagihan</th>
                        <th style="text-align: right;">Sudah Dibayar</th>
                        <th style="text-align: right;">Sisa Hutang</th>
                        <th style="text-align: center;">Status Bayar</th>
                        <th style="text-align: center; width: 80px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    @php
                        $qtyOrdered = (int) $order->qty_ordered_sum;
                        $netReceived = (int) $order->net_qty_received;
                        $netInvoiced = (int) $order->net_qty_invoiced;
                        $qtyReturned = (int) $order->qty_returned_sum;
                        $qtyReversed = (int) $order->qty_reversed_sum;
                        $totalInvGross = (float) $order->total_invoice_sum;
                        $totalInvEffective = (float) $order->effective_total_invoice;
                        $totalReversed = (float) $order->total_reversed_amount_sum;
                        $totalPaid = (float) $order->total_paid_sum;
                        $remaining = (float) $order->remaining_balance;
                        $hasUnbilledLPB = $netReceived > $netInvoiced;
                    @endphp
                    <tr style="cursor: pointer; transition: background-color 0.15s;" 
                        :style="activeRow === {{ $order->id }} ? 'background-color: #f8fafc;' : ''"
                        @click="activeRow = activeRow === {{ $order->id }} ? null : {{ $order->id }}">
                        {{-- Chevron expand indicator --}}
                        <td style="text-align: center; color: var(--text-secondary); font-size: 11px;">
                            <i class="fa-solid fa-chevron-right transition-transform" 
                               :class="activeRow === {{ $order->id }} ? 'rotate-90 text-primary' : ''"></i>
                        </td>

                        {{-- No PO & Tanggal --}}
                        <td>
                            <div style="font-family: monospace; font-weight: 700; color: var(--primary);">
                                {{ $order->po_number }}
                            </div>
                            <div style="font-size: 11.5px; color: var(--text-secondary);">
                                {{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}
                            </div>
                        </td>

                        {{-- Supplier --}}
                        <td>
                            <div style="font-weight: 600; color: var(--text-primary);">
                                {{ $order->supplier->name ?? '-' }}
                            </div>
                        </td>

                        {{-- Status PO --}}
                        <td style="text-align: center;">
                            <span class="badge badge-{{ $order->status }}">
                                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                            </span>
                        </td>

                        {{-- Nilai Pesanan --}}
                        <td style="text-align: right; font-weight: 600;">
                            Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                        </td>

                        {{-- Penerimaan (LPB) X/Y (Net setelah retur) --}}
                        <td style="text-align: center;">
                            <div style="display: inline-flex; align-items: center; gap: 6px;">
                                <span style="font-weight: 700; font-size: 13px; font-family: monospace;">
                                    {{ number_format($netReceived) }} / {{ number_format($qtyOrdered) }}
                                </span>
                                @if($netReceived >= $qtyOrdered && $qtyOrdered > 0)
                                    <i class="fa-solid fa-circle-check" style="color: #10b981; font-size: 13px;" title="Diterima Penuh (100%)"></i>
                                @elseif($netReceived > 0)
                                    <span style="font-size: 10px; font-weight: 700; background: #fef3c7; color: #b45309; padding: 2px 6px; border-radius: 9999px;" title="Diterima Sebagian">
                                        {{ $order->receipt_progress_percent }}%
                                    </span>
                                @else
                                    <span style="font-size: 10px; font-weight: 600; background: #f1f5f9; color: #64748b; padding: 2px 6px; border-radius: 9999px;">
                                        Belum LPB
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
                            @if($netReceived > 0)
                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                    <span style="font-weight: 700; font-size: 13px; font-family: monospace;">
                                        {{ number_format($netInvoiced) }} / {{ number_format($netReceived) }}
                                    </span>
                                    @if($netInvoiced >= $netReceived)
                                        <i class="fa-solid fa-circle-check" style="color: #10b981; font-size: 13px;" title="Semua LPB Sudah Ditagih (100%)"></i>
                                    @else
                                        <span style="font-size: 10px; font-weight: 700; background: #fee2e2; color: #b91c1c; padding: 2px 6px; border-radius: 9999px;" title="Ada LPB Belum Dibuatkan Invoice">
                                            Kurang {{ $netReceived - $netInvoiced }} unit
                                        </span>
                                    @endif
                                </div>
                                @if($qtyReversed > 0)
                                <div style="font-size: 10px; font-weight: 600; color: #b91c1c; margin-top: 2px;">
                                    <i class="fa-solid fa-rotate-left"></i> Batal Retur: {{ number_format($qtyReversed) }} item
                                </div>
                                @endif
                            @elseif($order->qty_received_sum > 0 && $netReceived == 0)
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

                        {{-- Sisa Hutang --}}
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
                            <a href="{{ route('purchase.orders.show', $order) }}" class="btn btn-secondary btn-sm" title="Lihat Detail Purchase Order">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </a>
                        </td>
                    </tr>

                    {{-- EXPANDED ACCORDION ROW: Breakdown Dokumen LPB, Retur, & Invoice --}}
                    <tr x-show="activeRow === {{ $order->id }}" x-cloak style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <td colspan="12" style="padding: 16px 24px;">
                            <div style="display: grid; grid-template-columns: 1.1fr 0.9fr 1.2fr; gap: 16px;">
                                {{-- Sub-tabel 1: LPB Terkait --}}
                                <div style="background: #ffffff; border: 1px solid var(--border); border-radius: 8px; padding: 14px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                        <div style="font-size: 12.5px; font-weight: 700; color: var(--text-primary);">
                                            <i class="fa-solid fa-boxes-stacked" style="color: var(--primary); margin-right: 6px;"></i>
                                            Daftar LPB ({{ $order->goodsReceipts->count() }})
                                        </div>
                                        @if(in_array($order->status, ['confirmed', 'partially_received']) && $netReceived < $qtyOrdered)
                                        <a href="{{ route('purchase.goods-receipts.create', ['po_id' => $order->id]) }}" class="btn btn-primary btn-sm" style="font-size: 11px; padding: 2px 8px;">
                                            + Buat LPB
                                        </a>
                                        @endif
                                    </div>
                                    @if($order->goodsReceipts->isNotEmpty())
                                    <table class="erp-table" style="font-size: 12px; margin: 0;">
                                        <thead>
                                            <tr>
                                                <th>No. LPB</th>
                                                <th>Tanggal</th>
                                                <th style="text-align: center;">Item</th>
                                                <th style="text-align: center;">Status Invoice</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($order->goodsReceipts as $grn)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('purchase.goods-receipts.show', $grn) }}" style="font-family: monospace; font-weight: 600; color: var(--primary); text-decoration: none;">
                                                        {{ $grn->receipt_number }}
                                                    </a>
                                                </td>
                                                <td>{{ $grn->received_date ? $grn->received_date->format('d/m/Y') : '-' }}</td>
                                                <td style="text-align: center;">{{ $grn->items->count() }} item</td>
                                                <td style="text-align: center;">
                                                    @if($grn->is_invoiced)
                                                        <span class="badge badge-done" style="font-size: 10px;">
                                                            <i class="fa-solid fa-check"></i> Sudah Di-Invoice
                                                        </span>
                                                    @else
                                                        <a href="{{ route('purchase.invoices.create', ['po_id' => $order->id, 'goods_receipt_id' => $grn->id]) }}" 
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
                                        Belum ada LPB.
                                    </div>
                                    @endif
                                </div>

                                {{-- Sub-tabel 2: Retur Pembelian Terkait --}}
                                <div style="background: #ffffff; border: 1px solid var(--border); border-radius: 8px; padding: 14px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                        <div style="font-size: 12.5px; font-weight: 700; color: var(--text-primary);">
                                            <i class="fa-solid fa-rotate-left" style="color: #dc2626; margin-right: 6px;"></i>
                                            Retur Pembelian ({{ $order->returns->count() }})
                                        </div>
                                    </div>
                                    @if($order->returns->isNotEmpty())
                                    <table class="erp-table" style="font-size: 12px; margin: 0;">
                                        <thead>
                                            <tr>
                                                <th>No. Retur</th>
                                                <th>Ref. LPB</th>
                                                <th style="text-align: center;">Qty</th>
                                                <th style="text-align: center;">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($order->returns as $ret)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('purchase.returns.show', $ret) }}" style="font-family: monospace; font-weight: 600; color: #dc2626; text-decoration: none;">
                                                        {{ $ret->return_number }}
                                                    </a>
                                                </td>
                                                <td>
                                                    <span style="font-family: monospace; font-size: 11px; color: var(--text-secondary);">
                                                        {{ $ret->goodsReceipt->receipt_number ?? '-' }}
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
                                        Tidak ada retur untuk PO ini.
                                    </div>
                                    @endif
                                </div>

                                {{-- Sub-tabel 3: Invoice Pembelian Terkait --}}
                                <div style="background: #ffffff; border: 1px solid var(--border); border-radius: 8px; padding: 14px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                        <div style="font-size: 12.5px; font-weight: 700; color: var(--text-primary);">
                                            <i class="fa-solid fa-file-invoice-dollar" style="color: #0284c7; margin-right: 6px;"></i>
                                            Daftar Invoice ({{ $order->invoices->count() }})
                                        </div>
                                        @if($hasUnbilledLPB)
                                        <a href="{{ route('purchase.invoices.create', ['po_id' => $order->id]) }}" class="btn btn-primary btn-sm" style="font-size: 11px; padding: 2px 8px;">
                                            + Terbitkan Invoice
                                        </a>
                                        @endif
                                    </div>
                                    @if($order->invoices->isNotEmpty())
                                    <table class="erp-table" style="font-size: 12px; margin: 0;">
                                        <thead>
                                            <tr>
                                                <th>No. Invoice</th>
                                                <th>Ref. LPB</th>
                                                <th style="text-align: right;">Tagihan Bersih</th>
                                                <th style="text-align: right;">Sisa</th>
                                                <th style="text-align: center;">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($order->invoices as $inv)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('purchase.invoices.show', $inv) }}" style="font-family: monospace; font-weight: 600; color: var(--primary); text-decoration: none;">
                                                        {{ $inv->invoice_number }}
                                                    </a>
                                                </td>
                                                <td>
                                                    @if($inv->goodsReceipt)
                                                        <span style="font-family: monospace; font-size: 11px; color: var(--text-secondary);">
                                                            {{ $inv->goodsReceipt->receipt_number }}
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
                                        Belum ada Invoice Pembelian.
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
                            Tidak ada data Purchase Order yang sesuai dengan filter.
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
                Menampilkan {{ $orders->firstItem() ?? 0 }} - {{ $orders->lastItem() ?? 0 }} dari {{ $orders->total() }} Purchase Order
            </div>
            <div>
                {{ $orders->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
