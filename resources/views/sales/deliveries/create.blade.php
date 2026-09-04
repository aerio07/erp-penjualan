@extends('layouts.app')
@section('title', 'Buat Surat Jalan')
@section('page-title', 'Buat Surat Jalan / Pengiriman')

@section('content')
<div class="animate-in" x-data="deliveryForm()">
    <div class="page-header">
        <div>
            <h1>Buat Surat Jalan</h1>
            <p>Pilih Sales Order yang barangnya akan dikirim dari gudang ke customer</p>
        </div>
        <a href="{{ route('sales.deliveries.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <form method="POST" action="{{ route('sales.deliveries.store') }}">
        @csrf

        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h3>Informasi Pengiriman</h3></div>
            <div class="card-body">
                <div class="form-row form-row-3">
                    <div class="form-group">
                        <label class="form-label">Sales Order <span style="color:var(--danger);">*</span></label>
                        <select name="sales_order_id" class="form-control" x-model="selectedSoId" @change="onSoChange(true)" required>
                            <option value="">-- Pilih Sales Order --</option>
                            @foreach($confirmedSos as $so)
                            <option value="{{ $so->id }}" {{ old('sales_order_id', $selectedSoId) == $so->id ? 'selected' : '' }}>
                                {{ $so->so_number }} - {{ $so->customer->name }} ({{ $so->order_date->format('d/m/Y') }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Gudang Asal Barang <span style="color:var(--danger);">*</span></label>
                        <select name="warehouse_id" class="form-control" required>
                            <option value="">-- Pilih Gudang --</option>
                            @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ old('warehouse_id') == $wh->id ? 'selected' : '' }}>
                                {{ $wh->name }} ({{ $wh->code }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tanggal Pengiriman <span style="color:var(--danger);">*</span></label>
                        <input type="date" name="delivery_date" value="{{ old('delivery_date', date('Y-m-d')) }}" class="form-control" required>
                    </div>
                </div>

                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Nama Penerima</label>
                        <input type="text" name="recipient_name" x-model="recipientName" class="form-control" placeholder="Nama penerima di lokasi">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alamat Tujuan Pengiriman</label>
                        <input type="text" name="shipping_address" x-model="shippingAddress" class="form-control" placeholder="Alamat lengkap pengiriman">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan Pengiriman / Nama Supir & No. Plat</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Nama supir, no. mobil, no. resi kurir...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Items Table --}}
        <div class="card" style="margin-bottom:20px;" x-show="selectedSo">
            <div class="card-header"><h3>Item Barang Dikirim</h3></div>
            <div class="table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th style="text-align:center; width:120px;">Qty Dipesan</th>
                            <th style="text-align:center; width:120px;">Sudah Dikirim</th>
                            <th style="text-align:center; width:120px;">Sisa SO</th>
                            <th style="width:180px;">Qty Kirim Saat Ini</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, idx) in items" :key="item.id">
                            <tr>
                                <td>
                                    <div style="font-weight:600;" x-text="item.product.name"></div>
                                    <div style="font-size:12px; color:var(--text-secondary);" x-text="item.product.sku"></div>
                                    <input type="hidden" :name="`items[${idx}][sales_order_item_id]`" :value="item.id">
                                </td>
                                <td style="text-align:center; font-weight:600;" x-text="item.qty_ordered + ' ' + item.product.unit"></td>
                                <td style="text-align:center;" x-text="item.qty_delivered"></td>
                                <td style="text-align:center;">
                                    <span class="badge badge-pending" x-text="item.qty_remaining"></span>
                                </td>
                                <td>
                                    <input type="number" 
                                        :name="`items[${idx}][qty_delivered]`" 
                                        x-model.number="item.current_delivered" 
                                        class="form-control" 
                                        :style="item.current_delivered > item.qty_remaining ? 'border-color:var(--danger); background:#fff5f5;' : ''"
                                        min="0" 
                                        :max="item.qty_remaining" 
                                        required>
                                    <div x-show="item.current_delivered > item.qty_remaining" style="color:var(--danger); font-size:11.5px; margin-top:4px; font-weight:500;">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Melebihi sisa SO (<span x-text="item.qty_remaining"></span>)
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:12px;" x-show="selectedSo">
            <a href="{{ route('sales.deliveries.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-truck-fast"></i> Cetak Surat Jalan & Potong Stok
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const confirmedSos = @json($confirmedSos->keyBy('id'));
const initialSoId = "{{ old('sales_order_id', $selectedSoId ?? '') }}";
const oldRecipientName = @json(old('recipient_name', ''));
const oldShippingAddress = @json(old('shipping_address', ''));
const oldItemsData = @json(old('items', []));

const oldItemsMap = {};
if (Array.isArray(oldItemsData)) {
    oldItemsData.forEach(item => {
        if (item && item.sales_order_item_id !== undefined) {
            oldItemsMap[item.sales_order_item_id] = item.qty_delivered;
        }
    });
}

function deliveryForm() {
    return {
        selectedSoId: initialSoId,
        selectedSo: null,
        recipientName: oldRecipientName,
        shippingAddress: oldShippingAddress,
        items: [],
        isFirstLoad: true,

        init() {
            if (this.selectedSoId) {
                this.onSoChange(false);
            }
            this.isFirstLoad = false;
        },

        onSoChange(isManualUserChange = false) {
            if (!this.selectedSoId || !confirmedSos[this.selectedSoId]) {
                this.selectedSo = null;
                this.items = [];
                if (isManualUserChange) {
                    this.recipientName = '';
                    this.shippingAddress = '';
                }
                return;
            }

            this.selectedSo = confirmedSos[this.selectedSoId];

            if (isManualUserChange || (!this.recipientName && !this.shippingAddress)) {
                this.recipientName = this.selectedSo.customer ? this.selectedSo.customer.name : '';
                this.shippingAddress = this.selectedSo.customer ? this.selectedSo.customer.address : '';
            }

            this.items = this.selectedSo.items.map(item => {
                const remaining = Math.max(0, item.qty_ordered - item.qty_delivered);
                let currentVal = remaining;

                if (this.isFirstLoad && oldItemsMap[item.id] !== undefined) {
                    currentVal = oldItemsMap[item.id];
                }

                return {
                    id: item.id,
                    product: item.product,
                    qty_ordered: item.qty_ordered,
                    qty_delivered: item.qty_delivered,
                    qty_remaining: remaining,
                    current_delivered: currentVal,
                };
            });
        }
    }
}
</script>
@endpush
