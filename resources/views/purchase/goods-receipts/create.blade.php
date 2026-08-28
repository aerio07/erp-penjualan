@extends('layouts.app')
@section('title', 'Catat Penerimaan Barang')
@section('page-title', 'Catat Penerimaan Barang (GRN)')

@section('content')
<div class="animate-in" x-data="grnForm()">
    <div class="page-header">
        <div>
            <h1>Catat Penerimaan Barang</h1>
            <p>Input total barang fisik yang datang dari supplier dan jumlah yang rusak. Qty kondisi baik yang masuk stok akan dihitung otomatis.</p>
        </div>
        <a href="{{ route('purchase.goods-receipts.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <form method="POST" action="{{ route('purchase.goods-receipts.store') }}" @submit="return validateForm($event)">
        @csrf

        {{-- Header Info --}}
        <div class="grid grid-3" style="margin-bottom:20px;">
            <div class="card" style="grid-column:span 2;">
                <div class="card-header"><h3>Informasi Surat Jalan / Penerimaan</h3></div>
                <div class="card-body">
                    <div class="form-row form-row-2">
                        <div class="form-group">
                            <label class="form-label">Purchase Order <span style="color:var(--danger);">*</span></label>
                            <select name="purchase_order_id" class="form-control" x-model="selectedPoId" @change="onPoChange()" required>
                                <option value="">-- Pilih PO Confirmed / Partial --</option>
                                @foreach($confirmedPos as $po)
                                <option value="{{ $po->id }}">
                                    {{ $po->po_number }} - {{ $po->supplier->name }} ({{ $po->order_date->format('d/m/Y') }})
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tanggal Diterima <span style="color:var(--danger);">*</span></label>
                            <input type="date" name="received_date" value="{{ old('received_date', date('Y-m-d')) }}" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nomor Surat Jalan Supplier & Catatan Tambahan</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Contoh: No. SJ: SJ-SUP-9981, Driver: Pak Budi (B 1234 CD), kondisi segel aman...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- PO Overview Card --}}
            <div class="card">
                <div class="card-header"><h3>Ringkasan Penerimaan</h3></div>
                <div class="card-body">
                    <template x-if="selectedPo">
                        <div style="display:flex; flex-direction:column; gap:12px; font-size:14px;">
                            <div>
                                <div style="font-size:12px; color:var(--text-secondary);">Supplier</div>
                                <div style="font-weight:600;" x-text="selectedPo.supplier ? selectedPo.supplier.name : '-'"></div>
                            </div>
                            <template x-if="selectedPo.warehouse">
                                <div>
                                    <div style="font-size:12px; color:var(--text-secondary);">Gudang Tujuan PO</div>
                                    <div style="font-weight:600; color:var(--primary);"><i class="fa-solid fa-warehouse"></i> <span x-text="selectedPo.warehouse.name + ' (' + selectedPo.warehouse.code + ')'"></span></div>
                                </div>
                            </template>
                            <template x-if="selectedPo.ship_to">
                                <div style="font-size:12px; color:var(--text-secondary); background:#f8fafc; padding:8px 10px; border-radius:6px;">
                                    <i class="fa-solid fa-truck-ramp-box text-primary"></i> <strong>Ship To:</strong> <span x-text="selectedPo.ship_to"></span>
                                </div>
                            </template>
                            <hr style="border:none; border-top:1px solid var(--border);">
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:var(--text-secondary);">Total Fisik Datang</span>
                                <span style="font-weight:700;" x-text="totalPhysicalQty + ' pcs'"></span>
                            </div>
                            <div style="display:flex; justify-content:space-between; color:var(--danger);">
                                <span><i class="fa-solid fa-triangle-exclamation"></i> Kondisi Rusak / Reject</span>
                                <span style="font-weight:700;" x-text="totalRejectedQty + ' pcs'"></span>
                            </div>
                            <hr style="border:none; border-top:1px dashed var(--border);">
                            <div style="display:flex; justify-content:space-between; color:var(--success); font-size:15px;">
                                <span><i class="fa-solid fa-circle-check"></i> <strong>Qty Baik (Masuk Stok)</strong></span>
                                <span style="font-weight:800;" x-text="totalAcceptedQty + ' pcs'"></span>
                            </div>
                        </div>
                    </template>
                    <template x-if="!selectedPo">
                        <div style="text-align:center; padding:24px 0; color:var(--text-secondary); font-size:13px;">
                            <i class="fa-solid fa-file-invoice" style="font-size:28px; margin-bottom:8px; display:block; opacity:0.4;"></i>
                            Pilih Purchase Order terlebih dahulu
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Items Table --}}
        <div class="card" style="margin-bottom:20px;" x-show="selectedPo">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h3>Item Barang Diterima & Alokasi Gudang</h3>
                    <p style="font-size:13px; color:var(--text-secondary); margin:0;">
                        Isi <strong>Qty Datang Fisik</strong> dan <strong>Qty Rusak</strong>. Sisa PO dihitung dari barang yang belum tiba secara fisik.
                    </p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th style="min-width:160px;">Produk</th>
                            <th style="text-align:center; width:75px;">Dipesan</th>
                            <th style="text-align:center; width:85px;">Sudah Tiba</th>
                            <th style="text-align:center; width:80px;">Sisa PO</th>
                            <th style="min-width:150px;">Gudang Tujuan <span style="color:var(--danger);">*</span></th>
                            <th style="width:115px;">Qty Datang <span style="color:var(--danger);">*</span></th>
                            <th style="width:105px;">Qty Rusak</th>
                            <th style="text-align:center; width:120px; background:rgba(16, 185, 129, 0.08);">Qty Baik (Masuk Stok)</th>
                            <th style="min-width:135px;">Alasan Sisa / Rusak</th>
                            <th style="min-width:115px;">Kondisi</th>
                            <th style="text-align:center; width:90px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, idx) in rows" :key="row.uid">
                            <tr>
                                <td>
                                    <div style="font-weight:600;" x-text="row.product_name"></div>
                                    <div style="font-size:12px; color:var(--text-secondary);" x-text="row.product_sku"></div>
                                    <input type="hidden" :name="`items[${idx}][purchase_order_item_id]`" :value="row.purchase_order_item_id">
                                    {{-- Hidden input qty_received (Qty Baik yang sudah dikalkulasi) --}}
                                    <input type="hidden" :name="`items[${idx}][qty_received]`" :value="rowGoodQty(row)">
                                    <input type="hidden" :name="`items[${idx}][qty_physical]`" :value="row.qty_physical">
                                </td>
                                <td style="text-align:center; font-weight:600;" x-text="row.qty_ordered + ' ' + (row.unit || '')"></td>
                                <td style="text-align:center; color:var(--text-secondary);" x-text="row.qty_already_arrived"></td>
                                <td style="text-align:center;">
                                    <span class="badge badge-pending" x-text="row.qty_remaining"></span>
                                </td>
                                <td>
                                    <select :name="`items[${idx}][warehouse_id]`" class="form-control" x-model="row.warehouse_id" required>
                                        <option value="">-- Pilih Gudang --</option>
                                        @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->code }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" x-model.number="row.qty_physical" @input="onQtyChange(row)" class="form-control" min="0" required placeholder="0" title="Total fisik yang datang">
                                </td>
                                <td>
                                    <input type="number" :name="`items[${idx}][qty_rejected]`" x-model.number="row.qty_rejected" @input="onQtyChange(row)" class="form-control" min="0" placeholder="0" title="Jumlah rusak">
                                </td>
                                <td style="text-align:center; background:rgba(16, 185, 129, 0.05);">
                                    <span class="badge badge-done" style="font-size:13px; font-weight:700; padding:6px 10px;" x-text="rowGoodQty(row) + ' ' + (row.unit || 'pcs')"></span>
                                    <template x-if="Number(row.qty_rejected) > Number(row.qty_physical)">
                                        <div style="font-size:11px; color:var(--danger); margin-top:2px;">Rusak &gt; Datang!</div>
                                    </template>
                                </td>
                                <td>
                                    <select :name="`items[${idx}][shortage_reason]`" class="form-control" x-model="row.shortage_reason">
                                        <option value="none">Tidak Ada Kurang / Terpenuhi</option>
                                        <option value="not_shipped">Belum Dikirim Supplier (Parsial)</option>
                                        <option value="damaged_in_transit">Rusak Saat Pengiriman</option>
                                    </select>
                                </td>
                                <td>
                                    <select :name="`items[${idx}][condition]`" class="form-control" x-model="row.condition">
                                        <option value="Good">Baik / Utuh</option>
                                        <option value="Damaged">Rusak / Cacat</option>
                                        <option value="Incomplete">Kurang Lengkap</option>
                                    </select>
                                </td>
                                <td style="text-align:center;">
                                    <div style="display:flex; gap:6px; justify-content:center;">
                                        <button type="button" class="btn btn-secondary btn-sm" @click="splitRow(row)" title="Bagi alokasi ke gudang lain">
                                            <i class="fa-solid fa-code-fork"></i> Split
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm btn-icon" @click="removeRow(idx)" x-show="countRowsForPoItem(row.purchase_order_item_id) > 1" title="Hapus split">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Item allocation status warnings --}}
            <div style="padding:14px 20px; border-top:1px solid var(--border); background:#f8fafc; border-radius:0 0 var(--radius) var(--radius);">
                <template x-for="itemStat in itemStats" :key="itemStat.id">
                    <div style="display:flex; align-items:center; justify-content:space-between; font-size:13px; margin-bottom:4px;">
                        <span>
                            <strong x-text="itemStat.name"></strong> — Datang Fisik: <span style="font-weight:600;" x-text="itemStat.physical"></span> / Sisa PO Belum Tiba: <span x-text="itemStat.remaining"></span>
                            <span style="color:var(--text-secondary); margin-left:8px;" x-text="'(Baik: ' + itemStat.good + ' masuk stok, Rusak: ' + itemStat.rejected + ' diretur)'"></span>
                        </span>
                        <span>
                            <template x-if="itemStat.physical > itemStat.remaining">
                                <span style="color:var(--danger); font-weight:600;">
                                    <i class="fa-solid fa-circle-exclamation"></i> Melebihi sisa PO (+<span x-text="itemStat.physical - itemStat.remaining"></span>)
                                </span>
                            </template>
                            <template x-if="itemStat.hasInvalidReject">
                                <span style="color:var(--danger); font-weight:600; margin-left:8px;">
                                    <i class="fa-solid fa-circle-xmark"></i> Qty rusak melebihi fisik datang
                                </span>
                            </template>
                            <template x-if="itemStat.physical <= itemStat.remaining && itemStat.physical > 0 && !itemStat.hasInvalidReject">
                                <span style="color:var(--success); font-weight:600;">
                                    <i class="fa-solid fa-circle-check"></i> Sesuai (Baik: <span x-text="itemStat.good"></span>)
                                </span>
                            </template>
                        </span>
                    </div>
                </template>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:12px;" x-show="selectedPo">
            <a href="{{ route('purchase.goods-receipts.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary" :disabled="hasExceededAllocation || totalPhysicalQty <= 0 || hasInvalidRejection">
                <i class="fa-solid fa-boxes-packing"></i> Simpan & Update Stok Gudang
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const confirmedPos = @json($confirmedPos->keyBy('id'));
const defaultWarehouseId = "{{ $warehouses->first()?->id ?? '' }}";
const initialPoId = "{{ $selectedPoId ?? '' }}";

function grnForm() {
    return {
        selectedPoId: initialPoId,
        selectedPo: null,
        rows: [],
        nextUid: 1,

        init() {
            if (this.selectedPoId) {
                this.onPoChange();
            }
        },

        onPoChange() {
            if (!this.selectedPoId || !confirmedPos[this.selectedPoId]) {
                this.selectedPo = null;
                this.rows = [];
                return;
            }

            this.selectedPo = confirmedPos[this.selectedPoId];
            this.rows = [];

            const poWarehouseId = (this.selectedPo && this.selectedPo.warehouse_id) 
                ? String(this.selectedPo.warehouse_id) 
                : defaultWarehouseId;

            this.selectedPo.items.forEach(item => {
                const alreadyArrived = (item.qty_received || 0) + (item.qty_rejected || 0);
                const remaining = Math.max(0, item.qty_ordered - alreadyArrived);
                this.rows.push({
                    uid: this.nextUid++,
                    purchase_order_item_id: item.id,
                    product_name: item.product ? item.product.name : 'Item #' + item.id,
                    product_sku: item.product ? item.product.sku : '',
                    unit: item.product ? item.product.unit : 'pcs',
                    qty_ordered: item.qty_ordered,
                    qty_already_received: item.qty_received || 0,
                    qty_already_rejected: item.qty_rejected || 0,
                    qty_already_arrived: alreadyArrived,
                    qty_remaining: remaining,
                    warehouse_id: poWarehouseId,
                    qty_physical: remaining, // Datang fisik default = sisa PO yang belum tiba
                    qty_rejected: 0,         // Rusak default = 0
                    shortage_reason: 'none',
                    condition: 'Good',
                });
            });
        },

        // Hitung Qty Baik otomatis = Qty Datang Fisik - Qty Rusak
        rowGoodQty(row) {
            const physical = Math.max(0, Number(row.qty_physical) || 0);
            const rejected = Math.max(0, Number(row.qty_rejected) || 0);
            return Math.max(0, physical - rejected);
        },

        onQtyChange(row) {
            const physical = Number(row.qty_physical) || 0;
            const rejected = Number(row.qty_rejected) || 0;
            
            if (rejected > 0) {
                row.shortage_reason = 'damaged_in_transit';
                row.condition = 'Damaged';
            } else if (physical < row.qty_remaining) {
                row.shortage_reason = 'not_shipped';
                row.condition = 'Good';
            } else {
                row.shortage_reason = 'none';
                row.condition = 'Good';
            }
        },

        splitRow(sourceRow) {
            // Hitung alokasi fisik saat ini untuk PO item ini
            const currentPhysical = this.rows
                .filter(r => r.purchase_order_item_id === sourceRow.purchase_order_item_id)
                .reduce((sum, r) => sum + (Number(r.qty_physical) || 0), 0);
            
            const unallocated = Math.max(0, sourceRow.qty_remaining - currentPhysical);

            // Tambahkan baris baru untuk PO item yang sama
            this.rows.push({
                uid: this.nextUid++,
                purchase_order_item_id: sourceRow.purchase_order_item_id,
                product_name: sourceRow.product_name,
                product_sku: sourceRow.product_sku,
                unit: sourceRow.unit,
                qty_ordered: sourceRow.qty_ordered,
                qty_already_received: sourceRow.qty_already_received,
                qty_already_rejected: sourceRow.qty_already_rejected,
                qty_already_arrived: sourceRow.qty_already_arrived,
                qty_remaining: sourceRow.qty_remaining,
                warehouse_id: '',
                qty_physical: unallocated,
                qty_rejected: 0,
                shortage_reason: unallocated < sourceRow.qty_remaining ? 'not_shipped' : 'none',
                condition: 'Good',
            });
        },

        removeRow(idx) {
            this.rows.splice(idx, 1);
        },

        countRowsForPoItem(poItemId) {
            return this.rows.filter(r => r.purchase_order_item_id === poItemId).length;
        },

        get totalPhysicalQty() {
            return this.rows.reduce((sum, r) => sum + (Number(r.qty_physical) || 0), 0);
        },

        get totalRejectedQty() {
            return this.rows.reduce((sum, r) => sum + (Number(r.qty_rejected) || 0), 0);
        },

        get totalAcceptedQty() {
            return this.rows.reduce((sum, r) => sum + this.rowGoodQty(r), 0);
        },

        get itemStats() {
            if (!this.selectedPo) return [];
            return this.selectedPo.items.map(item => {
                const alreadyArrived = (item.qty_received || 0) + (item.qty_rejected || 0);
                const remaining = Math.max(0, item.qty_ordered - alreadyArrived);
                const itemRows = this.rows.filter(r => r.purchase_order_item_id === item.id);
                const physical = itemRows.reduce((s, r) => s + (Number(r.qty_physical) || 0), 0);
                const rejected = itemRows.reduce((s, r) => s + (Number(r.qty_rejected) || 0), 0);
                const good = itemRows.reduce((s, r) => s + this.rowGoodQty(r), 0);
                const hasInvalidReject = itemRows.some(r => Number(r.qty_rejected) > Number(r.qty_physical));

                return {
                    id: item.id,
                    name: item.product ? item.product.name : 'Item #' + item.id,
                    remaining: remaining,
                    physical: physical,
                    good: good,
                    rejected: rejected,
                    hasInvalidReject: hasInvalidReject,
                };
            });
        },

        get hasExceededAllocation() {
            return this.itemStats.some(stat => stat.physical > stat.remaining);
        },

        get hasInvalidRejection() {
            return this.itemStats.some(stat => stat.hasInvalidReject);
        },

        validateForm(e) {
            if (this.hasInvalidRejection) {
                alert('Ada baris dengan Qty Rusak yang melebihi Qty Datang Fisik. Mohon periksa kembali.');
                e.preventDefault();
                return false;
            }
            if (this.hasExceededAllocation) {
                alert('Ada item yang total penerimaan fisiknya melebihi sisa PO. Mohon periksa kembali.');
                e.preventDefault();
                return false;
            }
            if (this.totalPhysicalQty <= 0) {
                alert('Isi minimal satu qty fisik barang yang datang.');
                e.preventDefault();
                return false;
            }
            return true;
        }
    }
}
</script>
@endpush
