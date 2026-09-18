@extends('layouts.app')
@section('title', 'Approval')
@section('page-title', 'Approval')

@section('content')
<div class="animate-in">
    <x-page-header title="Approval" subtitle="Persetujuan dokumen Purchase Request, Purchase Order, dan Sales Order" />

    {{-- Filter Bar --}}
    <x-list-filter-bar :action="route('approvals.index')" placeholder="Cari No. Request, Catatan..." :showDateFilter="true">
        <select name="approvable_type" class="form-control" style="height:38px; font-size:13px; min-width:160px; border-radius:6px;">
            <option value="">Semua Dokumen</option>
            <option value="App\Models\PurchaseRequest" {{ request('approvable_type') === 'App\Models\PurchaseRequest' ? 'selected' : '' }}>Purchase Request</option>
            <option value="App\Models\PurchaseOrder" {{ request('approvable_type') === 'App\Models\PurchaseOrder' ? 'selected' : '' }}>Purchase Order</option>
            <option value="App\Models\SalesOrder" {{ request('approvable_type') === 'App\Models\SalesOrder' ? 'selected' : '' }}>Sales Order</option>
        </select>
    </x-list-filter-bar>

    {{-- Pending Approvals --}}
    <div class="card" style="margin-bottom:24px;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0;"><i class="fa-solid fa-clock" style="color:var(--warning); margin-right:8px;"></i> Menunggu Persetujuan Anda</h3>
            <span class="badge badge-pending">{{ $pendingApprovals->total() }} pengajuan</span>
        </div>
        <div class="table-responsive" style="overflow-x:hidden;">
            <table class="erp-table" style="min-width:100% !important; table-layout:auto; width:100%;">
                <thead>
                    <tr>
                        <x-sortable-header column="request_number" title="No. Request" />
                        <th>Tipe</th>
                        <th>Dokumen / Ref</th>
                        <th>Pemohon</th>
                        <x-sortable-header column="amount" title="Nilai Transaksi" align="right" />
                        <x-sortable-header column="created_at" title="Tgl Pengajuan" />
                        <th style="text-align:center; width:220px;">Aksi Approval</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingApprovals as $app)
                    @php
                        $doc = $app->approvable;
                        $docNum = $doc->po_number ?? $doc->so_number ?? $doc->pr_number ?? '#'.$app->approvable_id;
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
                        } elseif ($app->approvable_type === 'App\Models\SalesOrder') {
                            $docRoute = route('sales.orders.show', $app->approvable_id);
                            $docTypeLabel = 'SO';
                            $badgeClass = 'badge-info';
                        }
                        $isPr = ($app->approvable_type === 'App\Models\PurchaseRequest');
                    @endphp
                    <tr>
                        <td style="font-weight:600; color:var(--primary); white-space:nowrap; padding:10px 12px;">{{ $app->request_number }}</td>
                        <td style="padding:10px 12px;">
                            <span class="badge {{ $badgeClass }}" style="font-size:11px; padding:2px 6px;">
                                {{ $docTypeLabel }}
                            </span>
                        </td>
                        <td style="padding:10px 12px; white-space:nowrap;">
                            <a href="{{ $docRoute }}" style="font-weight:700; font-size:13.5px; color:var(--primary); text-decoration:none;">
                                {{ $docNum }} <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i>
                            </a>
                        </td>
                        <td style="padding:10px 12px;">{{ $app->requester->name ?? '-' }}</td>
                        <td style="text-align:right; font-weight:700; color:var(--primary); padding:10px 12px; white-space:nowrap;">
                            @if($isPr)
                                <span style="color:var(--text-secondary); font-weight:400;">-</span>
                            @elseif($app->amount > 0)
                                Rp {{ number_format($app->amount, 0, ',', '.') }}
                            @else
                                <span style="font-size:12px; color:var(--text-secondary); font-weight:400;">-</span>
                            @endif
                        </td>
                        <td style="padding:10px 12px; white-space:nowrap; font-size:12.5px; color:var(--text-secondary);">
                            {{ $app->created_at ? $app->created_at->format('d/m/Y H:i') : '-' }}
                        </td>
                        <td style="text-align:center; padding:10px 12px;">
                            <div style="display:flex; gap:4px; justify-content:center; align-items:center;">
                                {{-- Form Setujui --}}
                                <form id="approve-form-{{ $app->id }}" method="POST" action="{{ route('approvals.approve', $app) }}" style="display:inline;">
                                    @csrf 
                                    @method('PATCH')
                                    <button type="button" class="btn btn-success btn-sm" onclick="approveApproval({{ $app->id }}, '{{ $docNum }}', '{{ (!$isPr && $app->amount > 0) ? number_format($app->amount, 0, ',', '.') : '' }}')" style="padding:4px 8px; font-size:11.5px; border-radius:5px; font-weight:600; white-space:nowrap;">
                                        <i class="fa-solid fa-check"></i> Setujui
                                    </button>
                                </form>

                                {{-- Form Revisi --}}
                                <button type="button" class="btn btn-warning btn-sm" onclick="reviseApproval({{ $app->id }}, '{{ $docNum }}')" style="padding:4px 8px; font-size:11.5px; border-radius:5px; font-weight:600; color:#78350f; background:#fde68a; border-color:#fcd34d; white-space:nowrap;">
                                    <i class="fa-solid fa-rotate-left"></i> Revisi
                                </button>

                                <form id="revise-form-{{ $app->id }}" method="POST" action="{{ route('approvals.revise', $app) }}" style="display:none;">
                                    @csrf 
                                    @method('PATCH')
                                    <input type="hidden" name="revision_reason" id="revision-reason-{{ $app->id }}" value="">
                                </form>

                                {{-- Form Tolak --}}
                                <button type="button" class="btn btn-danger btn-sm" onclick="rejectApproval({{ $app->id }}, '{{ $docNum }}')" style="padding:4px 8px; font-size:11.5px; border-radius:5px; font-weight:600; white-space:nowrap;">
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
        <div class="table-responsive" style="overflow-x:hidden;">
            <table class="erp-table" style="min-width:100% !important; table-layout:auto; width:100%;">
                <thead>
                    <tr>
                        <th>No. Request</th>
                        <th>Tipe</th>
                        <th>Dokumen / Ref</th>
                        <th>Pemohon</th>
                        <th style="text-align:right;">Nilai Transaksi</th>
                        <th style="text-align:center;">Status</th>
                        <th>Tgl Diproses</th>
                        <th>Diproses Oleh</th>
                        <th>Catatan / Alasan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($myApprovals as $app)
                    @php
                        $doc = $app->approvable;
                        $docNum = $doc->po_number ?? $doc->so_number ?? $doc->pr_number ?? ($app->approvable_id ? '#'.$app->approvable_id : '-');
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
                        } elseif ($app->approvable_type === 'App\Models\SalesOrder') {
                            $docRoute = $app->approvable_id ? route('sales.orders.show', $app->approvable_id) : '#';
                            $typeLabel = 'SO';
                            $badgeClass = 'badge-info';
                        }
                        $isPr = ($app->approvable_type === 'App\Models\PurchaseRequest');
                        
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
                        <td style="font-weight:600; padding:10px 12px; white-space:nowrap;">{{ $app->request_number }}</td>
                        <td style="padding:10px 12px;">
                            <span class="badge {{ $badgeClass }}" style="font-size:11px; padding:2px 6px;">
                                {{ $typeLabel }}
                            </span>
                        </td>
                        <td style="padding:10px 12px; white-space:nowrap;">
                            @if($docRoute !== '#')
                            <a href="{{ $docRoute }}" style="font-weight:600; color:var(--primary); text-decoration:none;">
                                {{ $docNum }} <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i>
                            </a>
                            @else
                            {{ $docNum }}
                            @endif
                        </td>
                        <td style="padding:10px 12px;">{{ $app->requester->name ?? '-' }}</td>
                        <td style="text-align:right; padding:10px 12px; white-space:nowrap; font-weight:600;">
                            @if($isPr)
                                <span style="color:var(--text-secondary); font-weight:400;">-</span>
                            @elseif($app->amount > 0)
                                Rp {{ number_format($app->amount, 0, ',', '.') }}
                            @else
                                <span style="color:var(--text-secondary); font-weight:400;">-</span>
                            @endif
                        </td>
                        <td style="text-align:center; padding:10px 12px;">
                            <span class="badge {{ $statusBadge }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td style="padding:10px 12px; font-size:12.5px; color:var(--text-secondary); white-space:nowrap;">
                            {{ $processDate ? $processDate->format('d/m/Y H:i') : '-' }}
                        </td>
                        <td style="padding:10px 12px; font-size:13px;">{{ $app->approver->name ?? '-' }}</td>
                        <td style="padding:10px 12px; font-size:12.5px; color:var(--text-secondary); max-width:200px;">
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
