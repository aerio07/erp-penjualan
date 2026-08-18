@extends('layouts.app')
@section('title', 'Buat Retur Pembelian')
@section('page-title', 'Buat Retur Pembelian')

@section('content')
<div class="animate-in" x-data="returnForm()">
    <div class="page-header">
        <div>
            <h1>Buat Retur Pembelian</h1>
            <p>Pilih bukti Penerimaan Barang (GRN) yang barangnya akan dikembalikan</p>
        </div>
        <a href="{{ route('purchase.returns.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <form method="POST" action="{{ route('purchase.returns.store') }}">
        @csrf

        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h3>Informasi Retur</h3></div>
            <div class="card-body">
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Bukti Penerimaan (GRN) <span style="color:var(--danger);">*</span></label>
                        <select name="goods_receipt_id" class="form-control" x-model="selectedGrnId" @change="onGrnChange()" required>
                            <option value="">-- Pilih GRN --</option>
                            @foreach($receipts as $grn)
                            <option value="{{ $grn->id }}">{{ $grn->receipt_number }} - Supplier: {{ $grn->purchaseOrder->supplier->name ?? '-' }} (Gudang: {{ $grn->warehouse_names }})</option>
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
                    <input type="text" name="reason" value="{{ old('reason') }}" class="form-control" placeholder="Contoh: Barang cacat pabrik / Kemasan rusak">
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan Tambahan</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Catatan pengiriman retur...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Items Table --}}
        <div class="card" style="margin-bottom:20px;" x-show="selectedGrn">
            <div class="card-header"><h3>Pilih Barang yang Dikembalikan</h3></div>
            <div class="table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th style="text-align:center; width:150px;">Jenis Barang</th>
                            <th style="text-align:center; width:120px;">Tersedia Retur</th>
                            <th style="width:140px;">Qty Retur</th>
                            <th style="width:160px;">Harga Beli Satuan</th>
                            <th>Alasan Khusus Item</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, idx) in items" :key="item.row_key">
                            <tr>
                                <td>
                                    <div style="font-weight:600;" x-text="item.product.name"></div>
                                    <div style="font-size:12px; color:var(--text-secondary);" x-text="item.product.sku"></div>
                                    <input type="hidden" :name="`items[${idx}][goods_receipt_item_id]`" :value="item.id">
                                    <input type="hidden" :name="`items[${idx}][product_id]`" :value="item.product.id">
                                    <input type="hidden" :name="`items[${idx}][source_type]`" :value="item.source_type">
                                </td>
                                <td style="text-align:center;">
                                    <span class="badge" :class="item.source_type === 'accepted' ? 'badge-done' : 'badge-cancelled'" x-text="item.source_label"></span>
                                </td>
                                <td style="text-align:center; font-weight:600;" x-text="item.qty_available + ' ' + item.product.unit"></td>
                                <td>
                                    <input type="number" :name="`items[${idx}][qty]`" x-model.number="item.qty_return" class="form-control" min="0" :max="item.qty_available">
                                </td>
                                <td>
                                    <input type="number" :name="`items[${idx}][unit_cost]`" x-model.number="item.unit_cost" class="form-control" min="0" readonly>
                                </td>
                                <td>
                                    <input type="text" :name="`items[${idx}][reason]`" class="form-control" placeholder="Cacat / Expired / Rusak">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:12px;" x-show="selectedGrn">
            <a href="{{ route('purchase.returns.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Draft Retur
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const receipts = @json($receipts->keyBy('id'));
const initialGrnId = "{{ $selectedGrnId ?? '' }}";

function returnForm() {
    return {
        selectedGrnId: initialGrnId,
        selectedGrn: null,
        items: [],

        init() {
            if (this.selectedGrnId) {
                this.onGrnChange();
            }
        },

        onGrnChange() {
            if (!this.selectedGrnId || !receipts[this.selectedGrnId]) {
                this.selectedGrn = null;
                this.items = [];
                return;
            }

            this.selectedGrn = receipts[this.selectedGrnId];
            this.items = this.selectedGrn.items.flatMap(item => {
                const product = item.purchase_order_item ? item.purchase_order_item.product : { name: 'Produk', sku: '-', unit: 'pcs' };
                const unitCost = item.unit_cost || (item.purchase_order_item ? item.purchase_order_item.unit_price : 0);
                const rows = [];

                if (item.qty_available_for_return_accepted > 0) {
                    rows.push({
                        row_key: `${item.id}-accepted`,
                        id: item.id,
                        product: product,
                        source_type: 'accepted',
                        source_label: 'Diterima Stok',
                        qty_available: item.qty_available_for_return_accepted,
                        qty_return: 0,
                        unit_cost: unitCost,
                    });
                }

                if (item.qty_available_for_return_rejected > 0) {
                    rows.push({
                        row_key: `${item.id}-rejected`,
                        id: item.id,
                        product: product,
                        source_type: 'rejected',
                        source_label: 'Rusak / Reject',
                        qty_available: item.qty_available_for_return_rejected,
                        qty_return: item.qty_available_for_return_rejected,
                        unit_cost: unitCost,
                    });
                }

                return rows;
            });
        }
    }
}
</script>
@endpush
