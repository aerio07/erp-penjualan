@extends('layouts.pdf')

@section('title', 'Surat Jalan - ' . $delivery->delivery_number)
@section('doc-title', 'SURAT JALAN')
@section('doc-subtitle', 'No. Pengiriman #: ' . $delivery->delivery_number)

@section('content')
    {{-- RECIPIENT / CUSTOMER SECTION --}}
    <div class="party-box">
        <div class="section-heading">PENERIMA / TUJUAN PENGIRIMAN:</div>
        <div class="party-name">{{ $delivery->recipient_name ?? $delivery->salesOrder->customer->name ?? '-' }}</div>
        <div class="party-info">
            @php
                $shipAddress = $delivery->shipping_address ?? $delivery->salesOrder->customer?->address;
            @endphp
            @if($shipAddress)
                {{ $shipAddress }}<br>
            @endif
            @if($delivery->salesOrder->customer?->phone)
                Telp: {{ $delivery->salesOrder->customer->phone }}<br>
            @endif
        </div>
    </div>

    {{-- DETAILS SECTION (Key - Value Pairs) --}}
    <div class="details-box">
        <table class="details-table">
            <tr>
                <td class="detail-label">Tanggal Pengiriman</td>
                <td class="detail-colon">:</td>
                <td class="detail-value">{{ $delivery->delivery_date ? $delivery->delivery_date->format('d F Y') : '-' }}</td>
            </tr>
            <tr>
                <td class="detail-label">Ref. Sales Order</td>
                <td class="detail-colon">:</td>
                <td class="detail-value">{{ $delivery->salesOrder->so_number ?? '-' }}</td>
            </tr>
            <tr>
                <td class="detail-label">Gudang Asal</td>
                <td class="detail-colon">:</td>
                <td class="detail-value">{{ $delivery->warehouse->name ?? '-' }}</td>
            </tr>
            @if($delivery->tracking_number)
            <tr>
                <td class="detail-label">No. Resi / Tracking</td>
                <td class="detail-colon">:</td>
                <td class="detail-value" style="font-weight: 700; color: #111111;">{{ $delivery->tracking_number }}</td>
            </tr>
            @endif
            <tr>
                <td class="detail-label">Status Pengiriman</td>
                <td class="detail-colon">:</td>
                <td class="detail-value" style="font-weight: 700; color: #111111;">{{ strtoupper(str_replace('_', ' ', $delivery->status)) }}</td>
            </tr>
        </table>
    </div>

    {{-- ITEMS TABLE --}}
    <div class="items-section">
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 6%;">#</th>
                    <th class="text-left" style="width: 20%;">KODE SKU</th>
                    <th class="text-left" style="width: 44%;">NAMA PRODUK</th>
                    <th class="text-center" style="width: 15%;">QTY DIKIRIM</th>
                    <th class="text-left" style="width: 15%;">KONDISI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($delivery->items as $i => $item)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td class="text-left" style="font-family: monospace; font-size: 8pt;">
                            {{ $item->salesOrderItem->product->sku ?? '-' }}
                        </td>
                        <td class="text-left">
                            <strong>{{ $item->salesOrderItem->product->name ?? '-' }}</strong>
                        </td>
                        <td class="text-center" style="font-weight: 700;">
                            {{ number_format($item->qty_delivered) }} {{ $item->salesOrderItem->product->unit ?? 'pcs' }}
                        </td>
                        <td class="text-left">
                            {{ $item->condition ?? 'Baik / Normal' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center" style="padding: 15px; color: #888888;">Tidak ada item pengiriman.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- TERMS & CONDITIONS / NOTES --}}
    <div class="terms-section" style="margin-top: 15px;">
        <div class="section-heading">CATATAN PENGIRIMAN & KETENTUAN:</div>
        <div class="terms-content">
            @if($delivery->notes)
                <p style="margin-bottom: 4px;"><strong>Catatan Khusus:</strong> {{ $delivery->notes }}</p>
            @endif
            <p>1. Harap periksa kesesuaian kuantitas dan kondisi fisik barang pada saat serah terima.</p>
            <p>2. Komplain atau klaim kerusakan barang wajib dilaporkan maksimal 1x24 jam setelah barang diterima.</p>
            <p>3. Surat Jalan ini merupakan bukti serah terima barang yang sah antara pihak pengirim dan penerima.</p>
        </div>
    </div>
@endsection

@section('signatures')
    @include('pdf.partials.signatures', [
        'cols' => [
            ['label' => 'Diterima Oleh:', 'name' => $delivery->recipient_name ?? $delivery->salesOrder->customer->name ?? 'Customer', 'role' => 'Penerima / Customer'],
            ['label' => 'Pengemudi / Kurir:', 'name' => '( ................................ )', 'role' => 'Ekspedisi / Pengantar'],
            ['label' => 'Petugas Gudang:', 'name' => $delivery->user->name ?? 'Kepala Gudang', 'role' => 'Warehouse Staff'],
        ]
    ])
@endsection
