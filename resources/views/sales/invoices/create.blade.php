@extends('layouts.app')
@section('title', 'Terbitkan Invoice Penjualan (3-Way Match)')
@section('page-title', 'Terbitkan Invoice Penjualan')

@section('content')
<div class="animate-in" x-data="invForm()">
    <div class="page-header">
        <div>
            <h1>Terbitkan Invoice Penjualan (3-Way Match)</h1>
            <p>Invoice tagihan dihitung secara otomatis berdasarkan <strong>barang yang sudah dikirim ke customer dan belum pernah di-invoice sebelumnya</strong> (mencegah double invoicing).</p>
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
                <div class="card-header"><h3>Informasi Faktur Penjualan</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Sales Order <span style="color:var(--danger);">*</span></label>
                        <select name="sales_order_id" class="form-control" x-model="selectedSoId" @change="onSoChange()" required>
                            <option value="">-- Pilih Sales Order yang Memiliki Barang Terkirim (Unbilled) --</option>
                            @foreach($orders as $so)
                            <option value="{{ $so->id }}">
                                {{ $so->so_number }} - {{ $so->customer->name }} (Status: {{ ucfirst(str_replace('_', ' ', $so->status)) }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-row form-row-3">
                        <div class="form-group">
                            <label class="form-label">Tanggal Invoice <span style="color:var(--danger);">*</span></label>
                            <input type="date" name="invoice_date" value="{{ old('invoice_date', date('Y-m-d')) }}" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tanggal Jatuh Tempo <span style="color:var(--danger);">*</span></label>
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

            {{-- Summary 3-Way Match Card --}}
            <div class="card">
                <div class="card-header"><h3>Ringkasan Tagihan 3-Way Match</h3></div>
                <div class="card-body">
                    <template x-if="selectedSo">
                        <div style="display:flex; flex-direction:column; gap:12px; font-size:14px;">
                            <div>
                                <div style="font-size:12px; color:var(--text-secondary);">Customer</div>
                                <div style="font-weight:600;" x-text="selectedSo.customer ? selectedSo.customer.name : '-'"></div>
                            </div>
                            <hr style="border:none; border-top:1px solid var(--border);">
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:var(--text-secondary);">Subtotal DPP (Barang Dikirim)</span>
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
                                <i class="fa-solid fa-shield-halved"></i> <strong>Anti-Double Invoicing:</strong> Menagih <span x-text="totalUnbilledQty"></span> unit terkirim yang belum pernah ditagih sebelumnya.
                            </div>
                        </div>
                    </template>
                    <template x-if="!selectedSo">
                        <div style="text-align:center; padding:24px 0; color:var(--text-secondary); font-size:13px;">
                            <i class="fa-solid fa-file-invoice-dollar" style="font-size:28px; margin-bottom:8px; display:block; opacity:0.4;"></i>
                            Pilih Sales Order terlebih dahulu
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Detail Verifikasi Pengiriman vs Tagihan --}}
        <div class="card" style="margin-bottom:20px;" x-show="selectedSo">
            <div class="card-header">
                <h3>Detail Verifikasi Pengiriman (Per Baris Produk)</h3>
            </div>
            <div class="table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th style="text-align:center; width:90px;">Dipesan (SO)</th>
                            <th style="text-align:center; width:100px;">Sudah Dikirim Total</th>
                            <th style="text-align:center; width:110px; color:var(--text-secondary);">Sudah Ditagih Lalu</th>
                            <th style="text-align:center; width:130px; background:rgba(16, 185, 129, 0.08);">Ditagih Saat Ini</th>
                            <th style="text-align:right; width:140px;">Harga Satuan</th>
                            <th style="text-align:right; width:150px;">Subtotal Tagihan Ini</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="item in (selectedSo ? selectedSo.items : [])" :key="item.id">
                            <tr>
                                <td>
                                    <div style="font-weight:600;" x-text="item.product ? item.product.name : 'Item #' + item.id"></div>
                                    <div style="font-size:12px; color:var(--text-secondary);" x-text="item.product ? item.product.sku : ''"></div>
                                </td>
                                <td style="text-align:center; font-weight:600;" x-text="item.qty_ordered + ' ' + (item.product ? item.product.unit : '')"></td>
                                <td style="text-align:center; color:var(--success); font-weight:600;" x-text="item.qty_delivered"></td>
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

        <div style="display:flex; justify-content:flex-end; gap:12px;" x-show="selectedSo">
            <a href="{{ route('sales.invoices.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary" :disabled="totalUnbilledQty <= 0">
                <i class="fa-solid fa-floppy-disk"></i> Terbitkan Invoice & Posting Jurnal (HPP + Piutang)
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const orders = @json($orders->keyBy('id'));
const initialSoId = "{{ $selectedSoId ?? '' }}";

function invForm() {
    return {
        selectedSoId: initialSoId,
        selectedSo: null,
        taxRate: 11,

        init() {
            if (this.selectedSoId) {
                this.onSoChange();
            }
        },

        onSoChange() {
            if (!this.selectedSoId || !orders[this.selectedSoId]) {
                this.selectedSo = null;
                return;
            }
            this.selectedSo = orders[this.selectedSoId];
            this.taxRate = parseFloat(this.selectedSo.tax_rate) || 11;
        },

        itemLineSubtotal(item) {
            const qty = Number(item.qty_unbilled) || 0;
            const price = Number(item.unit_price) || 0;
            const disc = Number(item.discount_percent) || 0;
            const base = qty * price;
            return base - (base * (disc / 100));
        },

        get totalUnbilledQty() {
            if (!this.selectedSo || !this.selectedSo.items) return 0;
            return this.selectedSo.items.reduce((s, it) => s + (Number(it.qty_unbilled) || 0), 0);
        },

        get subtotal() {
            if (!this.selectedSo || !this.selectedSo.items) return 0;
            const itemsSum = this.selectedSo.items.reduce((s, it) => s + this.itemLineSubtotal(it), 0);
            const totalOrderSubtotal = this.selectedSo.items.reduce((s, it) => s + (Number(it.subtotal) || 0), 0);
            const headerDisc = Number(this.selectedSo.discount_amount) || 0;
            const proratedDisc = totalOrderSubtotal > 0 ? (itemsSum / totalOrderSubtotal) * headerDisc : 0;
            return Math.max(0, itemsSum - proratedDisc);
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
