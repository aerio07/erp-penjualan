@extends('layouts.app')
@section('title', 'Buat Purchase Order')
@section('page-title', 'Buat Purchase Order')

@section('content')
<div class="animate-in" x-data="poForm()">
    <div class="page-header">
        <div>
            <h1>Buat Purchase Order</h1>
            <p>Isi form di bawah untuk membuat PO baru</p>
        </div>
        <a href="{{ route('purchase.orders.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <form method="POST" action="{{ route('purchase.orders.store') }}" id="poForm">
        @csrf

        <div class="grid grid-3" style="margin-bottom:20px;">
            {{-- Header Info --}}
            <div class="card" style="grid-column: span 2;">
                <div class="card-header"><h3>Informasi PO</h3></div>
                <div class="card-body">
                    <div class="form-row form-row-4">
                        <div class="form-group">
                            <label class="form-label">Supplier <span style="color:var(--danger);">*</span></label>
                            <select name="supplier_id" class="form-control {{ $errors->has('supplier_id') ? 'is-invalid' : '' }}" required>
                                <option value="">-- Pilih Supplier --</option>
                                @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" {{ old('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }} ({{ $s->code }})</option>
                                @endforeach
                            </select>
                            @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tanggal PO <span style="color:var(--danger);">*</span></label>
                            <input type="date" name="order_date" value="{{ old('order_date', date('Y-m-d')) }}" class="form-control {{ $errors->has('order_date') ? 'is-invalid' : '' }}" required>
                            @error('order_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Expected Delivery</label>
                            <input type="date" name="expected_date" value="{{ old('expected_date') }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tarif PPN (%)</label>
                            <input type="number" name="tax_rate" value="{{ old('tax_rate', 11) }}" x-model.number="taxRate" class="form-control" step="0.01" min="0" max="100">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="1" placeholder="Catatan tambahan...">{{ old('notes') }}</textarea>
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
                        <div>
                            <label class="form-label">Diskon Header (Rp)</label>
                            <input type="number" name="discount_amount" x-model.number="discountHeader" class="form-control" min="0" step="1000">
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:14px;">
                            <span style="color:var(--text-secondary);">Setelah Diskon</span>
                            <span x-text="'Rp ' + formatNum(subtotal - discountHeader)"></span>
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
                <h3>Item Purchase Order</h3>
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
                            <th style="width:150px;">Harga Satuan</th>
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
                                        <option value="{{ $p->id }}" data-price="{{ $p->purchase_price }}">{{ $p->name }} ({{ $p->sku }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" :name="`items[${idx}][qty_ordered]`" x-model.number="row.qty" @input="recalc()" class="form-control" min="1" required>
                                </td>
                                <td>
                                    <input type="number" :name="`items[${idx}][unit_price]`" x-model.number="row.price" @input="recalc()" class="form-control" min="0" step="100">
                                </td>
                                <td>
                                    <input type="number" :name="`items[${idx}][discount_percent]`" x-model.number="row.discount" @input="recalc()" class="form-control" min="0" max="100" step="0.1">
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

        @if(!empty($demandIds))
            @foreach((array)$demandIds as $dId)
                <input type="hidden" name="demand_ids[]" value="{{ $dId }}">
            @endforeach
        @endif

        <div style="display:flex; justify-content:flex-end; gap:12px;">
            <a href="{{ route('purchase.orders.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Simpan sebagai Draft
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const products = @json($products->keyBy('id'));

function poForm() {
    const prefilledId = '{{ $prefilledProductId ?? "" }}';
    const prefilledQty = {{ $prefilledQty ?? 1 }};
    let initialRows = [{ product_id: '', qty: 1, price: 0, discount: 0 }];

    if (prefilledId && products[prefilledId]) {
        initialRows = [{
            product_id: prefilledId,
            qty: prefilledQty,
            price: parseFloat(products[prefilledId].purchase_price) || 0,
            discount: 0
        }];
    }

    return {
        rows: initialRows,
        taxRate: 11,
        discountHeader: 0,

        addRow() { this.rows.push({ product_id: '', qty: 1, price: 0, discount: 0 }); },
        removeRow(idx) { this.rows.splice(idx, 1); },

        onProductChange(idx) {
            const id = this.rows[idx].product_id;
            if (id && products[id]) {
                this.rows[idx].price = parseFloat(products[id].purchase_price) || 0;
            }
        },

        rowSubtotal(row) {
            const base = row.qty * row.price;
            return base - (base * (row.discount / 100));
        },

        get subtotal() { return this.rows.reduce((s, r) => s + this.rowSubtotal(r), 0); },
        get taxableAmount() { return Math.max(0, this.subtotal - this.discountHeader); },
        get taxAmount() { return this.taxableAmount * (this.taxRate / 100); },
        get total() { return this.taxableAmount + this.taxAmount; },

        recalc() { /* Alpine handles reactivity */ },
        formatNum(v) { return new Intl.NumberFormat('id-ID').format(Math.round(v)); }
    }
}
</script>
@endpush
