@extends('layouts.app')
@section('title', 'Buat Retur Penjualan')
@section('page-title', 'Buat Retur Penjualan')

@section('content')
<div class="animate-in" x-data="returnForm()">
    <div class="page-header">
        <div>
            <h1>Buat Retur Penjualan</h1>
            <p>Pilih bukti pengiriman (Surat Jalan) yang barangnya dikembalikan oleh customer</p>
        </div>
        <a href="{{ route('sales.returns.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <form method="POST" action="{{ route('sales.returns.store') }}">
        @csrf

        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h3>Informasi Retur</h3></div>
            <div class="card-body">
                <div class="form-row form-row-3">
                    <div class="form-group">
                        <label class="form-label">Bukti Pengiriman (Surat Jalan) <span style="color:var(--danger);">*</span></label>
                        <select name="delivery_id" class="form-control" x-model="selectedDeliveryId" @change="onDeliveryChange()" required>
                            <option value="">-- Pilih Surat Jalan --</option>
                            @foreach($deliveries as $del)
                            <option value="{{ $del->id }}">{{ $del->delivery_number }} - Customer: {{ $del->salesOrder->customer->name ?? '-' }} (Gudang: {{ $del->warehouse->name ?? '-' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tanggal Retur <span style="color:var(--danger);">*</span></label>
                        <input type="date" name="return_date" value="{{ old('return_date', date('Y-m-d')) }}" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alasan Retur Utama</label>
                        <input type="text" name="reason" value="{{ old('reason') }}" class="form-control" placeholder="Contoh: Barang cacat / rusak transit">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan Tambahan</label>
                    <textarea name="notes" class="form-control" rows="1" placeholder="Catatan retur...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Alert Invoice Lunas Penuh --}}
        <div style="background:#fffbeb; border:1px solid #fde68a; color:#92400e; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:13.5px;" x-show="selectedDelivery && isFullyPaid">
            <div style="display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size:16px;"></i>
                <div>
                    <strong>Invoice ini sudah lunas dibayar penuh.</strong> Retur tidak dapat diproses otomatis karena akan menyebabkan kelebihan bayar. Silakan hubungi Finance untuk penanganan manual.
                </div>
            </div>
        </div>

        {{-- Items Table --}}
        <div class="card" style="margin-bottom:20px;" x-show="selectedDelivery">
            <div class="card-header">
                <h3>Pilih Barang yang Dikembalikan Customer</h3>
                <p style="font-size:12.5px; color:var(--text-secondary); margin:2px 0 0 0;">
                    Kuantitas retur dibatasi oleh sisa fisik terkirim dan sisa tagihan invoice yang belum dibayar untuk mencegah overpayment.
                </p>
            </div>
            <div class="table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th style="text-align:center; width:95px;">Qty Terkirim</th>
                            <th style="text-align:center; width:95px; color:var(--text-secondary);">Sudah Diretur</th>
                            <th style="text-align:center; width:100px; color:var(--primary);">Batas Fisik</th>
                            <th style="text-align:center; width:130px; color:#c2410c;">Batas Sisa Bayar</th>
                            <th style="text-align:center; width:100px; font-weight:700;">Maks Retur</th>
                            <th style="width:130px;">Qty Retur Ini</th>
                            <th style="width:170px;">Kondisi Barang</th>
                            <th>Alasan Khusus Item</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, idx) in items" :key="item.id">
                            <tr :style="item.max_allowed === 0 ? 'opacity: 0.65; background:#fef2f2;' : ''">
                                <td>
                                    <div style="font-weight:600;" x-text="item.product.name"></div>
                                    <div style="font-size:12px; color:var(--text-secondary);" x-text="item.product.sku"></div>
                                    <input type="hidden" :name="`items[${idx}][delivery_item_id]`" :value="item.id">
                                    <input type="hidden" :name="`items[${idx}][product_id]`" :value="item.product.id">
                                </td>
                                <td style="text-align:center; font-weight:600;" x-text="item.qty_delivered + ' ' + item.product.unit"></td>
                                <td style="text-align:center; color:var(--text-secondary);" x-text="item.qty_returned"></td>
                                <td style="text-align:center; font-weight:600; color:var(--primary);" x-text="item.qty_available_for_return"></td>
                                <td style="text-align:center; font-size:12px;">
                                    <template x-if="item.max_financial === null">
                                        <span class="badge badge-draft" style="font-size:11px;">Belum Di-invoice</span>
                                    </template>
                                    <template x-if="item.max_financial !== null">
                                        <span style="font-weight:600; color:#c2410c;" x-text="item.max_financial + ' ' + item.product.unit"></span>
                                    </template>
                                </td>
                                <td style="text-align:center; font-weight:700;">
                                    <template x-if="item.max_allowed > 0">
                                        <span style="color:#0284c7;" x-text="item.max_allowed + ' ' + item.product.unit"></span>
                                    </template>
                                    <template x-if="item.max_allowed === 0">
                                        <span style="color:#b91c1c; font-size:11px; font-weight:700; text-transform:uppercase; padding:2px 6px; background:#fee2e2; border-radius:4px;">
                                            Terkunci (0)
                                        </span>
                                    </template>
                                </td>
                                <td>
                                    <input type="number" 
                                           :name="`items[${idx}][qty]`" 
                                           x-model.number="item.qty_return" 
                                           class="form-control" 
                                           min="0" 
                                           :max="item.max_allowed"
                                           :disabled="item.max_allowed === 0 || isFullyPaid"
                                           placeholder="0">
                                </td>
                                <td>
                                    <select :name="`items[${idx}][condition]`" class="form-control" :disabled="item.max_allowed === 0 || isFullyPaid">
                                        <option value="baik">Bagus (Bisa Dijual Lagi)</option>
                                        <option value="rusak">Rusak / Cacat</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" :name="`items[${idx}][reason]`" class="form-control" placeholder="Detail alasan item" :disabled="item.max_allowed === 0 || isFullyPaid">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:12px;" x-show="selectedDelivery">
            <a href="{{ route('sales.returns.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary" :disabled="!hasAnyReturnQty || isFullyPaid">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Draft Retur
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const deliveries = @json($deliveries->keyBy('id'));
const initialDeliveryId = "{{ $selectedDeliveryId ?? '' }}";

function returnForm() {
    return {
        selectedDeliveryId: initialDeliveryId,
        selectedDelivery: null,
        invoice: null,
        isFullyPaid: false,
        items: [],

        init() {
            if (this.selectedDeliveryId) {
                this.onDeliveryChange();
            }
        },

        onDeliveryChange() {
            if (!this.selectedDeliveryId || !deliveries[this.selectedDeliveryId]) {
                this.selectedDelivery = null;
                this.invoice = null;
                this.isFullyPaid = false;
                this.items = [];
                return;
            }

            this.selectedDelivery = deliveries[this.selectedDeliveryId];
            this.invoice = this.selectedDelivery.sales_invoice || null;

            let sisaBelumBayar = null;
            if (this.invoice) {
                const totalPaid = (this.invoice.payments || []).reduce((acc, p) => acc + (Number(p.amount) || 0), 0);
                const effectiveTotal = Math.max(0, (Number(this.invoice.total_amount) || 0) - (Number(this.invoice.total_reversed_amount) || 0));
                sisaBelumBayar = Math.max(0, effectiveTotal - totalPaid);
                this.isFullyPaid = sisaBelumBayar <= 0.01;
            } else {
                this.isFullyPaid = false;
            }

            this.items = this.selectedDelivery.items.map(item => {
                const product = item.sales_order_item ? item.sales_order_item.product : { name: 'Produk', sku: '-', unit: 'pcs' };
                const qtyDelivered = Number(item.qty_delivered) || 0;
                const qtyReturned = Number(item.qty_returned) || 0;
                const qtyAvailablePhysical = Number(item.qty_available_for_return) || 0;

                let maxFinancial = null;
                let maxAllowed = qtyAvailablePhysical;

                if (this.invoice) {
                    if (this.isFullyPaid) {
                        maxFinancial = 0;
                        maxAllowed = 0;
                    } else {
                        const invItem = (this.invoice.items || []).find(i => i.delivery_item_id == item.id) || item.sales_invoice_item;
                        if (invItem && Number(invItem.qty_invoiced) > 0) {
                            const effectiveRate = ((Number(invItem.subtotal) || 0) + (Number(invItem.tax_amount) || 0)) / Number(invItem.qty_invoiced);
                            if (effectiveRate > 0) {
                                maxFinancial = Math.floor(sisaBelumBayar / effectiveRate);
                                maxAllowed = Math.min(qtyAvailablePhysical, maxFinancial);
                            }
                        }
                    }
                }

                return {
                    id: item.id,
                    product: product,
                    qty_delivered: qtyDelivered,
                    qty_returned: qtyReturned,
                    qty_available_for_return: qtyAvailablePhysical,
                    max_financial: maxFinancial,
                    max_allowed: maxAllowed,
                    qty_return: 0,
                };
            });
        },

        get hasAnyReturnQty() {
            return this.items.some(it => (Number(it.qty_return) || 0) > 0);
        }
    }
}
</script>
@endpush
