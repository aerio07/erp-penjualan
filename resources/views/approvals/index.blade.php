@extends('layouts.app')
@section('title', 'Approval Request')
@section('page-title', 'Approval Request')

@section('content')
<div class="animate-in">
    <x-page-header title="Approval Request" subtitle="Persetujuan dokumen Purchase Order dan Sales Order yang melebihi batas nominal limit" />

    {{-- Filter Bar --}}
    <x-list-filter-bar :action="route('approvals.index')" placeholder="Cari No. Request, Catatan..." :showDateFilter="true">
        <select name="approvable_type" class="form-control" style="height:38px; font-size:13px; min-width:160px; border-radius:6px;">
            <option value="">Semua Dokumen</option>
            <option value="App\Models\PurchaseOrder" {{ request('approvable_type') === 'App\Models\PurchaseOrder' ? 'selected' : '' }}>Purchase Order</option>
            <option value="App\Models\SalesOrder" {{ request('approvable_type') === 'App\Models\SalesOrder' ? 'selected' : '' }}>Sales Order</option>
        </select>
    </x-list-filter-bar>

    {{-- Pending Approvals --}}
    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <h3><i class="fa-solid fa-clock" style="color:var(--warning); margin-right:8px;"></i> Menunggu Persetujuan Anda</h3>
            <span class="badge badge-pending">{{ $pendingApprovals->total() }} pengajuan</span>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <x-sortable-header column="request_number" title="No. Request" />
                        <th>Tipe Dokumen</th>
                        <th>Dokumen / Ref</th>
                        <th>Pemohon</th>
                        <x-sortable-header column="amount" title="Nilai Transaksi" align="right" />
                        <x-sortable-header column="created_at" title="Tanggal Pengajuan" />
                        <th style="text-align:center;">Aksi Approval</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingApprovals as $app)
                    @php
                        $doc = $app->approvable;
                        $docNum = $doc->po_number ?? $doc->so_number ?? '#'.$app->approvable_id;
                        $docRoute = $app->approvable_type === 'App\Models\PurchaseOrder' 
                            ? route('purchase.orders.show', $app->approvable_id) 
                            : route('sales.orders.show', $app->approvable_id);
                    @endphp
                    <tr>
                        <td style="font-weight:600; color:var(--primary);">{{ $app->request_number }}</td>
                        <td>
                            <span class="badge badge-confirmed">
                                {{ strtoupper($app->approvable_type === 'App\Models\PurchaseOrder' ? 'Purchase Order' : 'Sales Order') }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ $docRoute }}" style="font-weight:700; font-size:14px; color:var(--primary); text-decoration:none;">
                                {{ $docNum }} <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:11px;"></i>
                            </a>
                        </td>
                        <td>{{ $app->requester->name ?? '-' }}</td>
                        <td style="text-align:right; font-weight:700; color:var(--primary);">
                            Rp {{ number_format($app->amount, 0, ',', '.') }}
                        </td>
                        <td>{{ $app->created_at ? $app->created_at->format('d/m/Y H:i') : '-' }}</td>
                        <td style="text-align:center;">
                            <div style="display:flex; gap:8px; justify-content:center;">
                                {{-- Form Setujui --}}
                                <form id="approve-form-{{ $app->id }}" method="POST" action="{{ route('approvals.approve', $app) }}" style="display:inline;">
                                    @csrf 
                                    @method('PATCH')
                                    <button type="button" class="btn btn-success btn-sm" onclick="approveApproval({{ $app->id }}, '{{ $docNum }}', '{{ number_format($app->amount, 0, ',', '.') }}')">
                                        <i class="fa-solid fa-check"></i> Setujui
                                    </button>
                                </form>

                                {{-- Form Tolak --}}
                                <button type="button" class="btn btn-danger btn-sm" onclick="rejectApproval({{ $app->id }}, '{{ $docNum }}')">
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
                        <td colspan="7" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            <i class="fa-solid fa-circle-check" style="font-size:32px; color:var(--success); display:block; margin-bottom:12px;"></i>
                            Tidak ada pengajuan approval yang sedang menunggu.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($pendingApprovals->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border);">
            {{ $pendingApprovals->links() }}
        </div>
        @endif
    </div>

    {{-- History Approvals --}}
    <div class="card">
        <div class="card-header"><h3>Riwayat Approval Anda</h3></div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>No. Request</th>
                        <th>Tipe Dokumen</th>
                        <th style="text-align:right;">Nilai Transaksi</th>
                        <th>Pemohon</th>
                        <th style="text-align:center;">Status</th>
                        <th>Alasan Penolakan / Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($myApprovals as $app)
                    <tr>
                        <td style="font-weight:600;">{{ $app->request_number }}</td>
                        <td>{{ $app->approvable_type === 'App\Models\PurchaseOrder' ? 'Purchase Order' : 'Sales Order' }}</td>
                        <td style="text-align:right;">Rp {{ number_format($app->amount, 0, ',', '.') }}</td>
                        <td>{{ $app->requester->name ?? '-' }}</td>
                        <td style="text-align:center;">
                            <span class="badge badge-{{ $app->status === 'approved' ? 'done' : 'cancelled' }}">
                                {{ ucfirst($app->status) }}
                            </span>
                        </td>
                        <td>{{ $app->rejection_reason ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:36px; color:var(--text-secondary);">
                            Belum ada riwayat approval.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($myApprovals->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border);">
            {{ $myApprovals->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function approveApproval(id, docNum, amount) {
    Swal.fire({
        title: 'Konfirmasi Persetujuan',
        html: `Setujui transaksi <strong>${docNum}</strong> sebesar <strong style="color:var(--primary);">Rp ${amount}</strong>?`,
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
