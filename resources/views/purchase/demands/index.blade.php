@extends('layouts.app')
@section('title', 'Kebutuhan Pengadaan (Procurement Demand Hub)')
@section('page-title', 'Kebutuhan Pengadaan Barang')

@section('content')
<div class="animate-in">
    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1>Pusat Kebutuhan Pengadaan (Procurement Demands)</h1>
            <p>Pemantauan real-time defisit stok dari Sales Order (Backorder) untuk konsolidasi pembuatan Purchase Order (PO).</p>
        </div>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('purchase.orders.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Buat PO Baru
            </a>
            <a href="{{ route('inventory.stock-summary') }}" class="btn btn-secondary">
                <i class="fa-solid fa-boxes-stacked"></i> Ringkasan Stok
            </a>
        </div>
    </div>

    {{-- Metric Cards --}}
    <div class="grid grid-4" style="margin-bottom:20px;">
        <div class="card" style="border-left: 4px solid #f59e0b;">
            <div class="card-body" style="padding:16px 20px;">
                <div style="font-size:12px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px;">Item Kurang Stok</div>
                <div style="font-size:26px; font-weight:700; color:var(--text-primary); margin-top:4px;">
                    {{ $totalBackorderItems }} <span style="font-size:14px; font-weight:400; color:var(--text-secondary);">SKU</span>
                </div>
                <div style="font-size:12px; color:#d97706; margin-top:4px; font-weight:500;">
                    <i class="fa-solid fa-triangle-exclamation"></i> Menunggu Pembuatan PO
                </div>
            </div>
        </div>

        <div class="card" style="border-left: 4px solid #ef4444;">
            <div class="card-body" style="padding:16px 20px;">
                <div style="font-size:12px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px;">SO Menunggu Barang</div>
                <div style="font-size:26px; font-weight:700; color:var(--text-primary); margin-top:4px;">
                    {{ $totalWaitingOrders }} <span style="font-size:14px; font-weight:400; color:var(--text-secondary);">Order</span>
                </div>
                <div style="font-size:12px; color:#dc2626; margin-top:4px; font-weight:500;">
                    <i class="fa-solid fa-clock"></i> Komitmen Pengiriman Customer
                </div>
            </div>
        </div>

        <div class="card" style="border-left: 4px solid #6366f1;">
            <div class="card-body" style="padding:16px 20px;">
                <div style="font-size:12px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px;">Total Kuantitas Defisit</div>
                <div style="font-size:26px; font-weight:700; color:#4f46e5; margin-top:4px;">
                    {{ number_format($totalShortageQty) }} <span style="font-size:14px; font-weight:400; color:var(--text-secondary);">Unit</span>
                </div>
                <div style="font-size:12px; color:#6366f1; margin-top:4px; font-weight:500;">
                    <i class="fa-solid fa-chart-line"></i> Total Demand Gap
                </div>
            </div>
        </div>

        <div class="card" style="border-left: 4px solid #10b981;">
            <div class="card-body" style="padding:16px 20px;">
                <div style="font-size:12px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px;">Sedang Dipesan (Incoming)</div>
                <div style="font-size:26px; font-weight:700; color:#059669; margin-top:4px;">
                    {{ number_format($totalIncomingQty) }} <span style="font-size:14px; font-weight:400; color:var(--text-secondary);">Unit</span>
                </div>
                <div style="font-size:12px; color:#059669; margin-top:4px; font-weight:500;">
                    <i class="fa-solid fa-truck"></i> PO Diterbitkan ke Supplier
                </div>
            </div>
        </div>
    </div>

    {{-- TABEL 1: KONSOLIDASI KEBUTUHAN PENGADAAN PER PRODUK (PURCHASING ACTION HUB) --}}
    <div class="card" style="margin-bottom:24px;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3>Konsolidasi Kebutuhan Pengadaan per Produk</h3>
                <p style="font-size:12.5px; color:var(--text-secondary); margin:2px 0 0 0;">
                    Ringkasan akumulasi defisit barang dari pesanan customer untuk memudahkan penerbitan PO sekaligus ke Supplier.
                </p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Produk / SKU</th>
                        <th style="text-align:center; background:rgba(239, 68, 68, 0.06); color:#dc2626;">Defisit SO (Backorder)</th>
                        <th style="text-align:center;">Stok On Hand</th>
                        <th style="text-align:center;">Stok Available</th>
                        <th style="text-align:center; color:#4f46e5;">Incoming PO</th>
                        <th style="text-align:center; font-weight:700;">Rekomendasi Beli</th>
                        <th style="text-align:center;">SO Terdampak</th>
                        <th style="text-align:center;">Aksi Purchasing</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($consolidatedProducts as $cp)
                    <tr>
                        <td>
                            <div style="font-weight:600; color:var(--text-primary);">{{ $cp['product']->name }}</div>
                            <div style="font-size:12px; color:var(--text-secondary); font-family:monospace;">
                                {{ $cp['product']->sku }} · Satuan: {{ $cp['product']->unit }}
                            </div>
                        </td>
                        <td style="text-align:center; font-weight:700; color:#dc2626; background:rgba(239, 68, 68, 0.03); font-size:15px;">
                            {{ number_format($cp['total_demanded']) }} {{ $cp['product']->unit }}
                        </td>
                        <td style="text-align:center; font-weight:600;">
                            {{ number_format($cp['on_hand']) }}
                        </td>
                        <td style="text-align:center; font-weight:600; {{ $cp['available'] > 0 ? 'color:var(--success);' : 'color:var(--text-secondary);' }}">
                            {{ number_format($cp['available']) }}
                        </td>
                        <td style="text-align:center; font-weight:600; color:#4f46e5;">
                            {{ number_format($cp['incoming']) }}
                        </td>
                        <td style="text-align:center;">
                            @if($cp['net_needed'] > 0)
                                <span style="display:inline-block; font-size:12px; font-weight:700; padding:4px 10px; border-radius:9999px; background:#fee2e2; color:#b91c1c; border:1px solid #fecaca;">
                                    Kurang {{ number_format($cp['net_needed']) }} {{ $cp['product']->unit }}
                                </span>
                            @else
                                <span style="display:inline-block; font-size:12px; font-weight:600; padding:4px 10px; border-radius:9999px; background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;">
                                    Tercukupi di PO
                                </span>
                            @endif
                        </td>
                        <td style="text-align:center; font-size:13px;">
                            <strong>{{ $cp['orders_count'] }}</strong> Sales Order
                        </td>
                        <td style="text-align:center;">
                            <a href="{{ route('purchase.orders.create', ['product_id' => $cp['product']->id, 'qty' => max(1, $cp['net_needed']), 'demand_ids' => $cp['demand_ids']]) }}" 
                               class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-cart-plus"></i> Buat PO Pengadaan
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:36px 20px; color:var(--text-secondary);">
                            <div style="display:inline-flex; width:48px; height:48px; border-radius:50%; background:#f1f5f9; align-items:center; justify-content:center; margin-bottom:12px; color:#64748b;">
                                <i class="fa-solid fa-circle-check" style="font-size:24px; color:#10b981;"></i>
                            </div>
                            <div style="font-weight:600; font-size:15px; color:var(--text-primary); margin-bottom:4px;">
                                Tidak Ada Defisit Kebutuhan Pengadaan Saat Ini
                            </div>
                            <p style="font-size:13px; color:var(--text-secondary); max-width:560px; margin:0 auto;">
                                Seluruh pesanan penjualan (Sales Order) yang terkonfirmasi telah tercukupi stok fisiknya. Ketika ada SO baru yang stoknya tidak mencukupi, sistem akan otomatis mendaftarkannya di sini.
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- TABEL 2: DETAIL RINCIAN DEMAND AUDIT TRAIL PER SO --}}
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div>
                <h3>Rincian Demand Pesanan (Traceability Log)</h3>
                <p style="font-size:12.5px; color:var(--text-secondary); margin:2px 0 0 0;">
                    Daftar histori dan status setiap kebutuhan barang per Sales Order.
                </p>
            </div>
            <div style="display:flex; gap:6px;">
                <a href="{{ route('purchase.demands.index', ['status' => 'active']) }}" class="btn btn-sm {{ $status === 'active' ? 'btn-primary' : 'btn-secondary' }}">Aktif (Pending + Ordered)</a>
                <a href="{{ route('purchase.demands.index', ['status' => 'pending']) }}" class="btn btn-sm {{ $status === 'pending' ? 'btn-primary' : 'btn-secondary' }}">Pending PO</a>
                <a href="{{ route('purchase.demands.index', ['status' => 'ordered']) }}" class="btn btn-sm {{ $status === 'ordered' ? 'btn-primary' : 'btn-secondary' }}">Sudah PO</a>
                <a href="{{ route('purchase.demands.index', ['status' => 'fulfilled']) }}" class="btn btn-sm {{ $status === 'fulfilled' ? 'btn-primary' : 'btn-secondary' }}">Selesai</a>
                <a href="{{ route('purchase.demands.index', ['status' => 'all']) }}" class="btn btn-sm {{ $status === 'all' ? 'btn-primary' : 'btn-secondary' }}">Semua</a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>No. Demand</th>
                        <th>No. Sales Order</th>
                        <th>Customer</th>
                        <th>Produk</th>
                        <th style="text-align:center;">Qty Kurang</th>
                        <th style="text-align:center;">Qty Terpenuhi</th>
                        <th style="text-align:center;">Status</th>
                        <th>Purchase Order Terkait</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($demands as $demand)
                    <tr>
                        <td style="font-family:monospace; font-weight:600; color:var(--text-primary);">
                            {{ $demand->demand_number }}
                        </td>
                        <td>
                            @if($demand->salesOrder)
                            <a href="{{ route('sales.orders.show', $demand->sales_order_id) }}" style="font-family:monospace; font-weight:600; color:var(--primary); text-decoration:none;">
                                {{ $demand->salesOrder->so_number }}
                            </a>
                            @else
                            -
                            @endif
                        </td>
                        <td>{{ $demand->salesOrder->customer->name ?? '-' }}</td>
                        <td>
                            <div style="font-weight:600;">{{ $demand->product->name }}</div>
                            <div style="font-size:12px; color:var(--text-secondary); font-family:monospace;">{{ $demand->product->sku }}</div>
                        </td>
                        <td style="text-align:center; font-weight:700; color:#dc2626;">
                            {{ number_format($demand->qty_demanded) }} {{ $demand->product->unit }}
                        </td>
                        <td style="text-align:center; font-weight:600; color:#059669;">
                            {{ number_format($demand->qty_fulfilled) }}
                        </td>
                        <td style="text-align:center;">
                            @php
                                $badgeStyle = match($demand->status) {
                                    'pending'   => 'background:#fef3c7; color:#b45309; border:1px solid #fde68a;',
                                    'ordered'   => 'background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;',
                                    'fulfilled' => 'background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;',
                                    'cancelled' => 'background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0;',
                                    default     => 'background:#f1f5f9; color:#475569;'
                                };
                                $statusName = match($demand->status) {
                                    'pending'   => 'Menunggu PO',
                                    'ordered'   => 'PO Terbit',
                                    'fulfilled' => 'Terpenuhi',
                                    'cancelled' => 'Dibatalkan',
                                    default     => ucfirst($demand->status)
                                };
                            @endphp
                            <span style="display:inline-block; font-size:11px; font-weight:700; text-transform:uppercase; padding:3px 8px; border-radius:9999px; {{ $badgeStyle }}">
                                {{ $statusName }}
                            </span>
                        </td>
                        <td>
                            @if($demand->purchaseOrder)
                            <a href="{{ route('purchase.orders.show', $demand->purchase_order_id) }}" style="font-family:monospace; font-weight:600; color:var(--primary); text-decoration:none;">
                                {{ $demand->purchaseOrder->po_number }}
                            </a>
                            <div style="font-size:11px; color:var(--text-secondary);">{{ $demand->purchaseOrder->supplier->name ?? '-' }}</div>
                            @else
                            <span style="color:var(--text-secondary); font-style:italic; font-size:12px;">Belum dibuatkan PO</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:36px; color:var(--text-secondary);">
                            <i class="fa-solid fa-list-check" style="font-size:28px; margin-bottom:8px; display:block; opacity:0.4;"></i>
                            Tidak ada data rincian demand yang sesuai filter.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($demands->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border);">
            {{ $demands->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
