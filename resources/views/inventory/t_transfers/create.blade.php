@extends('layouts.app')
@section('title', 'Buat Transfer Gudang')
@section('page-title', 'Buat Draft Transfer Gudang')

@section('content')
<div class="animate-in" x-data="trfForm()">
    <x-page-header 
        title="Buat Draft Transfer Gudang" 
        subtitle="Form pemindahan persediaan antar gudang. Stok gudang asal akan dikurangi setelah pengiriman/posting transfer."
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

    <form method="POST" action="{{ route('inventory.transfers.store') }}" @submit="return validateForm($event)">
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
                    <textarea name="notes" class="form-control" rows="2" placeholder="Catatan atau alasan transfer stok...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Items --}}
        <div class="card" style="margin-bottom:20px; overflow: visible;">
            <div class="card-header">
                <div>
                    <h3 style="margin-bottom: 2px;">Daftar Barang Ditransfer</h3>
                    <p style="font-size: 12px; color: var(--text-secondary); margin: 0;">Pilih item dan tentukan jumlah yang akan dipindahkan.</p>
                </div>
                <button type="button" class="btn btn-primary btn-sm" @click="addRow()">
                    <i class="fa-solid fa-plus"></i> Tambah Item
                </button>
            </div>
            <div class="table-responsive" style="min-height: 250px;">
                <table class="erp-table" style="min-width: 800px;">
                    <thead>
                        <tr>
                            <th style="min-width: 320px;">Produk (Kode & Nama)</th>
                            <th style="width: 170px; text-align: right;">Stok Tersedia</th>
                            <th style="width: 180px;">Jumlah Transfer (Qty)</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, idx) in rows" :key="idx">
                            <tr>
                                <td>
                                    <div style="position: relative; width: 100%;" x-data="{ openProd: false, prodSearch: '' }" @click.outside="openProd = false" @keydown.escape.window="openProd = false">
                                        <input type="hidden" :name="`items[${idx}][product_id]`" :value="row.product_id" required>
                                        
                                        <div 
                                            @click="openProd = !openProd; if(openProd) $nextTick(() => $refs.prodSearchInput.focus())"
                                            class="form-control searchable-select-trigger"
                                            style="display: flex !important; flex-wrap: nowrap !important; align-items: center !important; justify-content: space-between !important; cursor: pointer; min-height: 38px !important; height: 38px !important; max-height: 38px !important; background: #fff; padding: 0 0.75rem !important; border-radius: 0.5rem; box-sizing: border-box !important;"
                                        >
                                            <template x-if="products[row.product_id]">
                                                <div style="display: flex; align-items: center; gap: 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1 1 auto; min-width: 0;">
                                                    <span class="badge" style="background: #eff6ff; color: #1d4ed8; font-weight: 700; font-size: 11px;" x-text="products[row.product_id].sku"></span>
                                                    <span style="font-weight: 600; color: var(--text-primary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" x-text="products[row.product_id].name"></span>
                                                </div>
                                            </template>
                                            <template x-if="!products[row.product_id]">
                                                <span class="text-slate-400" style="white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; padding-right: 6px; flex: 1 1 auto; min-width: 0;"><i class="fa-solid fa-magnifying-glass" style="margin-right: 6px;"></i> Cari & Pilih Produk...</span>
                                            </template>
                                            <i class="fa-solid fa-chevron-down text-slate-400" :style="openProd ? 'transform: rotate(180deg);' : ''" style="font-size: 11px; transition: transform 0.2s; flex-shrink: 0; margin-left: auto;"></i>
                                        </div>

                                        <div 
                                            x-show="openProd" 
                                            x-transition:enter="transition ease-out duration-150"
                                            x-transition:enter-start="opacity-0 translate-y-1"
                                            x-transition:enter-end="opacity-100 translate-y-0"
                                            x-transition:leave="transition ease-in duration-100"
                                            x-transition:leave-start="opacity-100 translate-y-0"
                                            x-transition:leave-end="opacity-0 translate-y-1"
                                            style="position: absolute; top: calc(100% + 4px); left: 0; width: 100%; min-width: 360px; z-index: 1060; background: #fff; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); border: 1px solid #e2e8f0; max-height: 280px; display: flex; flex-direction: column; overflow: hidden;"
                                            x-cloak
                                        >
                                            <div style="padding: 6px 8px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; position: relative;">
                                                <div style="position: relative; display: flex; align-items: center;">
                                                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 12px; pointer-events: none; z-index: 2;"></i>
                                                    <input 
                                                        type="text" 
                                                        x-ref="prodSearchInput"
                                                        x-model="prodSearch" 
                                                        placeholder="Ketik Kode Item atau Nama Produk..." 
                                                        class="form-control searchable-select-search-input" 
                                                        style="width: 100% !important; padding-left: 30px !important; padding-right: 24px !important; font-size: 12.5px !important; height: 34px !important; border-radius: 6px !important;"
                                                        @click.stop
                                                    >
                                                    <template x-if="prodSearch">
                                                        <button 
                                                            type="button" 
                                                            @click="prodSearch = ''; $refs.prodSearchInput.focus()"
                                                            style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 2px 4px; font-size: 11px; z-index: 2;"
                                                        >
                                                            <i class="fa-solid fa-circle-xmark"></i>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                            <div style="overflow-y: auto; max-height: 220px;">
                                                <template x-for="p in getFilteredProducts(prodSearch)" :key="p.id">
                                                    <div 
                                                        @click="row.product_id = p.id; openProd = false; prodSearch = '';"
                                                        :class="{ 'bg-blue-50 font-semibold text-blue-700': String(row.product_id) === String(p.id) }"
                                                        style="padding: 9px 12px; cursor: pointer; font-size: 13px; border-bottom: 1px solid #f8fafc; display: flex; justify-content: space-between; align-items: center;"
                                                        onmouseover="this.style.backgroundColor='#f1f5f9'"
                                                        onmouseout="if(String(row.product_id) !== String(p.id)) this.style.backgroundColor='transparent'"
                                                    >
                                                        <div style="overflow: hidden; text-overflow: ellipsis; padding-right: 8px;">
                                                            <div style="font-weight: 600; color: #0f172a;" x-text="p.name"></div>
                                                            <div style="font-size: 11px; color: #64748b;">
                                                                Kode: <span class="font-mono text-blue-600" x-text="p.sku"></span>
                                                            </div>
                                                        </div>
                                                        <div style="text-align: right; white-space: nowrap;">
                                                            <span class="badge" :style="getAvailableStock(p.id) > 0 ? 'background:#dcfce7; color:#15803d;' : 'background:#fee2e2; color:#b91c1c;'">
                                                                Stok: <strong x-text="getAvailableStock(p.id)"></strong> <span x-text="p.unit || 'pcs'"></span>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </template>
                                                <div x-show="getFilteredProducts(prodSearch).length === 0" style="padding: 16px; text-align: center; color: #94a3b8; font-size: 12.5px;">
                                                    Tidak ada produk yang cocok
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: right; vertical-align: middle;">
                                    <template x-if="row.product_id">
                                        <div>
                                            <span 
                                                class="badge" 
                                                :style="getAvailableStock(row.product_id) > 0 ? 'background:#ecfdf5; color:#047857; font-size:13px; font-weight:700;' : 'background:#fef2f2; color:#b91c1c; font-size:13px; font-weight:700;'"
                                            >
                                                <span x-text="getAvailableStock(row.product_id)"></span> <span style="font-size: 11px; font-weight: 500;" x-text="products[row.product_id]?.unit || 'pcs'"></span>
                                            </span>
                                            <div x-show="!fromWhId" style="font-size: 10px; color: #94a3b8; margin-top: 2px;">(Pilih gudang asal)</div>
                                        </div>
                                    </template>
                                    <template x-if="!row.product_id">
                                        <span style="color: #94a3b8;">-</span>
                                    </template>
                                </td>
                                <td>
                                    <div style="position: relative;">
                                        <input 
                                            type="number" 
                                            :name="`items[${idx}][qty]`" 
                                            x-model.number="row.qty" 
                                            class="form-control" 
                                            min="1" 
                                            :max="fromWhId && row.product_id ? getAvailableStock(row.product_id) : null"
                                            required
                                            :style="fromWhId && row.product_id && row.qty > getAvailableStock(row.product_id) ? 'border-color: #ef4444; background: #fff5f5;' : ''"
                                        >
                                        <template x-if="fromWhId && row.product_id && row.qty > getAvailableStock(row.product_id)">
                                            <div style="font-size: 11px; color: #dc2626; margin-top: 3px; font-weight: 500;">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Melebihi stok tersedia!
                                            </div>
                                        </template>
                                    </div>
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <button type="button" class="btn btn-danger btn-sm btn-icon" @click="removeRow(idx)" x-show="rows.length > 1" title="Hapus Baris">
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
const stockMatrix = @json($stockMatrix ?? []);
const oldItems = @json(old('items', [['product_id' => '', 'qty' => 1]]));

function trfForm() {
    return {
        fromWhId: '{{ old('from_warehouse_id', '') }}',
        toWhId: '{{ old('to_warehouse_id', '') }}',
        rows: oldItems.length ? oldItems.map(i => ({ product_id: i.product_id || '', qty: i.qty ? Number(i.qty) : 1 })) : [{ product_id: '', qty: 1 }],

        addRow() { 
            this.rows.push({ product_id: '', qty: 1 }); 
        },
        removeRow(idx) { 
            if (this.rows.length > 1) {
                this.rows.splice(idx, 1); 
            }
        },
        getAvailableStock(productId) {
            if (!productId || !this.fromWhId) return 0;
            if (stockMatrix[productId] && stockMatrix[productId][this.fromWhId] !== undefined) {
                return Number(stockMatrix[productId][this.fromWhId]);
            }
            return 0;
        },
        getFilteredProducts(search) {
            const query = (search || '').toLowerCase().trim();
            const list = Object.values(products);
            if (!query) return list;
            return list.filter(p => {
                return (p.name && p.name.toLowerCase().includes(query)) || 
                       (p.sku && p.sku.toLowerCase().includes(query));
            });
        },
        validateForm(e) {
            if (!this.fromWhId) {
                alert('Silakan pilih Gudang Asal terlebih dahulu.');
                e.preventDefault();
                return false;
            }
            if (!this.toWhId) {
                alert('Silakan pilih Gudang Tujuan terlebih dahulu.');
                e.preventDefault();
                return false;
            }
            if (this.fromWhId === this.toWhId) {
                alert('Gudang Asal dan Gudang Tujuan tidak boleh sama.');
                e.preventDefault();
                return false;
            }
            const validRows = this.rows.filter(r => r.product_id && r.qty > 0);
            if (validRows.length === 0) {
                alert('Minimal 1 produk harus dipilih dengan jumlah transfer minimal 1.');
                e.preventDefault();
                return false;
            }
            for (let r of validRows) {
                const avail = this.getAvailableStock(r.product_id);
                if (r.qty > avail) {
                    const p = products[r.product_id];
                    alert(`Jumlah transfer produk "${p ? p.name : ''}" (${r.qty}) melebihi stok tersedia (${avail}) di gudang asal.`);
                    e.preventDefault();
                    return false;
                }
            }
            return true;
        }
    }
}
</script>
@endpush
