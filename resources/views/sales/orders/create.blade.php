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

    <form method="POST" action="{{ route('sales.orders.store') }}" @submit="validateForm($event)">
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
                            <select name="customer_id" class="form-control" required>
                                <option value="">-- Pilih Customer --</option>
                                @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->code }})</option>
                                @endforeach
                            </select>
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
                            <span style="color:var(--text-secondary);">Subtotal Item</span>
                            <span style="font-weight:600;" x-text="'Rp ' + formatNum(subtotal)"></span>
                        </div>
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
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <h3>Item Barang Dijual</h3>
                <button type="button" class="btn btn-primary btn-sm" @click="addRow()">
                    <i class="fa-solid fa-plus"></i> Tambah Item
                </button>
            </div>
            <div class="table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th style="width:100px;">Qty</th>
                            <th style="width:150px;">Harga Jual Satuan</th>
                            <th style="width:100px;">Diskon (%)</th>
                            <th style="width:150px; text-align:right;">Subtotal</th>
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, idx) in rows" :key="idx">
                            <tr>
                                <td>
                                    <select :name="`items[${idx}][product_id]`" class="form-control" x-model="row.product_id" @change="onProductChange(idx)" required>
                                        <option value="">-- Pilih Produk --</option>
                                        @foreach($products as $p)
                                        <option value="{{ $p->id }}" data-price="{{ $p->sell_price }}">{{ $p->name }} ({{ $p->sku }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" :name="`items[${idx}][qty_ordered]`" x-model.number="row.qty" class="form-control" min="1" required>
                                </td>
                                <td>
                                    <input type="text" inputmode="numeric" :name="`items[${idx}][unit_price]`" x-currency="row.price" class="form-control format-currency" placeholder="0">
                                </td>
                                <td>
                                    <input type="number" :name="`items[${idx}][discount_percent]`" x-model.number="row.discount" class="form-control" min="0" max="100" step="0.1">
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

        onProductChange(idx) {
            const id = this.rows[idx].product_id;
            if (id && products[id]) {
                this.rows[idx].price = this.getEffectivePrice(products[id]);
            }
        },

        setChannel(newChannel) {
            if (this.channel === newChannel) return;
            this.channel = newChannel;
            this.onChannelChange();
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
            const base = row.qty * row.price;
            return base - (base * (row.discount / 100));
        },

        get isDiscountInvalid() { return this.discountHeader > this.subtotal; },
        get subtotal() { return this.rows.reduce((s, r) => s + this.rowSubtotal(r), 0); },
        get taxableAmount() { return Math.max(0, this.subtotal - this.discountHeader); },
        get taxAmount() { return this.taxableAmount * (this.taxRate / 100); },
        get total() { return this.taxableAmount + this.taxAmount; },

        validateForm(e) {
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
