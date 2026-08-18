@extends('layouts.app')
@section('title', 'Detail PO - ' . $order->po_number)
@section('page-title', 'Detail Purchase Order')

@section('content')
<div class="animate-in">

    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1>{{ $order->po_number }}</h1>
            <p>{{ $order->supplier->name }} · {{ $order->order_date->format('d/m/Y') }}</p>
        </div>
        <div style="display:flex; gap:8px;">
            @if($order->status === 'draft')
            <a href="{{ route('purchase.orders.edit', $order) }}" class="btn btn-secondary"><i class="fa-solid fa-pen"></i> Edit</a>
            <form method="POST" action="{{ route('purchase.orders.confirm', $order) }}" style="display:inline;">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Konfirmasi PO</button>
            </form>
            @endif
            @if(in_array($order->status, ['draft','waiting_approval','confirmed']))
            <form method="POST" action="{{ route('purchase.orders.cancel', $order) }}" style="display:inline;">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-danger" onclick="return confirm('Batalkan PO ini?')"><i class="fa-solid fa-ban"></i> Batalkan</button>
            </form>
            @endif
            <a href="{{ route('pdf.purchase-order', $order) }}" class="btn btn-secondary" target="_blank"><i class="fa-solid fa-file-pdf"></i> PDF</a>
        </div>
    </div>

    <div class="grid grid-3" style="margin-bottom:20px;">
        {{-- Info PO --}}
        <div class="card" style="grid-column:span 2;">
            <div class="card-header">
                <h3>Informasi Purchase Order</h3>
                <span class="badge badge-{{ $order->status }}">{{ ucfirst(str_replace('_',' ',$order->status)) }}</span>
            </div>
            <div class="card-body">
                <div class="form-row form-row-2">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Supplier</div>
                        <div style="font-weight:600;">{{ $order->supplier->name }}</div>
                        <div style="font-size:13px; color:var(--text-secondary);">{{ $order->supplier->contact_person }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Dibuat Oleh</div>
                        <div style="font-weight:600;">{{ $order->user->name }}</div>
                        <div style="font-size:13px; color:var(--text-secondary);">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Tanggal PO</div>
                        <div style="font-weight:600;">{{ $order->order_date->format('d F Y') }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Expected Delivery</div>
                        <div style="font-weight:600;">{{ $order->expected_date ? $order->expected_date->format('d F Y') : '-' }}</div>
                    </div>
                </div>
                @if($order->notes)
                <div style="margin-top:16px; padding:12px; background:#f8fafc; border-radius:10px; font-size:14px; color:var(--text-secondary);">
                    <i class="fa-solid fa-note-sticky" style="margin-right:6px;"></i>{{ $order->notes }}
                </div>
                @endif
            </div>
        </div>

        {{-- Total Summary --}}
        <div class="card">
            <div class="card-header"><h3>Total</h3></div>
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

                {{-- GRN Status --}}
                @if($order->goodsReceipts->count() > 0)
                <div style="margin-top:16px; padding:12px; background:#ede9fe; border-radius:10px;">
                    <div style="font-size:12px; font-weight:600; color:#7c3aed; margin-bottom:4px;">GRN Terkait</div>
                    @foreach($order->goodsReceipts as $grn)
                    <a href="{{ route('purchase.goods-receipts.show', $grn) }}" style="font-size:13px; color:#4c1d95; text-decoration:none; display:block; margin-bottom:4px; font-weight:500;">
                        <i class="fa-solid fa-file-lines"></i> {{ $grn->receipt_number }} · {{ $grn->received_date->format('d/m/Y') }}
                    </a>
                    @endforeach
                </div>
                @endif
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
                        <th style="text-align:center;">Tiba Fisik (Datang)</th>
                        <th style="text-align:center;">Sisa Belum Dikirim</th>
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
                        <td style="text-align:center;">
                            <span style="font-weight:600;">{{ $item->qty_arrived }}</span>
                            <div style="font-size:11px; color:var(--text-secondary);">
                                (Baik: <span style="color:var(--success); font-weight:600;">{{ $item->qty_received }}</span>
                                @if($item->qty_rejected > 0)
                                , Rusak: <span style="color:var(--danger); font-weight:600;">{{ $item->qty_rejected }}</span>
                                @endif)
                            </div>
                        </td>
                        <td style="text-align:center;">
                            <span style="{{ $item->qty_remaining > 0 ? 'color:var(--warning); font-weight:600;' : 'color:var(--success); font-weight:600;' }}">
                                {{ $item->qty_remaining }}
                            </span>
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

    {{-- Invoice terkait --}}
    @if($order->invoices->count() > 0)
    <div class="card">
        <div class="card-header"><h3>Invoice Pembelian Terkait</h3></div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>No. Invoice</th>
                        <th>Tgl Invoice</th>
                        <th>Jatuh Tempo</th>
                        <th style="text-align:right;">Total</th>
                        <th style="text-align:center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->invoices as $inv)
                    <tr>
                        <td>
                            <a href="{{ route('purchase.invoices.show', $inv) }}" style="font-weight:600; color:var(--primary); text-decoration:none;">
                                {{ $inv->invoice_number }}
                            </a>
                            @if($inv->supplier_invoice_number)
                                <div style="font-size:11px; color:var(--text-secondary);">Inv Sup: {{ $inv->supplier_invoice_number }}</div>
                            @endif
                        </td>
                        <td>{{ $inv->invoice_date->format('d/m/Y') }}</td>
                        <td>{{ $inv->due_date->format('d/m/Y') }}</td>
                        <td style="text-align:right; font-weight:600;">Rp {{ number_format($inv->total_amount, 0, ',', '.') }}</td>
                        <td style="text-align:center;"><span class="badge badge-{{ $inv->status }}">{{ ucfirst($inv->status) }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
