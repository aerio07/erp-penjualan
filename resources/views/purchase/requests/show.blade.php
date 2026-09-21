@extends('layouts.app')
@section('title', 'Detail Purchase Request - ' . $purchase_request->pr_number)
@section('page-title', 'Detail Purchase Request')

@section('content')
<div class="animate-in">
    @php
        $latestApproval = $purchase_request->approvalRequests->sortByDesc('id')->first();
    @endphp

    {{-- Hidden forms for workflow actions --}}
    <form id="form-submit-pr" method="POST" action="{{ route('purchase.requests.submit', $purchase_request) }}" style="display:none;">
        @csrf @method('PATCH')
    </form>

    <x-page-header 
        title="{{ $purchase_request->pr_number }}" 
        subtitle="Diajukan oleh {{ $purchase_request->user->name ?? 'User' }} (Divisi {{ $purchase_request->division ?? 'Umum' }}) pada {{ $purchase_request->request_date?->format('d/m/Y') }}"
        :back-url="route('purchase.requests.index')"
    >
        <x-slot:actions>
            <div style="display:flex; gap:8px; align-items:center;">
                @if($purchase_request->isDraft() || $purchase_request->isRevised() || $purchase_request->isRejected())
                <button type="button" class="btn btn-primary"
                    data-confirm-swal="form-submit-pr"
                    data-swal-title="Ajukan Purchase Request"
                    data-swal-html="Ajukan Purchase Request <strong>{{ $purchase_request->pr_number }}</strong> untuk persetujuan (approval)?<br><span style='font-size:12.5px; color:#64748b;'>Dokumen akan dikirim ke Menu Approval.</span>"
                    data-swal-icon="question"
                    data-swal-icon-color="#2563eb"
                    data-swal-btn-text="Ya, Ajukan PR"
                    data-swal-btn-icon="fa-solid fa-paper-plane"
                    data-swal-btn-class="btn btn-primary"
                >
                    <i class="fa-solid fa-paper-plane"></i> Ajukan Approval
                </button>
                @endif

                @if($purchase_request->isWaitingApproval() && auth()->user()->isAdmin())
                <a href="{{ route('approvals.index') }}" class="btn btn-secondary">
                    <i class="fa-solid fa-stamp"></i> Buka Menu Approval
                </a>
                @endif

                @if($purchase_request->isConfirmed())
                <a href="{{ route('purchase.orders.create', ['purchase_request_id' => $purchase_request->id]) }}" class="btn btn-primary" style="background:#2563eb;">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Terbitkan Purchase Order (PO)
                </a>
                @endif

                @if($purchase_request->isConfirmed() || $purchase_request->isDone())
                <a href="{{ route('pdf.purchase-request', $purchase_request) }}" class="btn btn-secondary" target="_blank">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <button type="button" class="btn btn-secondary" onclick="window.print()">
                    <i class="fa-solid fa-print"></i> Cetak
                </button>
                @endif
            </div>
        </x-slot:actions>
    </x-page-header>

    {{-- Banner Status: Perlu Revisi --}}
    @if($purchase_request->isRevised() || ($purchase_request->isDraft() && $latestApproval && $latestApproval->status === 'revised'))
    <div class="card" style="margin-bottom:20px; border-left: 4px solid #f59e0b; background: #fffdf7; box-shadow: 0 2px 6px rgba(245, 158, 11, 0.08);">
        <div class="card-body" style="padding:16px 20px; display:flex; gap:14px; align-items:flex-start;">
            <div style="width:40px; height:40px; border-radius:10px; background:#fef3c7; color:#b45309; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0;">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div style="flex:1;">
                <div style="font-weight:700; color:#92400e; font-size:15px;">
                    Permintaan Revisi dari Approver ({{ $latestApproval->approver->name ?? $purchase_request->approver->name ?? 'Approver' }})
                </div>
                <div style="color:#78350f; font-size:13.5px; margin-top:6px; padding:10px 14px; background:#fffbeb; border:1px dashed #fcd34d; border-radius:8px;">
                    <i class="fa-solid fa-comment-dots" style="margin-right:6px; color:#d97706;"></i>
                    <strong>Catatan Revisi:</strong> {{ $purchase_request->rejection_reason ?: ($latestApproval->rejection_reason ?: 'Harap periksa kembali detail pengajuan PR ini.') }}
                </div>
                <div style="color:#b45309; font-size:12.5px; margin-top:8px;">
                    <i class="fa-solid fa-circle-info" style="margin-right:4px;"></i>
                    Silakan perbaiki data di halaman daftar Purchase Request, lalu klik <strong>"Ajukan Approval"</strong> untuk mengajukan ulang.
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Banner Status: Ditolak (Rejected) --}}
    @if($purchase_request->isRejected())
    <div class="card" style="margin-bottom:20px; border-left: 4px solid #ef4444; background: #fffbfb; box-shadow: 0 2px 6px rgba(239, 68, 68, 0.08);">
        <div class="card-body" style="padding:16px 20px; display:flex; gap:14px; align-items:flex-start;">
            <div style="width:40px; height:40px; border-radius:10px; background:#fee2e2; color:#b91c1c; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0;">
                <i class="fa-solid fa-ban"></i>
            </div>
            <div style="flex:1;">
                <div style="font-weight:700; color:#991b1b; font-size:15px;">
                    Purchase Request Ditolak (Rejected)
                </div>
                @if($purchase_request->rejection_reason)
                <div style="color:#7f1d1d; font-size:13.5px; margin-top:6px; padding:10px 14px; background:#fef2f2; border:1px dashed #fca5a5; border-radius:8px;">
                    <i class="fa-solid fa-comment-slash" style="margin-right:6px; color:#ef4444;"></i>
                    <strong>Alasan Penolakan:</strong> {{ $purchase_request->rejection_reason }}
                </div>
                @endif
                <div style="color:#991b1b; font-size:12.5px; margin-top:8px;">
                    <i class="fa-solid fa-circle-info" style="margin-right:4px;"></i>
                    Ditolak oleh: {{ $purchase_request->approver->name ?? 'Approver' }} pada {{ $purchase_request->approved_at?->format('d/m/Y H:i') }} | Dokumen ini ditolak oleh reviewer. Silakan perbaiki data di halaman daftar Purchase Request lalu ajukan kembali.
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Detail Info Card --}}
    <div class="grid grid-3" style="margin-bottom:20px;">
        <div class="card" style="grid-column: span 2;">
            <div class="card-header">
                <h3><i class="fa-solid fa-info-circle text-primary"></i> Data Pengajuan</h3>
            </div>
            <div class="card-body">
                <div class="grid grid-3" style="gap:16px;">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary);">No. Dokumen PR</div>
                        <div style="font-size:15px; font-weight:700; color:var(--primary); margin-top:2px;">
                            {{ $purchase_request->pr_number }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary);">Tanggal Pengajuan</div>
                        <div style="font-size:14px; font-weight:600; margin-top:2px;">
                            {{ $purchase_request->request_date?->format('d/m/Y') }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary);">Date Needed (Tgl Dibutuhkan)</div>
                        <div style="font-size:14px; font-weight:700; color:var(--text-primary); margin-top:2px;">
                            <i class="fa-solid fa-calendar-check text-primary"></i> {{ $purchase_request->required_date?->format('d/m/Y') }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary);">Divisi Peminta</div>
                        <div style="font-size:14px; font-weight:600; margin-top:2px;">
                            {{ $purchase_request->division ?? '-' }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary);">Pemohon (User)</div>
                        <div style="font-size:14px; font-weight:600; margin-top:2px;">
                            {{ $purchase_request->user->name ?? '-' }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary);">Batas Kedaluwarsa PR</div>
                        <div style="font-size:14px; font-weight:600; margin-top:2px;">
                            {{ $purchase_request->expired_date ? $purchase_request->expired_date->format('d/m/Y') : '-' }}
                        </div>
                    </div>
                </div>

                @if($purchase_request->notes)
                <div style="margin-top:16px; padding-top:12px; border-top:1px solid var(--border-color);">
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Catatan / Keperluan Pengadaan:</div>
                    <div style="font-size:13.5px; color:var(--text-primary); background:var(--bg-muted, #f8fafc); padding:10px 14px; border-radius:6px;">
                        {{ $purchase_request->notes }}
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Card Status & Approval --}}
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-stamp text-primary"></i> Status & Persetujuan</h3>
            </div>
            <div class="card-body">
                <div style="margin-bottom:16px;">
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Status Saat Ini:</div>
                    <x-status-badge :status="$purchase_request->status" class="text-xs px-2.5 py-1" />
                </div>

                @if($purchase_request->approved_at)
                <div style="padding:12px; border-radius:6px; background:var(--bg-muted, #f8fafc); font-size:13px;">
                    <div style="font-weight:600; color:var(--text-primary); margin-bottom:2px;">
                        {{ $purchase_request->isRejected() ? 'Ditolak oleh:' : 'Disetujui oleh:' }}
                    </div>
                    <div>{{ $purchase_request->approver->name ?? '-' }}</div>
                    <div style="font-size:12px; color:var(--text-secondary); margin-top:2px;">
                        {{ $purchase_request->approved_at->format('d/m/Y H:i') }}
                    </div>
                </div>
                @endif

                @if($purchase_request->purchaseOrders->count() > 0)
                <div style="margin-top:16px;">
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:6px;">Terkait Purchase Order:</div>
                    @foreach($purchase_request->purchaseOrders as $po)
                    <a href="{{ route('purchase.orders.show', $po) }}" class="btn btn-sm btn-secondary" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; text-decoration:none;">
                        <span><i class="fa-solid fa-file-invoice"></i> {{ $po->po_number }}</span>
                        <span class="badge badge-success" style="font-size:10px;">{{ ucfirst($po->status) }}</span>
                    </a>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Tabel Item Barang --}}
    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <h3><i class="fa-solid fa-list-check text-primary"></i> Rincian Barang yang Diajukan</h3>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th style="width:50px; text-align:center;">No</th>
                        <th style="width:140px;">Kode (SKU)</th>
                        <th>Nama Produk</th>
                        <th style="text-align:center; width:130px;">Qty Diminta</th>
                        <th style="text-align:center; width:130px;">Qty Diproses PO</th>
                        <th style="text-align:center; width:130px;">Sisa Kebutuhan</th>
                        <th style="width:100px; text-align:center;">Satuan</th>
                        <th>Catatan Item</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchase_request->items as $idx => $item)
                    <tr>
                        <td style="text-align:center; color:var(--text-secondary);">{{ $idx + 1 }}</td>
                        <td style="font-family:monospace; font-weight:600;">{{ $item->product->sku ?? '-' }}</td>
                        <td>
                            <div style="font-weight:600; color:var(--text-primary);">{{ $item->product->name ?? '-' }}</div>
                            <div style="font-size:12px; color:var(--text-secondary);">
                                Kategori: {{ $item->product->productCategory->name ?? $item->product->category ?? '-' }}
                            </div>
                        </td>
                        <td style="text-align:center; font-weight:700; font-size:15px; color:var(--primary);">
                            {{ number_format($item->qty) }}
                        </td>
                        <td style="text-align:center; font-weight:600; color:var(--text-secondary);">
                            {{ number_format($item->qty_ordered) }}
                        </td>
                        <td style="text-align:center; font-weight:700; {{ $item->qty_remaining > 0 ? 'color:#dc2626;' : 'color:#16a34a;' }}">
                            {{ number_format($item->qty_remaining) }}
                        </td>
                        <td style="text-align:center; color:var(--text-secondary);">{{ $item->product->unit ?? 'pcs' }}</td>
                        <td style="color:var(--text-secondary);">{{ $item->notes ?: '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
