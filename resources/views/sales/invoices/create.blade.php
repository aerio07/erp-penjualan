@extends('layouts.app')
@section('title', 'Terbitkan Invoice Penjualan (1 SJ = 1 Invoice)')
@section('page-title', 'Terbitkan Invoice Penjualan')

@section('content')
<div class="animate-in" x-data="invForm()">
    <div class="page-header">
        <div>
            <h1>Terbitkan Invoice Penjualan</h1>
            <p>Pilih <strong>Sales Order</strong> lalu pilih <strong>1 Surat Jalan (SJ)</strong> — seluruh qty yang dikirim di Surat Jalan tersebut akan langsung ditagihkan penuh.</p>
        </div>
        <a href="{{ route('sales.invoices.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <form method="POST" action="{{ route('sales.invoices.store') }}">
        @csrf

        <div class="grid grid-3" style="margin-bottom:20px;">
            {{-- Form Header --}}
            <div class="card" style="grid-column:span 2;">
                <div class="card-header"><h3>Informasi Faktur / Invoice Penjualan</h3></div>
                <div class="card-body">
                    <div class="form-row form-row-2">
                        <div class="form-group">
                            <label class="form-label">Sales Order <span style="color:var(--danger);">*</span></label>
                            <select name="sales_order_id" class="form-control" x-model="selectedSoId" @change="onSoChange()" required>
                                <option value="">-- Pilih Sales Order --</option>
                                @foreach($orders as $so)
                                <option value="{{ $so->id }}">
                                    {{ $so->so_number }} - {{ $so->customer->name }} (Status: {{ ucfirst(str_replace('_', ' ', $so->status)) }})
                                </option>
                                @endforeach
                            </select>
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
                                    <i class="fa-solid fa-triangle-exclamation"></i> Semua Surat Jalan pada Sales Order ini sudah pernah di-invoice.
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="form-row form-row-3">
                        <div class="form-group">
                            <label class="form-label">Tanggal Invoice <span style="color:var(--danger);">*</span></label>
                            <input type="date" name="invoice_date" value="{{ old('invoice_date', date('Y-m-d')) }}" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Jatuh Tempo <span style="color:var(--danger);">*</span></label>
                            <input type="date" name="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}" class="form-control" required>
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
                            <div>
                                <div style="font-size:12px; color:var(--text-secondary);">Surat Jalan Sumber</div>
                                <div style="font-weight:600; color:var(--primary);" x-text="selectedDelivery.delivery_number"></div>
                            </div>
                            <hr style="border:none; border-top:1px solid var(--border);">
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:var(--text-secondary);">Subtotal Item SJ</span>
                                <span style="font-weight:600;" x-text="'Rp ' + formatNum(rawSubtotal)"></span>
                            </div>
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
                            <div style="padding:10px; background:#f0fdf4; border-radius:8px; border:1px solid #bbf7d0; font-size:12.5px; color:#166534; margin-top:8px;">
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
            <div class="card-header">
                <h3>Rincian Item dari Surat Jalan (Otomatis — Qty Penuh)</h3>
            </div>
            <div class="table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th style="text-align:center; width:90px;">Dipesan (SO)</th>
                            <th style="text-align:center; width:110px; background:rgba(16, 185, 129, 0.08);">Dikirim di SJ (Ditagih)</th>
                            <th style="text-align:right; width:140px;">Harga Satuan</th>
                            <th style="text-align:center; width:80px;">Diskon</th>
                            <th style="text-align:right; width:150px;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="item in deliveryItems" :key="item.delivery_item_id">
                            <tr>
                                <td>
                                    <div style="font-weight:600;" x-text="item.product_name"></div>
                                    <div style="font-size:12px; color:var(--text-secondary);" x-text="item.product_sku"></div>
                                </td>
                                <td style="text-align:center; font-weight:600;" x-text="item.qty_ordered + ' ' + item.product_unit"></td>
                                <td style="text-align:center; background:rgba(16, 185, 129, 0.05);">
                                    <span class="badge badge-done" style="font-weight:700;" x-text="item.qty_delivered + ' ' + item.product_unit"></span>
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

        <div style="display:flex; justify-content:flex-end; gap:12px;" x-show="selectedDelivery">
            <a href="{{ route('sales.invoices.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary" :disabled="!selectedDelivery || totalQty <= 0">
                <i class="fa-solid fa-floppy-disk"></i> Terbitkan Invoice & Posting Jurnal (HPP + Piutang)
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const ordersData = @json($ordersData);
const deliveriesData = @json($availableDeliveries->keyBy('id'));
const initialSoId = "{{ $selectedSoId ?? '' }}";
const initialDeliveryId = "{{ $selectedDeliveryId ?? '' }}";

function invForm() {
    return {
        selectedSoId: initialSoId,
        selectedDeliveryId: initialDeliveryId,
        selectedSoData: null,
        selectedDelivery: null,
        availableDeliveries: [],
        deliveryItems: [],
        taxRate: 11,

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

            // Bangun daftar Surat Jalan yang belum di-invoice dari data server
            this.availableDeliveries = Object.values(deliveriesData)
                .filter(del => String(del.sales_order_id) === String(this.selectedSoId))
                .map(del => {
                    const totalQty = (del.items || []).reduce((s, it) => s + (Number(it.qty_delivered) || 0), 0);
                    const tgl = del.delivery_date ? String(del.delivery_date).substring(0, 10) : '-';
                    return {
                        ...del,
                        total_qty: totalQty,
                        display_label: `${del.delivery_number} · Tgl: ${tgl} · ${totalQty} unit`
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

            // Bangun data item dari Surat Jalan yang dipilih
            this.deliveryItems = (this.selectedDelivery.items || []).map(delItem => {
                const soItem = delItem.sales_order_item || {};
                const product = soItem.product || {};
                const qtyDelivered = Number(delItem.qty_delivered) || 0;
                const unitPrice = Number(soItem.unit_price) || 0;
                const discountPercent = Number(soItem.discount_percent) || 0;
                const lineBase = qtyDelivered * unitPrice;
                const lineDisc = lineBase * (discountPercent / 100);

                return {
                    delivery_item_id: delItem.id,
                    product_name: product.name || 'Item #' + delItem.id,
                    product_sku: product.sku || '',
                    product_unit: product.unit || 'pcs',
                    qty_ordered: soItem.qty_ordered || 0,
                    qty_delivered: qtyDelivered,
                    unit_price: unitPrice,
                    discount_percent: discountPercent,
                    line_subtotal: lineBase - lineDisc,
                };
            });
        },

        get totalQty() {
            return this.deliveryItems.reduce((s, it) => s + it.qty_delivered, 0);
        },

        get rawSubtotal() {
            return this.deliveryItems.reduce((s, it) => s + it.line_subtotal, 0);
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

        formatNum(v) { return new Intl.NumberFormat('id-ID').format(this.roundAmount(v || 0)); }
    }
}
</script>
@endpush
