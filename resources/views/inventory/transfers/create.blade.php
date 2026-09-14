@extends('layouts.app')
@section('title', 'Buat Transfer Gudang')
@section('page-title', 'Buat Draft Transfer Gudang')

@section('content')
<div class="animate-in" x-data="trfForm()">
    <x-page-header 
        title="Buat Draft Transfer Gudang" 
        subtitle="Form pemindahan persediaan antar gudang. Stok gudang asal akan dikurangi setelah pengiriman dikonfirmasi (Ship)."
        :back-url="route('inventory.transfers.index')"
    />

    @if($errors->any())
    <div class="alert alert-danger mb-4">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div>
            <strong>Terjadi kesalahan input:</strong>
            <ul style="margin-top:4px; padding-left:18px;">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('inventory.transfers.store') }}">
        @csrf

        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h3>Lokasi & Tanggal</h3></div>
            <div class="card-body">
                <div class="form-row form-row-3">
                    <div class="form-group">
                        <label class="form-label">Gudang Asal <span style="color:var(--danger);">*</span></label>
                        <x-searchable-select 
                            name="from_warehouse_id" 
                            :options="$warehouses" 
                            :value="old('from_warehouse_id')" 
                            placeholder="-- Pilih Gudang Asal --" 
                            search-placeholder="Cari nama atau kode gudang..." 
                            on-select="whId => { fromWhId = whId; }" 
                            required 
                        />
                    </div>

                    <div class="form-group">
                        <label class="form-label">Gudang Tujuan <span style="color:var(--danger);">*</span></label>
                        <x-searchable-select 
                            name="to_warehouse_id" 
                            :options="$warehouses" 
                            :value="old('to_warehouse_id')" 
                            placeholder="-- Pilih Gudang Tujuan --" 
                            search-placeholder="Cari nama atau kode gudang..." 
                            on-select="whId => { toWhId = whId; }" 
                            required 
                        />
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tanggal Dokumen <span style="color:var(--danger);">*</span></label>
                        <input type="date" name="transfer_date" value="{{ old('transfer_date', date('Y-m-d')) }}" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Catatan pengiriman transfer...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Items --}}
        <div class="card" style="margin-bottom:20px; overflow: visible;">
            <div class="card-header">
                <h3>Daftar Barang Ditransfer</h3>
                <button type="button" class="btn btn-primary btn-sm" @click="addRow()">
                    <i class="fa-solid fa-plus"></i> Tambah Item
                </button>
            </div>
            <div class="table-responsive" style="overflow: visible;">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th style="min-width: 260px;">Produk</th>
                            <th style="width:180px;">Jumlah Transfer (Qty)</th>
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
                                                    @click="row.product_id = p.id; openProd = false; prodSearch = '';"
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
                                    <input type="number" :name="`items[${idx}][qty]`" x-model.number="row.qty" class="form-control" min="1" required>
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
            :back-url="route('inventory.transfers.index')" 
            submit-label="Simpan Draft Transfer" 
        />
    </form>
</div>
@endsection

@push('scripts')
<script>
const products = @json($products->keyBy('id'));

function trfForm() {
    return {
        fromWhId: '{{ old('from_warehouse_id', '') }}',
        toWhId: '{{ old('to_warehouse_id', '') }}',
        rows: [{ product_id: '', qty: 1 }],

        addRow() { this.rows.push({ product_id: '', qty: 1 }); },
        removeRow(idx) { this.rows.splice(idx, 1); }
    }
}
</script>
@endpush
