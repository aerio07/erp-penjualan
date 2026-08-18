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
                <div class="form-row form-row-2">
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
                </div>

                <div class="form-group">
                    <label class="form-label">Alasan Retur Utama</label>
                    <input type="text" name="reason" value="{{ old('reason') }}" class="form-control" placeholder="Contoh: Barang tidak sesuai pesanan / rusak saat transit">
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan Tambahan</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Catatan retur...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Items Table --}}
        <div class="card" style="margin-bottom:20px;" x-show="selectedDelivery">
            <div class="card-header"><h3>Pilih Barang yang Dikembalikan Customer</h3></div>
            <div class="table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th style="text-align:center; width:100px;">Qty Dikirim</th>
                            <th style="text-align:center; width:110px; color:var(--text-secondary);">Sudah Diretur Lalu</th>
                            <th style="text-align:center; width:110px; color:var(--primary);">Bisa Diretur</th>
                            <th style="width:130px;">Qty Retur Ini</th>
                            <th style="width:180px;">Kondisi Barang</th>
                            <th>Alasan Khusus Item</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, idx) in items" :key="item.id">
                            <tr>
                                <td>
                                    <div style="font-weight:600;" x-text="item.product.name"></div>
                                    <div style="font-size:12px; color:var(--text-secondary);" x-text="item.product.sku"></div>
                                    <input type="hidden" :name="`items[${idx}][delivery_item_id]`" :value="item.id">
                                    <input type="hidden" :name="`items[${idx}][product_id]`" :value="item.product.id">
                                </td>
                                <td style="text-align:center; font-weight:600;" x-text="item.qty_delivered + ' ' + item.product.unit"></td>
                                <td style="text-align:center; color:var(--text-secondary);" x-text="item.qty_returned"></td>
                                <td style="text-align:center; font-weight:700; color:var(--primary);" x-text="item.qty_available_for_return"></td>
                                <td>
                                    <input type="number" 
                                           :name="`items[${idx}][qty]`" 
                                           x-model.number="item.qty_return" 
                                           class="form-control" 
                                           min="0" 
                                           :max="item.qty_available_for_return"
                                           placeholder="0">
                                </td>
                                <td>
                                    <select :name="`items[${idx}][condition]`" class="form-control">
                                        <option value="baik">Bagus (Bisa Dijual Lagi)</option>
                                        <option value="rusak">Rusak / Cacat</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" :name="`items[${idx}][reason]`" class="form-control" placeholder="Detail alasan item">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:12px;" x-show="selectedDelivery">
            <a href="{{ route('sales.returns.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary" :disabled="!hasAnyReturnQty">
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
        items: [],

        init() {
            if (this.selectedDeliveryId) {
                this.onDeliveryChange();
            }
        },

        onDeliveryChange() {
            if (!this.selectedDeliveryId || !deliveries[this.selectedDeliveryId]) {
                this.selectedDelivery = null;
                this.items = [];
                return;
            }

            this.selectedDelivery = deliveries[this.selectedDeliveryId];
            this.items = this.selectedDelivery.items.map(item => {
                const product = item.sales_order_item ? item.sales_order_item.product : { name: 'Produk', sku: '-', unit: 'pcs' };
                return {
                    id: item.id,
                    product: product,
                    qty_delivered: Number(item.qty_delivered) || 0,
                    qty_returned: Number(item.qty_returned) || 0,
                    qty_available_for_return: Number(item.qty_available_for_return) || 0,
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
