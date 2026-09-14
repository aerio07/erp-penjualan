@extends('layouts.app')
@section('title', 'Detail SO - ' . $order->so_number)
@section('page-title', 'Detail Sales Order')

@section('content')
<div class="animate-in">
    <x-detail-header 
        :title="$order->so_number"
        :subtitle="($order->customer->name ?? '-') . ' · ' . $order->order_date->format('d/m/Y') . ' · Channel: ' . ucfirst($order->channel ?? 'offline')"
        :status="$order->status"
        :back-url="route('sales.orders.index')"
    >
        <x-slot:actions>
            @if($order->status === 'draft')
            <a href="{{ route('sales.orders.edit', $order) }}" class="btn btn-secondary">
                <i class="fa-solid fa-pen"></i> Ubah
            </a>
            @endif
            <a href="{{ route('pdf.sales-order', $order) }}" class="btn btn-secondary" target="_blank">
                <i class="fa-solid fa-file-pdf"></i> PDF
            </a>
        </x-slot:actions>
    </x-detail-header>

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
                        <div style="font-weight:600;">{{ $order->order_date->format('d F Y') }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Target Pengiriman</div>
                        <div style="font-weight:600;">{{ $order->expected_delivery_date ? $order->expected_delivery_date->format('d F Y') : '-' }}</div>
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
            <div class="card-header"><h3>Total Tagihan</h3></div>
            <div class="card-body">
                <div style="display:flex; flex-direction:column; gap:10px; font-size:14px;">
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:var(--text-secondary);">Subtotal</span>
                        <span>Rp {{ number_format($order->items->sum('subtotal'), 0, ',', '.') }}</span>
                    </div>
                    @if($order->discount_amount > 0)
                    <div style="display:flex; justify-content:space-between; color:var(--danger);">
                        <span>Diskon Header</span>
                        <span>- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
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
            </div>
        </div>
    </div>

    @php
        $whAllocations = [];
        $totalActiveReserved = 0;
        $totalActiveDemanded = 0;

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
    @endphp

    {{-- Multi-Warehouse Allocation & Fulfillment Breakdown Banner --}}
    @if(count($whAllocations) > 0 || $totalActiveDemanded > 0)
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
        <div class="card-header"><h3>Item Pesanan & Alokasi Stok</h3></div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th style="text-align:center;">Qty Pesan</th>
                        <th style="text-align:center; background:rgba(37, 99, 235, 0.06); width:180px;">Reserved (Teralokasi)</th>
                        <th style="text-align:center; background:rgba(220, 38, 38, 0.06); width:150px;">Backorder</th>
                        <th style="text-align:center;">Qty Terkirim</th>
                        <th style="text-align:center;">Sisa Kirim</th>
                        <th style="text-align:center; background:rgba(16, 185, 129, 0.06);">Sisa Unbilled</th>
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
                        <td style="text-align:center; background:rgba(37, 99, 235, 0.03);">
                            <div style="font-weight:700; color:#2563eb; font-size:14px;">
                                {{ $item->qty_reserved }}
                            </div>
                            {{-- Tag Lokasi Gudang --}}
                            @foreach($item->reservations->where('status', 'active') as $res)
                                @php $activeRes = max(0, $res->qty_reserved - $res->qty_delivered); @endphp
                                @if($activeRes > 0)
                                    <div style="font-size:10.5px; color:#1e40af; background:#eff6ff; border:1px solid #bfdbfe; border-radius:4px; padding:2px 6px; margin-top:3px; display:inline-block; font-weight:600;">
                                        <i class="fa-solid fa-warehouse"></i> {{ $res->warehouse->name ?? 'Gudang' }}: {{ $activeRes }}
                                    </div>
                                @endif
                            @endforeach
                        </td>
                        <td style="text-align:center; background:rgba(220, 38, 38, 0.03);">
                            <div style="font-weight:700; color:#dc2626; font-size:14px;">
                                {{ $item->qty_demanded }}
                            </div>
                            @if($item->qty_demanded > 0)
                                <div style="font-size:10.5px; color:#991b1b; background:#fef2f2; border:1px solid #fecaca; border-radius:4px; padding:2px 6px; margin-top:3px; display:inline-block; font-weight:600;">
                                    <i class="fa-solid fa-clock"></i> Menunggu PO
                                </div>
                            @endif
                        </td>
                        <td style="text-align:center; color:var(--success); font-weight:600;">{{ $item->qty_delivered }}</td>
                        <td style="text-align:center;">
                            <span style="{{ $item->qty_remaining > 0 ? 'color:var(--warning); font-weight:600;' : 'color:var(--success);' }}">
                                {{ $item->qty_remaining }}
                            </span>
                        </td>
                        <td style="text-align:center; background:rgba(16, 185, 129, 0.03);">
                            <span class="badge badge-done">{{ $item->qty_unbilled }}</span>
                        </td>
                        <td style="text-align:right;">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                        <td style="text-align:center;">{{ $item->discount_percent > 0 ? $item->discount_percent.'%' : '-' }}</td>
                        <td style="text-align:right; font-weight:600;">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>



    {{-- Bottom Workflow Actions --}}
    @if($order->status === 'draft')
    <x-document-action-card>
        <form id="form-cancel-so" method="POST" action="{{ route('sales.orders.cancel', $order) }}" style="display:none;">
            @csrf @method('PATCH')
        </form>
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

        <form id="form-confirm-so" method="POST" action="{{ route('sales.orders.confirm', $order) }}" style="display:none;">
            @csrf @method('PATCH')
        </form>
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
    </x-document-action-card>
    @elseif($order->status === 'waiting_approval')
    <x-document-action-card>
        <form id="form-cancel-so-approval" method="POST" action="{{ route('sales.orders.cancel', $order) }}" style="display:none;">
            @csrf @method('PATCH')
        </form>
        <button type="button" class="btn btn-danger"
            data-confirm-swal="form-cancel-so-approval"
            data-swal-title="Batalkan Sales Order"
            data-swal-html="Yakin ingin membatalkan Sales Order <strong>{{ $order->so_number }}</strong>?<br><span style='font-size:12.5px; color:#64748b;'>Permintaan persetujuan yang sedang berjalan akan dibatalkan.</span>"
            data-swal-icon="warning"
            data-swal-btn-text="Ya, Batalkan SO"
            data-swal-btn-icon="fa-solid fa-ban"
            data-swal-btn-class="btn btn-danger"
        >
            <i class="fa-solid fa-ban"></i> Batalkan SO
        </button>
    </x-document-action-card>
    @elseif($order->status === 'confirmed')
    <x-document-action-card>
        @if($order->deliveries->where('status', '!=', 'cancelled')->count() === 0)
        <form id="form-cancel-so-confirmed" method="POST" action="{{ route('sales.orders.cancel', $order) }}" style="display:none;">
            @csrf @method('PATCH')
        </form>
        <button type="button" class="btn btn-danger"
            data-confirm-swal="form-cancel-so-confirmed"
            data-swal-title="Batalkan Sales Order"
            data-swal-html="Yakin ingin membatalkan Sales Order <strong>{{ $order->so_number }}</strong>?<br><span style='font-size:12.5px; color:#64748b;'>Status SO akan diubah menjadi Cancelled dan seluruh alokasi reservasi stok akan dilepaskan kembali ke gudang.</span>"
            data-swal-icon="warning"
            data-swal-btn-text="Ya, Batalkan SO"
            data-swal-btn-icon="fa-solid fa-ban"
            data-swal-btn-class="btn btn-danger"
        >
            <i class="fa-solid fa-ban"></i> Batalkan SO
        </button>
        @endif

        @if($order->canCreateDelivery())
        <a href="{{ route('sales.deliveries.create', ['so_id' => $order->id]) }}" class="btn btn-primary">
            <i class="fa-solid fa-truck-fast"></i> Buat Surat Jalan
        </a>
        @endif
    </x-document-action-card>
    @elseif($order->status === 'partially_delivered')
    @php
        $canCreateDel = $order->canCreateDelivery();
        $hasUninvoicedDel = $order->deliveries->where('is_invoiced', false)->where('status', 'delivered')->count() > 0;
    @endphp
    @if($canCreateDel || $hasUninvoicedDel)
    <x-document-action-card>
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
    </x-document-action-card>
    @endif
    @endif
</div>
@endsection
