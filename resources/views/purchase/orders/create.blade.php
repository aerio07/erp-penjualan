@extends('layouts.app')
@section('title', 'Buat Purchase Order')
@section('page-title', 'Buat Purchase Order')

@php
    $prOptions = $purchaseRequests->map(function($pr) {
        return [
            'id' => (string) $pr->id,
            'name' => $pr->pr_number . ' — Divisi ' . ($pr->division ?? 'Umum') . ' (' . $pr->items->count() . ' Item)',
            'subtext' => $pr->pr_number . ' · Divisi ' . ($pr->division ?? 'Umum'),
        ];
    });
@endphp

@section('content')
<div class="animate-in" x-data="poForm()">
    <x-page-header 
        title="Buat Purchase Order" 
        subtitle="Isi form di bawah untuk membuat PO baru"
        :back-url="route('purchase.orders.index')"
    />

    <form method="POST" action="{{ route('purchase.orders.store') }}" id="poForm" @submit="validateForm($event)" @keydown.enter="if ($event.target.tagName !== 'TEXTAREA') $event.preventDefault()">
        @csrf

        <div class="grid grid-3" style="margin-bottom:20px;">
            {{-- Header Info --}}
            <div class="card" style="grid-column: span 2;">
                <div class="card-header"><h3>Informasi PO</h3></div>
                <div class="card-body">
                    <div class="form-row form-row-2">
                        <div class="form-group">
                            <label class="form-label">Purchase Request <span style="color:var(--danger);">*</span></label>
                            <x-searchable-select 
                                name="purchase_request_id" 
                                :options="$prOptions" 
                                :value="old('purchase_request_id', $prefilledPrId ?? '')" 
                                placeholder="-- Pilih Purchase Request --" 
                                search-placeholder="Cari nomor PR atau divisi..." 
                                on-select="window.onPrChange"
                                required 
                            />
                            @error('purchase_request_id')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Supplier <span style="color:var(--danger);">*</span></label>
                            <x-searchable-select 
                                name="supplier_id" 
                                :options="$suppliers" 
                                :value="old('supplier_id')" 
                                placeholder="-- Pilih Supplier --" 
                                search-placeholder="Cari nama atau kode supplier..." 
                                required 
                            />
                        </div>
                    </div>

                    <div class="form-row form-row-3" style="margin-top:12px;">
                        <div class="form-group">
                            <label class="form-label">Tanggal PO <span style="color:var(--danger);">*</span></label>
                            <input type="date" name="order_date" value="{{ old('order_date', date('Y-m-d')) }}" class="form-control {{ $errors->has('order_date') ? 'is-invalid' : '' }}" required>
                            @error('order_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Expected Delivery <span style="color:var(--danger);">*</span></label>
                            <input type="date" name="expected_date" value="{{ old('expected_date') }}" class="form-control {{ $errors->has('expected_date') ? 'is-invalid' : '' }}" required>
                            @error('expected_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tarif PPN (%)</label>
                            <input type="number" name="tax_rate" value="{{ old('tax_rate', 11) }}" x-model.number="taxRate" class="form-control" step="0.01" min="0" max="100">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:12px;">
                        <label class="form-label"><i class="fa-solid fa-note-sticky text-primary"></i> Catatan Tambahan</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Instruksi khusus, syarat penyerahan barang, atau no. referensi...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Summary & Diskon --}}
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-receipt text-primary"></i> Ringkasan & Total</h3>
                </div>
                <div class="card-body">
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <div style="display:flex; justify-content:space-between; font-size:14px;">
                            <span style="color:var(--text-secondary);">Subtotal Item (Sebelum Diskon)</span>
                            <span style="font-weight:600;" x-text="'Rp ' + formatNum(grossSubtotal)"></span>
                        </div>
                        <template x-if="totalItemDiscount > 0">
                            <div style="display:flex; justify-content:space-between; font-size:13px; color:var(--danger);">
                                <span>Total Diskon Item</span>
                                <span style="font-weight:600;" x-text="'- Rp ' + formatNum(totalItemDiscount)"></span>
                            </div>
                        </template>
                        <div>
                            <label class="form-label">Diskon Header (Rp)</label>
                            <input type="text" 
                                   inputmode="numeric" 
                                   name="discount_amount" 
                                   x-currency="discountHeader" 
                                   :class="{'is-invalid': isDiscountInvalid}"
                                   class="form-control format-currency" 
                                   placeholder="0">
                            <template x-if="isDiscountInvalid">
                                <div style="font-size:11px; color:var(--danger); margin-top:4px; font-weight:600;">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Diskon melebihi subtotal (Maks: Rp <span x-text="formatNum(netSubtotal)"></span>)
                                </div>
                            </template>
                            @error('discount_amount')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:14px;">
                            <span style="color:var(--text-secondary);">Setelah Diskon</span>
                            <span x-text="'Rp ' + formatNum(taxableAmount)"></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:14px;">
                            <span style="color:var(--text-secondary);">PPN (<span x-text="taxRate"></span>%)</span>
                            <span x-text="'Rp ' + formatNum(taxAmount)"></span>
                        </div>
                        <hr style="border:none; border-top:1px solid var(--border);">
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
                <h3>Item Purchase Order</h3>
            </div>
            <div class="table-responsive" style="overflow: visible; min-height: 180px;">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th style="min-width: 260px;">Produk</th>
                            <th style="width:100px;">Qty</th>
                            <th style="width:160px;">Harga Satuan</th>
                            <th style="width:100px;">Diskon (%)</th>
                            <th style="width:150px; text-align:right;">Subtotal</th>
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="!selectedPrId">
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px 16px; color: var(--text-secondary);">
                                    <i class="fa-solid fa-file-lines" style="font-size: 28px; margin-bottom: 8px; display: block; opacity: 0.4;"></i>
                                    Pilih Purchase Request terlebih dahulu
                                </td>
                            </tr>
                        </template>
                        <template x-for="(row, idx) in rows" :key="idx">
                            <tr>
                                <td style="vertical-align: top !important;">
                                    <div style="position: relative; width: 100%;" x-data="{ openProd: false, prodSearch: '' }" @click.outside="openProd = false" @keydown.escape.window="openProd = false">
                                        <input type="hidden" :name="`items[${idx}][product_id]`" :value="row.product_id" required>
                                        
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
                                                <template x-for="p in availableProductsForPo.filter(item => !prodSearch || item.name.toLowerCase().includes(prodSearch.toLowerCase()) || item.sku.toLowerCase().includes(prodSearch.toLowerCase()))" :key="p.id">
                                                    <div 
                                                        @click="row.product_id = p.id; onProductChange(idx, { target: { value: p.id } }); openProd = false; prodSearch = '';"
                                                        :class="{ 'bg-blue-50 font-semibold text-blue-700': String(row.product_id) === String(p.id) }"
                                                        style="padding: 8px 10px; cursor: pointer; font-size: 13px; border-bottom: 1px solid #f8fafc; display: flex; justify-content: space-between; align-items: center;"
                                                        onmouseover="this.style.backgroundColor='#f1f5f9'"
                                                        onmouseout="if(String(row.product_id) !== String(p.id)) this.style.backgroundColor='transparent'"
                                                    >
                                                        <span x-text="p.name + ' (' + p.sku + ')'"></span>
                                                        <template x-if="selectedPrId && purchaseRequests[selectedPrId]">
                                                            <span class="badge" style="font-size: 10px; background: #e0e7ff; color: #3730a3;">Item PR</span>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td style="vertical-align: top !important;">
                                    <input 
                                        type="number" 
                                        :name="`items[${idx}][qty_ordered]`" 
                                        x-model.number="row.qty" 
                                        class="form-control bg-slate-100 text-slate-700 cursor-not-allowed font-semibold" 
                                        style="height:38px;" 
                                        readonly
                                        tabindex="-1"
                                        min="1" 
                                        required
                                    >
                                </td>
                                <td style="vertical-align: top !important;">
                                    <input type="text" 
                                           inputmode="numeric" 
                                           :name="`items[${idx}][unit_price]`" 
                                           :value="window.formatRupiah ? window.formatRupiah(row.price) : row.price" 
                                           @input="row.price = window.unformatRupiah ? window.unformatRupiah($event.target.value) : (parseInt($event.target.value) || 0); if(window.applyMaskWithCursor) window.applyMaskWithCursor($event.target); recalc();" 
                                           @focus="$event.target.select()" 
                                           @blur="if(window.formatRupiah) $event.target.value = window.formatRupiah(row.price)" 
                                           :class="[row.refPrice > 0 && row.price > row.refPrice ? 'input-price-warning' : '', 'form-control', 'format-currency']" 
                                           style="height:38px;" 
                                           placeholder="0" 
                                           required>
                                    <template x-if="row.refPrice > 0 && row.price > row.refPrice">
                                        <div class="price-badge price-badge--warning">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                            Harga melebihi patokan (Rp <span x-text="formatNum(row.refPrice)"></span>)
                                        </div>
                                    </template>
                                </td>
                                <td style="vertical-align: top !important;">
                                    <input type="number" :name="`items[${idx}][discount_percent]`" x-model.number="row.discount" @input="recalc()" class="form-control" style="height:38px;" min="0" max="100" step="0.1">
                                    <div style="font-size:11px; color:var(--danger); margin-top:3px; font-weight:500;" x-show="row.discount > 0" x-text="'-Rp ' + formatNum((row.qty || 0) * (row.price || 0) * ((row.discount || 0) / 100))"></div>
                                </td>
                                <td style="vertical-align: top !important; text-align:right; font-weight:600;">
                                    <div style="height: 38px; display: flex; align-items: center; justify-content: flex-end;">
                                        <span x-text="'Rp ' + formatNum(rowSubtotal(row))"></span>
                                    </div>
                                </td>
                                <td style="vertical-align: top !important; text-align:center;">
                                    <div style="height: 38px; display: flex; align-items: center; justify-content: center;">
                                        <template x-if="rows.length > 1">
                                            <button type="button" class="btn btn-danger btn-sm btn-icon" @click="removeRow(idx)" title="Hapus Item">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        @if(!empty($demandIds))
            @foreach((array)$demandIds as $dId)
                <input type="hidden" name="demand_ids[]" value="{{ $dId }}">
            @endforeach
        @endif

        <x-form-actions 
            :back-url="route('purchase.orders.index')" 
            submit-label="Simpan sebagai Draft" 
        />
    </form>
</div>
@endsection

@push('scripts')
<script>
const products = @json($products->keyBy('id'));
const purchaseRequests = @json($purchaseRequests->keyBy('id'));

window.onPrChange = function(prId) {
    if (window.poApp) {
        window.poApp.onPrSelect(prId);
    }
};

function poForm() {
    const prefilledPr = '{{ $prefilledPrId ?? old('purchase_request_id', '') }}';
    let initialRows = [];

    if (prefilledPr && purchaseRequests[prefilledPr] && purchaseRequests[prefilledPr].items && purchaseRequests[prefilledPr].items.length > 0) {
        initialRows = purchaseRequests[prefilledPr].items.map(it => {
            const prod = products[it.product_id] || (it.product || {});
            const pp = Math.round(parseFloat(prod.purchase_price || 0));
            return {
                product_id: it.product_id,
                qty: it.qty,
                price: pp,
                discount: 0,
                refPrice: pp
            };
        });
    }

    return {
        rows: initialRows,
        taxRate: 11,
        discountHeader: 0,
        selectedPrId: prefilledPr,

        get availableProductsForPo() {
            if (this.selectedPrId && purchaseRequests[this.selectedPrId] && purchaseRequests[this.selectedPrId].items && purchaseRequests[this.selectedPrId].items.length > 0) {
                const prProdIds = purchaseRequests[this.selectedPrId].items.map(it => String(it.product_id));
                return Object.values(products).filter(p => prProdIds.includes(String(p.id)));
            }
            return [];
        },

        init() {
            window.poApp = this;
            if (this.selectedPrId) {
                this.onPrSelect(this.selectedPrId);
            }
        },

        onPrSelect(prId) {
            this.selectedPrId = prId ? String(prId) : '';
            if (!this.selectedPrId) {
                this.rows = [];
                return;
            }
            const pr = purchaseRequests[this.selectedPrId];
            if (pr && pr.items && pr.items.length > 0) {
                this.rows = pr.items.map(it => {
                    const prod = products[it.product_id] || (it.product || {});
                    const pp = Math.round(parseFloat(prod.purchase_price || 0));
                    return {
                        product_id: it.product_id,
                        qty: it.qty,
                        price: pp,
                        discount: 0,
                        refPrice: pp
                    };
                });
                if (pr.required_date) {
                    const expInput = document.querySelector('input[name="expected_date"]');
                    if (expInput) {
                        expInput.value = (typeof pr.required_date === 'string') ? pr.required_date.substring(0, 10) : '';
                    }
                }
            } else {
                this.rows = [];
            }
        },

        addRow() { 
            // Pembuatan PO wajib berasal dari item PR
        },
        removeRow(idx) { 
            if (this.rows.length > 1) {
                this.rows.splice(idx, 1);
                this.recalc();
            }
        },

        onProductChange(idx, event) {
            const id = (event && event.target) ? event.target.value : this.rows[idx].product_id;
            this.rows[idx].product_id = id;
            if (this.selectedPrId && purchaseRequests[this.selectedPrId] && purchaseRequests[this.selectedPrId].items) {
                const prItem = purchaseRequests[this.selectedPrId].items.find(it => String(it.product_id) === String(id));
                if (prItem) {
                    this.rows[idx].qty = prItem.qty;
                }
            }
            if (id && products[id]) {
                const ref = Math.round(parseFloat(products[id].purchase_price)) || 0;
                this.rows[idx].price    = ref;
                this.rows[idx].refPrice = ref;
            } else {
                this.rows[idx].price    = 0;
                this.rows[idx].refPrice = 0;
            }
            this.recalc();
        },

        rowGross(row) {
            return (Number(row.qty) || 0) * (Number(row.price) || 0);
        },
        rowDiscount(row) {
            return this.rowGross(row) * ((Number(row.discount) || 0) / 100);
        },
        rowSubtotal(row) {
            return this.rowGross(row) - this.rowDiscount(row);
        },

        get grossSubtotal() { return this.rows.reduce((s, r) => s + this.rowGross(r), 0); },
        get totalItemDiscount() { return this.rows.reduce((s, r) => s + this.rowDiscount(r), 0); },
        get netSubtotal() { return this.grossSubtotal - this.totalItemDiscount; },
        get subtotal() { return this.grossSubtotal; },
        get isDiscountInvalid() { return (Number(this.discountHeader) || 0) > this.netSubtotal; },
        get hasOverPrice() { return this.rows.some(r => r.refPrice > 0 && r.price > r.refPrice); },
        get taxableAmount() { return Math.max(0, this.netSubtotal - (Number(this.discountHeader) || 0)); },
        get taxAmount() { return this.taxableAmount * ((Number(this.taxRate) || 0) / 100); },
        get total() { return this.taxableAmount + this.taxAmount; },

        recalc() { /* Alpine handles reactivity */ },
        validateForm(e) {
            if (!this.selectedPrId) {
                e.preventDefault();
                alert('Pilih Purchase Request (PR) yang telah disetujui terlebih dahulu!');
                return false;
            }
            if (!this.rows || this.rows.length === 0 || !this.rows[0].product_id) {
                e.preventDefault();
                alert('Daftar item PO dari Purchase Request belum termuat atau masih kosong!');
                return false;
            }
            if (this.isDiscountInvalid) {
                e.preventDefault();
                alert('Diskon header tidak boleh melebihi subtotal item!');
                return false;
            }
        },
        formatNum(v) { return new Intl.NumberFormat('id-ID').format(Math.round(v)); }
    }
}
</script>
@endpush
