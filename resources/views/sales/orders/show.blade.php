@extends('layouts.app')
@section('title', 'Detail SO - ' . $order->so_number)
@section('page-title', 'Detail Sales Order')

@section('content')
<div class="animate-in">
    @php
        $latestApproval = $order->approvalRequests->sortByDesc('id')->first();
        $isAdmin = auth()->user()->isAdmin();
        $canCreateDel = $order->canCreateDelivery();
        $hasUninvoicedDel = $order->deliveries->where('is_invoiced', false)->where('status', 'delivered')->count() > 0;
    @endphp

    <x-detail-header 
        :title="$order->so_number"
        :subtitle="($order->customer->name ?? '-') . ' · ' . $order->order_date->format('d/m/Y') . ' · Channel: ' . ucfirst($order->channel ?? 'offline')"
        :status="$order->status"
    >
        <x-slot:actions>
            {{-- Workflow Status Action Buttons (Kiri) --}}
            @if($order->status === 'draft')
                <button type="button" class="btn btn-danger"
                    data-confirm-swal="form-cancel-so"
                    data-swal-title="Batalkan Sales Order"
                    data-swal-html="Yakin ingin membatalkan Sales Order <strong>{{ $order->so_number }}</strong>?<br><span style='font-size:12.5px; color:#64748b;'>Status SO akan diubah menjadi Cancelled.</span>"
                    data-swal-icon="warning"
                    data-swal-btn-text="Ya, Batalkan SO"
                    data-swal-btn-icon="fa-solid fa-ban"
                    data-swal-btn-class="btn btn-danger"
                >
                    <i class="fa-solid fa-ban"></i> Batalkan SO
                </button>

                <button type="button" class="btn btn-primary"
                    data-confirm-swal="form-confirm-so"
                    data-swal-title="Konfirmasi Sales Order"
                    data-swal-html="Konfirmasi Sales Order <strong>{{ $order->so_number }}</strong> sekarang?<br><span style='font-size:12.5px; color:#64748b;'>Sales Order akan dikirim untuk persetujuan (approval) sebelum dapat diproses.</span>"
                    data-swal-icon="question"
                    data-swal-icon-color="#2563eb"
                    data-swal-btn-text="Ya, Konfirmasi SO"
                    data-swal-btn-icon="fa-solid fa-check"
                    data-swal-btn-class="btn btn-primary"
                >
                    <i class="fa-solid fa-check"></i> Konfirmasi SO Sekarang
                </button>
            @elseif($order->status === 'confirmed')
                @if($canCreateDel)
                <a href="{{ route('sales.deliveries.create', ['so_id' => $order->id]) }}" class="btn btn-primary">
                    <i class="fa-solid fa-truck-fast"></i> Buat Surat Jalan
                </a>
                @endif
            @elseif($order->status === 'partially_delivered')
                @if($canCreateDel)
                <a href="{{ route('sales.deliveries.create', ['so_id' => $order->id]) }}" class="btn btn-primary">
                    <i class="fa-solid fa-truck-fast"></i> Buat Surat Jalan (Sisa)
                </a>
                @endif
                @if($hasUninvoicedDel)
                <a href="{{ route('sales.invoices.create', ['so_id' => $order->id]) }}" class="btn btn-success">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Terbitkan Invoice
                </a>
                @endif
            @endif

            {{-- Divider --}}
            @if(
                $order->status === 'draft' || 
                ($order->status === 'confirmed' && $canCreateDel) || 
                ($order->status === 'partially_delivered' && ($canCreateDel || $hasUninvoicedDel))
            )
            <div style="width: 1px; height: 26px; background: #cbd5e1; margin: 0 4px;"></div>
            @endif

            {{-- Utilities (Kanan): Kembali & PDF --}}

            <a href="{{ route('sales.orders.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>

            @if(in_array($order->status, ['confirmed', 'partially_delivered', 'done']))
            <a href="{{ route('pdf.sales-order', $order) }}" class="btn btn-secondary" target="_blank">
                <i class="fa-solid fa-file-pdf"></i> PDF
            </a>
            @endif
        </x-slot:actions>
    </x-detail-header>

    {{-- Banner Status: Perlu Revisi (Draft dengan riwayat Revisi) --}}
    @if($order->status === 'draft' && $latestApproval && $latestApproval->status === 'revised')
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
                <div style="color:#b45309; font-size:12.5px; margin-top:8px;">
                    <i class="fa-solid fa-circle-info" style="margin-right:4px;"></i>
                    Silakan edit pesanan melalui daftar Sales Order, lalu klik <strong>"Konfirmasi SO Sekarang"</strong> untuk mengajukan ulang.
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Banner Status: Ditolak (Cancelled dengan riwayat Penolakan) --}}
    @if($order->status === 'cancelled' && $latestApproval && $latestApproval->status === 'rejected')
    <div class="card" style="margin-bottom:20px; border-left: 4px solid #ef4444; background: #fffbfb; box-shadow: 0 2px 6px rgba(239, 68, 68, 0.08);">
        <div class="card-body" style="padding:16px 20px; display:flex; gap:14px; align-items:flex-start;">
            <div style="width:40px; height:40px; border-radius:10px; background:#fee2e2; color:#b91c1c; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0;">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <div style="flex:1;">
                <div style="font-weight:700; color:#991b1b; font-size:15px;">
                    Sales Order Ditolak (Rejected) oleh {{ $latestApproval->approver->name ?? 'Admin' }}
                </div>
                @if($latestApproval->rejection_reason)
                <div style="color:#7f1d1d; font-size:13.5px; margin-top:6px; padding:10px 14px; background:#fef2f2; border:1px dashed #fca5a5; border-radius:8px;">
                    <i class="fa-solid fa-comment-slash" style="margin-right:6px; color:#ef4444;"></i>
                    <strong>Alasan Penolakan:</strong> {{ $latestApproval->rejection_reason }}
                </div>
                @endif
                <div style="color:#991b1b; font-size:12.5px; margin-top:8px;">
                    <i class="fa-solid fa-ban" style="margin-right:4px;"></i>
                    Dokumen ini telah dibatalkan secara permanen dan tidak dapat diproses lagi.
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-3" style="margin-bottom:20px;">
        <div class="card" style="grid-column:span 2;">
            <div class="card-header">
                <h3>Informasi Sales Order</h3>
                <div style="display:flex; gap:6px; align-items:center;">
                    @if($order->channel === 'online')
                        <span class="badge" style="background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe;">
                            <i class="fa-solid fa-globe" style="font-size:10px;"></i> Online
                        </span>
                    @else
                        <span class="badge" style="background:#dcfce7; color:#166534; border:1px solid #bbf7d0;">
                            <i class="fa-solid fa-store" style="font-size:10px;"></i> Offline
                        </span>
                    @endif
                    <x-status-badge :status="$order->status" />
                    @php
                        $fBadge = match($order->fulfillment_status) {
                            'ready_to_ship'       => 'background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;',
                            'partially_available' => 'background:#fef3c7; color:#b45309; border:1px solid #fde68a;',
                            'backorder'           => 'background:#fee2e2; color:#b91c1c; border:1px solid #fecaca;',
                            'partially_delivered' => 'background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;',
                            'delivered'           => 'background:#d1fae5; color:#047857; border:1px solid #a7f3d0;',
                            default               => 'background:#f1f5f9; color:#475569; border:1px solid #e2e8f0;'
                        };
                        $fLabel = match($order->fulfillment_status) {
                            'ready_to_ship'       => 'Ready to Ship',
                            'partially_available' => 'Partial Available',
                            'backorder'           => 'Backorder (PO)',
                            'partially_delivered' => 'Partially Delivered',
                            'delivered'           => 'Delivered',
                            default               => 'Pending'
                        };
                    @endphp
                    <span style="display:inline-block; font-size:11px; font-weight:700; text-transform:uppercase; padding:3px 8px; border-radius:9999px; {{ $fBadge }}">
                        Fulfillment: {{ $fLabel }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="form-row form-row-2">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Customer</div>
                        <div style="font-weight:600;">{{ $order->customer->name ?? '-' }}</div>
                        <div style="font-size:13px; color:var(--text-secondary);">{{ $order->customer->phone ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Sales Representative</div>
                        <div style="font-weight:600;">{{ $order->user->name ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Tanggal Order</div>
                        <div style="font-weight:600;">{{ $order->order_date->format('d/m/Y') }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Target Pengiriman</div>
                        <div style="font-weight:600;">{{ $order->expected_delivery_date ? $order->expected_delivery_date->format('d/m/Y') : '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Ekspedisi / Pengiriman</div>
                        <div style="font-weight:600;">{{ $order->expedition ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Sopir & Kendaraan</div>
                        <div style="font-weight:600;">{{ $order->driver_name ?? '-' }} @if($order->vehicle_number) ({{ $order->vehicle_number }}) @endif</div>
                    </div>
                </div>

                @if($order->notes)
                <div style="margin-top:16px; padding:12px; background:#f8fafc; border-radius:10px; font-size:13.5px; color:var(--text-secondary);">
                    <i class="fa-solid fa-note-sticky" style="margin-right:6px;"></i> {{ $order->notes }}
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-receipt text-primary"></i> Ringkasan & Total</h3>
            </div>
            <div class="card-body">
                @php
                    $grossSubtotal = $order->items->sum(fn($it) => $it->qty_ordered * $it->unit_price);
                    $itemDiscountSum = $order->items->sum(fn($it) => ($it->qty_ordered * $it->unit_price) * (($it->discount_percent ?? 0) / 100));
                    $netSubtotal = $grossSubtotal - $itemDiscountSum;
                    $taxableAmount = max(0, $netSubtotal - ($order->discount_amount ?? 0));
                @endphp
                <div style="display:flex; flex-direction:column; gap:10px; font-size:14px;">
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:var(--text-secondary);">Subtotal Item (Sebelum Diskon)</span>
                        <span style="font-weight:600;">Rp {{ number_format($grossSubtotal, 0, ',', '.') }}</span>
                    </div>
                    @if($itemDiscountSum > 0)
                    <div style="display:flex; justify-content:space-between; color:var(--danger); font-size:13.5px;">
                        <span>Total Diskon Item</span>
                        <span style="font-weight:600;">- Rp {{ number_format($itemDiscountSum, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    @if(($order->discount_amount ?? 0) > 0)
                    <div style="display:flex; justify-content:space-between; color:var(--danger); font-size:13.5px;">
                        <span>Diskon Header</span>
                        <span style="font-weight:600;">- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:var(--text-secondary);">Setelah Diskon</span>
                        <span>Rp {{ number_format($taxableAmount, 0, ',', '.') }}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:var(--text-secondary);">PPN ({{ $order->tax_rate }}%)</span>
                        <span>Rp {{ number_format($order->tax_amount, 0, ',', '.') }}</span>
                    </div>
                    <hr style="border:none; border-top:1px solid var(--border); margin:0;">
                    <div style="display:flex; justify-content:space-between; font-size:17px; font-weight:700;">
                        <span>Total</span>
                        <span style="color:var(--primary);">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $isDraftOrApproval = in_array($order->status, ['draft', 'waiting_approval']);
        $whAllocations = [];
        $totalActiveReserved = 0;
        $totalActiveDemanded = 0;

        if (!$isDraftOrApproval) {
            foreach($order->items as $it) {
                $totalActiveDemanded += (int) $it->qty_demanded;
                foreach($it->reservations->where('status', 'active') as $res) {
                    $act = max(0, $res->qty_reserved - $res->qty_delivered);
                    if ($act > 0) {
                        $wName = $res->warehouse->name ?? ('Gudang #' . $res->warehouse_id);
                        $whAllocations[$wName] = ($whAllocations[$wName] ?? 0) + $act;
                        $totalActiveReserved += $act;
                    }
                }
            }
        }
    @endphp

    {{-- Multi-Warehouse Allocation & Fulfillment Breakdown Banner (Hanya muncul jika SO sudah Confirmed / Eksekusi) --}}
    @if(!$isDraftOrApproval && (count($whAllocations) > 0 || $totalActiveDemanded > 0))
    <div class="card" style="margin-bottom:20px; border-left: 4px solid var(--primary); background: #f8fafc;">
        <div class="card-body" style="padding:14px 18px;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                <div>
                    <h4 style="margin:0; font-size:14px; font-weight:700; color:var(--text-primary); display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-boxes-stacked" style="color:var(--primary);"></i>
                        Rincian Lokasi Alokasi Stok Fisik
                        @if(count($whAllocations) > 1)
                            <span class="badge" style="background:#e0f2fe; color:#0369a1; font-size:11px; font-weight:700;">Multi-Gudang</span>
                        @endif
                    </h4>
                    <p style="margin:4px 0 0 0; font-size:12.5px; color:var(--text-secondary);">
                        @if(count($whAllocations) > 1)
                            Pesanan ini mengambil stok dari <strong>{{ count($whAllocations) }} lokasi gudang berbeda</strong>. Anda dapat menerbitkan Surat Jalan terpisah per gudang atau melakukan Transfer Antar Gudang terlebih dahulu.
                        @else
                            Pesanan ini dialokasikan dari gudang perusahaan berikut:
                        @endif
                    </p>
                </div>
                @if($order->canCreateDelivery())
                <a href="{{ route('sales.deliveries.create', ['so_id' => $order->id]) }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-truck-fast"></i> Buat Surat Jalan
                </a>
                @endif
            </div>

            <div style="display:flex; gap:10px; margin-top:12px; flex-wrap:wrap;">
                @foreach($whAllocations as $wName => $qty)
                <div style="background:#ffffff; border:1px solid var(--border); border-radius:6px; padding:6px 12px; font-size:13px; font-weight:600; box-shadow:0 1px 2px rgba(0,0,0,0.03);">
                    <i class="fa-solid fa-warehouse" style="color:var(--primary); margin-right:4px;"></i>
                    {{ $wName }}: <span style="color:#0284c7; font-weight:700;">{{ number_format($qty) }} unit</span>
                </div>
                @endforeach

                @if($totalActiveDemanded > 0)
                <div style="background:#fff1f2; border:1px solid #fecdd3; border-radius:6px; padding:6px 12px; font-size:13px; font-weight:600; color:#be123c;">
                    <i class="fa-solid fa-clock-rotate-left" style="margin-right:4px;"></i>
                    Backorder (Menunggu PO): <span style="font-weight:700;">{{ number_format($totalActiveDemanded) }} unit</span>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Item Table --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h3><i class="fa-solid fa-cart-flatbed" style="color:var(--primary); margin-right:8px;"></i> Item Pesanan</h3>
            <span class="badge badge-{{ $order->status }}" style="font-size:11px; padding:4px 10px;">
                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
            </span>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th style="width:50px; text-align:center;">No</th>
                        <th>Produk</th>
                        <th style="text-align:center; width:130px;">Qty Pesan</th>
                        <th style="text-align:right; width:160px;">Harga Satuan</th>
                        <th style="text-align:center; width:130px;">Diskon</th>
                        <th style="text-align:right; width:170px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $idx => $item)
                    <tr>
                        <td style="text-align:center; color:var(--text-secondary); font-size:12px;">{{ $idx + 1 }}</td>
                        <td>
                            <div style="font-weight:600;">{{ $item->product->name }}</div>
                            <div style="font-size:12px; color:var(--text-secondary);">SKU: {{ $item->product->sku }} · Satuan: {{ $item->product->unit ?? 'pcs' }}</div>
                        </td>
                        <td style="text-align:center; font-weight:700; font-size:14px; color:#0e1b35;">
                            {{ number_format($item->qty_ordered) }} <span style="font-size:12px; font-weight:normal; color:var(--text-secondary);">{{ $item->product->unit }}</span>
                        </td>
                        <td style="text-align:right;">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                        <td style="text-align:center;">
                            @if($item->discount_percent > 0)
                                @php
                                    $itemDiscRp = ($item->qty_ordered * $item->unit_price) * ($item->discount_percent / 100);
                                @endphp
                                <span style="font-weight:600;">{{ $item->discount_percent }}%</span>
                                <div style="font-size:11px; color:var(--danger); margin-top:2px; font-weight:600;">
                                    -Rp {{ number_format($itemDiscRp, 0, ',', '.') }}
                                </div>
                            @else
                                <span style="color:var(--text-secondary);">-</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            @if($item->discount_percent > 0)
                                <div style="font-size:11px; color:#94a3b8; text-decoration:line-through;">Rp {{ number_format($item->qty_ordered * $item->unit_price, 0, ',', '.') }}</div>
                            @endif
                            <div style="font-weight:700; color:var(--primary);">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Hidden Action Forms for Workflow Submission --}}
    @if($order->status === 'draft')
    <form id="form-cancel-so" method="POST" action="{{ route('sales.orders.cancel', $order) }}" style="display:none;">
        @csrf @method('PATCH')
    </form>
    <form id="form-confirm-so" method="POST" action="{{ route('sales.orders.confirm', $order) }}" style="display:none;">
        @csrf @method('PATCH')
    </form>
    @endif
</div>
@endsection
