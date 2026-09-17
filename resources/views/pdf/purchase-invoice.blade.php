@extends('layouts.pdf')

@section('title', 'Invoice Pembelian - ' . $invoice->invoice_number)
@section('doc-title', 'INVOICE PEMBELIAN')
@section('doc-subtitle', 'Invoice #: ' . $invoice->invoice_number)

@section('content')
    {{-- SUPPLIER & INVOICE DETAILS SECTION (Side by Side) --}}
    <table class="parties-table">
        <tr>
            <td style="width: 52%; vertical-align: top; padding-right: 15px;">
                <div class="section-heading">TAGIHAN DARI (SUPPLIER):</div>
                <div class="party-name">{{ $invoice->purchaseOrder->supplier->name ?? '-' }}</div>
                <div class="party-info">
                    @if($invoice->purchaseOrder->supplier?->address)
                        {{ $invoice->purchaseOrder->supplier->address }}<br>
                    @endif
                    @if($invoice->purchaseOrder->supplier?->phone)
                        Telp: {{ $invoice->purchaseOrder->supplier->phone }}<br>
                    @endif
                    @if($invoice->purchaseOrder->supplier?->email)
                        Email: {{ $invoice->purchaseOrder->supplier->email }}<br>
                    @endif
                </div>
            </td>
            <td style="width: 48%; vertical-align: top;">
                <div class="section-heading">RINCIAN INVOICE</div>
                <table class="details-table">
                    <tr>
                        <td class="detail-label">Tanggal Invoice</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value">{{ $invoice->invoice_date ? $invoice->invoice_date->format('d/m/Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="detail-label">Jatuh Tempo</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value">{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="detail-label">Ref. Purchase Order</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value">{{ $invoice->purchaseOrder->po_number ?? '-' }}</td>
                    </tr>
                    @if($invoice->goodsReceipt)
                    <tr>
                        <td class="detail-label">Ref. Penerimaan (LPB)</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value">{{ $invoice->goodsReceipt->receipt_number }}</td>
                    </tr>
                    @endif
                    @if($invoice->supplier_invoice_number)
                    <tr>
                        <td class="detail-label">No. Inv. Supplier</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value" style="font-weight: 700; color: #111111;">{{ $invoice->supplier_invoice_number }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="detail-label">Status Pembayaran</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value" style="font-weight: 700; color: #111111;">{{ strtoupper(str_replace('_', ' ', $invoice->status)) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ITEMS TABLE --}}
    <div class="items-section">
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 5%;">#</th>
                    <th class="text-left" style="width: 45%;">PRODUK</th>
                    <th class="text-right" style="width: 12%;">QTY</th>
                    <th class="text-right" style="width: 14%;">HARGA SATUAN</th>
                    <th class="text-right" style="width: 10%;">DISKON</th>
                    <th class="text-right" style="width: 14%;">SUBTOTAL</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $invoiceItems = $invoice->items->isNotEmpty() ? $invoice->items : ($invoice->purchaseOrder->items ?? collect());
                @endphp
                @forelse($invoiceItems as $i => $item)
                    @php
                        $qty = $item->qty_invoiced ?? $item->qty_received ?? 0;
                        $price = $item->unit_price;
                        $discPercent = $item->discount_percent ?? 0;
                        $subtotal = $item->subtotal ?? (($qty * $price) - (($qty * $price) * ($discPercent / 100)));
                    @endphp
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td class="text-left">
                            <strong>{{ $item->product->name ?? '-' }}</strong>
                            @if(!empty($item->product->sku))
                                <div style="font-size: 7.5pt; color: #666666;">SKU: {{ $item->product->sku }}</div>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($qty) }} {{ $item->product->unit ?? 'pcs' }}</td>
                        <td class="text-right">Rp {{ number_format($price, 0, ',', '.') }}</td>
                        <td class="text-right">{{ $discPercent > 0 ? $discPercent . '%' : '-' }}</td>
                        <td class="text-right">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center" style="padding: 15px; color: #888888;">Tidak ada item rincian.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- TOTALS SECTION (Right Aligned) --}}
    <div class="totals-table">
        <table class="totals-summary">
            <tr>
                <td class="tot-label">Subtotal (DPP):</td>
                <td class="tot-value">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
            </tr>
            @if($invoice->tax_amount > 0 || $invoice->tax_rate > 0)
            <tr>
                <td class="tot-label">PPN ({{ $invoice->tax_rate }}%):</td>
                <td class="tot-value">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr class="total-final">
                <td class="tot-label">TOTAL TAGIHAN:</td>
                <td class="tot-value">Rp {{ number_format($invoice->effective_total_amount, 0, ',', '.') }}</td>
            </tr>
            @if($invoice->total_reversed_amount > 0)
            <tr>
                <td class="tot-label tot-discount">Pengurang Retur:</td>
                <td class="tot-value tot-discount">- Rp {{ number_format($invoice->total_reversed_amount, 0, ',', '.') }}</td>
            </tr>
            @endif
            @if($invoice->total_paid > 0)
            <tr>
                <td class="tot-label" style="color: #059669;">Sudah Dibayar:</td>
                <td class="tot-value" style="color: #059669;">Rp {{ number_format($invoice->total_paid, 0, ',', '.') }}</td>
            </tr>
            @endif
            @if($invoice->outstanding_amount != $invoice->effective_total_amount)
            <tr style="font-weight: 700;">
                <td class="tot-label" style="color: #dc2626;">Sisa Hutang:</td>
                <td class="tot-value" style="color: #dc2626;">Rp {{ number_format($invoice->outstanding_amount, 0, ',', '.') }}</td>
            </tr>
            @endif
        </table>
        <div style="clear: both;"></div>
    </div>

    {{-- TERMS & CONDITIONS --}}
    <div class="terms-section">
        <div class="section-heading">CATATAN & KETENTUAN:</div>
        <div class="terms-content">
            @if($invoice->notes)
                <p style="margin-bottom: 3px;"><strong>Catatan:</strong> {{ $invoice->notes }}</p>
            @endif
            <p>1. Dokumen invoice ini merupakan bukti kewajiban tagihan resmi atas barang/jasa yang telah diterima sesuai PO & LPB terkait.</p>
            <p>2. Jadwal pembayaran akan diproses oleh tim Finance sesuai tanggal jatuh tempo yang tertera.</p>
        </div>
    </div>
@endsection

@section('signatures')
    @include('pdf.partials.signatures', [
        'cols' => [
            ['label' => 'Dibuat Oleh:', 'name' => auth()->user()?->name ?? 'Bagian Pembelian', 'role' => 'Purchasing Staff'],
            ['label' => 'Menyetujui:', 'name' => 'Manager Keuangan', 'role' => 'Finance & Accounting'],
            ['label' => 'Pihak Supplier:', 'name' => $invoice->purchaseOrder->supplier->name ?? 'Supplier', 'role' => 'Supplier / Penagih'],
        ]
    ])
@endsection
