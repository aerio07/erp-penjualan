@extends('layouts.app')
@section('title', 'Terbitkan Invoice Pembelian (3-Way Match)')
@section('page-title', 'Terbitkan Invoice Pembelian')

@section('content')
<div class="animate-in" x-data="invForm()">
    <div class="page-header">
        <div>
            <h1>Terbitkan Invoice Pembelian (3-Way Match)</h1>
            <p>Invoice dihitung secara otomatis berdasarkan <strong>barang yang diterima dalam kondisi baik yang belum pernah di-invoice sebelumnya</strong> (mencegah double invoicing).</p>
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
                    <div class="form-group">
                        <label class="form-label">Purchase Order <span style="color:var(--danger);">*</span></label>
                        <select name="purchase_order_id" class="form-control" x-model="selectedPoId" @change="onPoChange()" required>
                            <option value="">-- Pilih Purchase Order yang Memiliki Sisa Tagihan (Unbilled) --</option>
                            @foreach($orders as $po)
                            <option value="{{ $po->id }}">
                                {{ $po->po_number }} - {{ $po->supplier->name }} (Status: {{ ucfirst(str_replace('_', ' ', $po->status)) }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-row form-row-2">
                        <div class="form-group">
                            <label class="form-label">No. Faktur / Invoice Supplier</label>
                            <input type="text" name="supplier_invoice_number" value="{{ old('supplier_invoice_number') }}" class="form-control" placeholder="Contoh: INV/SUP/2026/001">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tarif PPN (%) <span style="color:var(--danger);">*</span></label>
                            <input type="number" name="tax_rate" x-model.number="taxRate" class="form-control" step="0.01" min="0" max="100" required>
                        </div>
                    </div>

                    <div class="form-row form-row-2">
                        <div class="form-group">
                            <label class="form-label">Tanggal Invoice <span style="color:var(--danger);">*</span></label>
                            <input type="date" name="invoice_date" value="{{ old('invoice_date', date('Y-m-d')) }}" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tanggal Jatuh Tempo <span style="color:var(--danger);">*</span></label>
                            <input type="date" name="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Catatan invoice...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Summary 3-Way Match Card --}}
            <div class="card">
                <div class="card-header"><h3>Ringkasan 3-Way Match</h3></div>
                <div class="card-body">
                    <template x-if="selectedPo">
                        <div style="display:flex; flex-direction:column; gap:12px; font-size:14px;">
                            <div>
                                <div style="font-size:12px; color:var(--text-secondary);">Supplier</div>
                                <div style="font-weight:600;" x-text="selectedPo.supplier ? selectedPo.supplier.name : '-'"></div>
                            </div>
                            <hr style="border:none; border-top:1px solid var(--border);">
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:var(--text-secondary);">Subtotal DPP (Item Baru)</span>
                                <span style="font-weight:600;" x-text="'Rp ' + formatNum(subtotal)"></span>
                            </div>
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:var(--text-secondary);">PPN (<span x-text="taxRate"></span>%)</span>
                                <span style="font-weight:600;" x-text="'Rp ' + formatNum(taxAmount)"></span>
                            </div>
                            <hr style="border:none; border-top:1px solid var(--border);">
                            <div style="display:flex; justify-content:space-between; font-size:16px; font-weight:700;">
                                <span>Total Tagihan Invoice Ini</span>
                                <span style="color:var(--primary);" x-text="'Rp ' + formatNum(totalAmount)"></span>
                            </div>
                            <div style="padding:10px; background:#f0fdf4; border-radius:8px; border:1px solid #bbf7d0; font-size:12.5px; color:#166534; margin-top:8px;">
                                <i class="fa-solid fa-shield-halved"></i> <strong>Anti-Double Invoicing:</strong> Menagih <span x-text="totalUnbilledQty"></span> unit baru yang belum pernah masuk tagihan sebelumnya.
                            </div>
                        </div>
                    </template>
                    <template x-if="!selectedPo">
                        <div style="text-align:center; padding:24px 0; color:var(--text-secondary); font-size:13px;">
                            <i class="fa-solid fa-file-invoice-dollar" style="font-size:28px; margin-bottom:8px; display:block; opacity:0.4;"></i>
                            Pilih Purchase Order terlebih dahulu
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Detail Perbandingan 3-Way Match --}}
        <div class="card" style="margin-bottom:20px;" x-show="selectedPo">
            <div class="card-header">
                <h3>Detail Verifikasi 3-Way Match (Per Baris Produk)</h3>
            </div>
            <div class="table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th style="text-align:center; width:90px;">Dipesan (PO)</th>
                            <th style="text-align:center; width:90px;">Lolos QC Total</th>
                            <th style="text-align:center; width:110px; color:var(--text-secondary);">Sudah Di-Invoice Lalu</th>
                            <th style="text-align:center; width:130px; background:rgba(16, 185, 129, 0.08);">Ditagih Saat Ini</th>
                            <th style="text-align:right; width:140px;">Harga Satuan</th>
                            <th style="text-align:right; width:150px;">Subtotal Tagihan Ini</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="item in (selectedPo ? selectedPo.items : [])" :key="item.id">
                            <tr>
                                <td>
                                    <div style="font-weight:600;" x-text="item.product ? item.product.name : 'Item #' + item.id"></div>
                                    <div style="font-size:12px; color:var(--text-secondary);" x-text="item.product ? item.product.sku : ''"></div>
                                </td>
                                <td style="text-align:center; font-weight:600;" x-text="item.qty_ordered + ' ' + (item.product ? item.product.unit : '')"></td>
                                <td style="text-align:center; color:var(--success); font-weight:600;" x-text="item.qty_received"></td>
                                <td style="text-align:center; color:var(--text-secondary);" x-text="item.qty_invoiced"></td>
                                <td style="text-align:center; background:rgba(16, 185, 129, 0.05);">
                                    <span class="badge badge-done" style="font-weight:700;" x-text="item.qty_unbilled + ' ' + (item.product ? item.product.unit : '')"></span>
                                </td>
                                <td style="text-align:right;" x-text="'Rp ' + formatNum(item.unit_price)"></td>
                                <td style="text-align:right; font-weight:600; color:var(--primary);" x-text="'Rp ' + formatNum(itemLineSubtotal(item))"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:12px;" x-show="selectedPo">
            <a href="{{ route('purchase.invoices.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary" :disabled="totalUnbilledQty <= 0">
                <i class="fa-solid fa-floppy-disk"></i> Terbitkan Invoice & Posting Jurnal
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const orders = @json($orders->keyBy('id'));
const initialPoId = "{{ $selectedPoId ?? '' }}";

function invForm() {
    return {
        selectedPoId: initialPoId,
        selectedPo: null,
        taxRate: 11,

        init() {
            if (this.selectedPoId) {
                this.onPoChange();
            }
        },

        onPoChange() {
            if (!this.selectedPoId || !orders[this.selectedPoId]) {
                this.selectedPo = null;
                return;
            }
            this.selectedPo = orders[this.selectedPoId];
            this.taxRate = parseFloat(this.selectedPo.tax_rate) || 11;
        },

        itemLineSubtotal(item) {
            const qty = Number(item.qty_unbilled) || 0;
            const price = Number(item.unit_price) || 0;
            const disc = Number(item.discount_percent) || 0;
            const base = qty * price;
            return base - (base * (disc / 100));
        },

        get totalUnbilledQty() {
            if (!this.selectedPo || !this.selectedPo.items) return 0;
            return this.selectedPo.items.reduce((s, it) => s + (Number(it.qty_unbilled) || 0), 0);
        },

        get subtotal() {
            if (!this.selectedPo || !this.selectedPo.items) return 0;
            const itemsSum = this.selectedPo.items.reduce((s, it) => s + this.itemLineSubtotal(it), 0);
            const headerDisc = Number(this.selectedPo.discount_amount) || 0;
            return Math.max(0, itemsSum - headerDisc);
        },

        get taxAmount() {
            return this.subtotal * (this.taxRate / 100);
        },

        get totalAmount() {
            return this.subtotal + this.taxAmount;
        },

        formatNum(v) { return new Intl.NumberFormat('id-ID').format(Math.round(v || 0)); }
    }
}
</script>
@endpush
