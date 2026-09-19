@extends('layouts.pdf')

@section('title', ($payment->voucher_type ?? 'BBM') . ' - ' . $payment->voucher_number)
@section('doc-title', $payment->voucher_title)
@section('doc-subtitle', $payment->voucher_number)

@section('extra_css')
<style>
    @page {
        size: a5 portrait;
        margin: 12mm 18mm 12mm 18mm;
    }
    body {
        padding: 0 10px !important;
        font-size: 8pt;
        line-height: 1.35;
    }
    .doc-header {
        margin-bottom: 8px;
    }
    .doc-title {
        font-size: 15pt;
        margin-bottom: 2px;
    }
    .doc-subtitle {
        font-size: 8.5pt;
    }
    .header-divider {
        margin: 8px 0 12px 0;
    }
    .parties-table {
        margin-bottom: 12px;
    }
    .section-heading {
        font-size: 7.5pt;
        margin-bottom: 4px;
    }
    .party-name {
        font-size: 9pt;
    }
    .party-info, .details-table td {
        font-size: 8pt;
    }
    .items-table thead th {
        font-size: 7.5pt;
        padding: 5px 6px;
    }
    .items-table tbody td {
        font-size: 8pt;
        padding: 6px 6px;
    }
</style>
@endsection

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
                <div class="section-heading">RINCIAN PEMBAYARAN</div>
                <table class="details-table">
                    <tr>
                        <td class="detail-label">Tanggal Pembayaran</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value">{{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="detail-label">Metode Pembayaran</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value" style="font-weight: bold; text-transform: uppercase;">{{ $payment->method }} ({{ $payment->voucher_type }})</td>
                    </tr>
                    @if($payment->chartOfAccount)
                    <tr>
                        <td class="detail-label">Akun Kas/Bank (COA)</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value">[{{ $payment->chartOfAccount->code }}] {{ $payment->chartOfAccount->name }}</td>
                    </tr>
                    @endif
                    @if(!empty(trim($payment->reference_number ?? '')))
                    <tr>
                        <td class="detail-label">No. Ref / Transfer</td>
                        <td class="detail-colon">:</td>
                        <td class="detail-value">{{ $payment->reference_number }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- PAYMENT ALLOCATION TABLE --}}
    <div class="section-heading" style="margin-top: 12px; margin-bottom: 6px;">ALOKASI PEMBAYARAN PIUTANG:</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">No.</th>
                <th style="width: 31%;">No. Invoice Penjualan</th>
                <th style="width: 15%; text-align: center;">Tanggal Invoice</th>
                <th style="width: 15%; text-align: center;">Tgl Jatuh Tempo</th>
                <th style="width: 17%; text-align: right;">Total Nilai Tagihan</th>
                <th style="width: 17%; text-align: right;">Jumlah Bayar</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center;">1</td>
                <td>
                    <span class="item-name">{{ $payment->salesInvoice->invoice_number ?? '-' }}</span>
                    @if($payment->salesInvoice->salesOrder?->so_number)
                        <br><span style="font-size: 7.5pt; color: #555555;">(Ref. Pesanan SO: {{ $payment->salesInvoice->salesOrder->so_number }})</span>
                    @endif
                </td>
                <td style="text-align: center;">{{ $payment->salesInvoice->invoice_date ? $payment->salesInvoice->invoice_date->format('d/m/Y') : '-' }}</td>
                <td style="text-align: center;">{{ $payment->salesInvoice->due_date ? $payment->salesInvoice->due_date->format('d/m/Y') : '-' }}</td>
                <td style="text-align: right;">Rp {{ number_format($payment->salesInvoice->effective_total_amount ?? $payment->salesInvoice->total_amount, 0, ',', '.') }}</td>
                <td style="text-align: right; font-weight: bold; color: #111111;">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="grand-total-row">
                <td colspan="5" style="text-align: right; font-weight: bold; padding: 6px 8px; text-transform: uppercase; border-top: 1px solid #e0e0e0; border-bottom: 1px solid #e2e8f0;">TOTAL:</td>
                <td style="text-align: right; font-weight: bold; padding: 6px 8px; font-size: 9.5pt; color: #111111; border-top: 1px solid #e0e0e0; border-bottom: 1px solid #e2e8f0;">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
            </tr>
            <tr style="background: #f8fafc;">
                <td colspan="6" style="padding: 6px 10px; font-size: 8pt; border-bottom: 1px solid #e2e8f0;">
                    <span style="font-weight: bold; color: #333333;">Terbilang:</span>
                    <span style="font-style: italic; color: #0f172a; font-weight: 600;"># {{ terbilang($payment->amount) }} #</span>
                </td>
            </tr>
        </tfoot>
    </table>

    {{-- NOTES & SIGNATURES --}}
    <table style="width: 100%; margin-top: 16px; border-collapse: collapse;">
        <tr>
            <td style="width: 52%; vertical-align: top; padding-right: 15px;">
                @if(!empty(trim($payment->notes ?? '')))
                    <div style="font-size: 7.5pt; font-weight: bold; color: #555555; text-transform: uppercase; margin-bottom: 3px;">CATATAN PEMBAYARAN:</div>
                    <div style="font-size: 8pt; color: #333333; background: #f9f9f9; border: 1px solid #e5e5e5; border-radius: 4px; padding: 6px 8px;">
                        {{ $payment->notes }}
                    </div>
                @endif
            </td>
            <td style="width: 48%; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse; text-align: center; font-size: 8pt;">
                    <tr>
                        <td style="width: 50%; padding-bottom: 45px;">
                            Diterima / Dicatat Oleh,<br><br>
                        </td>
                        <td style="width: 50%; padding-bottom: 45px;">
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
                        <td style="font-size: 7pt; color: #666666; padding-top: 2px;">Finance & Kasir</td>
                        <td style="font-size: 7pt; color: #666666; padding-top: 2px;">Otorisasi Kas/Bank</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@endsection
