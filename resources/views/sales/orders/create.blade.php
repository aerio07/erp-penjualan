@extends('layouts.app')
@section('title', 'Buat Sales Order')
@section('page-title', 'Buat Sales Order')

@section('content')
<div class="animate-in" x-data="soForm()">
    <x-page-header
        title="Buat Sales Order"
        subtitle="Isi data penawaran / pesanan penjualan baru"
        :back-url="route('sales.orders.index')"
    />

    <form method="POST" action="{{ route('sales.orders.store') }}" @submit="validateForm($event)" @keydown.enter="if ($event.target.tagName !== 'TEXTAREA') $event.preventDefault()">
        @csrf

        <div class="grid grid-3" style="margin-bottom:20px;">
            <div class="card" style="grid-column:span 2;">
                <div class="card-header"><h3>Informasi Sales Order</h3></div>
                <div class="card-body">
                    <!-- Channel Penjualan (Offline / Online) -->
                    <div style="margin-bottom:16px; padding:12px 16px; background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
                        <div>
                            <div style="font-weight:700; font-size:13px; color:#0e1b35; display:flex; align-items:center; gap:6px;">
                                <i class="fa-solid fa-route" style="color:var(--primary);"></i> Channel Penjualan <span style="color:var(--danger);">*</span>
                            </div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-top:2px;">Menentukan harga default barang yang dimasukkan (Online vs Offline).</div>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <input type="hidden" name="channel" :value="channel">
                            <button type="button"
                                    @click="setChannel('offline')"
                                    class="px-4 py-2 rounded-lg text-xs font-bold transition-all inline-flex items-center gap-2 cursor-pointer border shadow-xs select-none"
                                    :class="channel === 'offline' 
                                        ? 'bg-slate-900 text-white border-slate-900' 
                                        : 'bg-white text-slate-600 hover:bg-slate-50 border-slate-200'">
                                <i class="fa-solid fa-store text-xs" :class="channel === 'offline' ? 'text-white' : 'text-slate-500'"></i>
                                <span>Offline</span>
                                <i x-show="channel === 'offline'" class="fa-solid fa-check text-[10px] ml-0.5"></i>
                            </button>
                            <button type="button"
                                    @click="setChannel('online')"
                                    class="px-4 py-2 rounded-lg text-xs font-bold transition-all inline-flex items-center gap-2 cursor-pointer border shadow-xs select-none"
                                    :class="channel === 'online' 
                                        ? 'bg-slate-900 text-white border-slate-900' 
                                        : 'bg-white text-slate-600 hover:bg-slate-50 border-slate-200'">
                                <i class="fa-solid fa-globe text-xs" :class="channel === 'online' ? 'text-white' : 'text-slate-500'"></i>
                                <span>Online</span>
                                <i x-show="channel === 'online'" class="fa-solid fa-check text-[10px] ml-0.5"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-row form-row-4">
                        <div class="form-group">
                            <label class="form-label">Customer <span style="color:var(--danger);">*</span></label>
                            <x-searchable-select 
                                name="customer_id" 
                                :options="$customers" 
                                :value="old('customer_id')" 
                                placeholder="-- Pilih Customer --" 
                                search-placeholder="Cari nama atau kode customer..." 
                                required 
                            />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tanggal Order <span style="color:var(--danger);">*</span></label>
                            <input type="date" name="order_date" value="{{ old('order_date', date('Y-m-d')) }}" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Target Pengiriman</label>
                            <input type="date" name="expected_delivery_date" value="{{ old('expected_delivery_date') }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tarif PPN (%)</label>
                            <input type="number" name="tax_rate" x-model.number="taxRate" class="form-control" step="0.01" min="0" max="100">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="1" placeholder="Catatan SO...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3>Ringkasan</h3></div>
                <div class="card-body">
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <div style="display:flex; justify-content:space-between; font-size:14px;">
                            <span style="color:var(--text-secondary);">Subtotal Bruto</span>
                            <span style="font-weight:600;" x-text="'Rp ' + formatNum(grossSubtotal)"></span>
                        </div>
                        <template x-if="itemDiscountTotal > 0">
                            <div style="display:flex; justify-content:space-between; font-size:13.5px; color:var(--danger);">
                                <span>Diskon Item</span>
                                <span style="font-weight:600;" x-text="'- Rp ' + formatNum(itemDiscountTotal)"></span>
                            </div>
                        </template>
                        <div style="display:flex; justify-content:space-between; font-size:14px;" :style="itemDiscountTotal > 0 ? 'border-top:1px dashed #e2e8f0; padding-top:6px;' : ''">
                            <span style="color:var(--text-secondary); font-weight:600;">Subtotal Bersih</span>
                            <span style="font-weight:700;" x-text="'Rp ' + formatNum(subtotal)"></span>
                        </div>
                        <div>
                            <label class="form-label">Diskon Header / Tambahan (Rp)</label>
                            <input type="text" 
                                   inputmode="numeric" 
                                   name="discount_amount" 
                                   x-currency="discountHeader" 
                                   :class="{'is-invalid': discountHeader > subtotal}"
                                   class="form-control format-currency" 
                                   placeholder="0">
                            <template x-if="discountHeader > subtotal">
                                <div style="font-size:11px; color:var(--danger); margin-top:4px; font-weight:600;">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Diskon melebihi subtotal (Maks: Rp <span x-text="formatNum(subtotal)"></span>)
                                </div>
                            </template>
                            @error('discount_amount')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>
                        <template x-if="itemDiscountTotal > 0 || discountHeader > 0">
                            <div style="display:flex; justify-content:space-between; font-size:13px; background:#fef2f2; border:1px solid #fecaca; padding:6px 10px; border-radius:6px; color:#b91c1c; font-weight:600;">
                                <span>Total Seluruh Diskon</span>
                                <span x-text="'- Rp ' + formatNum(itemDiscountTotal + (Number(discountHeader) || 0))"></span>
                            </div>
                        </template>
                        <div style="display:flex; justify-content:space-between; font-size:14px;">
                            <span style="color:var(--text-secondary);">Setelah Diskon</span>
                            <span x-text="'Rp ' + formatNum(taxableAmount)"></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:14px;">
                            <span style="color:var(--text-secondary);">PPN (<span x-text="taxRate"></span>%)</span>
                            <span x-text="'Rp ' + formatNum(taxAmount)"></span>
                        </div>
                        <hr style="border:none; border-top:1px solid var(--border); margin:0;">
                        <div style="display:flex; justify-content:space-between; font-size:16px; font-weight:700;">
                            <span>Total</span>
                            <span style="color:var(--primary);" x-text="'Rp ' + formatNum(total)"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Item Table --}}
        <div class="card" style="margin-bottom:20px; overflow: visible;">
            <div class="card-header">
                <h3>Item Sales Order</h3>
                <button type="button" class="btn btn-primary btn-sm" @click="addRow()">
                    <i class="fa-solid fa-plus"></i> Tambah Item
                </button>
            </div>
            <style>
                .item-form-table td {
                    vertical-align: top !important;
                    padding-top: 0.65rem !important;
                    padding-bottom: 0.65rem !important;
                }
            </style>
            <div class="table-responsive" style="overflow: visible; min-height: 180px;">
                <table class="erp-table item-form-table">
                    <thead>
                        <tr>
                            <th style="min-width: 260px;">Produk</th>
                            <th style="width:100px;">Qty</th>
                            <th style="min-width:185px;">Harga Jual Satuan</th>
                            <th style="width:100px;">Diskon (%)</th>
                            <th style="width:150px; text-align:right;">Subtotal</th>
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, idx) in rows" :key="idx">
                            <tr style="vertical-align: top;">
                                <td style="vertical-align: top !important;">
                                    <div style="position: relative; width: 100%;" x-data="{ openProd: false, prodSearch: '' }" @click.outside="openProd = false" @keydown.escape.window="openProd = false">
                                        <input 
                                            type="text" 
                                            :name="`items[${idx}][product_id]`" 
                                            :value="row.product_id" 
                                            required
                                            tabindex="-1"
                                            aria-hidden="true"
                                            class="searchable-select-native-input"
                                            style="position: absolute; top: 0; left: 0; width: 100%; height: 38px; opacity: 0; pointer-events: none; z-index: -1;"
                                            @focus="$event.target.nextElementSibling?.classList.add('focused')"
                                            @blur="$event.target.nextElementSibling?.classList.remove('focused')"
                                        >
                                        
                                        <div 
                                            @click="openProd = !openProd; if(openProd) $nextTick(() => $refs.prodSearchInput.focus())"
                                            class="form-control searchable-select-trigger"
                                            style="display: flex !important; flex-wrap: nowrap !important; align-items: center !important; justify-content: space-between !important; cursor: pointer; min-height: 38px !important; height: 38px !important; max-height: 38px !important; background: #fff; padding: 0 0.75rem !important; border-radius: 0.5rem; box-sizing: border-box !important;"
                                        >
                                            <span x-text="products[row.product_id] ? (products[row.product_id].name + ' (' + products[row.product_id].sku + ')') : '-- Pilih Produk --'"
                                                  :class="!row.product_id ? 'text-slate-400' : 'text-slate-900 font-semibold'"
                                                  style="white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; padding-right: 6px; flex: 1 1 auto; min-width: 0;"
                                            ></span>
                                            <i class="fa-solid fa-chevron-down text-slate-400" :style="openProd ? 'transform: rotate(180deg);' : ''" style="font-size: 11px; transition: transform 0.2s; flex-shrink: 0; margin-left: auto;"></i>
                                        </div>

                                        {{-- Dropdown Menu --}}
                                        <div 
                                            x-show="openProd" 
                                            x-transition:enter="transition ease-out duration-150"
                                            x-transition:enter-start="opacity-0 translate-y-1"
                                            x-transition:enter-end="opacity-100 translate-y-0"
                                            x-transition:leave="transition ease-in duration-100"
                                            x-transition:leave-start="opacity-100 translate-y-0"
                                            x-transition:leave-end="opacity-0 translate-y-1"
                                            style="position: absolute; top: calc(100% + 4px); left: 0; min-width: 320px; z-index: 1060; background: #fff; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); border: 1px solid #e2e8f0; max-height: 260px; display: flex; flex-direction: column; overflow: hidden;"
                                            x-cloak
                                        >
                                            <div style="padding: 6px 8px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; position: relative;">
                                                <div style="position: relative; display: flex; align-items: center;">
                                                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 12px; pointer-events: none; z-index: 2;"></i>
                                                    <input 
                                                        type="text" 
                                                        x-ref="prodSearchInput"
                                                        x-model="prodSearch" 
                                                        placeholder="Cari nama atau SKU produk..." 
                                                        class="form-control searchable-select-search-input" 
                                                        style="width: 100% !important; padding-left: 30px !important; padding-right: 24px !important; font-size: 12px !important; height: 32px !important; border-radius: 6px !important;"
                                                        @click.stop
                                                    >
                                                    <template x-if="prodSearch">
                                                        <button 
                                                            type="button" 
                                                            @click="prodSearch = ''; $refs.prodSearchInput.focus()"
                                                            style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 2px 4px; font-size: 11px; z-index: 2;"
                                                        >
                                                            <i class="fa-solid fa-circle-xmark"></i>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                            <div style="overflow-y: auto; max-height: 200px;">
                                                <template x-for="p in Object.values(products).filter(item => !prodSearch || item.name.toLowerCase().includes(prodSearch.toLowerCase()) || item.sku.toLowerCase().includes(prodSearch.toLowerCase()))" :key="p.id">
                                                    <div 
                                                        @click="row.product_id = p.id; onProductChange(idx, { target: { value: p.id } }); openProd = false; prodSearch = '';"
                                                        :class="{ 'bg-blue-50 font-semibold text-blue-700': String(row.product_id) === String(p.id) }"
                                                        style="padding: 8px 10px; cursor: pointer; font-size: 13px; border-bottom: 1px solid #f8fafc;"
                                                        onmouseover="this.style.backgroundColor='#f1f5f9'"
                                                        onmouseout="if(String(row.product_id) !== String(p.id)) this.style.backgroundColor='transparent'"
                                                    >
                                                        <span x-text="p.name + ' (' + p.sku + ')'"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                 <td style="vertical-align: top !important;">
                                     <input type="number" 
                                            :name="`items[${idx}][qty_ordered]`" 
                                            x-model.number="row.qty" 
                                            @keydown.enter.prevent="$event.target.blur()"
                                            class="form-control" 
                                            min="1" required style="height: 38px;">
                                 </td>
                                 <td style="vertical-align: top !important;">
                                     <input type="number" 
                                            :name="`items[${idx}][unit_price]`" 
                                            x-model.number="row.price" 
                                            @input="hppWarningConfirmed = false"
                                            @keydown.enter.prevent="$event.target.blur()"
                                            class="form-control" 
                                            :class="[row.product_id && isBelowHpp(row) ? 'input-price-danger' : '', 'form-control']"
                                            style="height: 38px;"
                                            min="0" step="100" placeholder="0" required>
                                     <template x-if="row.product_id && isBelowHpp(row)">
                                         <div class="price-badge price-badge--danger">
                                             <i class="fa-solid fa-triangle-exclamation"></i>
                                             <span>
                                                 <template x-if="row.price < getProductHpp(row.product_id)">
                                                     <span>&lt; HPP: Rp <span x-text="formatNum(getProductHpp(row.product_id))"></span></span>
                                                 </template>
                                                 <template x-if="row.price >= getProductHpp(row.product_id)">
                                                     <span>Netto &lt; HPP: Rp <span x-text="formatNum(getProductHpp(row.product_id))"></span></span>
                                                 </template>
                                             </span>
                                         </div>
                                     </template>
                                 </td>
                                 <td style="vertical-align: top !important;">
                                     <input type="number" 
                                            :name="`items[${idx}][discount_percent]`" 
                                            x-model.number="row.discount" 
                                            @input="hppWarningConfirmed = false"
                                            @keydown.enter.prevent="$event.target.blur()"
                                            class="form-control" 
                                            min="0" max="100" step="0.1"
                                            style="height: 38px;">
                                 </td>
                                 <td style="vertical-align: top !important; text-align:right;">
                                     <div style="min-height: 38px; display: flex; flex-direction: column; justify-content: center; align-items: flex-end;">
                                         <template x-if="row.discount > 0">
                                             <span style="font-size:11px; color:#94a3b8; text-decoration:line-through;" x-text="'Rp ' + formatNum(row.qty * row.price)"></span>
                                         </template>
                                         <span style="font-weight:700; color:#0e1b35;" x-text="'Rp ' + formatNum(rowSubtotal(row))"></span>
                                         <template x-if="row.discount > 0">
                                             <span style="font-size:10.5px; color:#b91c1c; font-weight:600;" x-text="'Hemat Rp ' + formatNum((row.qty * row.price) * (row.discount / 100))"></span>
                                         </template>
                                     </div>
                                 </td>
                                 <td style="vertical-align: top !important; text-align:center;">
                                     <div style="height: 38px; display: flex; align-items: center; justify-content: center;">
                                         <button type="button" class="btn btn-danger btn-sm btn-icon" @click="removeRow(idx)" x-show="rows.length > 1">
                                             <i class="fa-solid fa-xmark"></i>
                                         </button>
                                     </div>
                                 </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <x-form-actions 
            :back-url="route('sales.orders.index')" 
            submit-label="Simpan sebagai Draft" 
        />
    </form>
</div>
@endsection

@push('scripts')
<script>
const products = @json($products->keyBy('id'));

function soForm() {
    return {
        rows: [{ product_id: '', qty: 1, price: 0, discount: 0 }],
        channel: '{{ old('channel', 'offline') }}',
        taxRate: 11,
        discountHeader: 0,
        hppWarningConfirmed: false,

        getProductHpp(productId) {
            if (!productId || !products[productId]) return 0;
            return parseFloat(products[productId].purchase_price) || 0;
        },

        isBelowHpp(row) {
            if (!row.product_id) return false;
            const hpp = this.getProductHpp(row.product_id);
            if (hpp <= 0) return false;
            const price = parseFloat(row.price) || 0;
            const discount = parseFloat(row.discount) || 0;
            const effectiveUnitPrice = price * (1 - discount / 100);
            return price < hpp || effectiveUnitPrice < hpp;
        },

        hasBelowHppItems() {
            return this.rows.some(r => r.product_id && this.isBelowHpp(r));
        },

        addRow() { this.rows.push({ product_id: '', qty: 1, price: 0, discount: 0 }); },
        removeRow(idx) { this.rows.splice(idx, 1); },

        getEffectivePrice(prod) {
            if (!prod) return 0;
            if (this.channel === 'online') {
                return (prod.price_online !== null && prod.price_online !== undefined && prod.price_online !== '')
                    ? parseFloat(prod.price_online)
                    : (parseFloat(prod.sell_price) || 0);
            } else {
                return (prod.price_offline !== null && prod.price_offline !== undefined && prod.price_offline !== '')
                    ? parseFloat(prod.price_offline)
                    : (parseFloat(prod.sell_price) || 0);
            }
        },

        onProductChange(idx, event) {
            const id = (event && event.target) ? event.target.value : this.rows[idx].product_id;
            this.rows[idx].product_id = id;
            if (id && products[id]) {
                this.rows[idx].price = this.getEffectivePrice(products[id]);
            }
            this.hppWarningConfirmed = false;
        },

        setChannel(newChannel) {
            if (this.channel === newChannel) return;
            this.channel = newChannel;
            this.onChannelChange();
            this.hppWarningConfirmed = false;
        },

        onChannelChange() {
            // Update harga default untuk baris item yang sudah dipilih produknya
            this.rows.forEach(r => {
                if (r.product_id && products[r.product_id]) {
                    r.price = this.getEffectivePrice(products[r.product_id]);
                }
            });
        },

        rowSubtotal(row) {
            const base = (row.qty || 0) * (row.price || 0);
            return base - (base * ((row.discount || 0) / 100));
        },

        get isDiscountInvalid() { return (Number(this.discountHeader) || 0) > this.subtotal; },
        get grossSubtotal() { return this.rows.reduce((s, r) => s + ((r.qty || 0) * (r.price || 0)), 0); },
        get itemDiscountTotal() { return this.rows.reduce((s, r) => s + (((r.qty || 0) * (r.price || 0)) * ((r.discount || 0) / 100)), 0); },
        get subtotal() { return this.rows.reduce((s, r) => s + this.rowSubtotal(r), 0); },
        get taxableAmount() { return Math.max(0, this.subtotal - (Number(this.discountHeader) || 0)); },
        get taxAmount() { return this.taxableAmount * (this.taxRate / 100); },
        get total() { return this.taxableAmount + this.taxAmount; },

        validateForm(e) {
            // 1. Validasi Target Pengiriman (jika diisi, harus >= order_date)
            const expDeliveryInput = document.querySelector('input[name="expected_delivery_date"]');
            const orderDateInput = document.querySelector('input[name="order_date"]');
            if (expDeliveryInput && expDeliveryInput.value && orderDateInput && orderDateInput.value) {
                if (expDeliveryInput.value < orderDateInput.value) {
                    e.preventDefault();
                    expDeliveryInput.setCustomValidity('Target tanggal pengiriman tidak boleh lebih awal dari tanggal order!');
                    expDeliveryInput.reportValidity();
                    return false;
                } else {
                    expDeliveryInput.setCustomValidity('');
                }
            }

            // 2. Validasi Item Minimal 1
            if (!this.rows || this.rows.length === 0) {
                e.preventDefault();
                return false;
            }

            // 3. Validasi Diskon Header
            if (this.isDiscountInvalid) {
                e.preventDefault();
                const discInput = document.querySelector('input[name="discount_amount"]');
                if (discInput) {
                    discInput.setCustomValidity('Diskon header tidak boleh melebihi subtotal item!');
                    discInput.reportValidity();
                }
                return false;
            }

            // 4. Peringatan Konfirmasi Harga di Bawah HPP
            if (this.hasBelowHppItems() && !this.hppWarningConfirmed) {
                e.preventDefault();
                const belowItems = this.rows.filter(r => r.product_id && this.isBelowHpp(r))
                    .map(r => {
                        const p = products[r.product_id];
                        const hpp = this.getProductHpp(r.product_id);
                        const netPrice = (r.price || 0) * (1 - (r.discount || 0) / 100);
                        return `<li style="margin-bottom:8px; text-align:left;">
                                    <strong>${p ? p.name : 'Produk'} (${p ? p.sku : '-'})</strong><br>
                                    <span style="color:#64748b;">Harga Jual:</span> <span style="font-weight:700; color:#dc2626;">Rp ${this.formatNum(r.price)}</span>
                                    ${r.discount > 0 ? '<span style="color:#64748b;">(Netto: Rp ' + this.formatNum(netPrice) + ')</span>' : ''} 
                                    <span style="color:#64748b;">| HPP / Modal:</span> <span style="font-weight:700; color:#0284c7;">Rp ${this.formatNum(hpp)}</span>
                                </li>`;
                    }).join('');

                Swal.fire({
                    title: 'Peringatan: Harga di Bawah HPP!',
                    html: `<div style="font-size:13px; color:#475569; text-align:left; margin-bottom:12px;">
                            Terdapat produk dengan harga jual lebih rendah dari <strong>Harga Pokok Penjualan (HPP / Modal)</strong>:
                           </div>
                           <ul style="font-size:12.5px; text-align:left; margin-bottom:16px; padding-left:20px; max-height:180px; overflow-y:auto; background:#fff1f2; border:1px solid #fecdd3; border-radius:8px; padding:10px 14px 10px 28px;">
                            ${belowItems}
                           </ul>
                           <div style="font-size:13px; color:#b91c1c; text-align:left; font-weight:600;">
                            Apakah Anda yakin tetap ingin menyimpan Sales Order ini dengan harga di bawah HPP?
                           </div>`,
                    icon: 'warning',
                    iconColor: '#f59e0b',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fa-solid fa-triangle-exclamation" style="margin-right:6px;"></i> Ya, Tetap Simpan',
                    cancelButtonText: 'Batal & Periksa Kembali',
                    reverseButtons: true,
                    customClass: {
                        popup: 'erp-swal-modal',
                        confirmButton: 'btn btn-warning',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.hppWarningConfirmed = true;
                        this.$nextTick(() => {
                            e.target.submit();
                        });
                    }
                });
                return false;
            }
        },

        formatNum(v) { return new Intl.NumberFormat('id-ID').format(Math.round(v)); }
    }
}
</script>
@endpush
