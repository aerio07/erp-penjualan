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
                'qty' => $item->qty_ordered,
                'price' => (float)$item->unit_price,
                'discount' => (float)$item->discount_percent,
            ];
        })->values()->toArray();
    }
    if (empty($initialRows)) {
        $initialRows = [['product_id' => '', 'qty' => 1, 'price' => 0, 'discount' => 0]];
    }
@endphp

<div class="animate-in" x-data="poForm()">
    <x-page-header 
        :title="'Edit Purchase Order: ' . $order->po_number" 
        subtitle="Perbarui rincian Purchase Order yang berstatus Draft"
        :back-url="route('purchase.orders.show', $order)"
    >
        <x-slot:action>
            <a href="{{ route('purchase.orders.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-list"></i> Daftar PO
            </a>
        </x-slot:action>
    </x-page-header>

    <form method="POST" action="{{ route('purchase.orders.update', $order) }}" id="poForm" @submit="validateForm($event)">
        @csrf
        @method('PUT')

        <div class="grid grid-3" style="margin-bottom:20px;">
            {{-- Header Info --}}
            <div class="card" style="grid-column: span 2;">
                <div class="card-header"><h3>Informasi PO</h3></div>
                <div class="card-body">
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

                    <div class="form-row form-row-2" style="margin-top:12px;">
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-warehouse text-primary"></i> Lokasi Gudang Tujuan</label>
                            <x-searchable-select 
                                name="warehouse_id" 
                                :options="$warehouses" 
                                :value="old('warehouse_id', $order->warehouse_id)" 
                                placeholder="-- Pilih Gudang Tujuan --" 
                                search-placeholder="Cari nama atau kode gudang..." 
                                on-select="whId => onWarehouseChange(whId)" 
                            />
                            <span style="font-size:11px; color:var(--text-secondary); margin-top:4px; display:block;">Pilih gudang untuk menetapkan alokasi stok dan mengisi alamat pengiriman otomatis.</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-note-sticky text-primary"></i> Catatan Tambahan</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Instruksi khusus, syarat penyerahan barang, atau no. referensi...">{{ old('notes', $order->notes) }}</textarea>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:6px;">
                        <label class="form-label"><i class="fa-solid fa-truck-ramp-box text-primary"></i> Ship To (Alamat Tujuan Pengiriman Barang)</label>
                        <textarea name="ship_to" x-model="shipTo" class="form-control" rows="2" placeholder="Nama penerima, PIC/kontak, nama gudang, dan alamat lengkap tujuan pengiriman...">{{ old('ship_to', $order->ship_to) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Summary --}}
            <div class="card">
                <div class="card-header"><h3>Ringkasan</h3></div>
                <div class="card-body">
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <div style="display:flex; justify-content:space-between; font-size:14px;">
                            <span style="color:var(--text-secondary);">Subtotal Item</span>
                            <span style="font-weight:600;" x-text="'Rp ' + formatNum(subtotal)"></span>
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
                                    <i class="fa-solid fa-triangle-exclamation"></i> Diskon melebihi subtotal (Maks: Rp <span x-text="formatNum(subtotal)"></span>)
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
                <button type="button" class="btn btn-primary btn-sm" @click="addRow()">
                    <i class="fa-solid fa-plus"></i> Tambah Item
                </button>
            </div>
            <div class="table-responsive" style="overflow: visible;">
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
                                <td style="position: relative;" x-data="{ openProd: false, prodSearch: '' }">
                                    <input type="hidden" :name="`items[${idx}][product_id]`" :value="row.product_id" required>
                                    
                                    <div 
                                        @click="openProd = !openProd; if(openProd) $nextTick(() => $refs.prodSearchInput.focus())"
                                        class="form-control"
                                        style="display: flex; align-items: center; justify-content: space-between; cursor: pointer; min-height: 38px; background: #fff;"
                                        @click.outside="openProd = false"
                                    >
                                        <span x-text="products[row.product_id] ? (products[row.product_id].name + ' (' + products[row.product_id].sku + ')') : '-- Pilih Produk --'"
                                              :style="!row.product_id ? 'color: #94a3b8;' : 'color: #0f172a; font-weight: 500;'"
                                              style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-right: 6px;"
                                        ></span>
                                        <i class="fa-solid fa-chevron-down text-slate-400" style="font-size: 11px;"></i>
                                    </div>

                                    {{-- Dropdown Menu --}}
                                    <div 
                                        x-show="openProd" 
                                        x-transition
                                        style="position: absolute; top: 100%; left: 0; min-width: 320px; z-index: 1060; background: #fff; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); border: 1px solid #e2e8f0; max-height: 260px; display: flex; flex-direction: column; overflow: hidden;"
                                        x-cloak
                                    >
                                        <div style="padding: 6px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; position: relative;">
                                            <div style="position: relative; display: flex; align-items: center;">
                                                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 9px; color: #64748b; font-size: 12px; pointer-events: none;"></i>
                                                <input 
                                                    type="text" 
                                                    x-ref="prodSearchInput"
                                                    x-model="prodSearch" 
                                                    placeholder="Cari nama atau SKU produk..." 
                                                    class="form-control" 
                                                    style="width: 100%; padding-left: 28px; font-size: 12px; height: 32px; border-radius: 6px;"
                                                    @click.stop
                                                >
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
                                </td>
                                <td>
                                    <input type="number" :name="`items[${idx}][qty_ordered]`" x-model.number="row.qty" @input="recalc()" class="form-control" min="1" required>
                                </td>
                                <td>
                                    <input type="number" :name="`items[${idx}][unit_price]`" x-model.number="row.price" @input="recalc()" class="form-control" min="0" step="100" placeholder="0" required>
                                </td>
                                <td>
                                    <input type="number" :name="`items[${idx}][discount_percent]`" x-model.number="row.discount" @input="recalc()" class="form-control" min="0" max="100" step="0.1">
                                    <div style="font-size:11px; color:var(--danger); margin-top:3px; font-weight:500;" x-show="row.discount > 0" x-text="'-Rp ' + formatNum((row.qty || 0) * (row.price || 0) * ((row.discount || 0) / 100))"></div>
                                </td>
                                <td style="text-align:right; font-weight:600;">
                                    <span x-text="'Rp ' + formatNum(rowSubtotal(row))"></span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm btn-icon" @click="removeRow(idx)" x-show="rows.length > 1">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <x-form-actions 
            :back-url="route('purchase.orders.show', $order)" 
            submit-label="Perbarui Purchase Order" 
        />
    </form>
</div>
@endsection

@push('scripts')
<script>
const products = @json($products->keyBy('id'));
const warehouses = @json($warehouses->keyBy('id'));

function poForm() {
    return {
        rows: @json($initialRows),
        taxRate: {{ old('tax_rate', $order->tax_rate ?? 11) }},
        discountHeader: {{ old('discount_amount', $order->discount_amount ?? 0) }},
        warehouseId: @json(old('warehouse_id', $order->warehouse_id ?? '')),
        shipTo: @json(old('ship_to', $order->ship_to ?? '')),

        onWarehouseChange(whId) {
            this.warehouseId = whId;
            if (whId && warehouses[whId]) {
                const wh = warehouses[whId];
                this.shipTo = wh.name + (wh.address ? ' - ' + wh.address : '');
            }
        },

        addRow() { this.rows.push({ product_id: '', qty: 1, price: 0, discount: 0 }); },
        removeRow(idx) { this.rows.splice(idx, 1); },

        onProductChange(idx, event) {
            const id = (event && event.target) ? event.target.value : this.rows[idx].product_id;
            this.rows[idx].product_id = id;
            if (id && products[id]) {
                this.rows[idx].price = Math.round(parseFloat(products[id].purchase_price)) || 0;
            }
        },

        rowSubtotal(row) {
            const base = (row.qty || 0) * (row.price || 0);
            return base - (base * ((row.discount || 0) / 100));
        },

        get isDiscountInvalid() { return this.discountHeader > this.subtotal; },
        get totalItemDiscount() { return this.rows.reduce((s, r) => s + ((Number(r.qty) || 0) * (Number(r.price) || 0) * ((Number(r.discount) || 0) / 100)), 0); },
        get subtotal() { return this.rows.reduce((s, r) => s + this.rowSubtotal(r), 0); },
        get taxableAmount() { return Math.max(0, this.subtotal - (this.discountHeader || 0)); },
        get taxAmount() { return this.taxableAmount * ((this.taxRate || 0) / 100); },
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
