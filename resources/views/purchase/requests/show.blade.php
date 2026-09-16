@extends('layouts.app')
@section('title', 'Detail Purchase Request - ' . $purchase_request->pr_number)
@section('page-title', 'Detail Purchase Request')

@section('content')
<div class="animate-in">
    <x-page-header 
        title="{{ $purchase_request->pr_number }}" 
        subtitle="Diajukan oleh {{ $purchase_request->user->name ?? 'User' }} (Divisi {{ $purchase_request->division ?? 'Umum' }}) pada {{ $purchase_request->request_date?->format('d/m/Y') }}"
        :back-url="route('purchase.requests.index')"
    >
        <x-slot:actions>
            <div style="display:flex; gap:8px; align-items:center;">
                @if(in_array($purchase_request->status, ['draft', 'rejected']))
                <a href="{{ route('purchase.requests.edit', $purchase_request) }}" class="btn btn-secondary">
                    <i class="fa-solid fa-pen"></i> Edit PR
                </a>
                @endif

                @if(in_array($purchase_request->status, ['draft', 'rejected']))
                <form method="POST" action="{{ route('purchase.requests.submit', $purchase_request) }}" style="display:inline;">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Ajukan Purchase Request {{ $purchase_request->pr_number }} ini untuk persetujuan (approval)?')">
                        <i class="fa-solid fa-paper-plane"></i> Ajukan Approval
                    </button>
                </form>
                @endif

                @if($purchase_request->status === 'waiting_approval' && (auth()->user()->role === 'admin' || auth()->user()->role === 'purchasing'))
                <button type="button" class="btn btn-danger" onclick="document.getElementById('rejectModal').style.display = 'flex'">
                    <i class="fa-solid fa-xmark"></i> Tolak PR
                </button>
                <form method="POST" action="{{ route('purchase.requests.approve', $purchase_request) }}" style="display:inline;">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success" onclick="return confirm('Setujui Purchase Request {{ $purchase_request->pr_number }} ini?')">
                        <i class="fa-solid fa-check"></i> Setujui (Approve)
                    </button>
                </form>
                @endif

                @if($purchase_request->status === 'approved')
                <a href="{{ route('purchase.orders.create', ['purchase_request_id' => $purchase_request->id]) }}" class="btn btn-primary" style="background:#2563eb;">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Terbitkan Purchase Order (PO)
                </a>
                @endif

                <button type="button" class="btn btn-secondary" onclick="window.print()">
                    <i class="fa-solid fa-print"></i> Cetak
                </button>
            </div>
        </x-slot:actions>
    </x-page-header>

    {{-- Alert Alasan Penolakan jika ada --}}
    @if($purchase_request->status === 'rejected' && $purchase_request->rejection_reason)
    <div class="alert alert-danger" style="margin-bottom:20px; display:flex; align-items:flex-start; gap:12px; padding:14px 18px; border-radius:8px; background:#fef2f2; border:1px solid #fecaca; color:#b91c1c;">
        <i class="fa-solid fa-circle-xmark" style="font-size:20px; margin-top:2px;"></i>
        <div>
            <strong>Purchase Request Ini Telah Ditolak</strong>
            <div style="margin-top:2px;">Alasan: {{ $purchase_request->rejection_reason }}</div>
            <div style="font-size:12px; color:#991b1b; margin-top:4px;">
                Ditolak oleh: {{ $purchase_request->approver->name ?? 'Approver' }} pada {{ $purchase_request->approved_at?->format('d/m/Y H:i') }}
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
                            {{ $purchase_request->request_date?->format('d F Y') }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary);">Date Needed (Tgl Dibutuhkan)</div>
                        <div style="font-size:14px; font-weight:700; color:var(--text-primary); margin-top:2px;">
                            <i class="fa-solid fa-calendar-check text-primary"></i> {{ $purchase_request->required_date?->format('d F Y') }}
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
                            {{ $purchase_request->expired_date ? $purchase_request->expired_date->format('d F Y') : '-' }}
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
                    <span class="badge {{ $purchase_request->status_badge }}" style="font-size:14px; padding:6px 14px;">
                        {{ $purchase_request->status_label }}
                    </span>
                </div>

                @if($purchase_request->approved_at)
                <div style="padding:12px; border-radius:6px; background:var(--bg-muted, #f8fafc); font-size:13px;">
                    <div style="font-weight:600; color:var(--text-primary); margin-bottom:2px;">
                        {{ $purchase_request->status === 'rejected' ? 'Ditolak oleh:' : 'Disetujui oleh:' }}
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
                        <th style="text-align:center; width:130px;">Qty Sudah di-PO</th>
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

{{-- Modal Reject PR --}}
<div id="rejectModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center;">
    <div class="card" style="width:100%; max-width:450px; background:var(--bg-card, #fff); box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h3>Tolak Purchase Request</h3>
            <button type="button" onclick="document.getElementById('rejectModal').style.display = 'none'" style="background:none; border:none; font-size:18px; cursor:pointer;">&times;</button>
        </div>
        <form method="POST" action="{{ route('purchase.requests.reject', $purchase_request) }}">
            @csrf
            @method('PATCH')
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Alasan Penolakan <span style="color:var(--danger);">*</span></label>
                    <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Tuliskan alasan mengapa PR ini ditolak..."></textarea>
                </div>
            </div>
            <div class="card-footer" style="display:flex; justify-content:flex-end; gap:8px; padding:12px 16px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('rejectModal').style.display = 'none'">Batal</button>
                <button type="submit" class="btn btn-danger">Tolak PR Ini</button>
            </div>
        </form>
    </div>
</div>
@endsection