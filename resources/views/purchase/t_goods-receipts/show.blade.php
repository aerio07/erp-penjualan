@extends('layouts.app')
@section('title', 'Detail Penerimaan Barang - ' . $goodsReceipt->receipt_number)
@section('page-title', 'Detail Penerimaan Barang')

@section('content')
<div class="animate-in">
    <form id="form-submit-grn" method="POST" action="{{ route('purchase.goods-receipts.submit', $goodsReceipt) }}" style="display:none;">
        @csrf
    </form>

    <x-detail-header 
        :title="$goodsReceipt->receipt_number" 
        :subtitle="'Penerimaan untuk PO: ' . ($goodsReceipt->purchaseOrder->po_number ?? '-')"
        :status="$goodsReceipt->status->value ?? 'draft'"
        :back-url="route('purchase.goods-receipts.index')"
    >
        <x-slot:actions>
            @if($goodsReceipt->canSubmitApproval())

            <button type="button" class="btn btn-primary"
                data-confirm-swal="form-submit-grn"
                data-swal-title="Ajukan Approval"
                data-swal-html="Ajukan Penerimaan Barang <strong>{{ $goodsReceipt->receipt_number }}</strong> untuk persetujuan (approval)?<br><span style='font-size:12.5px; color:#64748b;'>Dokumen akan dikirim ke Menu Approval. Stok akan dicatat setelah disetujui.</span>"
                data-swal-icon="question"
                data-swal-icon-color="#2563eb"
                data-swal-btn-text="Ya, Ajukan Approval"
                data-swal-btn-icon="fa-solid fa-paper-plane"
                data-swal-btn-class="btn btn-primary"
            >
                <i class="fa-solid fa-paper-plane"></i> Ajukan Approval
            </button>
            @endif

            @if($goodsReceipt->isWaitingApproval() && auth()->user()->isAdmin())
            <a href="{{ route('approvals.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-stamp"></i> Buka Menu Approval
            </a>
            @endif

            @if($goodsReceipt->isConfirmed())
                @if(!$goodsReceipt->is_invoiced)
                <a href="{{ route('purchase.invoices.create', ['po_id' => $goodsReceipt->purchase_order_id, 'grn_id' => $goodsReceipt->id]) }}" class="btn btn-primary">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Lempar ke Invoice / Buat Tagihan
                </a>
                @endif

                @if($goodsReceipt->items->sum('qty_rejected') > 0 || $goodsReceipt->items->sum('qty_received') > 0)
                <a href="{{ route('purchase.returns.create', ['grn_id' => $goodsReceipt->id]) }}" class="btn btn-warning">
                    <i class="fa-solid fa-rotate-left"></i> Buat Retur Pembelian
                </a>
                @endif
            @endif
        </x-slot:actions>
    </x-detail-header>

    {{-- Banner Status: Perlu Revisi --}}
    @if($goodsReceipt->isRevised())
    <div class="card" style="margin-bottom:20px; border-left: 4px solid #f59e0b; background: #fffdf7; box-shadow: 0 2px 6px rgba(245, 158, 11, 0.08);">
        <div class="card-body" style="padding:16px 20px; display:flex; gap:14px; align-items:flex-start;">
            <div style="width:40px; height:40px; border-radius:10px; background:#fef3c7; color:#b45309; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0;">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div style="flex:1;">
                <div style="font-weight:700; color:#92400e; font-size:15px;">
                    Permintaan Revisi dari Approver
                </div>
                <div style="color:#78350f; font-size:13.5px; margin-top:6px; padding:10px 14px; background:#fffbeb; border:1px dashed #fcd34d; border-radius:8px;">
                    <i class="fa-solid fa-comment-dots" style="margin-right:6px; color:#d97706;"></i>
                    <strong>Catatan Revisi:</strong> {{ $goodsReceipt->rejection_reason ?: 'Harap periksa kembali detail penerimaan barang ini.' }}
                </div>
                <div style="color:#b45309; font-size:12.5px; margin-top:8px;">
                    <i class="fa-solid fa-circle-info" style="margin-right:4px;"></i>
                    Silakan perbaiki data di halaman daftar Penerimaan Barang, lalu klik <strong>"Ajukan Approval"</strong> untuk mengajukan ulang.
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Banner Status: Ditolak (Rejected) --}}
    @if($goodsReceipt->isRejected())
    <div class="card" style="margin-bottom:20px; border-left: 4px solid #ef4444; background: #fef2f2; box-shadow: 0 2px 6px rgba(239, 68, 68, 0.08);">
        <div class="card-body" style="padding:16px 20px; display:flex; gap:14px; align-items:flex-start;">
            <div style="width:40px; height:40px; border-radius:10px; background:#fee2e2; color:#b91c1c; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0;">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <div style="flex:1;">
                <div style="font-weight:700; color:#991b1b; font-size:15px;">
                    Dokumen Penerimaan Ditolak oleh Approver
                </div>
                <div style="color:#7f1d1d; font-size:13.5px; margin-top:6px; padding:10px 14px; background:#fef2f2; border:1px dashed #fca5a5; border-radius:8px;">
                    <i class="fa-solid fa-comment-dots" style="margin-right:6px; color:#dc2626;"></i>
                    <strong>Alasan Penolakan:</strong> {{ $goodsReceipt->rejection_reason ?: 'Penerimaan barang ini ditolak.' }}
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Banner Status: Waiting Approval --}}
    @if($goodsReceipt->isWaitingApproval())
    <div class="card" style="margin-bottom:20px; border-left: 4px solid #3b82f6; background: #eff6ff;">
        <div class="card-body" style="padding:16px 20px; display:flex; gap:14px; align-items:center;">
            <div style="width:40px; height:40px; border-radius:10px; background:#dbeafe; color:#1d4ed8; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0;">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div style="flex:1;">
                <div style="font-weight:700; color:#1e40af; font-size:15px;">
                    Menunggu Persetujuan (Approval)
                </div>
                <div style="color:#1e3a8a; font-size:13px; margin-top:2px;">
                    Dokumen telah diajukan dan sedang menunggu persetujuan dari Approver. Stok gudang akan otomatis bertambah setelah disetujui.
                </div>
            </div>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('approvals.index') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-stamp"></i> Proses di Menu Approval
            </a>
            @endif
        </div>
    </div>
    @endif

    {{-- Banner Status: Draft --}}
    @if($goodsReceipt->isDraft())
    <div class="card" style="margin-bottom:20px; border-left: 4px solid #64748b; background: #f8fafc;">
        <div class="card-body" style="padding:14px 20px; display:flex; gap:14px; align-items:center;">
            <div style="width:36px; height:36px; border-radius:8px; background:#e2e8f0; color:#475569; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0;">
                <i class="fa-solid fa-file-lines"></i>
            </div>
            <div style="flex:1;">
                <div style="font-weight:700; color:#334155; font-size:14px;">
                    Status Dokumen: Draft
                </div>
                <div style="color:#64748b; font-size:12.5px; margin-top:2px;">
                    Dokumen ini masih berupa draft. Stok gudang belum diperbarui. Klik <strong>"Ajukan Approval"</strong> jika data fisik sudah benar dan siap disetujui.
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-3" style="margin-bottom:20px;">
        <div class="card" style="grid-column:span 2;">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h3>Informasi Penerimaan</h3>
                <span class="badge {{ $goodsReceipt->status_badge }}">{{ $goodsReceipt->status_label }}</span>
            </div>
            <div class="card-body">
                <div class="form-row form-row-2">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Supplier</div>
                        <div style="font-weight:600;">{{ $goodsReceipt->purchaseOrder->supplier->name ?? '-' }}</div>
                        <div style="font-size:13px; color:var(--text-secondary);">{{ $goodsReceipt->purchaseOrder->supplier->phone ?? '' }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Gudang Penerimaan</div>
                        <div style="font-weight:600; color:var(--primary);">
                            <i class="fa-solid fa-warehouse"></i> {{ $goodsReceipt->warehouse->name ?? $goodsReceipt->warehouse_names }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Tanggal Diterima</div>
                        <div style="font-weight:600;">{{ $goodsReceipt->received_date ? $goodsReceipt->received_date->format('d/m/Y') : '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Diterima Oleh</div>
                        <div style="font-weight:600;">{{ $goodsReceipt->user->name ?? '-' }}</div>
                    </div>
                </div>

                @if($goodsReceipt->approvedBy)
                <div style="margin-top:16px; padding:10px 14px; background:#ecfdf5; border-radius:8px; font-size:13px; color:#065f46; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-circle-check" style="color:#059669;"></i>
                    <span>Disetujui oleh <strong>{{ $goodsReceipt->approvedBy->name }}</strong> pada {{ $goodsReceipt->approved_at?->format('d/m/Y H:i') }}</span>
                </div>
                @endif

                @if($goodsReceipt->notes)
                <div style="margin-top:16px; padding:12px; background:#f8fafc; border-radius:10px; font-size:14px; color:var(--text-secondary);">
                    <i class="fa-solid fa-note-sticky" style="margin-right:6px;"></i> {{ $goodsReceipt->notes }}
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Ringkasan Fisik</h3></div>
            <div class="card-body">
                <div style="display:flex; flex-direction:column; gap:16px;">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary);">Total Fisik Datang</div>
                        <div style="font-size:24px; font-weight:700;">
                            {{ number_format($goodsReceipt->items->sum('qty_received') + $goodsReceipt->items->sum('qty_rejected')) }} pcs
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary);">Kondisi Baik (Masuk Stok)</div>
                        <div style="font-size:20px; font-weight:700; color:var(--success);">
                            <i class="fa-solid fa-circle-check"></i> {{ number_format($goodsReceipt->items->sum('qty_received')) }} pcs
                        </div>
                    </div>
                    @if($goodsReceipt->items->sum('qty_rejected') > 0)
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary);">Kondisi Rusak / Ditolak (Reject)</div>
                        <div style="font-size:20px; font-weight:700; color:var(--danger);">
                            <i class="fa-solid fa-triangle-exclamation"></i> {{ number_format($goodsReceipt->items->sum('qty_rejected')) }} pcs
                        </div>
                    </div>
                    @endif

                    @if($goodsReceipt->purchaseReturns->count() > 0)
                    <hr style="border:none; border-top:1px solid var(--border); margin:0;">
                    <div style="padding:10px; background:#fff1f2; border-radius:8px; border:1px solid #fecdd3;">
                        <div style="font-size:12px; font-weight:600; color:#e11d48; margin-bottom:6px;">
                            <i class="fa-solid fa-rotate-left"></i> Dokumen Retur Terkait:
                        </div>
                        @foreach($goodsReceipt->purchaseReturns as $ret)
                        <a href="{{ route('purchase.returns.show', $ret) }}" style="display:flex; justify-content:space-between; align-items:center; font-size:13px; color:#be123c; font-weight:600; text-decoration:none; margin-bottom:4px;">
                            <span>{{ $ret->return_number }}</span>
                            <span class="badge {{ $ret->status_badge }}">{{ $ret->status_label }}</span>
                        </a>
                        @endforeach
                    </div>
                    @endif

                    @if($goodsReceipt->is_invoiced && $goodsReceipt->purchaseInvoice)
                    <hr style="border:none; border-top:1px solid var(--border); margin:0;">
                    <div style="padding:12px; background:#eff6ff; border-radius:8px; border:1px solid #bfdbfe;">
                        <div style="font-size:12px; font-weight:600; color:#1e40af; margin-bottom:4px;">
                            <i class="fa-solid fa-file-invoice-dollar"></i> Faktur Pembelian Terkait:
                        </div>
                        <a href="{{ route('purchase.invoices.show', $goodsReceipt->purchaseInvoice) }}" style="font-size:14.5px; font-weight:700; color:var(--primary); text-decoration:none; display:block;">
                            #{{ $goodsReceipt->purchaseInvoice->invoice_number }}
                        </a>
                        <div style="font-size:12.5px; color:var(--text-secondary); margin-top:2px;">
                            Total Tagihan: <strong>Rp {{ number_format($goodsReceipt->purchaseInvoice->total_amount, 0, ',', '.') }}</strong>
                        </div>
                        <div style="margin-top:6px;">
                            <span class="badge {{ $goodsReceipt->purchaseInvoice->isPaid() ? 'badge-success' : 'badge-warning' }}">
                                {{ $goodsReceipt->purchaseInvoice->status_label }}
                            </span>
                        </div>
                    </div>
                    @elseif(!$goodsReceipt->is_invoiced && $goodsReceipt->isConfirmed())
                    <hr style="border:none; border-top:1px solid var(--border); margin:0;">
                    <div style="padding:12px; background:#f0fdf4; border-radius:8px; border:1px solid #bbf7d0;">
                        <div style="font-size:12px; font-weight:600; color:#166534; margin-bottom:4px;">
                            <i class="fa-solid fa-circle-check"></i> Siap Diterbitkan Invoice
                        </div>
                        <p style="font-size:12px; color:var(--text-secondary); margin:0 0 10px 0;">
                            Barang telah disetujui dan masuk gudang. Anda dapat menerbitkan faktur tagihan supplier sekarang.
                        </p>
                        <a href="{{ route('purchase.invoices.create', ['po_id' => $goodsReceipt->purchase_order_id, 'grn_id' => $goodsReceipt->id]) }}" class="btn btn-primary btn-sm" style="width:100%; justify-content:center;">
                            <i class="fa-solid fa-file-invoice-dollar"></i> Terbitkan Invoice Pembelian
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Item Table --}}
    <div class="card">
        <div class="card-header"><h3>Detail Item Diterima</h3></div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th style="width:40px; text-align:center;">#</th>
                        <th>Produk</th>
                        <th style="text-align:center;">Qty Baik (Stok Masuk)</th>
                        <th style="text-align:center;">Qty Rusak (Reject)</th>
                        <th style="text-align:right;">Harga Acuan HPP</th>
                        <th style="text-align:center;">Kondisi</th>
                        <th style="text-align:center;">Alasan Sisa / Rusak</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($goodsReceipt->items as $idx => $item)
                    <tr>
                        <td style="text-align:center;">{{ $idx + 1 }}</td>
                        <td>
                            <div style="font-weight:600;">{{ $item->purchaseOrderItem->product->name ?? '-' }}</div>
                            <div style="font-size:12px; color:var(--text-secondary);">{{ $item->purchaseOrderItem->product->sku ?? '-' }}</div>
                        </td>
                        <td style="text-align:center; font-weight:600; color:var(--success);">
                            {{ $item->qty_received }} {{ $item->purchaseOrderItem->product->unit ?? 'pcs' }}
                        </td>
                        <td style="text-align:center; {{ $item->qty_rejected > 0 ? 'color:var(--danger); font-weight:600;' : 'color:var(--text-secondary);' }}">
                            {{ $item->qty_rejected }}
                        </td>
                        <td style="text-align:right;">
                            Rp {{ number_format($item->unit_cost, 0, ',', '.') }}
                        </td>
                        <td style="text-align:center;">
                            <span class="badge {{ $item->condition === 'Good' ? 'badge-done' : 'badge-cancelled' }}">
                                {{ $item->condition }}
                            </span>
                        </td>
                        <td style="text-align:center; font-size:12px;">
                            @if($item->shortage_reason === 'damaged_in_transit')
                                <span style="color:var(--danger); font-weight:600;"><i class="fa-solid fa-truck-burst"></i> Rusak Saat Kirim</span>
                            @elseif($item->shortage_reason === 'not_shipped')
                                <span style="color:var(--warning); font-weight:600;"><i class="fa-solid fa-box-open"></i> Belum Dikirim (Parsial)</span>
                            @else
                                <span style="color:var(--text-secondary);">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
