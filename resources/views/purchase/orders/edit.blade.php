@extends('layouts.app')
@section('title', 'Edit Purchase Order - ' . $order->po_number)
@section('page-title', 'Edit Purchase Order')

@section('content')
@php
    $initialRows = old('items');
    if (!$initialRows) {
        $initialRows = $order->items->map(function($item) {
            return [
                'product_id' => $item->product_id,
                'qty'        => $item->qty_ordered,
                'price'      => (float)$item->unit_price,
                'discount'   => (float)$item->discount_percent,
                'refPrice'   => (float)($item->product->purchase_price ?? 0),
            ];
        })->values()->toArray();
    }
    if (empty($initialRows)) {
        $initialRows = [['product_id' => '', 'qty' => 1, 'price' => 0, 'discount' => 0, 'refPrice' => 0]];
    }
@endphp

<div class="animate-in" x-data="poForm()">
    <x-page-header 
        :title="'Edit Purchase Order: ' . $order->po_number" 
        subtitle="Perbarui rincian Purchase Order yang berstatus Draft"
        :back-url="route('purchase.orders.index')"
    >
        <x-slot:action>
            <a href="{{ route('purchase.orders.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-list"></i> Daftar PO
            </a>
        </x-slot:action>
    </x-page-header>

    @php
        $latestApproval = $order->approvalRequests->sortByDesc('id')->first();
    @endphp
    @if($latestApproval && $latestApproval->status === 'revised')
    <div class="alert alert-warning" style="margin-bottom:20px; display:flex; align-items:flex-start; gap:12px; padding:14px 18px; border-radius:8px; background:#fffdf7; border:1px solid #fcd34d; color:#92400e;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size:18px; margin-top:2px; color:#d97706;"></i>
        <div>
            <strong>Catatan Permintaan Revisi dari Approver ({{ $latestApproval->approver->name ?? 'Admin' }}):</strong>
            <div style="margin-top:4px;">{{ $latestApproval->rejection_reason ?: ($order->cancel_reason ?: 'Harap periksa kembali detail pesanan ini.') }}</div>
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('purchase.orders.update', $order) }}" id="poForm" @submit="validateForm($event)" @keydown.enter="if ($event.target.tagName !== 'TEXTAREA') $event.preventDefault()">
        @csrf
        @method('PUT')

        <div class="grid grid-3" style="margin-bottom:20px;">
            {{-- Header Info --}}
            <div class="card" style="grid-column: span 2;">
                <div class="card-header"><h3>Informasi PO</h3></div>
                <div class="card-body">
                    @if($order->purchaseRequest)
                    <div style="margin-bottom:16px; padding:10px 14px; border-radius:8px; background:rgba(99, 102, 241, 0.05); border:1px solid rgba(99, 102, 241, 0.2); display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-file-invoice text-primary"></i>
                        <span style="font-size:13px;">Dibuat dari Purchase Request: <strong>{{ $order->purchaseRequest->pr_number }}</strong> (Divisi {{ $order->purchaseRequest->division ?? 'Umum' }})</span>
                    </div>
                    @endif

                    <div class="form-row form-row-4">
                        <div class="form-group">
                            <label class="form-label">Supplier <span style="color:var(--danger);">*</span></label>
                            <x-searchable-select 
                                name="supplier_id" 
                                :options="$suppliers" 
                                :value="old('supplier_id', $order->supplier_id)" 
                                placeholder="-- Pilih Supplier --" 
                                search-placeholder="Cari nama atau kode supplier..." 
                                required 
                            />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tanggal PO <span style="color:var(--danger);">*</span></label>
                            <input type="date" name="order_date" value="{{ old('order_date', $order->order_date ? $order->order_date->format('Y-m-d') : '') }}" class="form-control {{ $errors->has('order_date') ? 'is-invalid' : '' }}" required>
                            @error('order_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Expected Delivery <span style="color:var(--danger);">*</span></label>
                            <input type="date" name="expected_date" value="{{ old('expected_date', $order->expected_date ? $order->expected_date->format('Y-m-d') : '') }}" class="form-control {{ $errors->has('expected_date') ? 'is-invalid' : '' }}" required>
                            @error('expected_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tarif PPN (%)</label>
                            <input type="number" name="tax_rate" value="{{ old('tax_rate', $order->tax_rate) }}" x-model.number="taxRate" class="form-control" step="0.01" min="0" max="100">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:12px;">
                        <label class="form-label"><i class="fa-solid fa-note-sticky text-primary"></i> Catatan Tambahan</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Instruksi khusus, syarat penyerahan barang, atau no. referensi...">{{ old('notes', $order->notes) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Summary --}}
            <div class="card">
                <div class="card-header"><h3>Ringkasan</h3></div>
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
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3>Item Purchase Order</h3>
                <div style="display: flex; align-items: center; gap: 8px;">
                    @if(!$order->purchase_request_id)
                    <button type="button" class="btn btn-primary btn-sm" @click="addRow()">
                        <i class="fa-solid fa-plus"></i> Tambah Item
                    </button>
                    @endif
                </div>
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
                        <template x-for="(row, idx) in rows" :key="idx">
                            <tr>
                                <td style="vertical-align: top !important;">
                                    @if($order->purchase_request_id)
                                    {{-- Produk dari PR: tampilkan sebagai teks statis, tidak bisa diganti --}}
                                    <input type="hidden" :name="`items[${idx}][product_id]`" :value="row.product_id" required>
                                    <div 
                                        class="form-control"
                                        style="display: flex !important; align-items: center !important; min-height: 38px !important; height: 38px !important; background: #f8fafc; cursor: default; padding: 0 0.75rem !important; border-radius: 0.5rem; color: #334155; font-weight: 600;"
                                    >
                                        <span x-text="products[row.product_id] ? (products[row.product_id].name + ' (' + products[row.product_id].sku + ')') : '-'"
                                              style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                        ></span>
                                    </div>
                                    @else
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
                                    @endif
                                </td>
                                <td style="vertical-align: top !important;">
                                    <input 
                                        type="number" 
                                        :name="`items[${idx}][qty_ordered]`" 
                                        x-model.number="row.qty" 
                                        @input="recalc()" 
                                        class="form-control {{ $order->purchase_request_id ? 'bg-slate-100 text-slate-700 cursor-not-allowed font-semibold' : '' }}" 
                                        style="height:38px;" 
                                        min="1" 
                                        {{ $order->purchase_request_id ? 'readonly tabindex=-1' : '' }}
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
                                        @if(!$order->purchase_request_id)
                                        <button type="button" class="btn btn-danger btn-sm btn-icon" @click="removeRow(idx)" x-show="rows.length > 1" title="Hapus Item">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <x-form-actions 
            :back-url="route('purchase.orders.index')" 
            submit-label="Perbarui Purchase Order" 
        />
    </form>
</div>
@endsection

@push('scripts')
<script>
const products = @json($products->keyBy('id'));

function poForm() {
    return {
        rows: @json($initialRows),
        taxRate: {{ old('tax_rate', $order->tax_rate ?? 11) }},
        discountHeader: {{ old('discount_amount', $order->discount_amount ?? 0) }},

        addRow() { this.rows.push({ product_id: '', qty: 1, price: 0, discount: 0, refPrice: 0 }); },
        removeRow(idx) { this.rows.splice(idx, 1); },

        onProductChange(idx, event) {
            const id = (event && event.target) ? event.target.value : this.rows[idx].product_id;
            this.rows[idx].product_id = id;
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
            if (this.isDiscountInvalid) {
                e.preventDefault();
                alert('Diskon header tidak boleh melebihi subtotal item!');
                return false;
            }
        },
        formatNum(v) { return new Intl.NumberFormat('id-ID').format(Math.round(v || 0)); }
    }
}
</script>
@endpush
