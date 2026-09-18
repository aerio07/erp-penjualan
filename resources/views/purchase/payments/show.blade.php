@extends('layouts.app')
@section('title', 'Detail Pembayaran Hutang - ' . $payment->voucher_number)
@section('page-title', 'Detail Pembayaran Hutang')

@section('content')
<div class="animate-in">
    {{-- Hidden Form for Post --}}
    @if($payment->status === 'draft')
    <form id="form-post-payment" method="POST" action="{{ route('purchase.payments.post', $payment) }}" style="display:none;">
        @csrf
    </form>
    <form id="form-delete-payment" method="POST" action="{{ route('purchase.payments.destroy', $payment) }}" style="display:none;">
        @csrf
        @method('DELETE')
    </form>
    @endif

    <x-detail-header 
        :title="'Pembayaran Hutang #' . $payment->voucher_number" 
        :subtitle="'Ref. Invoice: ' . ($payment->purchaseInvoice->invoice_number ?? '-') . ' · Supplier: ' . ($payment->purchaseInvoice->purchaseOrder->supplier->name ?? '-')"
        :status="$payment->status"
        :back-url="route('purchase.payments.index')"
    >
        <x-slot:actions>
            @if($payment->status === 'draft')
                <a href="{{ route('purchase.payments.edit', $payment) }}" class="btn btn-secondary">
                    <i class="fa-solid fa-pen-to-square"></i> Edit Draft
                </a>
                
                <button type="button" class="btn btn-primary"
                    data-confirm-swal="form-post-payment"
                    data-swal-title="Posting Pembayaran Hutang"
                    data-swal-html="Posting pembayaran hutang sebesar <strong>Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong> sekarang?<br><span style='font-size:12.5px; color:#64748b;'>Jurnal akuntansi otomatis terbentuk dan status tagihan invoice supplier akan diperbarui.</span>"
                    data-swal-icon="question"
                    data-swal-icon-color="#2563eb"
                    data-swal-btn-text="Ya, Posting Pembayaran"
                    data-swal-btn-icon="fa-solid fa-check"
                    data-swal-btn-class="btn btn-primary"
                >
                    <i class="fa-solid fa-paper-plane"></i> Posting Pembayaran
                </button>

                <button type="button" class="btn btn-danger"
                    data-confirm-swal="form-delete-payment"
                    data-swal-title="Hapus Draft Pembayaran"
                    data-swal-html="Yakin ingin menghapus draft pembayaran ini?<br><span style='font-size:12.5px; color:#64748b;'>Data draft pembayaran yang dihapus tidak dapat dikembalikan.</span>"
                    data-swal-icon="warning"
                    data-swal-btn-text="Ya, Hapus Draft"
                    data-swal-btn-icon="fa-solid fa-trash"
                    data-swal-btn-class="btn btn-danger"
                >
                    <i class="fa-solid fa-trash"></i> Hapus Draft
                </button>
            @elseif($payment->status === 'posted')
                {{-- Tombol Printout PDF: Hanya Muncul Setelah Status POSTED --}}
                <a href="{{ route('pdf.purchase-payment', $payment) }}" target="_blank" class="btn btn-secondary">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
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
            <x-status-badge :status="$payment->status" />
        </div>
        <div class="card-body">
            <div class="form-row form-row-4" style="margin-bottom:16px;">
                <div>
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">No. Pembayaran</div>
                    <div style="font-weight:700; font-size:14.5px; color:var(--primary);">
                        {{ $payment->payment_number ?? $payment->voucher_number }}
                    </div>
                    @if($payment->reference_number)
                        <div style="font-size:11.5px; color:var(--text-secondary); margin-top:2px;">
                            Ref: {{ $payment->reference_number }}
                        </div>
                    @endif
                </div>

                <div>
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Supplier</div>
                    <div style="font-weight:600; font-size:14px; color:var(--text-primary);">
                        {{ $payment->purchaseInvoice->purchaseOrder->supplier->name ?? '-' }}
                    </div>
                    @if($payment->purchaseInvoice->purchaseOrder->supplier?->phone)
                        <div style="font-size:11.5px; color:var(--text-secondary); margin-top:2px;">
                            <i class="fa-solid fa-phone" style="font-size:10px;"></i> {{ $payment->purchaseInvoice->purchaseOrder->supplier->phone }}
                        </div>
                    @endif
                </div>

                <div>
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Metode Pembayaran</div>
                    <div>
                        <span class="badge" style="background:#f1f5f9; color:#334155; border:1px solid #cbd5e1; font-weight:700;">
                            <i class="fa-solid fa-credit-card" style="margin-right:4px;"></i> {{ strtoupper($payment->method) }} ({{ $payment->voucher_type }})
                        </span>
                    </div>
                </div>

                <div>
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Tanggal Pembayaran</div>
                    <div style="font-weight:600; font-size:14px; color:var(--text-primary);">
                        {{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '-' }}
                    </div>
                </div>
            </div>

            {{-- Rekening Transfer Supplier (Jika Ada) --}}
            @if($payment->purchaseInvoice->purchaseOrder->supplier?->bank_name || $payment->purchaseInvoice->purchaseOrder->supplier?->bank_account_number)
            <div style="margin-bottom:16px; padding:10px 14px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; font-size:13px; display:flex; align-items:center; gap:10px;">
                <i class="fa-solid fa-building-columns" style="color:#2563eb; font-size:16px;"></i>
                <div>
                    <span style="color:#1e40af; font-weight:700;">Rekening Transfer Supplier:</span>
                    <span style="color:#1e3a8a; font-weight:600; margin-left:4px;">
                        {{ $payment->purchaseInvoice->purchaseOrder->supplier->bank_name ?? 'Bank' }} - {{ $payment->purchaseInvoice->purchaseOrder->supplier->bank_account_number }}
                    </span>
                    @if($payment->purchaseInvoice->purchaseOrder->supplier?->bank_account_holder)
                        <span style="color:#475569; font-size:12px; margin-left:4px;">
                            (a.n {{ $payment->purchaseInvoice->purchaseOrder->supplier->bank_account_holder }})
                        </span>
                    @endif
                </div>
            </div>
            @endif

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px 16px; margin-bottom:16px;">
                <div style="font-size:12px; font-weight:700; color:#475569; text-transform:uppercase; margin-bottom:8px;">
                    Informasi Tagihan Invoice Terkait
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                    <div>
                        <span style="font-size:13.5px; font-weight:700; color:var(--primary);">
                            {{ $payment->purchaseInvoice->invoice_number }}
                        </span>
                        @if($payment->purchaseInvoice->supplier_invoice_number)
                            <span style="font-size:12px; color:var(--text-secondary); margin-left:6px;">
                                (Faktur Vendor: #{{ $payment->purchaseInvoice->supplier_invoice_number }})
                            </span>
                        @endif
                    </div>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <span style="font-size:13px; color:var(--text-secondary);">Status Invoice:</span>
                        <x-status-badge :status="$payment->purchaseInvoice->status" />
                        <a href="{{ route('purchase.invoices.show', $payment->purchaseInvoice) }}" class="btn btn-secondary btn-sm" style="font-size:11px; padding:2px 8px;">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Buka Invoice
                        </a>
                    </div>
                </div>
            </div>

            <hr style="border:none; border-top:1px solid var(--border); margin:16px 0;">

            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <span style="font-size:15px; color:var(--text-secondary);">Jumlah Pembayaran Kas/Bank:</span>
                    <div style="font-size:11.5px; color:var(--text-secondary); margin-top:2px;">
                        Dicatat oleh: <strong>{{ $payment->user->name ?? 'Staff Finance' }}</strong>
                    </div>
                </div>
                <span style="font-size:24px; font-weight:700; color:var(--success); font-family: 'Inter', monospace;">
                    Rp {{ number_format($payment->amount, 0, ',', '.') }}
                </span>
            </div>

            @if($payment->notes)
            <div style="margin-top:16px; padding:12px; background:#f8fafc; border-radius:10px; font-size:13.5px; color:var(--text-secondary); border:1px solid #e2e8f0;">
                <i class="fa-solid fa-note-sticky" style="margin-right:6px; color:#64748b;"></i> {{ $payment->notes }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
