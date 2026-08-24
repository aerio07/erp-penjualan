@extends('layouts.app')
@section('title', 'Detail Produk — ' . $product->name)
@section('page-title', 'Detail Master Produk')

@section('content')
<div class="animate-in">
    <!-- Header -->
    <div class="page-header">
        <div style="display:flex; align-items:center; gap:16px;">
            @if($product->image_url)
                <a href="{{ $product->image_url }}" target="_blank" title="Klik untuk melihat foto ukuran penuh" style="display:block; flex-shrink:0;">
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" style="width:60px; height:60px; border-radius:10px; object-fit:cover; border:2px solid #e2e8f0; box-shadow:0 2px 6px rgba(0,0,0,0.08); transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                </a>
            @else
                <div style="width:60px; height:60px; border-radius:10px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:24px; flex-shrink:0; border:2px dashed #cbd5e1;">
                    <i class="fa-solid fa-box"></i>
                </div>
            @endif
            <div>
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:4px;">
                    <h1 style="margin-bottom:0;">{{ $product->name }}</h1>
                    <span class="badge {{ $product->is_active ? 'badge-done' : 'badge-cancelled' }}" style="font-size:12px; padding:4px 10px;">
                        {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
                <p style="margin-bottom:0;">
                    Kode SKU: <strong style="color:var(--primary);">{{ $product->sku }}</strong> &nbsp;·&nbsp;
                    Kategori: 
                    @if($product->productCategory)
                        <a href="{{ route('master.categories.show', $product->productCategory) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                            {{ $product->productCategory->name }}
                        </a>
                    @else
                        <span>{{ $product->category ?? 'Umum' }}</span>
                    @endif
                    &nbsp;·&nbsp;
                    Satuan: <span>{{ $product->unit }}</span>
                </p>
            </div>
        </div>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <!-- Form Toggle Status Aktif / Nonaktif -->
            <form method="POST" action="{{ route('master.products.toggle-status', $product) }}" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin {{ $product->is_active ? 'menonaktifkan' : 'mengaktifkan' }} produk {{ $product->name }}?');">
                @csrf
                @method('PATCH')
                @if($product->is_active)
                    <button type="submit" class="btn btn-secondary" style="color:#b91c1c; border-color:#fca5a5;" title="Klik untuk menonaktifkan produk">
                        <i class="fa-solid fa-power-off"></i> Nonaktifkan Produk
                    </button>
                @else
                    <button type="submit" class="btn btn-primary" style="background:#16a34a; border-color:#16a34a;" title="Klik untuk mengaktifkan produk">
                        <i class="fa-solid fa-check-circle"></i> Aktifkan Produk
                    </button>
                @endif
            </form>

            <a href="{{ route('master.products.edit', $product) }}" class="btn btn-secondary">
                <i class="fa-solid fa-pen"></i> Edit
            </a>

            <button type="button" data-confirm-delete="delete-product-show" data-name="{{ $product->name }} ({{ $product->sku }})" class="btn btn-danger" title="Hapus Produk">
                <i class="fa-solid fa-trash"></i> Hapus
            </button>
            <form id="delete-product-show" method="POST" action="{{ route('master.products.destroy', $product) }}" style="display:none;">
                @csrf
                @method('DELETE')
            </form>

            <a href="{{ route('master.products.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Alert jika status nonaktif -->
    @if(!$product->is_active)
    <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; gap:12px;">
        <div style="display:flex; align-items:center; gap:10px;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size:18px;"></i>
            <div>
                <strong>Produk ini saat ini Nonaktif.</strong>
                <span style="font-size:13px; margin-left:4px;">Produk nonaktif tidak akan muncul pada pilihan transaksi baru.</span>
            </div>
        </div>
        <form method="POST" action="{{ route('master.products.toggle-status', $product) }}">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-sm btn-primary" style="background:#16a34a; border-color:#16a34a; white-space:nowrap;">
                <i class="fa-solid fa-check"></i> Aktifkan Sekarang
            </button>
        </form>
    </div>
    @endif

    <!-- 4 Metrik Statistik Stok -->
    <div class="grid grid-4" style="margin-bottom:24px; gap:16px;">
        <!-- Stok Fisik (On Hand) -->
        <div class="card" style="padding:16px; border-left:4px solid var(--primary);">
            <div style="font-size:12px; color:var(--text-secondary); text-transform:uppercase; font-weight:600; margin-bottom:6px;">
                <i class="fa-solid fa-boxes-stacked" style="margin-right:4px;"></i> Total Stok Fisik
            </div>
            <div style="font-size:24px; font-weight:800; color:var(--primary);">
                {{ number_format($totalOnHand) }} <span style="font-size:14px; font-weight:500; color:var(--text-secondary);">{{ $product->unit }}</span>
            </div>
            <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">
                Nilai Aset: <strong>Rp {{ number_format($totalOnHand * $product->purchase_price, 0, ',', '.') }}</strong>
            </div>
        </div>

        <!-- Stok Siap Jual (Available) -->
        <div class="card" style="padding:16px; border-left:4px solid #16a34a;">
            <div style="font-size:12px; color:var(--text-secondary); text-transform:uppercase; font-weight:600; margin-bottom:6px;">
                <i class="fa-solid fa-circle-check" style="margin-right:4px; color:#16a34a;"></i> Stok Siap Jual
            </div>
            <div style="font-size:24px; font-weight:800; color:#16a34a;">
                {{ number_format($totalAvailable) }} <span style="font-size:14px; font-weight:500; color:var(--text-secondary);">{{ $product->unit }}</span>
            </div>
            <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">
                @if($totalAvailable <= 0)
                    <span class="badge badge-cancelled" style="font-size:11px;">Stok Habis</span>
                @elseif($totalAvailable <= $product->min_stock)
                    <span class="badge badge-pending" style="font-size:11px;">Hampir Habis (Min: {{ $product->min_stock }})</span>
                @else
                    <span class="badge badge-done" style="font-size:11px;">Stok Aman</span>
                @endif
            </div>
        </div>

        <!-- Stok Dipesan (Reserved) -->
        <div class="card" style="padding:16px; border-left:4px solid #d97706;">
            <div style="font-size:12px; color:var(--text-secondary); text-transform:uppercase; font-weight:600; margin-bottom:6px;">
                <i class="fa-solid fa-lock" style="margin-right:4px; color:#d97706;"></i> Dipesan (Reserved)
            </div>
            <div style="font-size:24px; font-weight:800; color:#d97706;">
                {{ number_format($totalReserved) }} <span style="font-size:14px; font-weight:500; color:var(--text-secondary);">{{ $product->unit }}</span>
            </div>
            <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">
                Teralokasi untuk pesanan SO aktif
            </div>
        </div>

        <!-- Stok Masuk (Incoming PO) -->
        <div class="card" style="padding:16px; border-left:4px solid #2563eb;">
            <div style="font-size:12px; color:var(--text-secondary); text-transform:uppercase; font-weight:600; margin-bottom:6px;">
                <i class="fa-solid fa-truck-ramp-box" style="margin-right:4px; color:#2563eb;"></i> PO Menuju Gudang
            </div>
            <div style="font-size:24px; font-weight:800; color:#2563eb;">
                {{ number_format($totalIncoming) }} <span style="font-size:14px; font-weight:500; color:var(--text-secondary);">{{ $product->unit }}</span>
            </div>
            <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">
                @if($totalBackorder > 0)
                    <span style="color:#dc2626; font-weight:600;">Defisit/Backorder: {{ $totalBackorder }} {{ $product->unit }}</span>
                @else
                    Pesanan ke supplier belum tiba
                @endif
            </div>
        </div>
    </div>

    <!-- Informasi Produk & Stok Per Gudang -->
    <div class="grid grid-3" style="margin-bottom:24px; gap:20px;">
        <!-- Card 1: Informasi Master Produk & Harga (2 Col) -->
        <div class="card" style="grid-column:span 2;">
            <div class="card-header">
                <h3><i class="fa-solid fa-circle-info" style="color:var(--primary); margin-right:8px;"></i> Informasi Produk & Harga</h3>
            </div>
            <div class="card-body">
                <div class="form-row form-row-3" style="margin-bottom:16px;">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">SKU Produk</div>
                        <div style="font-weight:700; font-size:15px; color:var(--primary);">{{ $product->sku }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Nama Produk</div>
                        <div style="font-weight:600; font-size:15px;">{{ $product->name }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Kategori</div>
                        <div>
                            @if($product->productCategory)
                                <a href="{{ route('master.categories.show', $product->productCategory) }}" class="badge badge-confirmed" style="text-decoration:none;">
                                    {{ $product->productCategory->name }}
                                </a>
                            @else
                                <span class="badge badge-confirmed">{{ $product->category ?? 'Umum' }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="form-row form-row-3" style="margin-bottom:16px;">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Satuan (Unit)</div>
                        <div style="font-weight:600;">{{ $product->unit }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Batas Min. Stok (Alert)</div>
                        <div style="font-weight:600;">{{ number_format($product->min_stock) }} {{ $product->unit }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Status Master</div>
                        <div>
                            <span class="badge {{ $product->is_active ? 'badge-done' : 'badge-cancelled' }}">
                                {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                    </div>
                </div>

                <hr style="border:0; border-top:1px solid var(--border); margin:16px 0;">

                @php
                    $marginRp = $product->sell_price - $product->purchase_price;
                    $marginPct = $product->sell_price > 0 ? ($marginRp / $product->sell_price) * 100 : 0;
                @endphp
                <div class="form-row form-row-3" style="margin-bottom:16px;">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Harga Beli (HPP)</div>
                        <div style="font-weight:700; font-size:16px;">Rp {{ number_format($product->purchase_price, 0, ',', '.') }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Harga Jual Standar</div>
                        <div style="font-weight:700; font-size:16px; color:#16a34a;">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Estimasi Margin Profit</div>
                        <div style="font-weight:700; font-size:16px; color:{{ $marginRp >= 0 ? '#2563eb' : '#dc2626' }};">
                            Rp {{ number_format($marginRp, 0, ',', '.') }}
                            <span style="font-size:12px; font-weight:500;">({{ number_format($marginPct, 1) }}%)</span>
                        </div>
                    </div>
                </div>

                @if($product->notes)
                <div style="margin-top:16px; padding:12px; background:#f8fafc; border-radius:8px; border:1px solid var(--border);">
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px; font-weight:600;">Catatan Produk:</div>
                    <div style="font-size:13px; color:#334155;">{{ $product->notes }}</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Card 2: Stok Per Gudang (1 Col) -->
        <div class="card">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h3><i class="fa-solid fa-warehouse" style="color:var(--primary); margin-right:8px;"></i> Stok per Gudang</h3>
                <a href="{{ route('inventory.stock-card') }}?product_id={{ $product->id }}" class="btn btn-secondary btn-sm" title="Buka Kartu Stok">
                    <i class="fa-solid fa-rectangle-list"></i> Kartu Stok
                </a>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-responsive">
                    <table class="erp-table" style="margin:0;">
                        <thead>
                            <tr>
                                <th>Gudang</th>
                                <th style="text-align:right;">Fisik</th>
                                <th style="text-align:right;">Dipesan</th>
                                <th style="text-align:right;">Siap Jual</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($warehouseStocks as $stk)
                            <tr>
                                <td>
                                    <div style="font-weight:600;">{{ $stk['warehouse']->name }}</div>
                                    <small style="color:var(--text-secondary);">{{ $stk['warehouse']->code }}</small>
                                </td>
                                <td style="text-align:right; font-weight:600;">{{ number_format($stk['on_hand']) }}</td>
                                <td style="text-align:right; color:#d97706;">{{ number_format($stk['reserved']) }}</td>
                                <td style="text-align:right; font-weight:700; color:{{ $stk['available'] > 0 ? '#16a34a' : '#9ca3af' }};">
                                    {{ number_format($stk['available']) }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" style="text-align:center; padding:24px; color:var(--text-secondary);">
                                    Belum ada data gudang aktif
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Riwayat Transaksi Tabs Component (Alpine.js) -->
    <div class="card" x-data="{ tab: 'movements' }">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); padding:0 20px;">
            <div style="display:flex; gap:16px;">
                <button type="button" @click="tab = 'movements'"
                    :style="tab === 'movements' ? 'border-bottom:3px solid var(--primary); color:var(--primary); font-weight:700;' : 'color:var(--text-secondary);'"
                    style="background:none; border:none; padding:16px 8px; cursor:pointer; font-size:14px; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-arrows-rotate"></i> Pergerakan Stok Terakhir
                    <span class="badge" style="background:#e2e8f0; font-size:11px;">{{ $recentMovements->count() }}</span>
                </button>
                <button type="button" @click="tab = 'sales'"
                    :style="tab === 'sales' ? 'border-bottom:3px solid var(--primary); color:var(--primary); font-weight:700;' : 'color:var(--text-secondary);'"
                    style="background:none; border:none; padding:16px 8px; cursor:pointer; font-size:14px; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-cart-shopping"></i> Riwayat Penjualan (SO)
                    <span class="badge" style="background:#e2e8f0; font-size:11px;">{{ $recentSalesItems->count() }}</span>
                </button>
                <button type="button" @click="tab = 'purchases'"
                    :style="tab === 'purchases' ? 'border-bottom:3px solid var(--primary); color:var(--primary); font-weight:700;' : 'color:var(--text-secondary);'"
                    style="background:none; border:none; padding:16px 8px; cursor:pointer; font-size:14px; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-truck"></i> Riwayat Pembelian (PO)
                    <span class="badge" style="background:#e2e8f0; font-size:11px;">{{ $recentPurchaseItems->count() }}</span>
                </button>
            </div>
            <div>
                <a x-show="tab === 'movements'" href="{{ route('inventory.movements.index') }}" class="btn btn-secondary btn-sm">Lihat Semua Mutasi</a>
                <a x-show="tab === 'sales'" href="{{ route('sales.orders.index') }}" class="btn btn-secondary btn-sm">Lihat Semua SO</a>
                <a x-show="tab === 'purchases'" href="{{ route('purchase.orders.index') }}" class="btn btn-secondary btn-sm">Lihat Semua PO</a>
            </div>
        </div>

        <!-- Tab 1: Pergerakan Stok -->
        <div x-show="tab === 'movements'" class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Tipe Mutasi</th>
                        <th>Gudang</th>
                        <th style="text-align:right;">Jumlah</th>
                        <th style="text-align:right;">Biaya Satuan</th>
                        <th>Catatan / Ref</th>
                        <th>Operator</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentMovements as $mov)
                    <tr>
                        <td>{{ $mov->movement_date ? $mov->movement_date->format('d/m/Y') : $mov->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            @php
                                $typeBadge = match($mov->type) {
                                    'in', 'return_in', 'transfer_in' => 'badge-done',
                                    'out', 'return_out', 'transfer_out' => 'badge-cancelled',
                                    'adjustment' => 'badge-pending',
                                    default => 'badge-confirmed',
                                };
                            @endphp
                            <span class="badge {{ $typeBadge }}">{{ strtoupper(str_replace('_', ' ', $mov->type)) }}</span>
                        </td>
                        <td>{{ $mov->warehouse->name ?? '-' }}</td>
                        <td style="text-align:right; font-weight:700; color:{{ $mov->quantity > 0 ? '#16a34a' : '#dc2626' }};">
                            {{ $mov->quantity > 0 ? '+' : '' }}{{ number_format($mov->quantity) }} {{ $product->unit }}
                        </td>
                        <td style="text-align:right;">Rp {{ number_format($mov->unit_cost ?? 0, 0, ',', '.') }}</td>
                        <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $mov->notes }}">{{ $mov->notes ?? '-' }}</td>
                        <td>{{ $mov->user->name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:36px; color:var(--text-secondary);">
                            <i class="fa-solid fa-box-open" style="font-size:28px; margin-bottom:8px; display:block; opacity:0.4;"></i>
                            Belum ada riwayat pergerakan stok untuk produk ini
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Tab 2: Riwayat Penjualan -->
        <div x-show="tab === 'sales'" class="table-responsive" style="display:none;">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>No. SO</th>
                        <th>Pelanggan</th>
                        <th>Tanggal Order</th>
                        <th style="text-align:right;">Qty Dipesan</th>
                        <th style="text-align:right;">Harga Satuan</th>
                        <th style="text-align:right;">Subtotal</th>
                        <th style="text-align:center;">Status SO</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentSalesItems as $item)
                    <tr>
                        <td>
                            @if($item->salesOrder)
                                <a href="{{ route('sales.orders.show', $item->salesOrder) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                                    {{ $item->salesOrder->so_number }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $item->salesOrder->customer->name ?? '-' }}</td>
                        <td>{{ $item->salesOrder && $item->salesOrder->order_date ? $item->salesOrder->order_date->format('d/m/Y') : '-' }}</td>
                        <td style="text-align:right; font-weight:600;">{{ number_format($item->qty_ordered) }} {{ $product->unit }}</td>
                        <td style="text-align:right;">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:600;">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        <td style="text-align:center;">
                            @if($item->salesOrder)
                                <span class="badge badge-{{ $item->salesOrder->status }}">{{ ucfirst(str_replace('_', ' ', $item->salesOrder->status)) }}</span>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:36px; color:var(--text-secondary);">
                            <i class="fa-solid fa-cart-shopping" style="font-size:28px; margin-bottom:8px; display:block; opacity:0.4;"></i>
                            Belum ada riwayat order penjualan untuk produk ini
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Tab 3: Riwayat Pembelian -->
        <div x-show="tab === 'purchases'" class="table-responsive" style="display:none;">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>No. PO</th>
                        <th>Supplier</th>
                        <th>Tanggal PO</th>
                        <th style="text-align:right;">Qty Dipesan</th>
                        <th style="text-align:right;">Qty Diterima</th>
                        <th style="text-align:right;">Harga Beli</th>
                        <th style="text-align:right;">Subtotal</th>
                        <th style="text-align:center;">Status PO</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPurchaseItems as $item)
                    <tr>
                        <td>
                            @if($item->purchaseOrder)
                                <a href="{{ route('purchase.orders.show', $item->purchaseOrder) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                                    {{ $item->purchaseOrder->po_number }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $item->purchaseOrder->supplier->name ?? '-' }}</td>
                        <td>{{ $item->purchaseOrder && $item->purchaseOrder->order_date ? $item->purchaseOrder->order_date->format('d/m/Y') : '-' }}</td>
                        <td style="text-align:right; font-weight:600;">{{ number_format($item->qty_ordered) }} {{ $product->unit }}</td>
                        <td style="text-align:right; color:#16a34a;">{{ number_format($item->qty_received) }} {{ $product->unit }}</td>
                        <td style="text-align:right;">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:600;">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        <td style="text-align:center;">
                            @if($item->purchaseOrder)
                                <span class="badge badge-{{ $item->purchaseOrder->status }}">{{ ucfirst(str_replace('_', ' ', $item->purchaseOrder->status)) }}</span>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:36px; color:var(--text-secondary);">
                            <i class="fa-solid fa-truck" style="font-size:28px; margin-bottom:8px; display:block; opacity:0.4;"></i>
                            Belum ada riwayat purchase order untuk produk ini
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
