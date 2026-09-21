@extends('layouts.app')
@section('title', 'Detail PO - ' . $order->po_number)
@section('page-title', 'Detail Purchase Order')

@section('content')
<div class="animate-in">
    @php
        $latestApproval = $order->approvalRequests->sortByDesc('id')->first();
        $isAdmin = auth()->user()->isAdmin();
    @endphp

    {{-- Hidden forms for workflow actions --}}
    <form id="form-cancel-po" method="POST" action="{{ route('purchase.orders.cancel', $order) }}" style="display:none;">
        @csrf @method('PATCH')
        <input type="hidden" name="cancel_reason" id="po-cancel-reason" value="">
    </form>
    <form id="form-confirm-po" method="POST" action="{{ route('purchase.orders.confirm', $order) }}" style="display:none;">
        @csrf @method('PATCH')
    </form>

    <x-detail-header 
        :title="$order->po_number" 
        :subtitle="$order->supplier->name . ' · ' . $order->order_date->format('d/m/Y')"
        :status="$order->status"
    >
        <x-slot:actions>
            {{-- Workflow buttons (left) --}}
            @if($order->isDraft())
            <button type="button" class="btn btn-danger" onclick="confirmCancelPO()">
                <i class="fa-solid fa-ban"></i> Batalkan PO
            </button>
            <button type="button" class="btn btn-primary"
                data-confirm-swal="form-confirm-po"
                data-swal-title="Konfirmasi Purchase Order"
                data-swal-html="Konfirmasi Purchase Order <strong>{{ $order->po_number }}</strong> sekarang?<br><span style='font-size:12.5px; color:#64748b;'>PO akan dikirim untuk persetujuan (approval) sebelum dapat diproses.</span>"
                data-swal-icon="question"
                data-swal-icon-color="#2563eb"
                data-swal-btn-text="Ya, Konfirmasi PO"
                data-swal-btn-icon="fa-solid fa-check"
                data-swal-btn-class="btn btn-primary"
            >
                <i class="fa-solid fa-check"></i> Konfirmasi PO
            </button>
            @elseif($order->isWaitingApproval())
                @if($isAdmin)
                <a href="{{ route('approvals.index') }}" class="btn btn-secondary">
                    <i class="fa-solid fa-stamp"></i> Buka Menu Approval
                </a>
                @endif
                <button type="button" class="btn btn-danger" onclick="confirmCancelPO()">
                    <i class="fa-solid fa-ban"></i> Batalkan PO
                </button>
            @elseif($order->status->canReceiveGoods())
                <a href="{{ route('purchase.goods-receipts.create', ['po_id' => $order->id]) }}" class="btn btn-primary">
                    <i class="fa-solid fa-truck-ramp-box"></i> Terima Barang
                </a>
            @endif

            {{-- Divider --}}
            @if(
                $order->isDraft() ||
                $order->isWaitingApproval() ||
                $order->status->canReceiveGoods()
            )
            <div style="width:1px; height:28px; background:var(--border); margin:0 4px;"></div>
            @endif

            {{-- Utility / Nav buttons (right) --}}
            
            <a href="{{ route('purchase.orders.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
            @if(in_array($order->status, [\App\Enums\PurchaseOrderStatus::Confirmed, \App\Enums\PurchaseOrderStatus::PartiallyReceived, \App\Enums\PurchaseOrderStatus::Done]))
            <a href="{{ route('pdf.purchase-order', $order) }}" class="btn btn-secondary" target="_blank"><i class="fa-solid fa-file-pdf"></i> PDF</a>
            @endif
        </x-slot:actions>
    </x-detail-header>

    {{-- Banner: Permintaan Revisi dari Approver --}}
    @if($order->isDraft() && $latestApproval && $latestApproval->status === 'revised')
    <div class="card" style="margin-bottom:20px; border-left: 4px solid #f59e0b; background: #fffdf7; box-shadow: 0 2px 6px rgba(245, 158, 11, 0.08);">
        <div class="card-body" style="padding:16px 20px; display:flex; gap:14px; align-items:flex-start;">
            <div style="width:40px; height:40px; border-radius:10px; background:#fef3c7; color:#b45309; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0;">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div style="flex:1;">
                <div style="font-weight:700; color:#92400e; font-size:15px;">
                    Permintaan Revisi dari Approver ({{ $latestApproval->approver->name ?? 'Admin' }})
                </div>
                <div style="color:#78350f; font-size:13.5px; margin-top:6px; padding:10px 14px; background:#fffbeb; border:1px dashed #fcd34d; border-radius:8px;">
                    <i class="fa-solid fa-comment-dots" style="margin-right:6px; color:#d97706;"></i>
                    <strong>Catatan Revisi:</strong> {{ $latestApproval->rejection_reason ?: 'Harap periksa kembali detail pesanan.' }}
                </div>
                <div style="margin-top:8px; font-size:12.5px; color:#92400e;">
                    <i class="fa-solid fa-clock" style="margin-right:4px;"></i>
                    Diminta pada {{ $latestApproval->updated_at->format('d/m/Y H:i') }}
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Banner Status: Dibatalkan (Cancelled) --}}
    @if($order->isCancelled())
    @php
        $reasonText = $order->cancel_reason ?: ($latestApproval->rejection_reason ?? null);
        if (!$reasonText && $order->notes && preg_match('/\[Alasan (?:Pembatalan|Penolakan)\]:\s*(.+)/', $order->notes, $matches)) {
            $reasonText = trim($matches[1]);
        }
        $isRejectedByAdmin = ($latestApproval && $latestApproval->status === 'rejected');
    @endphp
    <div class="card" style="margin-bottom:20px; border-left: 4px solid #ef4444; background: #fffbfb; box-shadow: 0 2px 6px rgba(239, 68, 68, 0.08);">
        <div class="card-body" style="padding:16px 20px; display:flex; gap:14px; align-items:flex-start;">
            <div style="width:40px; height:40px; border-radius:10px; background:#fee2e2; color:#b91c1c; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0;">
                <i class="fa-solid fa-ban"></i>
            </div>
            <div style="flex:1;">
                <div style="font-weight:700; color:#991b1b; font-size:15px;">
                    @if($isRejectedByAdmin)
                        Purchase Order Ditolak (Rejected) oleh {{ $latestApproval->approver->name ?? 'Admin' }}
                    @else
                        Purchase Order Dibatalkan (Cancelled)
                    @endif
                </div>
                @if($reasonText)
                <div style="color:#7f1d1d; font-size:13.5px; margin-top:6px; padding:10px 14px; background:#fef2f2; border:1px dashed #fca5a5; border-radius:8px;">
                    <i class="fa-solid fa-comment-slash" style="margin-right:6px; color:#ef4444;"></i>
                    <strong>Alasan:</strong> {{ $reasonText }}
                </div>
                @endif
                <div style="color:#991b1b; font-size:12.5px; margin-top:8px;">
                    <i class="fa-solid fa-circle-info" style="margin-right:4px;"></i>
                    Dokumen ini telah dibatalkan secara permanen dan tidak dapat diproses lagi.
                </div>
            </div>
        </div>
    </div>
    @endif


    <div class="grid grid-3" style="margin-bottom:20px;">
        {{-- Info PO --}}
        <div class="card" style="grid-column:span 2;">
            <div class="card-header">
                <h3>Informasi Purchase Order</h3>
                <x-status-badge :status="$order->status" />
            </div>
            <div class="card-body">
                <div class="form-row form-row-2">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Supplier</div>
                        <div style="font-weight:600;">{{ $order->supplier->name }}</div>
                        <div style="font-size:13px; color:var(--text-secondary);">{{ $order->supplier->contact_person }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Dibuat Oleh</div>
                        <div style="font-weight:600;">{{ $order->user->name }}</div>
                        <div style="font-size:13px; color:var(--text-secondary);">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Tanggal PO</div>
                        <div style="font-weight:600;">{{ $order->order_date->format('d/m/Y') }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Expected Delivery</div>
                        <div style="font-weight:600;">{{ $order->expected_date ? $order->expected_date->format('d/m/Y') : '-' }}</div>
                    </div>
                    @if($order->purchaseRequest)
                    <div style="grid-column: span 2;">
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Referensi Purchase Request (PR)</div>
                        <div style="font-weight:600;">
                            <a href="{{ route('purchase.requests.show', $order->purchaseRequest) }}" style="color:var(--primary); text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                                <i class="fa-solid fa-file-invoice"></i> {{ $order->purchaseRequest->pr_number }}
                            </a>
                            <span style="font-size:12px; color:var(--text-secondary); margin-left:6px; font-weight:normal;">(Divisi {{ $order->purchaseRequest->division ?? 'Umum' }})</span>
                        </div>
                    </div>
                    @endif
                </div>
                @if($order->notes)
                <div style="margin-top:14px; padding:12px; background:#f8fafc; border-radius:10px; font-size:14px; color:var(--text-secondary);">
                    <i class="fa-solid fa-note-sticky" style="margin-right:6px;"></i>{{ $order->notes }}
                </div>
                @endif
            </div>
        </div>

        {{-- Total Summary --}}
        <div class="card">
            <div class="card-header"><h3>Total</h3></div>
            <div class="card-body">
                <div style="display:flex; flex-direction:column; gap:10px; font-size:14px;">
                    @php
                        $itemDiscountSum = $order->items->sum(function($item) {
                            return ($item->qty_ordered * $item->unit_price) * (($item->discount_percent ?? 0) / 100);
                        });
                        $grossSubtotal = $order->items->sum(function($item) {
                            return $item->qty_ordered * $item->unit_price;
                        });
                    @endphp
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:var(--text-secondary);">Subtotal Item (Sebelum Diskon)</span>
                        <span>Rp {{ number_format($grossSubtotal, 0, ',', '.') }}</span>
                    </div>
                    @if($itemDiscountSum > 0)
                    <div style="display:flex; justify-content:space-between; color:var(--danger);">
                        <span>Diskon Item</span>
                        <span>- Rp {{ number_format($itemDiscountSum, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    @if($order->discount_amount > 0)
                    <div style="display:flex; justify-content:space-between; color:var(--danger);">
                        <span>Diskon Header</span>
                        <span>- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    @if($itemDiscountSum > 0 && $order->discount_amount > 0)
                    <div style="display:flex; justify-content:space-between; font-weight:600; color:var(--danger); font-size:13px; background:#fef2f2; padding:4px 8px; border-radius:6px;">
                        <span>Total Seluruh Diskon</span>
                        <span>- Rp {{ number_format($itemDiscountSum + $order->discount_amount, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:var(--text-secondary);">PPN ({{ $order->tax_rate }}%)</span>
                        <span>Rp {{ number_format($order->tax_amount, 0, ',', '.') }}</span>
                    </div>
                    <hr style="border:none; border-top:1px solid var(--border);">
                    <div style="display:flex; justify-content:space-between; font-size:17px; font-weight:700;">
                        <span>Total</span>
                        <span style="color:var(--primary);">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                    </div>
                </div>

                {{-- GRN Status --}}
                @if($order->goodsReceipts->count() > 0)
                <div style="margin-top:16px; padding:12px; background:#ede9fe; border-radius:10px;">
                    <div style="font-size:12px; font-weight:600; color:#7c3aed; margin-bottom:4px;">GRN Terkait</div>
                    @foreach($order->goodsReceipts as $grn)
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                        <a href="{{ route('purchase.goods-receipts.show', $grn) }}" style="font-size:13px; color:#4c1d95; text-decoration:none; font-weight:500;">
                            <i class="fa-solid fa-file-lines"></i> {{ $grn->receipt_number }} · {{ $grn->received_date->format('d/m/Y') }}
                        </a>
                        @if($grn->is_invoiced)
                            <span class="badge badge-done" style="font-size:10px; padding:2px 8px;">Sudah Diinvoice</span>
                        @else
                            <span class="badge badge-pending" style="font-size:10px; padding:2px 8px;">Belum Diinvoice</span>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Item Table --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><h3>Item Pesanan</h3></div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th style="text-align:center;">Qty Pesan</th>
                        <th style="text-align:center;">Tiba Fisik (Datang)</th>
                        <th style="text-align:center;">Sisa Belum Dikirim</th>
                        <th style="text-align:right;">Harga Satuan</th>
                        <th style="text-align:center;">Diskon</th>
                        <th style="text-align:right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>
                            <div style="font-weight:600;">{{ $item->product->name }}</div>
                            <div style="font-size:12px; color:var(--text-secondary);">{{ $item->product->sku }}</div>
                        </td>
                        <td style="text-align:center; font-weight:600;">{{ $item->qty_ordered }} {{ $item->product->unit }}</td>
                        <td style="text-align:center;">
                            <span style="font-weight:600;">{{ $item->qty_arrived }}</span>
                            <div style="font-size:11px; color:var(--text-secondary);">
                                (Baik: <span style="color:var(--success); font-weight:600;">{{ $item->qty_received }}</span>
                                @if($item->qty_rejected > 0)
                                , Rusak: <span style="color:var(--danger); font-weight:600;">{{ $item->qty_rejected }}</span>
                                @endif)
                            </div>
                        </td>
                        <td style="text-align:center;">
                            <span style="{{ $item->qty_remaining > 0 ? 'color:var(--warning); font-weight:600;' : 'color:var(--success); font-weight:600;' }}">
                                {{ $item->qty_remaining }}
                            </span>
                        </td>
                        <td style="text-align:right;">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                        <td style="text-align:center;">
                            @if($item->discount_percent > 0)
                                @php
                                    $itemDiscRp = ($item->qty_ordered * $item->unit_price) * ($item->discount_percent / 100);
                                @endphp
                                <span style="font-weight:600;">{{ $item->discount_percent }}%</span>
                                <div style="font-size:11px; color:var(--danger); margin-top:2px;">
                                    -Rp {{ number_format($itemDiscRp, 0, ',', '.') }}
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td style="text-align:right; font-weight:600;">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Invoice terkait --}}
    @if($order->invoices->count() > 0)
    <div class="card">
        <div class="card-header"><h3>Invoice Pembelian Terkait</h3></div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>No. Invoice</th>
                        <th>Tgl Invoice</th>
                        <th>Jatuh Tempo</th>
                        <th style="text-align:right;">Total</th>
                        <th style="text-align:center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->invoices as $inv)
                    <tr>
                        <td>
                            <a href="{{ route('purchase.invoices.show', $inv) }}" style="font-weight:600; color:var(--primary); text-decoration:none;">
                                {{ $inv->invoice_number }}
                            </a>
                            @if($inv->supplier_invoice_number)
                                <div style="font-size:11px; color:var(--text-secondary);">Inv Sup: {{ $inv->supplier_invoice_number }}</div>
                            @endif
                        </td>
                        <td>{{ $inv->invoice_date->format('d/m/Y') }}</td>
                        <td>{{ $inv->due_date->format('d/m/Y') }}</td>
                        <td style="text-align:right; font-weight:600;">Rp {{ number_format($inv->total_amount, 0, ',', '.') }}</td>
                        <td style="text-align:center;"><span class="badge {{ $inv->status_badge }}">{{ $inv->status_label }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif


</div>
@endsection

@push('scripts')
<script>
function confirmCancelPO() {
    Swal.fire({
        title: 'Batalkan Purchase Order',
        html: `Apakah Anda yakin ingin membatalkan Purchase Order <strong>{{ $order->po_number }}</strong>?<br><span style="font-size:12.5px; color:#64748b;">Status PO akan diubah menjadi <strong>Dibatalkan (Cancelled)</strong>.</span>`,
        icon: 'warning',
        iconColor: '#dc2626',
        input: 'textarea',
        inputLabel: 'Alasan Pembatalan (Wajib Diisi)',
        inputPlaceholder: 'Tuliskan alasan mengapa Purchase Order ini dibatalkan...',
        inputAttributes: { 'aria-label': 'Alasan Pembatalan' },
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-ban" style="margin-right:6px;"></i> Ya, Batalkan PO',
        cancelButtonText: 'Kembali',
        reverseButtons: true,
        customClass: {
            popup: 'erp-swal-modal',
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false,
        inputValidator: (value) => {
            if (!value || !value.trim()) {
                return 'Alasan pembatalan Purchase Order wajib diisi!';
            }
            if (value.trim().length < 3) {
                return 'Alasan pembatalan minimal 3 karakter!';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('po-cancel-reason').value = result.value.trim();
            document.getElementById('form-cancel-po').submit();
        }
    });
}
</script>
@endpush
