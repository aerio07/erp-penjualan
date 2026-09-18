@extends('layouts.pdf')

@section('title', 'Bukti Pengeluaran Kas - BKK-' . str_pad($payment->id, 5, '0', STR_PAD_LEFT))
@section('doc-title', 'BUKTI PENGELUARAN KAS / BANK')
@section('doc-subtitle', 'Payment Voucher #: BKK-' . str_pad($payment->id, 5, '0', STR_PAD_LEFT))

@section('content')
    {{-- SUPPLIER & PAYMENT DETAILS SECTION (Side by Side) --}}
    <table class="parties-table">
        <tr>
            <td style="width: 52%; vertical-align: top; padding-right: 15px;">
                <div class="section-heading">DIBAYARKAN KEPADA (SUPPLIER):</div>
                <div class="party-name">{{ $payment->purchaseInvoice->purchaseOrder->supplier->name ?? '-' }}</div>
                <div class="party-info">
                    @if($payment->purchaseInvoice->purchaseOrder->supplier?->address)
                        {{ $payment->purchaseInvoice->purchaseOrder->supplier->address }}<br>
                    @endif
                    @if($payment->purchaseInvoice->purchaseOrder->supplier?->phone)
                        Telp: {{ $payment->purchaseInvoice->purchaseOrder->supplier->phone }}<br>
                    @endif
                    @if($payment->purchaseInvoice->purchaseOrder->supplier?->email)
                        Email: {{ $payment->purchaseInvoice->purchaseOrder->supplier->email }}<br>
                    @endif
                    @if($payment->purchaseInvoice->purchaseOrder->supplier?->bank_name || $payment->purchaseInvoice->purchaseOrder->supplier?->bank_account_number)
                        <div style="margin-top: 5px; padding-top: 5px; border-top: 1px dashed #cccccc;">
                            <strong>Rekening Supplier:</strong><br>
                            {{ $payment->purchaseInvoice->purchaseOrder->supplier->bank_name ?? 'Bank' }} - {{ $payment->purchaseInvoice->purchaseOrder->supplier->bank_account_number }}
                            @if($payment->purchaseInvoice->purchaseOrder->supplier->bank_account_holder)
                                (a.n {{ $payment->purchaseInvoice->purchaseOrder->supplier->bank_account_holder }})
                            @endif
                        </div>
                    @endif
                </div>
            </td>
            <td style="width: 48%; vertical-align: top;">
                <div class="section-heading">RINCIAN PEMBAYARAN</div>
                <table class="details-table">
                    <tr>
                        <td class="detail-label">No. Bukti Kas Keluar</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value" style="font-weight: bold; color: #111111;">BKK-{{ str_pad($payment->id, 5, '0', STR_PAD_LEFT) }}</td>
                    </tr>
                    <tr>
                        <td class="detail-label">Tanggal Pembayaran</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value">{{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="detail-label">Metode Pembayaran</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value" style="font-weight: bold; text-transform: uppercase;">{{ $payment->method }}</td>
                    </tr>
                    <tr>
                        <td class="detail-label">Ref. Invoice Pembelian</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value">{{ $payment->purchaseInvoice->invoice_number ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="detail-label">Ref. Purchase Order</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value">{{ $payment->purchaseInvoice->purchaseOrder->po_number ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="detail-label">Status Transaksi</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value" style="font-weight: 700; color: #111111;">POSTED (LUNAS/BERHASIL)</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- PAYMENT ALLOCATION TABLE --}}
    <div class="section-heading" style="margin-top: 15px; margin-bottom: 6px;">ALOKASI PELUNASAN TAGIHAN:</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">No.</th>
                <th style="width: 35%;">No. Invoice Pembelian</th>
                <th style="width: 20%; text-align: center;">Tanggal Invoice</th>
                <th style="width: 20%; text-align: right;">Total Nilai Tagihan</th>
                <th style="width: 20%; text-align: right;">Jumlah Dibayarkan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center;">1</td>
                <td>
                    <span class="item-name">{{ $payment->purchaseInvoice->invoice_number ?? '-' }}</span>
                    @if($payment->purchaseInvoice->supplier_invoice_number)
                        <br><span style="font-size: 8pt; color: #555555;">(No. Faktur Vendor: {{ $payment->purchaseInvoice->supplier_invoice_number }})</span>
                    @endif
                </td>
                <td style="text-align: center;">{{ $payment->purchaseInvoice->invoice_date ? $payment->purchaseInvoice->invoice_date->format('d/m/Y') : '-' }}</td>
                <td style="text-align: right;">Rp {{ number_format($payment->purchaseInvoice->effective_total_amount ?? $payment->purchaseInvoice->total_amount, 0, ',', '.') }}</td>
                <td style="text-align: right; font-weight: bold; color: #111111;">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="grand-total-row">
                <td colspan="4" style="text-align: right; font-weight: bold; padding: 6px 8px; text-transform: uppercase;">TOTAL KAS/BANK KELUAR:</td>
                <td style="text-align: right; font-weight: bold; padding: 6px 8px; font-size: 10pt; color: #111111;">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- NOTES & SIGNATURES --}}
    <table style="width: 100%; margin-top: 20px; border-collapse: collapse;">
        <tr>
            <td style="width: 55%; vertical-align: top; padding-right: 20px;">
                @if($payment->notes)
                    <div style="font-size: 8pt; font-weight: bold; color: #555555; text-transform: uppercase; margin-bottom: 3px;">CATATAN PEMBAYARAN:</div>
                    <div style="font-size: 8.5pt; color: #333333; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 8px;">
                        {{ $payment->notes }}
                    </div>
                @else
                    <div style="font-size: 8pt; color: #888888; font-style: italic;">
                        Bukti pembayaran sah ini diterbitkan secara elektronik oleh sistem ERP FlashSuite Pro.
                    </div>
                @endif
            </td>
            <td style="width: 45%; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse; text-align: center; font-size: 8.5pt;">
                    <tr>
                        <td style="width: 50%; padding-bottom: 50px;">
                            Dibuat Oleh,<br><br>
                        </td>
                        <td style="width: 50%; padding-bottom: 50px;">
                            Disetujui / Dibayarkan,<br><br>
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
                        <td style="font-size: 7.5pt; color: #666666; padding-top: 2px;">Finance & Accounting</td>
                        <td style="font-size: 7.5pt; color: #666666; padding-top: 2px;">Otorisasi Kas/Bank</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@endsection
