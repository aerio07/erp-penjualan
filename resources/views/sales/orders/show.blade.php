@extends('layouts.app')
@section('title', 'Detail SO - ' . $order->so_number)
@section('page-title', 'Detail Sales Order')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>{{ $order->so_number }}</h1>
            <p>Customer: <strong>{{ $order->customer->name ?? '-' }}</strong> · Tgl Order: {{ $order->order_date->format('d/m/Y') }}</p>
        </div>
        <div style="display:flex; gap:8px;">
            @if($order->status === 'draft')
            <form method="POST" action="{{ route('sales.orders.confirm', $order) }}" style="display:inline;">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Konfirmasi SO</button>
            </form>
            @endif
            @if(in_array($order->status, ['draft','waiting_approval','confirmed']))
            <form method="POST" action="{{ route('sales.orders.cancel', $order) }}" style="display:inline;">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-danger" onclick="return confirm('Batalkan SO ini?')"><i class="fa-solid fa-ban"></i> Batalkan</button>
            </form>
            @endif
            <a href="{{ route('pdf.sales-order', $order) }}" class="btn btn-secondary" target="_blank">
                <i class="fa-solid fa-file-pdf"></i> Export PDF
            </a>
            <a href="{{ route('sales.orders.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        </div>
    </div>

    <div class="grid grid-3" style="margin-bottom:20px;">
        <div class="card" style="grid-column:span 2;">
            <div class="card-header">
                <h3>Informasi Sales Order</h3>
                <span class="badge badge-{{ $order->status }}">{{ ucfirst(str_replace('_',' ',$order->status)) }}</span>
            </div>
            <div class="card-body">
                <div class="form-row form-row-2">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Customer</div>
                        <div style="font-weight:600;">{{ $order->customer->name ?? '-' }}</div>
                        <div style="font-size:13px; color:var(--text-secondary);">{{ $order->customer->phone ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Sales Representative</div>
                        <div style="font-weight:600;">{{ $order->user->name ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Tanggal Order</div>
                        <div style="font-weight:600;">{{ $order->order_date->format('d F Y') }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Target Pengiriman</div>
                        <div style="font-weight:600;">{{ $order->expected_delivery_date ? $order->expected_delivery_date->format('d F Y') : '-' }}</div>
                    </div>
                </div>

                @if($order->notes)
                <div style="margin-top:16px; padding:12px; background:#f8fafc; border-radius:10px; font-size:13.5px; color:var(--text-secondary);">
                    <i class="fa-solid fa-note-sticky" style="margin-right:6px;"></i> {{ $order->notes }}
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Total Tagihan</h3></div>
            <div class="card-body">
                <div style="display:flex; flex-direction:column; gap:10px; font-size:14px;">
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:var(--text-secondary);">Subtotal</span>
                        <span>Rp {{ number_format($order->items->sum('subtotal'), 0, ',', '.') }}</span>
                    </div>
                    @if($order->discount_amount > 0)
                    <div style="display:flex; justify-content:space-between; color:var(--danger);">
                        <span>Diskon Header</span>
                        <span>- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:var(--text-secondary);">PPN ({{ $order->tax_rate }}%)</span>
                        <span>Rp {{ number_format($order->tax_amount, 0, ',', '.') }}</span>
                    </div>
                    <hr style="border:none; border-top:1px solid var(--border);">
                    <div style="display:flex; justify-content:space-between; font-size:17px; font-weight:700;">
                        <span>Total</span>
                        <span style="color:var(--primary);">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Item Table --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><h3>Item Pesanan</h3></div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th style="text-align:center;">Qty Pesan</th>
                        <th style="text-align:center;">Qty Terkirim</th>
                        <th style="text-align:center;">Sisa Kirim</th>
                        <th style="text-align:center;">Sudah Di-Invoice</th>
                        <th style="text-align:center; background:rgba(16, 185, 129, 0.08);">Sisa Unbilled</th>
                        <th style="text-align:right;">Harga Satuan</th>
                        <th style="text-align:center;">Diskon</th>
                        <th style="text-align:right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>
                            <div style="font-weight:600;">{{ $item->product->name }}</div>
                            <div style="font-size:12px; color:var(--text-secondary);">{{ $item->product->sku }}</div>
                        </td>
                        <td style="text-align:center; font-weight:600;">{{ $item->qty_ordered }} {{ $item->product->unit }}</td>
                        <td style="text-align:center; color:var(--success); font-weight:600;">{{ $item->qty_delivered }}</td>
                        <td style="text-align:center;">
                            <span style="{{ $item->qty_remaining > 0 ? 'color:var(--warning); font-weight:600;' : 'color:var(--success);' }}">
                                {{ $item->qty_remaining }}
                            </span>
                        </td>
                        <td style="text-align:center; color:var(--text-secondary);">{{ $item->qty_invoiced }}</td>
                        <td style="text-align:center; background:rgba(16, 185, 129, 0.05);">
                            <span class="badge badge-done">{{ $item->qty_unbilled }}</span>
                        </td>
                        <td style="text-align:right;">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                        <td style="text-align:center;">{{ $item->discount_percent > 0 ? $item->discount_percent.'%' : '-' }}</td>
                        <td style="text-align:right; font-weight:600;">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Deliveries & Invoices Lists --}}
    <div class="grid grid-2">
        {{-- Surat Jalan (Deliveries) --}}
        <div class="card">
            <div class="card-header">
                <h3>Surat Jalan Terkait</h3>
                @if(in_array($order->status, ['confirmed', 'partially_delivered']))
                <a href="{{ route('sales.deliveries.create', ['so_id' => $order->id]) }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-truck-fast"></i> Buat Surat Jalan
                </a>
                @endif
            </div>
            <div class="card-body" style="padding:0;">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>No. Surat Jalan</th>
                            <th>Tanggal</th>
                            <th>Gudang</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($order->deliveries as $del)
                        <tr>
                            <td>
                                <a href="{{ route('sales.deliveries.show', $del) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                                    {{ $del->delivery_number }}
                                </a>
                            </td>
                            <td>{{ $del->delivery_date->format('d/m/Y') }}</td>
                            <td>{{ $del->warehouse->name ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" style="text-align:center; padding:20px; color:var(--text-secondary);">
                                Belum ada Surat Jalan (pengiriman).
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Invoices Terkait --}}
        <div class="card">
            <div class="card-header">
                <h3>Invoice Penjualan Terkait</h3>
                @php
                    $hasUnbilled = $order->items->sum('qty_unbilled') > 0;
                @endphp
                @if(in_array($order->status, ['confirmed', 'partially_delivered', 'done']) && $hasUnbilled)
                <a href="{{ route('sales.invoices.create', ['so_id' => $order->id]) }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Terbitkan Invoice
                </a>
                @endif
            </div>
            <div class="card-body" style="padding:0;">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>No. Invoice</th>
                            <th>Total Tagihan</th>
                            <th style="text-align:center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($order->invoices as $inv)
                        <tr>
                            <td>
                                <a href="{{ route('sales.invoices.show', $inv) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                                    {{ $inv->invoice_number }}
                                </a>
                            </td>
                            <td style="font-weight:600;">Rp {{ number_format($inv->total_amount, 0, ',', '.') }}</td>
                            <td style="text-align:center;">
                                <span class="badge badge-{{ $inv->status }}">
                                    {{ ucfirst($inv->status) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" style="text-align:center; padding:20px; color:var(--text-secondary);">
                                Belum ada Invoice Penjualan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
