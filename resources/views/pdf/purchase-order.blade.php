@extends('layouts.pdf')

@section('title', 'Purchase Order - ' . $order->po_number)
@section('doc-title', 'PURCHASE ORDER')
@section('doc-subtitle', 'Purchase Order #: ' . $order->po_number)

@section('content')
    @php
        // Item discounts sum
        $pdfItemDiscount = $order->items->sum(function($item) {
            return (($item->qty_ordered ?? 0) * ($item->unit_price ?? 0)) * (($item->discount_percent ?? 0) / 100);
        });

        // Gross subtotal
        $grossSubtotal = $order->items->sum(function($item) {
            return ($item->qty_ordered ?? 0) * ($item->unit_price ?? 0);
        });
    @endphp

    {{-- VENDOR & ORDER DETAILS SECTION (Side by Side) --}}
    <table class="parties-table">
        <tr>
            <td style="width: 52%; vertical-align: top; padding-right: 15px;">
                <div class="section-heading">VENDOR / SUPPLIER</div>
                <div class="party-name">{{ $order->supplier->name ?? '-' }}</div>
                <div class="party-info">
                    @if(!empty($order->supplier->contact_person))
                        Attn: {{ $order->supplier->contact_person }}<br>
                    @endif
                    {{ $order->supplier->address ?: 'Alamat tidak tercatat' }}<br>
                    Phone: {{ $order->supplier->phone ?: '-' }}<br>
                    Email: {{ $order->supplier->email ?: '-' }}
                </div>
            </td>
            <td style="width: 48%; vertical-align: top;">
                <div class="section-heading">ORDER DETAILS</div>
                <table class="details-table">
                    <tr>
                        <td class="detail-label">Date</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value">{{ $order->order_date ? $order->order_date->format('d/m/Y') : date('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="detail-label">Expected Delivery</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value">{{ $order->expected_date ? $order->expected_date->format('d/m/Y') : '-' }}</td>
                    </tr>
                    @if($order->purchaseRequest)
                    <tr>
                        <td class="detail-label">Ref. PR</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value">{{ $order->purchaseRequest->pr_number }} (Divisi {{ $order->purchaseRequest->division ?? 'Umum' }})</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="detail-label">Purchasing Staff</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value">{{ $order->user->name ?? '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ITEMS SECTION --}}
    <div class="items-section">
        <div class="section-heading">ITEMS ORDERED</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-left" style="width: 44%;">Description</th>
                    <th class="text-center" style="width: 14%;">Quantity</th>
                    <th class="text-right" style="width: 21%;">Unit Price</th>
                    <th class="text-right" style="width: 21%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->items as $item)
                <tr>
                    <td class="text-left">
                        <span style="font-weight: 600; color: #111;">{{ $item->product->name ?? '-' }}</span>
                        @if(!empty($item->product->sku))
                            <span style="font-size: 7.5pt; color: #777;">({{ $item->product->sku }})</span>
                        @endif
                        @if(($item->discount_percent ?? 0) > 0)
                            <div style="font-size: 7pt; color: #dc2626;">Diskon Item: {{ $item->discount_percent }}%</div>
                        @endif
                    </td>
                    <td class="text-center">
                        {{ number_format($item->qty_ordered ?? 0) }} {{ $item->product->unit ?? 'Unit' }}
                    </td>
                    <td class="text-right">
                        Rp {{ number_format($item->unit_price ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="text-right" style="font-weight: 600;">
                        Rp {{ number_format($item->subtotal ?? 0, 0, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center" style="padding: 15px; color: #888;">Tidak ada item.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- TOTALS SECTION (Right Aligned) --}}
    <table class="totals-table">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                @if($order->notes)
                    <div class="section-heading">NOTES</div>
                    <div style="font-size: 8pt; color: #555; line-height: 1.4; padding-right: 20px;">
                        {{ $order->notes }}
                    </div>
                @endif
            </td>
            <td style="width: 45%; vertical-align: top;">
                <table style="width: 100%;">
                    <tr>
                        <td class="tot-label">Subtotal:</td>
                        <td class="tot-value">Rp {{ number_format($grossSubtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if($pdfItemDiscount > 0)
                    <tr>
                        <td class="tot-label tot-discount">Diskon Item:</td>
                        <td class="tot-value tot-discount">- Rp {{ number_format($pdfItemDiscount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if(($order->discount_amount ?? 0) > 0)
                    <tr>
                        <td class="tot-label tot-discount">Diskon Header:</td>
                        <td class="tot-value tot-discount">- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="tot-label">Tax ({{ $order->tax_rate ?? 0 }}%):</td>
                        <td class="tot-value">Rp {{ number_format($order->tax_amount ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="total-final">
                        <td class="tot-label" style="font-size: 10pt; font-weight: 800;">TOTAL:</td>
                        <td class="tot-value" style="font-size: 10pt; font-weight: 800;">Rp {{ number_format($order->total_amount ?? 0, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- TERMS & CONDITIONS --}}
    <div class="terms-section">
        <div class="section-heading">TERMS & CONDITIONS</div>
        <div class="terms-content">
            Faktur (Invoice) dan Surat Jalan wajib mencantumkan nomor Purchase Order ini. Barang yang dikirim harus sesuai spesifikasi yang tertera. Terima kasih atas kerja sama Anda.
        </div>
    </div>
@endsection

@section('signatures')
    @php
        $approver = $order->approvalRequests->where('status', 'approved')->first()?->approver?->name;
    @endphp
    @include('pdf.partials.signatures', [
        'cols' => [
            ['label' => 'Dibuat Oleh', 'name' => $order->user->name ?? 'Purchasing Staff', 'role' => 'Purchasing Staff'],
            ['label' => 'Disetujui Oleh', 'name' => $approver ?: 'Manajemen / Direktur', 'role' => 'Approval'],
            ['label' => 'Diterima & Dikonfirmasi', 'name' => $order->supplier->contact_person ?: ($order->supplier->name ?? 'Supplier'), 'role' => 'Vendor / Supplier'],
        ]
    ])
@endsection
