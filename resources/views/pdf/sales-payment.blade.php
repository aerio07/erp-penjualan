@extends('layouts.pdf')

@section('title', $payment->voucher_title . ' - ' . $payment->voucher_number)
@section('doc-title', $payment->voucher_title)
@section('doc-subtitle', 'Voucher #: ' . $payment->voucher_number)

@section('content')
    {{-- CUSTOMER & PAYMENT DETAILS SECTION (Side by Side) --}}
    <table class="parties-table">
        <tr>
            <td style="width: 52%; vertical-align: top; padding-right: 15px;">
                <div class="section-heading">DITERIMA DARI (CUSTOMER):</div>
                <div class="party-name">{{ $payment->salesInvoice->salesOrder->customer->name ?? '-' }}</div>
                <div class="party-info">
                    @if($payment->salesInvoice->salesOrder->customer?->address)
                        {{ $payment->salesInvoice->salesOrder->customer->address }}<br>
                    @endif
                    @if($payment->salesInvoice->salesOrder->customer?->phone)
                        Telp: {{ $payment->salesInvoice->salesOrder->customer->phone }}<br>
                    @endif
                    @if($payment->salesInvoice->salesOrder->customer?->email)
                        Email: {{ $payment->salesInvoice->salesOrder->customer->email }}<br>
                    @endif
                </div>
            </td>
            <td style="width: 48%; vertical-align: top;">
                <div class="section-heading">RINCIAN PENERIMAAN</div>
                <table class="details-table">
                    <tr>
                        <td class="detail-label">{{ $payment->voucher_label }}</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value" style="font-weight: bold; color: #111111;">{{ $payment->voucher_number }}</td>
                    </tr>
                    <tr>
                        <td class="detail-label">Tanggal Penerimaan</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value">{{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="detail-label">Metode Pembayaran</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value" style="font-weight: bold; text-transform: uppercase;">{{ $payment->method }}</td>
                    </tr>
                    @if($payment->reference_number)
                    <tr>
                        <td class="detail-label">No. Ref / Transfer</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value">{{ $payment->reference_number }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="detail-label">Ref. Invoice Penjualan</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value">{{ $payment->salesInvoice->invoice_number ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="detail-label">Ref. Sales Order</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value">{{ $payment->salesInvoice->salesOrder->so_number ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="detail-label">Status Transaksi</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value" style="font-weight: 700; color: #111111;">BERHASIL / LUNAS</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- PAYMENT ALLOCATION TABLE --}}
    <div class="section-heading" style="margin-top: 15px; margin-bottom: 6px;">ALOKASI PENERIMAAN PIUTANG:</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">No.</th>
                <th style="width: 35%;">No. Invoice Penjualan</th>
                <th style="width: 20%; text-align: center;">Tanggal Invoice</th>
                <th style="width: 20%; text-align: right;">Total Nilai Tagihan</th>
                <th style="width: 20%; text-align: right;">Jumlah Diterima</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center;">1</td>
                <td>
                    <span class="item-name">{{ $payment->salesInvoice->invoice_number ?? '-' }}</span>
                    @if($payment->salesInvoice->salesOrder?->so_number)
                        <br><span style="font-size: 8pt; color: #555555;">(Ref. Pesanan SO: {{ $payment->salesInvoice->salesOrder->so_number }})</span>
                    @endif
                </td>
                <td style="text-align: center;">{{ $payment->salesInvoice->invoice_date ? $payment->salesInvoice->invoice_date->format('d/m/Y') : '-' }}</td>
                <td style="text-align: right;">Rp {{ number_format($payment->salesInvoice->effective_total_amount ?? $payment->salesInvoice->total_amount, 0, ',', '.') }}</td>
                <td style="text-align: right; font-weight: bold; color: #111111;">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="grand-total-row">
                <td colspan="4" style="text-align: right; font-weight: bold; padding: 6px 8px; text-transform: uppercase;">TOTAL {{ $payment->method === 'cash' ? 'KAS' : 'BANK' }} DITERIMA:</td>
                <td style="text-align: right; font-weight: bold; padding: 6px 8px; font-size: 10pt; color: #111111;">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- NOTES & SIGNATURES --}}
    <table style="width: 100%; margin-top: 20px; border-collapse: collapse;">
        <tr>
            <td style="width: 55%; vertical-align: top; padding-right: 20px;">
                @if($payment->notes)
                    <div style="font-size: 8pt; font-weight: bold; color: #555555; text-transform: uppercase; margin-bottom: 3px;">CATATAN PENERIMAAN:</div>
                    <div style="font-size: 8.5pt; color: #333333; background: #f9f9f9; border: 1px solid #e5e5e5; border-radius: 4px; padding: 8px;">
                        {{ $payment->notes }}
                    </div>
                @else
                    <div style="font-size: 8pt; color: #888888; font-style: italic;">
                        Bukti penerimaan pembayaran sah ini diterbitkan secara elektronik oleh sistem ERP FlashSuite Pro.
                    </div>
                @endif
            </td>
            <td style="width: 45%; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse; text-align: center; font-size: 8.5pt;">
                    <tr>
                        <td style="width: 50%; padding-bottom: 50px;">
                            Diterima / Dicatat Oleh,<br><br>
                        </td>
                        <td style="width: 50%; padding-bottom: 50px;">
                            Disetujui / Otorisasi,<br><br>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-bottom: 1px solid #333333; font-weight: bold;">
                            {{ $payment->user->name ?? 'Staff Finance' }}
                        </td>
                        <td style="border-bottom: 1px solid #333333; font-weight: bold;">
                            Finance Manager
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: 7.5pt; color: #666666; padding-top: 2px;">Finance & Kasir</td>
                        <td style="font-size: 7.5pt; color: #666666; padding-top: 2px;">Otorisasi Kas/Bank</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@endsection
