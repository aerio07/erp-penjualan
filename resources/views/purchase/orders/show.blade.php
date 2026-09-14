@extends('layouts.app')
@section('title', 'Detail PO - ' . $order->po_number)
@section('page-title', 'Detail Purchase Order')

@section('content')
<div class="animate-in">

    {{-- Header --}}
    <x-detail-header 
        :title="$order->po_number" 
        :subtitle="$order->supplier->name . ' · ' . $order->order_date->format('d/m/Y')"
        :status="$order->status"
        :back-url="route('purchase.orders.index')"
    >
        <x-slot:actions>
            @if($order->status === 'draft')
            <a href="{{ route('purchase.orders.edit', $order) }}" class="btn btn-secondary"><i class="fa-solid fa-pen"></i> Ubah</a>
            @endif
            <a href="{{ route('pdf.purchase-order', $order) }}" class="btn btn-secondary" target="_blank"><i class="fa-solid fa-file-pdf"></i> PDF</a>
        </x-slot:actions>
    </x-detail-header>

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
                    @if($order->warehouse)
                    <div style="grid-column: span 2;">
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Gudang Tujuan Masuk Stok</div>
                        <div style="font-weight:600; color:var(--primary);"><i class="fa-solid fa-warehouse"></i> {{ $order->warehouse->name }} ({{ $order->warehouse->code }})</div>
                    </div>
                    @endif
                </div>
                @if($order->ship_to)
                <div style="margin-top:16px; padding:12px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; font-size:13px; color:#166534;">
                    <div style="font-weight:600; margin-bottom:3px; display:flex; align-items:center; gap:6px;">
                        <i class="fa-solid fa-truck-ramp-box"></i> Ship To (Tujuan Pengiriman):
                    </div>
                    <div>{{ $order->ship_to }}</div>
                </div>
                @endif

                @if($order->notes)
                <div style="margin-top:12px; padding:12px; background:#f8fafc; border-radius:10px; font-size:14px; color:var(--text-secondary);">
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
                    @php
                        $itemDiscountSum = $order->items->sum(function($item) {
                            return ($item->qty_ordered * $item->unit_price) * (($item->discount_percent ?? 0) / 100);
                        });
                        $grossSubtotal = $order->items->sum(function($item) {
                            return $item->qty_ordered * $item->unit_price;
                        });
                    @endphp
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:var(--text-secondary);">Subtotal Kotor</span>
                        <span>Rp {{ number_format($grossSubtotal, 0, ',', '.') }}</span>
                    </div>
                    @if($itemDiscountSum > 0)
                    <div style="display:flex; justify-content:space-between; color:var(--danger);">
                        <span>Diskon Item</span>
                        <span>- Rp {{ number_format($itemDiscountSum, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    @if($order->discount_amount > 0)
                    <div style="display:flex; justify-content:space-between; color:var(--danger);">
                        <span>Diskon Header</span>
                        <span>- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    @if($itemDiscountSum > 0 && $order->discount_amount > 0)
                    <div style="display:flex; justify-content:space-between; font-weight:600; color:var(--danger); font-size:13px; background:#fef2f2; padding:4px 8px; border-radius:6px;">
                        <span>Total Seluruh Diskon</span>
                        <span>- Rp {{ number_format($itemDiscountSum + $order->discount_amount, 0, ',', '.') }}</span>
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
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                        <a href="{{ route('purchase.goods-receipts.show', $grn) }}" style="font-size:13px; color:#4c1d95; text-decoration:none; font-weight:500;">
                            <i class="fa-solid fa-file-lines"></i> {{ $grn->receipt_number }} · {{ $grn->received_date->format('d/m/Y') }}
                        </a>
                        @if($grn->is_invoiced)
                            <span class="badge badge-done" style="font-size:10px; padding:2px 8px;">Sudah Diinvoice</span>
                        @else
                            <span class="badge badge-pending" style="font-size:10px; padding:2px 8px;">Belum Diinvoice</span>
                        @endif
                    </div>
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
                        <td style="text-align:center;">
                            @if($item->discount_percent > 0)
                                @php
                                    $itemDiscRp = ($item->qty_ordered * $item->unit_price) * ($item->discount_percent / 100);
                                @endphp
                                <span style="font-weight:600;">{{ $item->discount_percent }}%</span>
                                <div style="font-size:11px; color:var(--danger); margin-top:2px;">
                                    -Rp {{ number_format($itemDiscRp, 0, ',', '.') }}
                                </div>
                            @else
                                -
                            @endif
                        </td>
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

    {{-- Bottom Workflow Actions --}}
    @if($order->status === 'draft')
    <x-document-action-card>
        <form id="form-cancel-po" method="POST" action="{{ route('purchase.orders.cancel', $order) }}" style="display:none;">
            @csrf @method('PATCH')
        </form>
        <button type="button" class="btn btn-danger"
            data-confirm-swal="form-cancel-po"
            data-swal-title="Batalkan Purchase Order"
            data-swal-html="Yakin ingin membatalkan Purchase Order <strong>{{ $order->po_number }}</strong>?<br><span style='font-size:12.5px; color:#64748b;'>Status PO akan diubah menjadi Cancelled.</span>"
            data-swal-icon="warning"
            data-swal-btn-text="Ya, Batalkan PO"
            data-swal-btn-icon="fa-solid fa-ban"
            data-swal-btn-class="btn btn-danger"
        >
            <i class="fa-solid fa-ban"></i> Batalkan PO
        </button>

        <form id="form-confirm-po" method="POST" action="{{ route('purchase.orders.confirm', $order) }}" style="display:none;">
            @csrf @method('PATCH')
        </form>
        <button type="button" class="btn btn-primary"
            data-confirm-swal="form-confirm-po"
            data-swal-title="Konfirmasi Purchase Order"
            data-swal-html="Konfirmasi Purchase Order <strong>{{ $order->po_number }}</strong> sekarang?<br><span style='font-size:12.5px; color:#64748b;'>Status akan berubah menjadi Confirmed dan siap diteruskan ke supplier.</span>"
            data-swal-icon="question"
            data-swal-icon-color="#2563eb"
            data-swal-btn-text="Ya, Konfirmasi PO"
            data-swal-btn-icon="fa-solid fa-check"
            data-swal-btn-class="btn btn-primary"
        >
            <i class="fa-solid fa-check"></i> Konfirmasi PO Sekarang
        </button>
    </x-document-action-card>
    @elseif($order->status === 'waiting_approval')
    <x-document-action-card>
        <form id="form-cancel-po-approval" method="POST" action="{{ route('purchase.orders.cancel', $order) }}" style="display:none;">
            @csrf @method('PATCH')
        </form>
        <button type="button" class="btn btn-danger"
            data-confirm-swal="form-cancel-po-approval"
            data-swal-title="Batalkan Purchase Order"
            data-swal-html="Yakin ingin membatalkan Purchase Order <strong>{{ $order->po_number }}</strong>?<br><span style='font-size:12.5px; color:#64748b;'>Permintaan persetujuan yang sedang berjalan akan dibatalkan.</span>"
            data-swal-icon="warning"
            data-swal-btn-text="Ya, Batalkan PO"
            data-swal-btn-icon="fa-solid fa-ban"
            data-swal-btn-class="btn btn-danger"
        >
            <i class="fa-solid fa-ban"></i> Batalkan PO
        </button>
    </x-document-action-card>
    @endif

</div>
@endsection
