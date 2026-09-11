@extends('layouts.app')
@section('title', 'Catat Penerimaan Barang')
@section('page-title', 'Catat Penerimaan Barang (GRN)')

@section('content')
<div class="animate-in" x-data="grnForm()">
    <x-page-header 
        title="Catat Penerimaan Barang (GRN)" 
        subtitle="Input total barang fisik yang datang dari supplier dan jumlah yang rusak. Qty kondisi baik yang masuk stok akan dihitung otomatis."
        :back-url="route('purchase.goods-receipts.index')"
    />

    <form method="POST" action="{{ route('purchase.goods-receipts.store') }}" @submit="validateForm($event)">
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

        {{-- Auto-Generate Purchase Invoice Option --}}
        <div class="card" style="margin-bottom:20px; border-left: 4px solid var(--primary);" x-show="selectedPo">
            <div class="card-header" style="background:#f8fafc; display:flex !important; flex-direction:row !important; justify-content:space-between !important; align-items:center !important; flex-wrap:wrap !important; gap:16px !important;">
                <div style="flex:1 1 300px; min-width:250px;">
                    <h3 style="display:flex; align-items:center; gap:8px; margin:0; font-size:15px; font-weight:700;">
                        <i class="fa-solid fa-file-invoice-dollar" style="color:var(--primary);"></i>
                        Faktur Pembelian (Purchase Invoice) & Jurnal Akuntansi
                    </h3>
                    <p style="margin:4px 0 0 0; font-size:12.5px; color:var(--text-secondary);">
                        Tentukan apakah penerimaan barang ini langsung diterbitkan tagihan/faktur pembayarannya ke supplier.
                    </p>
                </div>
                <div style="display:inline-flex !important; width:auto !important; flex:0 0 auto !important;">
                    <label style="display:inline-flex !important; width:auto !important; align-items:center !important; gap:10px !important; cursor:pointer !important; background:#ffffff !important; padding:8px 14px !important; border:1px solid var(--border) !important; border-radius:8px !important; font-weight:600 !important; font-size:13px !important; box-shadow:0 1px 2px rgba(0,0,0,0.05) !important; user-select:none !important; margin:0 !important; text-transform:none !important; letter-spacing:normal !important;">
                        <input type="checkbox" name="auto_generate_invoice" value="1" x-model="autoGenerateInvoice" @change="onToggleAutoInvoice()" style="width:18px !important; height:18px !important; accent-color:var(--primary); cursor:pointer; margin:0 !important;">
                        <span style="color:#0e1b35; font-size:13px; font-weight:600;">Langsung Terbitkan Purchase Invoice & Posting Jurnal</span>
                    </label>
                </div>
            </div>
            <div class="card-body">
                <template x-if="!autoGenerateInvoice">
                    <div style="display:flex; align-items:center; gap:12px; padding:12px 16px; background:#eff6ff; border-radius:8px; color:#1e40af; font-size:13px;">
                        <i class="fa-solid fa-circle-info" style="font-size:18px; flex-shrink:0;"></i>
                        <div>
                            <strong>Alur Bertahap (Default):</strong> Penerimaan barang hanya akan menambah stok fisik di gudang. Tagihan supplier (Purchase Invoice) dan pengakuan hutang akuntansi dapat diterbitkan terpisah nanti oleh bagian Finance.
                        </div>
                    </div>
                </template>

                <template x-if="autoGenerateInvoice">
                    <div style="display:flex; flex-direction:column; gap:16px;">
                        <div style="display:flex; align-items:center; gap:12px; padding:12px 16px; background:#ecfdf5; border-radius:8px; color:#065f46; font-size:13px;">
                            <i class="fa-solid fa-check-double" style="font-size:18px; flex-shrink:0;"></i>
                            <div>
                                <strong>Alur Terintegrasi:</strong> Stok masuk ke gudang, Faktur Pembelian langsung terbit, dan Jurnal Akuntansi (Persediaan, PPN Masukan, dan Hutang Usaha) langsung otomatis terposting ke buku besar!
                            </div>
                        </div>

                        <div class="form-row form-row-3">
                            <div class="form-group">
                                <label class="form-label">No. Faktur / Nota Supplier</label>
                                <input type="text" name="supplier_invoice_number" x-model="supplierInvoiceNumber" class="form-control" placeholder="No. invoice resmi vendor (opsional)">
                                <small style="color:var(--text-secondary); font-size:11.5px;">Nomor referensi faktur fisik vendor</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Tanggal Faktur / Invoice <span style="color:var(--danger);">*</span></label>
                                <input type="date" name="invoice_date" x-model="invoiceDate" @change="onInvoiceDateChange()" class="form-control" :required="autoGenerateInvoice">
                                <small style="color:var(--text-secondary); font-size:11.5px;">Tgl pengakuan hutang dagang</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Tanggal Jatuh Tempo <span style="color:var(--danger);">*</span></label>
                                <input type="date" name="due_date" x-model="dueDate" class="form-control" :required="autoGenerateInvoice">
                            </div>
                        </div>

                        <div class="form-row form-row-2">
                            <div class="form-group">
                                <label class="form-label">Tarif PPN (%)</label>
                                <input type="number" name="invoice_tax_rate" x-model.number="taxRate" class="form-control" step="0.01" min="0" max="100">
                                <small style="color:var(--text-secondary); font-size:11.5px;">Diambil otomatis dari tarif pajak PO</small>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <x-form-actions
            :cancel-url="route('purchase.goods-receipts.index')">
            <x-slot:submitButton>
                <button type="submit" 
                        class="btn btn-primary inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold rounded-lg text-white shadow-xs">
                    <i class="fa-solid" :class="autoGenerateInvoice ? 'fa-file-invoice-dollar' : 'fa-boxes-packing'"></i>
                    <span x-text="autoGenerateInvoice ? 'Simpan Penerimaan & Terbitkan Faktur Pembelian' : 'Simpan & Update Stok Gudang'"></span>
                </button>
            </x-slot:submitButton>
        </x-form-actions>
    </form>
</div>
@endsection

@push('scripts')
<script>
const confirmedPos = @json($confirmedPos->keyBy('id'));
const defaultWarehouseId = "{{ $warehouses->first()?->id ?? '' }}";
const initialPoId = "{{ $selectedPoId ?? '' }}";

function parseTermDays(term) {
    if (typeof term === 'number') return term;
    if (!term) return 30;
    if (String(term).toUpperCase().trim() === 'COD') return 0;
    const match = String(term).match(/\d+/);
    return match ? parseInt(match[0], 10) : 30;
}

function grnForm() {
    return {
        selectedPoId: initialPoId,
        selectedPo: null,
        rows: [],
        nextUid: 1,
        autoGenerateInvoice: {{ old('auto_generate_invoice') ? 'true' : 'false' }},
        supplierInvoiceNumber: @json(old('supplier_invoice_number', '')),
        receivedDate: "{{ old('received_date', date('Y-m-d')) }}",
        invoiceDate: "{{ old('invoice_date', date('Y-m-d')) }}",
        dueDate: "{{ old('due_date', '') }}",
        taxRate: 11,
        paymentTerm: 30,
        paymentTermLabel: 'NET 30',

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
            this.taxRate = parseFloat(this.selectedPo.tax_rate) || 0;
            const supp = this.selectedPo.supplier;
            this.paymentTerm = supp ? (supp.payment_term_days ?? parseTermDays(supp.payment_term)) : 30;
            this.paymentTermLabel = supp ? (supp.payment_term || (this.paymentTerm + ' hari')) : 'NET 30';

            if (!this.dueDate) {
                this.calculateDueDate();
            }

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

        get isGrnInvalid() {
            return this.hasExceededAllocation || this.totalPhysicalQty <= 0 || this.hasInvalidRejection;
        },

        validateForm(e) {
            if (!this.selectedPo) {
                alert('Pilih Purchase Order (PO) terlebih dahulu.');
                e.preventDefault();
                return false;
            }
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
        },

        onToggleAutoInvoice() {
            if (this.autoGenerateInvoice) {
                if (!this.invoiceDate) this.invoiceDate = this.receivedDate;
                this.calculateDueDate();
            }
        },

        onInvoiceDateChange() {
            this.calculateDueDate();
        },

        calculateDueDate() {
            if (!this.invoiceDate) return;
            const d = new Date(this.invoiceDate);
            if (isNaN(d.getTime())) return;
            const term = parseInt(this.paymentTerm) || 0;
            d.setDate(d.getDate() + term);
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            this.dueDate = `${yyyy}-${mm}-${dd}`;
        }
    }
}
</script>
@endpush
