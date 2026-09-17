@extends('layouts.app')
@section('title', 'Detail Pembayaran Hutang')
@section('page-title', 'Detail Pembayaran Hutang')

@section('content')
<div class="animate-in">
    <x-detail-header 
        title="Detail Pembayaran Hutang" 
        :subtitle="'Invoice Ref: ' . ($payment->purchaseInvoice->invoice_number ?? '-')"
        :badge-text="$payment->status === 'posted' ? 'Posted' : 'Draft'"
        :badge-class="'badge-' . ($payment->status === 'posted' ? 'posted' : 'draft')"
        :back-url="route('purchase.payments.index')"
    >
        <x-slot:actions>
            @if($payment->status === 'draft')
            <a href="{{ route('purchase.payments.edit', $payment) }}" class="btn btn-warning">
                <i class="fa-solid fa-pen-to-square"></i> Edit Draft
            </a>
            <form method="POST" action="{{ route('purchase.payments.post', $payment) }}" style="display:inline;" onsubmit="return confirm('Posting pembayaran hutang ini sekarang? Jurnal akuntansi akan otomatis tercatat dan pembayaran tidak dapat diedit lagi.')">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="fa-solid fa-check"></i> Posting Pembayaran
                </button>
            </form>
            <form method="POST" action="{{ route('purchase.payments.destroy', $payment) }}" style="display:inline;" onsubmit="return confirm('Hapus draft pembayaran hutang ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fa-solid fa-trash"></i> Hapus Draft
                </button>
            </form>
            @endif
        </x-slot:actions>
    </x-detail-header>

    @if(session('success'))
    <div class="alert alert-success" style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-circle-check" style="font-size: 18px;"></i>
        <div>{{ session('success') }}</div>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger" style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size: 18px;"></i>
        <div>{{ session('error') }}</div>
    </div>
    @endif

    <div class="card w-full">
        <div class="card-header">
            <h3>Bukti Transaksi Pembayaran</h3>
            <span class="badge badge-{{ $payment->status === 'posted' ? 'posted' : 'draft' }}">
                {{ $payment->status === 'posted' ? 'Telah Diposting' : 'Draft Pembayaran' }}
            </span>
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
                    <div style="font-weight:600;">{{ $payment->payment_date->format('d/m/Y') }}</div>
                </div>
                <div>
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">No. Referensi / Cek</div>
                    <div style="font-weight:600;">{{ $payment->reference_number ?? '-' }}</div>
                </div>
            </div>

            <hr style="border:none; border-top:1px solid var(--border); margin:16px 0;">

            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span style="font-size:15px; color:var(--text-secondary);">Jumlah Dibayar:</span>
                <span style="font-size:24px; font-weight:700; color:var(--success); font-family: 'Inter', monospace;">
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
