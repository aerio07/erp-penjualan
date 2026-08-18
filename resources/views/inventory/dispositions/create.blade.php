@extends('layouts.app')
@section('title', 'Form Disposisi Barang Karantina')
@section('page-title', 'Form Disposisi Barang Karantina')

@section('content')
<div class="animate-in" x-data="{
    resolutionType: '{{ old('resolution_type', 'write_off') }}',
    qty: {{ old('qty', 1) }},
    unitCost: {{ $unitCost }},
    salePrice: {{ old('sale_price', 0) }},
    availableQuarantine: {{ $availableQuarantine }},
    get totalCost() { return this.qty * this.unitCost; },
    get totalRevenue() { return this.resolutionType === 'sold_as_reject' ? (this.qty * this.salePrice) : 0; }
}">
    <div class="page-header">
        <div>
            <h1>Form Penyelesaian Barang Karantina</h1>
            <p>Pilih produk & gudang untuk menyelesaikan status barang retur rusak/cacat</p>
        </div>
        <div>
            <a href="{{ route('inventory.dispositions.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar
            </a>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-danger mb-4">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div>
            <strong>Terjadi kesalahan input:</strong>
            <ul style="margin-top:4px; padding-left:18px;">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Step 1: Filter Selection (jika belum dipilihi) --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <h3>1. Pilih Produk & Gudang Karantina</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('inventory.dispositions.create') }}" style="display:flex; gap:16px; flex-wrap:wrap; align-items:flex-end;">
                <div style="flex:1; min-width:240px;">
                    <label class="form-label">Produk <span style="color:var(--danger);">*</span></label>
                    <select name="product_id" class="form-control" onchange="this.form.submit()" required>
                        <option value="">-- Pilih Produk --</option>
                        @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ $productId == $p->id ? 'selected' : '' }}>
                            {{ $p->name }} ({{ $p->sku }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div style="flex:1; min-width:240px;">
                    <label class="form-label">Gudang <span style="color:var(--danger);">*</span></label>
                    <select name="warehouse_id" class="form-control" onchange="this.form.submit()" required>
                        <option value="">-- Pilih Gudang --</option>
                        @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ $warehouseId == $wh->id ? 'selected' : '' }}>
                            {{ $wh->name }} ({{ $wh->code }})
                        </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if($selectedProduct && $selectedWarehouse)
    {{-- Info Card Stok Karantina --}}
    <div class="card" style="margin-bottom:20px; border-left:4px solid var(--warning); background:#fffbeb;">
        <div class="card-body" style="display:flex; gap:24px; align-items:center; flex-wrap:wrap;">
            <div style="width:48px; height:48px; background:#fef3c7; color:#d97706; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px;">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div style="flex:1;">
                <div style="font-size:16px; font-weight:700; color:#92400e;">
                    {{ $selectedProduct->name }} (SKU: {{ $selectedProduct->sku }})
                </div>
                <div style="font-size:13px; color:#b45309; margin-top:2px;">
                    Gudang: <strong>{{ $selectedWarehouse->name }}</strong> · Estimasi HPP Per Unit: <strong>Rp {{ number_format($unitCost, 0, ',', '.') }}</strong>
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:12px; color:#92400e; font-weight:600;">Sisa Stok Karantina Tersedia:</div>
                <div style="font-size:26px; font-weight:800; color:#b45309;">
                    {{ number_format($availableQuarantine) }} <span style="font-size:14px; font-weight:500;">{{ $selectedProduct->unit }}</span>
                </div>
            </div>
        </div>
    </div>

    @if($availableQuarantine <= 0)
    <div class="alert alert-warning">
        <i class="fa-solid fa-circle-info"></i> Tidak ada stok karantina (barang rusak) yang tersedia untuk diselesaikan pada produk dan gudang ini.
    </div>
    @else

    {{-- Form Disposisi --}}
    <form method="POST" action="{{ route('inventory.dispositions.store') }}">
        @csrf
        <input type="hidden" name="product_id" value="{{ $selectedProduct->id }}">
        <input type="hidden" name="warehouse_id" value="{{ $selectedWarehouse->id }}">

        <div class="grid grid-2">
            <div class="card">
                <div class="card-header">
                    <h3>2. Rincian Disposisi & Metode Penyelesaian</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Tanggal Penyelesaian <span style="color:var(--danger);">*</span></label>
                        <input type="date" name="disposed_at" value="{{ old('disposed_at', date('Y-m-d')) }}" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Jumlah Disposisi (Qty) <span style="color:var(--danger);">*</span></label>
                        <input type="number" name="qty" x-model.number="qty" min="1" max="{{ $availableQuarantine }}" class="form-control" required>
                        <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">
                            Maksimal {{ number_format($availableQuarantine) }} {{ $selectedProduct->unit }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Metode Penyelesaian <span style="color:var(--danger);">*</span></label>
                        <div style="display:flex; flex-direction:column; gap:10px; margin-top:8px;">
                            <label style="display:flex; align-items:flex-start; gap:10px; padding:12px; border:1px solid var(--border); border-radius:10px; cursor:pointer; background: white;" :style="resolutionType === 'write_off' ? 'border-color:var(--danger); background:#fef2f2;' : ''">
                                <input type="radio" name="resolution_type" value="write_off" x-model="resolutionType" style="margin-top:3px;">
                                <div>
                                    <strong style="color:var(--danger);"><i class="fa-solid fa-trash-can"></i> Write Off (Penghapusan Total)</strong>
                                    <div style="font-size:12.5px; color:var(--text-secondary); margin-top:2px;">
                                        Barang dibuang / tidak bernilai. Mencatat kerugian persediaan sebesar HPP barang ke akun <strong>5-1300 Kerugian Persediaan Rusak</strong>.
                                    </div>
                                </div>
                            </label>

                            <label style="display:flex; align-items:flex-start; gap:10px; padding:12px; border:1px solid var(--border); border-radius:10px; cursor:pointer; background: white;" :style="resolutionType === 'sold_as_reject' ? 'border-color:var(--success); background:#f0fdf4;' : ''">
                                <input type="radio" name="resolution_type" value="sold_as_reject" x-model="resolutionType" style="margin-top:3px;">
                                <div>
                                    <strong style="color:var(--success);"><i class="fa-solid fa-hand-holding-dollar"></i> Sold as Reject (Dijual Sebagai Reject)</strong>
                                    <div style="font-size:12.5px; color:var(--text-secondary); margin-top:2px;">
                                        Barang dijual obral/pengepul secara tunai. Mencatat kas masuk & pendapatan reject (4-1400), serta HPP reject (5-1400).
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="form-group" x-show="resolutionType === 'sold_as_reject'" x-transition>
                        <label class="form-label">Harga Jual Per Unit (Rp) <span style="color:var(--danger);">*</span></label>
                        <input type="number" step="0.01" min="0" name="sale_price" x-model.number="salePrice" class="form-control" placeholder="Contoh: 15000">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Catatan / Alasan Disposisi</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Keterangan tambahan barang rusak atau detail pembeli reject...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Ringkasan Simulasi Jurnal --}}
            <div class="card">
                <div class="card-header">
                    <h3>3. Simulasi Dampak Keuangan & Jurnal</h3>
                </div>
                <div class="card-body">
                    <div style="margin-bottom:16px; padding:12px; background:#f8fafc; border-radius:10px; border:1px solid var(--border);">
                        <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:13.5px;">
                            <span>Total HPP Barang (Nilai Asal):</span>
                            <strong style="color:var(--danger);">Rp <span x-text="totalCost.toLocaleString('id-ID')"></span></strong>
                        </div>
                        <template x-if="resolutionType === 'sold_as_reject'">
                            <div style="display:flex; justify-content:space-between; font-size:13.5px; border-top:1px dashed var(--border); padding-top:6px; margin-top:6px;">
                                <span>Total Penerimaan Jual Reject:</span>
                                <strong style="color:var(--success);">Rp <span x-text="totalRevenue.toLocaleString('id-ID')"></span></strong>
                            </div>
                        </template>
                    </div>

                    <div style="font-size:13px; font-weight:600; color:var(--text-secondary); margin-bottom:8px;">
                        Draft Jurnal Otomatis yang Akan Dibukukan:
                    </div>

                    <div class="table-responsive">
                        <table class="erp-table" style="font-size:12.5px;">
                            <thead>
                                <tr>
                                    <th>Kode - Nama Akun</th>
                                    <th style="text-align:right;">Debet</th>
                                    <th style="text-align:right;">Kredit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="resolutionType === 'write_off'">
                                    <tr style="background:#fff;">
                                        <td><strong>5-1300</strong> Kerugian Persediaan Rusak</td>
                                        <td style="text-align:right; font-weight:600; color:var(--danger);">Rp <span x-text="totalCost.toLocaleString('id-ID')"></span></td>
                                        <td style="text-align:right;">-</td>
                                    </tr>
                                </template>
                                <template x-if="resolutionType === 'write_off'">
                                    <tr style="background:#fff;">
                                        <td>&nbsp;&nbsp;&nbsp;&nbsp;<strong>1-1400</strong> Persediaan Barang</td>
                                        <td style="text-align:right;">-</td>
                                        <td style="text-align:right; font-weight:600; color:var(--danger);">Rp <span x-text="totalCost.toLocaleString('id-ID')"></span></td>
                                    </tr>
                                </template>

                                <template x-if="resolutionType === 'sold_as_reject'">
                                    <tr style="background:#fff;">
                                        <td><strong>1-1100</strong> Kas / Bank</td>
                                        <td style="text-align:right; font-weight:600; color:var(--success);">Rp <span x-text="totalRevenue.toLocaleString('id-ID')"></span></td>
                                        <td style="text-align:right;">-</td>
                                    </tr>
                                </template>
                                <template x-if="resolutionType === 'sold_as_reject'">
                                    <tr style="background:#fff;">
                                        <td>&nbsp;&nbsp;&nbsp;&nbsp;<strong>4-1400</strong> Pendapatan Penjualan Reject</td>
                                        <td style="text-align:right;">-</td>
                                        <td style="text-align:right; font-weight:600; color:var(--success);">Rp <span x-text="totalRevenue.toLocaleString('id-ID')"></span></td>
                                    </tr>
                                </template>
                                <template x-if="resolutionType === 'sold_as_reject'">
                                    <tr style="background:#fff;">
                                        <td><strong>5-1400</strong> HPP Penjualan Reject</td>
                                        <td style="text-align:right; font-weight:600; color:var(--danger);">Rp <span x-text="totalCost.toLocaleString('id-ID')"></span></td>
                                        <td style="text-align:right;">-</td>
                                    </tr>
                                </template>
                                <template x-if="resolutionType === 'sold_as_reject'">
                                    <tr style="background:#fff;">
                                        <td>&nbsp;&nbsp;&nbsp;&nbsp;<strong>1-1400</strong> Persediaan Barang</td>
                                        <td style="text-align:right;">-</td>
                                        <td style="text-align:right; font-weight:600; color:var(--danger);">Rp <span x-text="totalCost.toLocaleString('id-ID')"></span></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top:24px; display:flex; justify-content:flex-end;">
                        <button type="submit" class="btn btn-primary" style="padding:12px 24px; font-size:15px; font-weight:600;">
                            <i class="fa-solid fa-check"></i> Simpan Disposisi & Pembukuan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @endif
    @endif
</div>
@endsection
