@extends('layouts.app')
@section('title', 'Approval')
@section('page-title', 'Approval')

@section('content')
<style>
    /* Optimasi Tabel Approval agar muat 1 layar tanpa scroll horizontal dan tombol aksi tidak terpotong */
    .approval-table {
        width: 100% !important;
        min-width: 0 !important;
        table-layout: auto !important;
    }
    .approval-table thead th {
        padding: 9px 8px !important;
        font-size: 0.71rem !important;
        letter-spacing: 0.02em !important;
        white-space: nowrap !important;
    }
    .approval-table tbody td {
        padding: 8px 8px !important;
        font-size: 0.83rem !important;
        vertical-align: middle !important;
    }
    .approval-actions {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 5px !important;
        white-space: nowrap !important;
    }
    .approval-btn {
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px !important;
        padding: 5px 8px !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        line-height: 1.2 !important;
        border-radius: 5px !important;
        white-space: nowrap !important;
        box-shadow: none !important;
    }
</style>

<div class="animate-in">
    <x-page-header title="Approval" subtitle="Persetujuan dokumen Purchase Request, Purchase Order, Penerimaan Barang (LPB), dan Sales Order" />

    {{-- Filter Bar --}}
    <x-list-filter-bar :action="route('approvals.index')" placeholder="Cari No. Request, Catatan..." :showDateFilter="true">
        <select name="approvable_type" class="form-control" style="height:38px; font-size:13px; min-width:160px; border-radius:6px;">
            <option value="">Semua Dokumen</option>
            <option value="App\Models\PurchaseRequest" {{ request('approvable_type') === 'App\Models\PurchaseRequest' ? 'selected' : '' }}>Purchase Request</option>
            <option value="App\Models\PurchaseOrder" {{ request('approvable_type') === 'App\Models\PurchaseOrder' ? 'selected' : '' }}>Purchase Order</option>
            <option value="App\Models\GoodsReceipt" {{ request('approvable_type') === 'App\Models\GoodsReceipt' ? 'selected' : '' }}>Penerimaan Barang (LPB)</option>
            <option value="App\Models\SalesOrder" {{ request('approvable_type') === 'App\Models\SalesOrder' ? 'selected' : '' }}>Sales Order</option>
        </select>
    </x-list-filter-bar>

    {{-- Pending Approvals --}}
    <div class="card" style="margin-bottom:24px;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0;"><i class="fa-solid fa-clock" style="color:var(--warning); margin-right:8px;"></i> Menunggu Persetujuan Anda</h3>
            <span class="badge badge-pending">{{ $pendingApprovals->total() }} pengajuan</span>
        </div>
        <div class="table-responsive" style="overflow-x:auto;">
            <table class="erp-table approval-table">
                <thead>
                    <tr>
                        <x-sortable-header column="request_number" title="No. Request" />
                        <th style="width:45px; text-align:center;">Tipe</th>
                        <th>Dokumen / Ref</th>
                        <th>Pemohon</th>
                        <x-sortable-header column="amount" title="Nilai Transaksi" align="right" />
                        <x-sortable-header column="created_at" title="Tgl Pengajuan" />
                        <th style="text-align:center; width:185px;">Aksi Approval</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingApprovals as $app)
                    @php
                        $doc = $app->approvable;
                        $docNum = $doc->receipt_number ?? $doc->po_number ?? $doc->so_number ?? $doc->pr_number ?? '#'.$app->approvable_id;
                        $docRoute = '#';
                        $docTypeLabel = 'Dokumen';
                        $badgeClass = 'badge-confirmed';
                        
                        if ($app->approvable_type === 'App\Models\PurchaseRequest') {
                            $docRoute = route('purchase.requests.show', $app->approvable_id);
                            $docTypeLabel = 'PR';
                            $badgeClass = 'badge-warning';
                        } elseif ($app->approvable_type === 'App\Models\PurchaseOrder') {
                            $docRoute = route('purchase.orders.show', $app->approvable_id);
                            $docTypeLabel = 'PO';
                            $badgeClass = 'badge-confirmed';
                        } elseif ($app->approvable_type === 'App\Models\GoodsReceipt') {
                            $docRoute = route('purchase.goods-receipts.show', $app->approvable_id);
                            $docTypeLabel = 'LPB';
                            $badgeClass = 'badge-primary';
                        } elseif ($app->approvable_type === 'App\Models\SalesOrder') {
                            $docRoute = route('sales.orders.show', $app->approvable_id);
                            $docTypeLabel = 'SO';
                            $badgeClass = 'badge-info';
                        }
                        $hasNoAmount = in_array($app->approvable_type, [
                            'App\Models\PurchaseRequest',
                            'App\Models\GoodsReceipt',
                        ]);
                        $displayAmount = (float) (($app->amount > 0) ? $app->amount : ($doc->total_amount ?? 0));
                    @endphp
                    <tr>
                        <td style="font-weight:600; color:var(--primary); white-space:nowrap;">{{ $app->request_number }}</td>
                        <td style="text-align:center;">
                            <span class="badge {{ $badgeClass }}" style="font-size:11px; padding:2px 6px;">
                                {{ $docTypeLabel }}
                            </span>
                        </td>
                        <td style="white-space:nowrap;">
                            <a href="{{ $docRoute }}" style="font-weight:700; font-size:13px; color:var(--primary); text-decoration:none;">
                                {{ $docNum }} <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i>
                            </a>
                        </td>
                        <td>{{ $app->requester->name ?? '-' }}</td>
                        <td style="text-align:right; font-weight:700; color:var(--primary); white-space:nowrap;">
                            @if($hasNoAmount)
                                <span style="color:var(--text-secondary); font-weight:400;">-</span>
                            @else
                                Rp {{ number_format($displayAmount, 0, ',', '.') }}
                            @endif
                        </td>
                        <td style="white-space:nowrap; font-size:12.5px; color:var(--text-secondary);">
                            {{ $app->created_at ? $app->created_at->format('d/m/Y') : '-' }}
                        </td>
                        <td style="text-align:center; width:185px;">
                            <div class="approval-actions">
                                {{-- Form Setujui --}}
                                <form id="approve-form-{{ $app->id }}" method="POST" action="{{ route('approvals.approve', $app) }}" style="display:inline; margin:0;">
                                    @csrf 
                                    @method('PATCH')
                                    <button type="button" class="btn btn-success approval-btn" onclick="approveApproval({{ $app->id }}, '{{ $docNum }}', '{{ (!$hasNoAmount) ? number_format($displayAmount, 0, ',', '.') : '' }}')">
                                        <i class="fa-solid fa-check"></i> Setujui
                                    </button>
                                </form>

                                {{-- Form Revisi --}}
                                <button type="button" class="btn btn-warning approval-btn" onclick="reviseApproval({{ $app->id }}, '{{ $docNum }}')" style="color:#78350f; background:#fde68a; border-color:#fcd34d;">
                                    <i class="fa-solid fa-rotate-left"></i> Revisi
                                </button>

                                <form id="revise-form-{{ $app->id }}" method="POST" action="{{ route('approvals.revise', $app) }}" style="display:none;">
                                    @csrf 
                                    @method('PATCH')
                                    <input type="hidden" name="revision_reason" id="revision-reason-{{ $app->id }}" value="">
                                </form>

                                {{-- Form Tolak --}}
                                <button type="button" class="btn btn-danger approval-btn" onclick="rejectApproval({{ $app->id }}, '{{ $docNum }}')">
                                    <i class="fa-solid fa-ban"></i> Tolak
                                </button>

                                <form id="reject-form-{{ $app->id }}" method="POST" action="{{ route('approvals.reject', $app) }}" style="display:none;">
                                    @csrf 
                                    @method('PATCH')
                                    <input type="hidden" name="rejection_reason" id="rejection-reason-{{ $app->id }}" value="">
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:40px; color:var(--text-secondary);">
                            <i class="fa-solid fa-circle-check" style="font-size:32px; color:var(--success); display:block; margin-bottom:12px;"></i>
                            Tidak ada pengajuan approval yang sedang menunggu.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($pendingApprovals->hasPages())
        <div style="padding:14px 20px; border-top:1px solid var(--border);">
            {{ $pendingApprovals->links() }}
        </div>
        @endif
    </div>

    {{-- History Approvals --}}
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0;"><i class="fa-solid fa-clock-rotate-left text-primary" style="margin-right:8px;"></i> Riwayat Approval</h3>
            <span class="badge badge-secondary">{{ $myApprovals->total() }} riwayat</span>
        </div>
        <div class="table-responsive" style="overflow-x:auto;">
            <table class="erp-table approval-table">
                <thead>
                    <tr>
                        <th>No. Request</th>
                        <th style="width:45px; text-align:center;">Tipe</th>
                        <th>Dokumen / Ref</th>
                        <th>Pemohon</th>
                        <th style="text-align:right;">Nilai Transaksi</th>
                        <th style="text-align:center; width:80px;">Status</th>
                        <th>Tgl Diproses</th>
                        <th>Diproses Oleh</th>
                        <th>Catatan / Alasan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($myApprovals as $app)
                    @php
                        $doc = $app->approvable;
                        $docNum = $doc->receipt_number ?? $doc->po_number ?? $doc->so_number ?? $doc->pr_number ?? ($app->approvable_id ? '#'.$app->approvable_id : '-');
                        $docRoute = '#';
                        $typeLabel = 'Dokumen';
                        $badgeClass = 'badge-confirmed';
                        
                        if ($app->approvable_type === 'App\Models\PurchaseRequest') {
                            $docRoute = $app->approvable_id ? route('purchase.requests.show', $app->approvable_id) : '#';
                            $typeLabel = 'PR';
                            $badgeClass = 'badge-warning';
                        } elseif ($app->approvable_type === 'App\Models\PurchaseOrder') {
                            $docRoute = $app->approvable_id ? route('purchase.orders.show', $app->approvable_id) : '#';
                            $typeLabel = 'PO';
                            $badgeClass = 'badge-confirmed';
                        } elseif ($app->approvable_type === 'App\Models\GoodsReceipt') {
                            $docRoute = $app->approvable_id ? route('purchase.goods-receipts.show', $app->approvable_id) : '#';
                            $typeLabel = 'LPB';
                            $badgeClass = 'badge-primary';
                        } elseif ($app->approvable_type === 'App\Models\SalesOrder') {
                            $docRoute = $app->approvable_id ? route('sales.orders.show', $app->approvable_id) : '#';
                            $typeLabel = 'SO';
                            $badgeClass = 'badge-info';
                        }
                        $hasNoAmount = in_array($app->approvable_type, [
                            'App\Models\PurchaseRequest',
                            'App\Models\GoodsReceipt',
                        ]);
                        $displayAmount = (float) (($app->amount > 0) ? $app->amount : ($doc->total_amount ?? 0));
                        
                        $statusBadge = match($app->status) {
                            'approved' => 'badge-done',
                            'revised'  => 'badge-warning',
                            'rejected' => 'badge-cancelled',
                            default    => 'badge-secondary',
                        };
                        $statusLabel = match($app->status) {
                            'approved' => 'Disetujui',
                            'revised'  => 'Revisi',
                            'rejected' => 'Ditolak',
                            default    => ucfirst($app->status),
                        };
                        $processDate = $app->approved_at ?? $app->updated_at;
                    @endphp
                    <tr>
                        <td style="font-weight:600; white-space:nowrap;">{{ $app->request_number }}</td>
                        <td style="text-align:center;">
                            <span class="badge {{ $badgeClass }}" style="font-size:11px; padding:2px 6px;">
                                {{ $typeLabel }}
                            </span>
                        </td>
                        <td style="white-space:nowrap;">
                            @if($docRoute !== '#')
                            <a href="{{ $docRoute }}" style="font-weight:600; color:var(--primary); text-decoration:none;">
                                {{ $docNum }} <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i>
                            </a>
                            @else
                            {{ $docNum }}
                            @endif
                        </td>
                        <td>{{ $app->requester->name ?? '-' }}</td>
                        <td style="text-align:right; white-space:nowrap; font-weight:600;">
                            @if($hasNoAmount)
                                <span style="color:var(--text-secondary); font-weight:400;">-</span>
                            @else
                                Rp {{ number_format($displayAmount, 0, ',', '.') }}
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <span class="badge {{ $statusBadge }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td style="font-size:12.5px; color:var(--text-secondary); white-space:nowrap;">
                            {{ $processDate ? $processDate->format('d/m/Y H:i') : '-' }}
                        </td>
                        <td style="font-size:13px;">{{ $app->approver->name ?? '-' }}</td>
                        <td style="font-size:12.5px; color:var(--text-secondary); max-width:200px;">
                            {{ $app->rejection_reason ?? '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" style="text-align:center; padding:36px; color:var(--text-secondary);">
                            Belum ada riwayat approval.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($myApprovals->hasPages())
        <div style="padding:14px 20px; border-top:1px solid var(--border);">
            {{ $myApprovals->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function approveApproval(id, docNum, amount) {
    const amountText = amount ? ` sebesar <strong style="color:var(--primary);">Rp ${amount}</strong>` : '';
    Swal.fire({
        title: 'Konfirmasi Persetujuan',
        html: `Setujui transaksi <strong>${docNum}</strong>${amountText}?`,
        icon: 'question',
        iconColor: '#16a34a',
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-check" style="margin-right:6px;"></i> Ya, Setujui',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        focusConfirm: true,
        customClass: {
            popup: 'erp-swal-modal',
            confirmButton: 'btn btn-success',
            cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('approve-form-' + id).submit();
        }
    });
}

function reviseApproval(id, docNum) {
    Swal.fire({
        title: 'Minta Revisi Dokumen',
        html: `Dokumen <strong>${docNum}</strong> akan dikembalikan ke status <strong>Draft</strong> untuk direvisi.<br><span style="font-size:12px; color:#64748b;">Pembuat dokumen dapat mengubah dan mengajukannya kembali.</span>`,
        icon: 'warning',
        iconColor: '#f59e0b',
        input: 'textarea',
        inputLabel: 'Catatan / Alasan Revisi (Wajib)',
        inputPlaceholder: 'Tuliskan catatan detail revisi yang harus diperbaiki...',
        inputAttributes: { 'aria-label': 'Catatan Revisi' },
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-rotate-left" style="margin-right:6px;"></i> Kirim Revisi',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        focusConfirm: true,
        customClass: {
            popup: 'erp-swal-modal',
            confirmButton: 'btn btn-warning',
            cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false,
        inputValidator: (value) => {
            if (!value || !value.trim()) {
                return 'Catatan revisi wajib diisi!';
            }
            if (value.trim().length < 3) {
                return 'Catatan revisi minimal 3 karakter!';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('revision-reason-' + id).value = result.value.trim();
            document.getElementById('revise-form-' + id).submit();
        }
    });
}

function rejectApproval(id, docNum) {
    Swal.fire({
        title: 'Konfirmasi Penolakan',
        html: `Yakin ingin menolak transaksi <strong>${docNum}</strong>?<br><span style="font-size:12.5px; color:#64748b;">Dokumen akan dibatalkan (Cancelled).</span>`,
        icon: 'warning',
        iconColor: '#dc2626',
        input: 'textarea',
        inputLabel: 'Alasan Penolakan (Opsional)',
        inputPlaceholder: 'Tuliskan alasan penolakan dokumen (opsional)...',
        inputAttributes: { 'aria-label': 'Alasan Penolakan' },
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-ban" style="margin-right:6px;"></i> Ya, Tolak Dokumen',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        focusCancel: true,
        customClass: {
            popup: 'erp-swal-modal',
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('rejection-reason-' + id).value = (result.value || '').trim();
            document.getElementById('reject-form-' + id).submit();
        }
    });
}
</script>
@endpush
