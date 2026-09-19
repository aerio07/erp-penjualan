@extends('layouts.app')
@section('title', 'Terbitkan Invoice Penjualan (1 SJ = 1 Invoice)')
@section('page-title', 'Terbitkan Invoice Penjualan')

@section('content')
<div class="animate-in" x-data="invForm()">
    <x-page-header 
        title="Terbitkan Invoice Penjualan" 
        subtitle="Pilih Sales Order lalu pilih 1 Surat Jalan (SJ) — seluruh qty yang dikirim di Surat Jalan tersebut akan langsung ditagihkan penuh."
        :back-url="route('sales.invoices.index')"
    />

    <form method="POST" action="{{ route('sales.invoices.store') }}" @submit="validateForm($event)">
        @csrf

        <div class="grid grid-3" style="margin-bottom:20px;">
            {{-- Form Header --}}
            <div class="card" style="grid-column:span 2;">
                <div class="card-header"><h3>Informasi Faktur / Invoice Penjualan</h3></div>
                <div class="card-body">
                    <div class="form-row form-row-2">
                        <div class="form-group">
                            <label class="form-label">Sales Order <span style="color:var(--danger);">*</span></label>
                            <x-searchable-select 
                                name="sales_order_id" 
                                :options="$orders->map(fn($so) => ['id' => $so->id, 'name' => $so->so_number . ' - ' . $so->customer->name . ' (Status: ' . ucfirst(str_replace('_', ' ', $so->status)) . ')'])" 
                                placeholder="-- Pilih Sales Order --" 
                                search-placeholder="Cari nomor SO atau nama customer..." 
                                on-select="soId => { selectedSoId = soId; onSoChange(); }" 
                                required 
                            />
                        </div>

                        <div class="form-group">
                            <label class="form-label">Surat Jalan (Delivery) <span style="color:var(--danger);">*</span></label>
                            <select name="delivery_id" class="form-control" x-model="selectedDeliveryId" @change="onDeliveryChange()" required :disabled="!selectedSoId">
                                <option value="">-- Pilih Surat Jalan --</option>
                                <template x-for="del in availableDeliveries" :key="del.id">
                                    <option :value="del.id" x-text="del.display_label"></option>
                                </template>
                            </select>
                            <template x-if="selectedSoId && availableDeliveries.length === 0">
                                <div style="color:var(--warning); font-size:12px; margin-top:4px; font-weight:600;">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Tidak ada Surat Jalan berstatus 'Diterima' (Delivered) yang belum di-invoice pada Sales Order ini. (Jika barang masih Dalam Pengiriman, konfirmasi penerimaan terlebih dahulu).
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="form-row form-row-3">
                        <div class="form-group">
                            <label class="form-label">Tanggal Invoice <span style="color:var(--danger);">*</span></label>
                            <input type="date" name="invoice_date" x-model="invoiceDate" @change="calculateDueDate()" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tanggal Jatuh Tempo <span style="color:var(--danger);">*</span></label>
                            <input type="date" name="due_date" x-model="dueDate" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tarif PPN (%) <span style="color:var(--danger);">*</span></label>
                            <input type="number" name="tax_rate" x-model.number="taxRate" class="form-control" step="0.01" min="0" max="100" required>
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
                    <template x-if="selectedDelivery">
                        <div style="display:flex; flex-direction:column; gap:12px; font-size:14px;">
                            <div>
                                <div style="font-size:12px; color:var(--text-secondary);">Customer</div>
                                <div style="font-weight:600;" x-text="selectedSoData ? selectedSoData.customer.name : '-'"></div>
                            </div>
                            <hr style="border:none; border-top:1px solid var(--border);">
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:var(--text-secondary);">Subtotal Item SJ</span>
                                <span style="font-weight:600;" x-text="'Rp ' + formatNum(grossSubtotal)"></span>
                            </div>
                            <template x-if="itemDiscountTotal > 0">
                                <div style="display:flex; justify-content:space-between; color:var(--danger);">
                                    <span>Diskon Item</span>
                                    <span style="font-weight:600;" x-text="'- Rp ' + formatNum(itemDiscountTotal)"></span>
                                </div>
                            </template>
                            <template x-if="proratedDiscount > 0">
                                <div style="display:flex; justify-content:space-between; color:var(--danger);">
                                    <span>Prorasi Diskon Header SO</span>
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
                                <i class="fa-solid fa-rotate-left"></i> <strong>Penyesuaian Retur:</strong> Sebanyak <span style="font-weight:700;" x-text="totalReturnedQty"></span> unit telah diretur oleh customer sebelum invoice diterbitkan. Invoice hanya menagih barang bersih (<span style="font-weight:700;" x-text="totalQty"></span> unit).
                            </div>
                            <div x-show="totalReturnedQty === 0" style="padding:10px; background:#f0fdf4; border-radius:8px; border:1px solid #bbf7d0; font-size:12.5px; color:#166534; margin-top:8px;">
                                <i class="fa-solid fa-shield-halved"></i> <strong>1 SJ = 1 Invoice:</strong> Menagih penuh <span x-text="totalQty"></span> unit dari Surat Jalan <span x-text="selectedDelivery.delivery_number"></span>.
                            </div>
                        </div>
                    </template>
                    <template x-if="!selectedDelivery">
                        <div style="text-align:center; padding:24px 0; color:var(--text-secondary); font-size:13px;">
                            <i class="fa-solid fa-file-invoice-dollar" style="font-size:28px; margin-bottom:8px; display:block; opacity:0.4;"></i>
                            Pilih Sales Order dan Surat Jalan terlebih dahulu
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Detail Item dari Surat Jalan (read-only) --}}
        <div class="card" style="margin-bottom:20px;" x-show="selectedDelivery">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h3>Rincian Item dari Surat Jalan</h3>
                <template x-if="totalReturnedQty > 0">
                    <span class="badge" style="background:#fef2f2; color:#b91c1c; font-size:11px; padding:4px 8px;">
                        <i class="fa-solid fa-rotate-left"></i> Total Diretur: <span x-text="totalReturnedQty"></span> unit
                    </span>
                </template>
            </div>
            <div class="table-responsive">
                <table class="erp-table" style="min-width: 900px;">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th style="text-align:center; width:90px;">Dipesan (SO)</th>
                            <th style="text-align:center; width:95px;">Dikirim (SJ)</th>
                            <th style="text-align:center; width:85px; color:#dc2626;">Diretur</th>
                            <th style="text-align:center; width:110px; background:rgba(16, 185, 129, 0.08);">Ditagih (Net)</th>
                            <th style="text-align:right; width:130px;">Harga Satuan</th>
                            <th style="text-align:center; width:80px;">Diskon</th>
                            <th style="text-align:right; width:140px;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="item in deliveryItems" :key="item.delivery_item_id">
                            <tr>
                                <td>
                                    <div style="font-weight:600;" x-text="item.product_name"></div>
                                    <div style="font-size:12px; color:var(--text-secondary);" x-text="item.product_sku"></div>
                                </td>
                                <td style="text-align:center;" x-text="item.qty_ordered + ' ' + item.product_unit"></td>
                                <td style="text-align:center;" x-text="item.qty_delivered + ' ' + item.product_unit"></td>
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

        <x-form-actions 
            :cancel-url="route('sales.invoices.index')" 
            submit-label="Simpan sebagai Draft Invoice" 
        />
    </form>
</div>
@endsection

@push('scripts')
<script>
const ordersData = @json($ordersData);
const deliveriesData = @json($availableDeliveries->keyBy('id'));
const initialSoId = "{{ $selectedSoId ?? '' }}";
const initialDeliveryId = "{{ $selectedDeliveryId ?? '' }}";

function parseTermDays(term) {
    if (typeof term === 'number') return term;
    if (!term) return 30;
    if (String(term).toUpperCase().trim() === 'COD') return 0;
    const match = String(term).match(/\d+/);
    return match ? parseInt(match[0], 10) : 30;
}

function invForm() {
    return {
        selectedSoId: initialSoId,
        selectedDeliveryId: initialDeliveryId,
        selectedSoData: null,
        selectedDelivery: null,
        availableDeliveries: [],
        deliveryItems: [],
        taxRate: 11,
        invoiceDate: "{{ old('invoice_date', date('Y-m-d')) }}",
        dueDate: "{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}",
        paymentTerm: 30,
        paymentTermLabel: 'NET 30',

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
        },

        init() {
            if (this.selectedSoId) {
                const savedDelId = this.selectedDeliveryId;
                this.onSoChange();
                if (savedDelId) {
                    this.selectedDeliveryId = savedDelId;
                    this.onDeliveryChange();
                }
            }
        },

        onSoChange() {
            this.selectedDeliveryId = '';
            this.selectedDelivery = null;
            this.deliveryItems = [];

            if (!this.selectedSoId || !ordersData[this.selectedSoId]) {
                this.selectedSoData = null;
                this.availableDeliveries = [];
                return;
            }
            this.selectedSoData = ordersData[this.selectedSoId];
            this.taxRate = parseFloat(this.selectedSoData.tax_rate) || 11;
            const cust = this.selectedSoData.customer;
            this.paymentTerm = cust ? (cust.payment_term_days ?? parseTermDays(cust.payment_term)) : 30;
            this.paymentTermLabel = cust ? (cust.payment_term || (this.paymentTerm + ' hari')) : 'NET 30';
            this.calculateDueDate();

            // Bangun daftar Surat Jalan yang belum di-invoice dari data server
            this.availableDeliveries = Object.values(deliveriesData)
                .filter(del => String(del.sales_order_id) === String(this.selectedSoId))
                .map(del => {
                    const totalDelivered = (del.items || []).reduce((s, it) => s + (Number(it.qty_delivered) || 0), 0);
                    const totalInvoiced = (del.items || []).reduce((s, it) => s + (Number(it.qty_invoiced) || 0), 0);
                    const totalReturned = (del.items || []).reduce((s, it) => s + (Number(it.qty_returned_completed) || 0), 0);
                    const remainingToInvoice = Math.max(0, totalDelivered - totalInvoiced - totalReturned);
                    const tgl = del.delivery_date ? String(del.delivery_date).substring(0, 10) : '-';
                    const retInfo = totalReturned > 0 ? ` (Retur: ${totalReturned} unit)` : '';

                    return {
                        id: del.id,
                        delivery_number: del.delivery_number,
                        delivery_date: del.delivery_date,
                        warehouse_name: del.warehouse ? del.warehouse.name : '-',
                        remaining_qty: remainingToInvoice,
                        total_delivered: totalDelivered,
                        total_invoiced: totalInvoiced,
                        total_returned: totalReturned,
                        condition_status: del.condition_status,
                        receipt_status: del.receipt_status,
                        is_eligible: remainingToInvoice > 0,
                        display_label: `${del.delivery_number} · Tgl: ${tgl} · Sisa: ${remainingToInvoice} unit${retInfo}`
                    };
                });
        },

        onDeliveryChange() {
            if (!this.selectedDeliveryId || !deliveriesData[this.selectedDeliveryId]) {
                this.selectedDelivery = null;
                this.deliveryItems = [];
                return;
            }
            this.selectedDelivery = deliveriesData[this.selectedDeliveryId];

            const soItems = (this.selectedSoData?.items || []).reduce((acc, it) => {
                acc[it.product_id] = it;
                return acc;
            }, {});

            this.deliveryItems = (this.selectedDelivery.items || []).map(delItem => {
                const soItem = soItems[delItem.product_id] || {};
                const qtyDelivered = Number(delItem.qty_delivered) || 0;
                const qtyPreviouslyInvoiced = Number(delItem.qty_invoiced) || 0;
                const qtyReturned = Number(delItem.qty_returned_completed) || 0;
                const qtyInvoiced = Math.max(0, qtyDelivered - qtyPreviouslyInvoiced - qtyReturned);

                const unitPrice = Number(soItem.unit_price) || 0;
                const discountPercent = Number(soItem.discount_percent) || 0;
                const lineBase = qtyInvoiced * unitPrice;
                const lineDisc = lineBase * (discountPercent / 100);

                return {
                    delivery_item_id: delItem.id,
                    product_id: delItem.product_id,
                    product_name: delItem.product ? delItem.product.name : '-',
                    product_sku: delItem.product ? delItem.product.sku : '-',
                    product_unit: delItem.product ? delItem.product.unit : 'pcs',
                    qty_ordered: soItem.qty_ordered || 0,
                    qty_delivered: qtyDelivered,
                    qty_returned: qtyReturned,
                    qty_invoiced: qtyInvoiced,
                    unit_price: unitPrice,
                    discount_percent: discountPercent,
                    line_subtotal: lineBase - lineDisc,
                };
            }).filter(it => it.qty_invoiced > 0);
        },

        get isInvoiceInvalid() {
            return !this.selectedDelivery || this.totalQty <= 0;
        },

        get totalQty() {
            return this.deliveryItems.reduce((s, it) => s + it.qty_invoiced, 0);
        },

        get totalReturnedQty() {
            return (this.selectedDelivery?.items || []).reduce((s, it) => s + (Number(it.qty_returned_completed) || 0), 0);
        },

        get rawSubtotal() {
            return this.deliveryItems.reduce((s, it) => s + it.line_subtotal, 0);
        },

        get grossSubtotal() {
            return this.deliveryItems.reduce((s, it) => s + (it.qty_invoiced * it.unit_price), 0);
        },

        get itemDiscountTotal() {
            return this.deliveryItems.reduce((s, it) => s + (it.qty_invoiced * it.unit_price * (it.discount_percent / 100)), 0);
        },

        get isLastDelivery() {
            if (!this.selectedSoId || !this.selectedDeliveryId) return false;
            const otherDeliveries = this.availableDeliveries.filter(d => String(d.id) !== String(this.selectedDeliveryId));
            return otherDeliveries.length === 0;
        },

        get isIntegerCurrency() {
            if (!this.selectedSoData) return true;
            const soTax = Number(this.selectedSoData.tax_amount) || 0;
            const soSubtotal = (this.selectedSoData.items || []).reduce((s, it) => s + (Number(it.subtotal) || 0), 0);
            return (Math.round(soTax) === soTax) && (Math.round(soSubtotal) === soSubtotal);
        },

        roundAmount(val) {
            if (this.isIntegerCurrency) {
                return Math.round(val || 0);
            }
            return Math.round((val || 0) * 100) / 100;
        },

        get proratedDiscount() {
            if (!this.selectedSoData) return 0;
            const headerDisc = Number(this.selectedSoData.discount_amount) || 0;
            if (headerDisc <= 0) return 0;
            const totalSoSubtotal = (this.selectedSoData.items || []).reduce((s, it) => s + (Number(it.subtotal) || 0), 0);
            if (totalSoSubtotal <= 0) return 0;

            const usedDisc = Number(this.selectedSoData.used_header_discount) || 0;

            if (this.isLastDelivery) {
                // Surat Jalan terakhir: menyerap seluruh sisa diskon header agar jumlah pas 100% tanpa selisih pembulatan
                return Math.max(0, this.roundAmount(headerDisc - usedDisc));
            }

            return this.roundAmount((this.rawSubtotal / totalSoSubtotal) * headerDisc);
        },

        get dpp() {
            return Math.max(0, this.rawSubtotal - this.proratedDiscount);
        },

        get taxAmount() {
            if (this.isLastDelivery && this.selectedSoData) {
                const soTax = Number(this.selectedSoData.tax_amount) || 0;
                const usedTax = Number(this.selectedSoData.used_tax_amount) || 0;
                const soTaxRate = Number(this.selectedSoData.tax_rate) || 0;
                if (soTax > 0 && Math.abs(soTaxRate - this.taxRate) < 0.001) {
                    // Surat Jalan terakhir: menyerap seluruh sisa PPN dari SO agar pas 100% tanpa selisih pembulatan
                    return Math.max(0, this.roundAmount(soTax - usedTax));
                }
            }
            return this.roundAmount(this.dpp * (this.taxRate / 100));
        },

        get totalAmount() {
            return this.dpp + this.taxAmount;
        },

        validateForm(e) {
            if (!this.selectedDelivery) {
                alert('Pilih Surat Jalan terlebih dahulu.');
                e.preventDefault();
                return false;
            }
            if (this.totalQty <= 0) {
                alert('Tidak ada item yang dapat difakturkan dari Surat Jalan ini.');
                e.preventDefault();
                return false;
            }
            return true;
        },

        formatNum(v) { return new Intl.NumberFormat('id-ID').format(this.roundAmount(v || 0)); }
    }
}
</script>
@endpush
