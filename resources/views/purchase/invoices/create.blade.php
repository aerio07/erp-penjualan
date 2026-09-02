@extends('layouts.app')
@section('title', 'Terbitkan Invoice Pembelian (1 LPB = 1 Invoice)')
@section('page-title', 'Terbitkan Invoice Pembelian')

@section('content')
<div class="animate-in" x-data="invForm()">
    <div class="page-header">
        <div>
            <h1>Terbitkan Invoice Pembelian</h1>
            <p>Pilih <strong>Purchase Order</strong> lalu pilih <strong>1 Laporan Penerimaan Barang (LPB)</strong> — seluruh qty diterima di LPB tersebut akan langsung ditagihkan penuh.</p>
        </div>
        <a href="{{ route('purchase.invoices.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <form method="POST" action="{{ route('purchase.invoices.store') }}">
        @csrf

        <div class="grid grid-3" style="margin-bottom:20px;">
            {{-- Form Header --}}
            <div class="card" style="grid-column:span 2;">
                <div class="card-header"><h3>Informasi Faktur / Invoice</h3></div>
                <div class="card-body">
                    <div class="form-row form-row-2">
                        <div class="form-group">
                            <label class="form-label">Purchase Order <span style="color:var(--danger);">*</span></label>
                            <select name="purchase_order_id" class="form-control" x-model="selectedPoId" @change="onPoChange()" required>
                                <option value="">-- Pilih Purchase Order --</option>
                                @foreach($orders as $po)
                                <option value="{{ $po->id }}">
                                    {{ $po->po_number }} - {{ $po->supplier->name }} (Status: {{ ucfirst(str_replace('_', ' ', $po->status)) }})
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Laporan Penerimaan Barang (LPB) <span style="color:var(--danger);">*</span></label>
                            <select name="goods_receipt_id" class="form-control" x-model="selectedGrnId" @change="onGrnChange()" required :disabled="!selectedPoId">
                                <option value="">-- Pilih LPB --</option>
                                <template x-for="grn in availableGrns" :key="grn.id">
                                    <option :value="grn.id" x-text="grn.display_label"></option>
                                </template>
                            </select>
                            <template x-if="selectedPoId && availableGrns.length === 0">
                                <div style="color:var(--warning); font-size:12px; margin-top:4px; font-weight:600;">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Semua LPB pada PO ini sudah pernah di-invoice.
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="form-row form-row-4">
                        <div class="form-group">
                            <label class="form-label">No. Faktur Supplier</label>
                            <input type="text" name="supplier_invoice_number" value="{{ old('supplier_invoice_number') }}" class="form-control" placeholder="INV/SUP/2026/001">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tarif PPN (%) <span style="color:var(--danger);">*</span></label>
                            <input type="number" name="tax_rate" x-model.number="taxRate" class="form-control" step="0.01" min="0" max="100" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tanggal Invoice <span style="color:var(--danger);">*</span></label>
                            <input type="date" name="invoice_date" value="{{ old('invoice_date', date('Y-m-d')) }}" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Jatuh Tempo <span style="color:var(--danger);">*</span></label>
                            <input type="date" name="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="1" placeholder="Catatan invoice...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Summary Card --}}
            <div class="card">
                <div class="card-header"><h3>Ringkasan Invoice</h3></div>
                <div class="card-body">
                    <template x-if="selectedGrn">
                        <div style="display:flex; flex-direction:column; gap:12px; font-size:14px;">
                            <div>
                                <div style="font-size:12px; color:var(--text-secondary);">Supplier</div>
                                <div style="font-weight:600;" x-text="selectedPoData ? selectedPoData.supplier.name : '-'"></div>
                            </div>
                            <div>
                                <div style="font-size:12px; color:var(--text-secondary);">LPB Sumber</div>
                                <div style="font-weight:600; color:var(--primary);" x-text="selectedGrn.receipt_number"></div>
                            </div>
                            <hr style="border:none; border-top:1px solid var(--border);">
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:var(--text-secondary);">Subtotal Item LPB</span>
                                <span style="font-weight:600;" x-text="'Rp ' + formatNum(rawSubtotal)"></span>
                            </div>
                            <template x-if="proratedDiscount > 0">
                                <div style="display:flex; justify-content:space-between; color:var(--danger);">
                                    <span>Prorasi Diskon Header PO</span>
                                    <span style="font-weight:600;" x-text="'- Rp ' + formatNum(proratedDiscount)"></span>
                                </div>
                            </template>
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:var(--text-secondary);">DPP (Dasar Pengenaan Pajak)</span>
                                <span style="font-weight:600;" x-text="'Rp ' + formatNum(dpp)"></span>
                            </div>
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:var(--text-secondary);">PPN (<span x-text="taxRate"></span>%)</span>
                                <span style="font-weight:600;" x-text="'Rp ' + formatNum(taxAmount)"></span>
                            </div>
                            <hr style="border:none; border-top:1px solid var(--border);">
                            <div style="display:flex; justify-content:space-between; font-size:16px; font-weight:700;">
                                <span>Total Tagihan Invoice</span>
                                <span style="color:var(--primary);" x-text="'Rp ' + formatNum(totalAmount)"></span>
                            </div>
                            <div x-show="totalReturnedQty > 0" style="padding:10px; background:#fef2f2; border-radius:8px; border:1px solid #fecaca; font-size:12px; color:#991b1b; margin-top:8px;">
                                <i class="fa-solid fa-rotate-left"></i> <strong>Penyesuaian Retur:</strong> Sebanyak <span style="font-weight:700;" x-text="totalReturnedQty"></span> unit telah diretur ke supplier sebelum invoice diterbitkan. Invoice hanya menagih barang bersih (<span style="font-weight:700;" x-text="totalQty"></span> unit).
                            </div>
                            <div x-show="totalReturnedQty === 0" style="padding:10px; background:#f0fdf4; border-radius:8px; border:1px solid #bbf7d0; font-size:12.5px; color:#166534; margin-top:8px;">
                                <i class="fa-solid fa-shield-halved"></i> <strong>1 LPB = 1 Invoice:</strong> Menagih penuh <span x-text="totalQty"></span> unit dari LPB <span x-text="selectedGrn.receipt_number"></span>.
                            </div>
                        </div>
                    </template>
                    <template x-if="!selectedGrn">
                        <div style="text-align:center; padding:24px 0; color:var(--text-secondary); font-size:13px;">
                            <i class="fa-solid fa-file-invoice-dollar" style="font-size:28px; margin-bottom:8px; display:block; opacity:0.4;"></i>
                            Pilih Purchase Order dan LPB terlebih dahulu
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Detail Item dari LPB (read-only) --}}
        <div class="card" style="margin-bottom:20px;" x-show="selectedGrn">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h3>Rincian Item dari LPB</h3>
                <template x-if="totalReturnedQty > 0">
                    <span class="badge" style="background:#fef2f2; color:#b91c1c; font-size:11px; padding:4px 8px;">
                        <i class="fa-solid fa-rotate-left"></i> Total Diretur: <span x-text="totalReturnedQty"></span> unit
                    </span>
                </template>
            </div>
            <div class="table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th style="text-align:center; width:90px;">Dipesan (PO)</th>
                            <th style="text-align:center; width:95px;">Diterima (LPB)</th>
                            <th style="text-align:center; width:85px; color:#dc2626;">Diretur</th>
                            <th style="text-align:center; width:110px; background:rgba(16, 185, 129, 0.08);">Ditagih (Net)</th>
                            <th style="text-align:right; width:130px;">Harga Satuan</th>
                            <th style="text-align:center; width:80px;">Diskon</th>
                            <th style="text-align:right; width:140px;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="item in grnItems" :key="item.grn_item_id">
                            <tr>
                                <td>
                                    <div style="font-weight:600;" x-text="item.product_name"></div>
                                    <div style="font-size:12px; color:var(--text-secondary);" x-text="item.product_sku"></div>
                                </td>
                                <td style="text-align:center;" x-text="item.qty_ordered + ' ' + item.product_unit"></td>
                                <td style="text-align:center;" x-text="item.qty_received + ' ' + item.product_unit"></td>
                                <td style="text-align:center; font-weight:600; color:#dc2626;" x-text="item.qty_returned > 0 ? '-' + item.qty_returned : '-'"></td>
                                <td style="text-align:center; background:rgba(16, 185, 129, 0.05);">
                                    <span class="badge badge-done" style="font-weight:700;" x-text="item.qty_invoiced + ' ' + item.product_unit"></span>
                                </td>
                                <td style="text-align:right;" x-text="'Rp ' + formatNum(item.unit_price)"></td>
                                <td style="text-align:center;" x-text="item.discount_percent > 0 ? item.discount_percent + '%' : '-'"></td>
                                <td style="text-align:right; font-weight:600; color:var(--primary);" x-text="'Rp ' + formatNum(item.line_subtotal)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:12px;" x-show="selectedGrn">
            <a href="{{ route('purchase.invoices.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary" :disabled="!selectedGrn || totalQty <= 0">
                <i class="fa-solid fa-floppy-disk"></i> Terbitkan Invoice & Posting Jurnal
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const ordersData = @json($ordersData);
const receiptsData = @json($availableReceipts->keyBy('id'));
const initialPoId = "{{ $selectedPoId ?? '' }}";
const initialGrnId = "{{ $selectedGrnId ?? '' }}";

function invForm() {
    return {
        selectedPoId: initialPoId,
        selectedGrnId: initialGrnId,
        selectedPoData: null,
        selectedGrn: null,
        availableGrns: [],
        grnItems: [],
        taxRate: 11,

        init() {
            if (this.selectedPoId) {
                const savedGrnId = this.selectedGrnId;
                this.onPoChange();
                if (savedGrnId) {
                    this.selectedGrnId = savedGrnId;
                    this.onGrnChange();
                }
            }
        },

        onPoChange() {
            this.selectedGrnId = '';
            this.selectedGrn = null;
            this.grnItems = [];

            if (!this.selectedPoId || !ordersData[this.selectedPoId]) {
                this.selectedPoData = null;
                this.availableGrns = [];
                return;
            }
            this.selectedPoData = ordersData[this.selectedPoId];
            this.taxRate = parseFloat(this.selectedPoData.tax_rate) || 11;

            // Bangun daftar LPB yang belum diinvoice dari data server
            this.availableGrns = Object.values(receiptsData)
                .filter(grn => String(grn.purchase_order_id) === String(this.selectedPoId))
                .map(grn => {
                    const totalReceived = (grn.items || []).reduce((s, it) => s + (Number(it.qty_received) || 0), 0);
                    const totalReturned = (grn.items || []).reduce((s, it) => s + (Number(it.qty_returned_completed_accepted) || 0), 0);
                    const totalQty = Math.max(0, totalReceived - totalReturned);
                    const tgl = grn.received_date ? String(grn.received_date).substring(0, 10) : '-';
                    const retInfo = totalReturned > 0 ? ` (Retur: ${totalReturned} unit)` : '';
                    return {
                        ...grn,
                        total_qty: totalQty,
                        total_returned: totalReturned,
                        display_label: `${grn.receipt_number} · Tgl: ${tgl} · Ditagih: ${totalQty} unit${retInfo}`
                    };
                });
        },

        onGrnChange() {
            if (!this.selectedGrnId || !receiptsData[this.selectedGrnId]) {
                this.selectedGrn = null;
                this.grnItems = [];
                return;
            }
            this.selectedGrn = receiptsData[this.selectedGrnId];

            // Bangun data item dari LPB yang dipilih (kurangi retur completed jika ada)
            this.grnItems = (this.selectedGrn.items || []).map(grnItem => {
                const poItem = grnItem.purchase_order_item || {};
                const product = poItem.product || {};
                const qtyReceived = Number(grnItem.qty_received) || 0;
                const qtyReturned = Number(grnItem.qty_returned_completed_accepted) || 0;
                const qtyInvoiced = Math.max(0, qtyReceived - qtyReturned);
                const unitPrice = Number(poItem.unit_price) || 0;
                const discountPercent = Number(poItem.discount_percent) || 0;
                const lineBase = qtyInvoiced * unitPrice;
                const lineDisc = lineBase * (discountPercent / 100);

                return {
                    grn_item_id: grnItem.id,
                    product_name: product.name || 'Item #' + grnItem.id,
                    product_sku: product.sku || '',
                    product_unit: product.unit || 'pcs',
                    qty_ordered: poItem.qty_ordered || 0,
                    qty_received: qtyReceived,
                    qty_returned: qtyReturned,
                    qty_invoiced: qtyInvoiced,
                    unit_price: unitPrice,
                    discount_percent: discountPercent,
                    line_subtotal: lineBase - lineDisc,
                };
            }).filter(it => it.qty_invoiced > 0);
        },

        get totalQty() {
            return this.grnItems.reduce((s, it) => s + it.qty_invoiced, 0);
        },

        get totalReturnedQty() {
            return (this.selectedGrn?.items || []).reduce((s, it) => s + (Number(it.qty_returned_completed_accepted) || 0), 0);
        },

        get rawSubtotal() {
            return this.grnItems.reduce((s, it) => s + it.line_subtotal, 0);
        },

        get isLastGrn() {
            if (!this.selectedPoId || !this.selectedGrnId) return false;
            const otherGrns = this.availableGrns.filter(g => String(g.id) !== String(this.selectedGrnId));
            return otherGrns.length === 0;
        },

        get isIntegerCurrency() {
            if (!this.selectedPoData) return true;
            const poTax = Number(this.selectedPoData.tax_amount) || 0;
            const poSubtotal = (this.selectedPoData.items || []).reduce((s, it) => s + (Number(it.subtotal) || 0), 0);
            return (Math.round(poTax) === poTax) && (Math.round(poSubtotal) === poSubtotal);
        },

        roundAmount(val) {
            if (this.isIntegerCurrency) {
                return Math.round(val || 0);
            }
            return Math.round((val || 0) * 100) / 100;
        },

        get proratedDiscount() {
            if (!this.selectedPoData) return 0;
            const headerDisc = Number(this.selectedPoData.discount_amount) || 0;
            if (headerDisc <= 0) return 0;
            const totalPoSubtotal = (this.selectedPoData.items || []).reduce((s, it) => s + (Number(it.subtotal) || 0), 0);
            if (totalPoSubtotal <= 0) return 0;

            const usedDisc = Number(this.selectedPoData.used_header_discount) || 0;

            if (this.isLastGrn) {
                // LPB terakhir: menyerap seluruh sisa diskon header agar jumlah pas 100% tanpa selisih pembulatan
                return Math.max(0, this.roundAmount(headerDisc - usedDisc));
            }

            return this.roundAmount((this.rawSubtotal / totalPoSubtotal) * headerDisc);
        },

        get dpp() {
            return Math.max(0, this.rawSubtotal - this.proratedDiscount);
        },

        get taxAmount() {
            if (this.isLastGrn && this.selectedPoData) {
                const poTax = Number(this.selectedPoData.tax_amount) || 0;
                const usedTax = Number(this.selectedPoData.used_tax_amount) || 0;
                const poTaxRate = Number(this.selectedPoData.tax_rate) || 0;
                if (poTax > 0 && Math.abs(poTaxRate - this.taxRate) < 0.001) {
                    // LPB terakhir: menyerap seluruh sisa PPN dari PO agar pas 100% tanpa selisih pembulatan
                    return Math.max(0, this.roundAmount(poTax - usedTax));
                }
            }
            return this.roundAmount(this.dpp * (this.taxRate / 100));
        },

        get totalAmount() {
            return this.dpp + this.taxAmount;
        },

        formatNum(v) { return new Intl.NumberFormat('id-ID').format(this.roundAmount(v || 0)); }
    }
}
</script>
@endpush
