@extends('layouts.app')
@section('title', 'Detail Penerimaan Piutang')
@section('page-title', 'Detail Penerimaan Piutang')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Detail Penerimaan Piutang</h1>
            <p>Invoice Ref: <a href="{{ route('sales.invoices.show', $payment->salesInvoice) }}" style="color:var(--primary); font-weight:600;">{{ $payment->salesInvoice->invoice_number }}</a></p>
        </div>
        <a href="{{ route('sales.payments.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card w-full">
        <div class="card-header">
            <h3>Bukti Penerimaan Pembayaran</h3>
            <span class="badge badge-done">Telah Diposting</span>
        </div>
        <div class="card-body">
            <div class="form-row form-row-4" style="margin-bottom:16px;">
                <div>
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Customer</div>
                    <div style="font-weight:600;">{{ $payment->salesInvoice->salesOrder->customer->name ?? '-' }}</div>
                </div>
                <div>
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Metode Pembayaran</div>
                    <div><span class="badge badge-confirmed">{{ strtoupper($payment->method) }}</span></div>
                </div>
                <div>
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Tanggal Diterima</div>
                    <div style="font-weight:600;">{{ $payment->payment_date->format('d F Y') }}</div>
                </div>
                <div>
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">No. Referensi / Bank</div>
                    <div style="font-weight:600;">{{ $payment->reference_number ?? '-' }}</div>
                </div>
            </div>

            <hr style="border:none; border-top:1px solid var(--border); margin:16px 0;">

            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span style="font-size:15px; color:var(--text-secondary);">Jumlah Diterima:</span>
                <span style="font-size:24px; font-weight:700; color:var(--success);">
                    Rp {{ number_format($payment->amount, 0, ',', '.') }}
                </span>
            </div>

            @if($payment->notes)
            <div style="margin-top:16px; padding:12px; background:#f8fafc; border-radius:10px; font-size:13.5px; color:var(--text-secondary);">
                <i class="fa-solid fa-note-sticky" style="margin-right:6px;"></i> {{ $payment->notes }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
