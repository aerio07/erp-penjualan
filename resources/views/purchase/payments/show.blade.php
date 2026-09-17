@extends('layouts.app')
@section('title', 'Detail Pembayaran Hutang')
@section('page-title', 'Detail Pembayaran Hutang')

@section('content')
<div class="animate-in">
    <x-detail-header 
        title="Detail Pembayaran Hutang" 
        :subtitle="'Invoice Ref: ' . ($payment->purchaseInvoice->invoice_number ?? '-')"
        status="done"
        :back-url="route('purchase.payments.index')"
    >
        <x-slot:actions>
            <a href="{{ route('purchase.payments.edit', $payment) }}" class="btn btn-warning">
                <i class="fa-solid fa-pen-to-square"></i> Edit Pembayaran
            </a>
        </x-slot:actions>
    </x-detail-header>

    <div class="card w-full">
        <div class="card-header">
            <h3>Bukti Transaksi Pembayaran</h3>
            <span class="badge badge-done">Telah Diposting</span>
        </div>
        <div class="card-body">
            <div class="form-row form-row-4" style="margin-bottom:16px;">
                <div>
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Supplier</div>
                    <div style="font-weight:600;">{{ $payment->purchaseInvoice->purchaseOrder->supplier->name ?? '-' }}</div>
                </div>
                <div>
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Metode Pembayaran</div>
                    <div><span class="badge badge-confirmed">{{ strtoupper($payment->method) }}</span></div>
                </div>
                <div>
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Tanggal Pembayaran</div>
                    <div style="font-weight:600;">{{ $payment->payment_date->format('m/d/Y') }}</div>
                </div>
                <div>
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">No. Referensi / Cek</div>
                    <div style="font-weight:600;">{{ $payment->reference_number ?? '-' }}</div>
                </div>
            </div>

            <hr style="border:none; border-top:1px solid var(--border); margin:16px 0;">

            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span style="font-size:15px; color:var(--text-secondary);">Jumlah Dibayar:</span>
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
