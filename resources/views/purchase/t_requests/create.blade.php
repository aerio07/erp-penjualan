@extends('layouts.app')
@section('title', 'Buat Purchase Request')
@section('page-title', 'Buat Purchase Request')

@section('content')
<div class="animate-in" x-data="purchaseRequestForm()">
    <x-page-header 
        title="Buat Purchase Request (PR)" 
        subtitle="Form pengajuan pengadaan barang baru dari divisi internal"
        :back-url="route('purchase.requests.index')"
    />

    <form method="POST" action="{{ route('purchase.requests.store') }}" id="prForm" @submit="validateForm($event)">
        @csrf

        {{-- Card 1: Header Informasi PR --}}
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <h3><i class="fa-solid fa-file-lines text-primary"></i> Informasi Pengajuan</h3>
            </div>
            <div class="card-body">
                <div class="form-row form-row-4">
                    <div class="form-group">
                        <label class="form-label">No. PR</label>
                        <input type="text" class="form-control" value="[ Otomatis oleh Sistem ]" readonly style="background:var(--bg-muted, #f1f5f9); color:var(--text-secondary);">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal PR <span style="color:var(--danger);">*</span></label>
                        <input type="date" name="request_date" value="{{ old('request_date', date('Y-m-d')) }}" class="form-control {{ $errors->has('request_date') ? 'is-invalid' : '' }}" required>
                        @error('request_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date Needed (Tgl Dibutuhkan) <span style="color:var(--danger);">*</span></label>
                        <input type="date" name="required_date" value="{{ old('required_date') }}" class="form-control {{ $errors->has('required_date') ? 'is-invalid' : '' }}" required>
                        @error('required_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Expired Date (Batas Waktu)</label>
                        <input type="date" name="expired_date" value="{{ old('expired_date') }}" class="form-control">
                    </div>
                </div>

                <div class="form-row form-row-2" style="margin-top:14px;">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-building-user text-primary"></i> Divisi Peminta 
                            @if(auth()->user()->role === 'admin')
                                <span style="color:var(--danger);">*</span>
                                <span style="font-size:11px; color:var(--primary); font-weight:normal;">(Admin: Bebas Pilih Divisi)</span>
                            @else
                                <span style="font-size:11px; color:var(--text-secondary); font-weight:normal;">(Otomatis dari Akun: {{ auth()->user()->division_name }})</span>
                            @endif
                        </label>
                        @if(auth()->user()->role === 'admin')
                            <select name="division" class="form-control {{ $errors->has('division') ? 'is-invalid' : '' }}" required>
                                @foreach($divisions as $div)
                                <option value="{{ $div }}" {{ old('division', auth()->user()->division_name) === $div ? 'selected' : '' }}>{{ $div }}</option>
                                @endforeach
                            </select>
                            @error('division')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @else
                            <input type="text" name="division" value="{{ auth()->user()->division_name }}" class="form-control" readonly style="background:var(--bg-muted, #f8fafc); font-weight:600; color:var(--text-primary); cursor:not-allowed;">
                        @endif
                    </div>
                </div>

                <div class="form-group" style="margin-top:14px;">
                    <label class="form-label"><i class="fa-solid fa-note-sticky text-primary"></i> Catatan / Keperluan Pengadaan</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Jelaskan alasan atau keperluan pengadaan barang ini...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Card 2: Detail Barang (Tabel Dinamis) --}}
        <div class="card" style="margin-bottom:24px; overflow: visible;">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h3><i class="fa-solid fa-boxes-stacked text-primary"></i> Daftar Barang yang Diminta</h3>
                    <p style="font-size:12.5px; color:var(--text-secondary); margin:2px 0 0 0;">
                        Tentukan produk, jumlah unit, dan catatan kebutuhan untuk setiap item.
                    </p>
                </div>
                <button type="button" @click="addItem()" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus"></i> Tambah Barang
                </button>
            </div>

            <div class="table-responsive" style="min-height: 280px; width: 100%;">
                <table class="erp-table" style="table-layout: fixed; width: 100%; min-width: 950px;">
                    <thead>
                        <tr>
                            <th style="width:4%; text-align:center;">No</th>
                            <th style="width:11%;">Kode (SKU)</th>
                            <th style="width:31%;">Nama Barang <span style="color:var(--danger);">*</span></th>
                            <th style="width:7%; text-align:center;">On Hand</th>
                            <th style="width:7%; text-align:center;">Min Stock</th>
                            <th style="width:9%; text-align:center;">Qty Diminta <span style="color:var(--danger);">*</span></th>
                            <th style="width:7%; text-align:center;">Satuan</th>
                            <th style="width:18%;">Catatan Barang</th>
                            <th style="width:6%; text-align:center; padding-left:2px !important; padding-right:2px !important;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="index">
                            <tr>
                                <td style="text-align:center; color:var(--text-secondary);" x-text="index + 1"></td>
                                <td style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <span style="font-family:monospace; font-weight:600;" x-text="item.sku || '-'"></span>
                                </td>
                                <td style="vertical-align: top !important; position: relative; max-width: 0; overflow: visible;">
                                    <div style="position: relative; width: 100%;" x-data="{ openProd: false, prodSearch: '' }" @click.outside="openProd = false" @keydown.escape.window="openProd = false">
                                        <input type="hidden" :name="'items[' + index + '][product_id]'" :value="item.product_id" required>
                                        
                                        <div 
                                            @click="openProd = !openProd; if(openProd) $nextTick(() => $refs.prodSearchInput.focus())"
                                            class="form-control searchable-select-trigger"
                                            style="display: flex !important; flex-wrap: nowrap !important; align-items: center !important; justify-content: space-between !important; cursor: pointer; min-height: 38px !important; height: 38px !important; max-height: 38px !important; background: #fff; padding: 0 0.75rem !important; border-radius: 0.5rem; box-sizing: border-box !important;"
                                        >
                                            <span x-text="item.name ? (item.name + ' (' + item.sku + ')') : '-- Cari / Pilih Produk --'"
                                                  :class="!item.product_id ? 'text-slate-400' : 'text-slate-900 font-semibold'"
                                                  style="white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; padding-right: 6px; flex: 1 1 auto; min-width: 0;"
                                            ></span>
                                            <i class="fa-solid fa-chevron-down text-slate-400" :style="openProd ? 'transform: rotate(180deg);' : ''" style="font-size: 11px; transition: transform 0.2s; flex-shrink: 0; margin-left: auto;"></i>
                                        </div>

                                        {{-- Dropdown Menu --}}
                                        <div 
                                            x-show="openProd" 
                                            x-transition:enter="transition ease-out duration-150"
                                            x-transition:enter-start="opacity-0 translate-y-1"
                                            x-transition:enter-end="opacity-100 translate-y-0"
                                            x-transition:leave="transition ease-in duration-100"
                                            x-transition:leave-start="opacity-100 translate-y-0"
                                            x-transition:leave-end="opacity-0 translate-y-1"
                                            style="position: absolute; top: calc(100% + 4px); left: 0; min-width: 320px; z-index: 1060; background: #fff; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); border: 1px solid #e2e8f0; max-height: 260px; display: flex; flex-direction: column; overflow: hidden;"
                                            x-cloak
                                        >
                                            <div style="padding: 6px 8px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; position: relative;">
                                                <div style="position: relative; display: flex; align-items: center;">
                                                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 12px; pointer-events: none; z-index: 2;"></i>
                                                    <input 
                                                        type="text" 
                                                        x-ref="prodSearchInput"
                                                        x-model="prodSearch" 
                                                        placeholder="Cari nama atau SKU produk..." 
                                                        class="form-control searchable-select-search-input" 
                                                        style="width: 100% !important; padding-left: 30px !important; padding-right: 24px !important; font-size: 12px !important; height: 32px !important; border-radius: 6px !important;"
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
                                            <div style="overflow-y: auto; max-height: 200px;">
                                                <template x-for="p in availableProducts.filter(item => !prodSearch || item.name.toLowerCase().includes(prodSearch.toLowerCase()) || item.sku.toLowerCase().includes(prodSearch.toLowerCase()))" :key="p.id">
                                                    <div 
                                                        @click="selectProduct(index, p); openProd = false; prodSearch = '';"
                                                        :class="{ 'bg-blue-50 font-semibold text-blue-700': String(item.product_id) === String(p.id) }"
                                                        style="padding: 8px 10px; cursor: pointer; font-size: 13px; border-bottom: 1px solid #f8fafc;"
                                                        onmouseover="this.style.backgroundColor='#f1f5f9'"
                                                        onmouseout="if(String(item.product_id) !== String(p.id)) this.style.backgroundColor='transparent'"
                                                    >
                                                        <span x-text="p.name + ' (' + p.sku + ')'"></span>
                                                    </div>
                                                </template>
                                                <div x-show="availableProducts.filter(item => !prodSearch || item.name.toLowerCase().includes(prodSearch.toLowerCase()) || item.sku.toLowerCase().includes(prodSearch.toLowerCase())).length === 0" style="padding: 12px; font-size: 13px; color: #94a3b8; text-align: center;">
                                                    Tidak ada produk yang cocok
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align:center;">
                                    <span style="font-weight:600;" x-text="item.on_hand !== null ? item.on_hand : '-'"></span>
                                </td>
                                <td style="text-align:center; color:var(--text-secondary);">
                                    <span x-text="item.min_stock !== null ? item.min_stock : '-'"></span>
                                </td>
                                <td>
                                    <input type="number" 
                                           :name="'items[' + index + '][qty]'" 
                                           x-model.number="item.qty" 
                                           class="form-control" 
                                           min="1" 
                                           style="width: 100%; max-width: 100%; box-sizing: border-box; text-align:center; font-weight:700;" 
                                           required>
                                </td>
                                <td style="text-align:center; color:var(--text-secondary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" x-text="item.unit || '-'"></td>
                                <td>
                                    <input type="text" 
                                           :name="'items[' + index + '][notes]'" 
                                           x-model="item.notes" 
                                           class="form-control" 
                                           style="width: 100%; max-width: 100%; box-sizing: border-box;"
                                           placeholder="Catatan item (opsional)">
                                </td>
                                <td style="text-align:center; vertical-align:middle; padding-left:2px !important; padding-right:2px !important;">
                                    <button type="button" 
                                            @click="removeItem(index)" 
                                            class="btn btn-danger btn-sm btn-icon" 
                                            style="width:30px; height:30px; min-width:30px; max-width:30px; padding:0; display:inline-flex; align-items:center; justify-content:center; border-radius:6px; margin:0 auto;" 
                                            :disabled="items.length <= 1"
                                            title="Hapus Item">
                                        <i class="fa-solid fa-trash" style="font-size:11px;"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Form Actions --}}
        <x-form-actions 
            :back-url="route('purchase.requests.index')" 
            submit-label="Simpan sebagai Draft" 
        />
    </form>
</div>

<script>
function purchaseRequestForm() {
    return {
        availableProducts: @json($products),
        items: [
            {
                product_id: '',
                sku: '',
                name: '',
                unit: '',
                on_hand: null,
                min_stock: null,
                qty: 1,
                notes: ''
            }
        ],
        addItem() {
            this.items.push({
                product_id: '',
                sku: '',
                name: '',
                unit: '',
                on_hand: null,
                min_stock: null,
                qty: 1,
                notes: ''
            });
        },
        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },
        selectProduct(index, p) {
            this.items[index].product_id = p.id;
            this.items[index].sku = p.sku;
            this.items[index].name = p.name;
            this.items[index].unit = p.unit;
            this.items[index].on_hand = p.on_hand;
            this.items[index].min_stock = p.min_stock;
        },
        onProductChange(index) {
            const pid = this.items[index].product_id;
            const found = this.availableProducts.find(p => p.id == pid);
            if (found) {
                this.items[index].sku = found.sku;
                this.items[index].name = found.name;
                this.items[index].unit = found.unit;
                this.items[index].on_hand = found.on_hand;
                this.items[index].min_stock = found.min_stock;
            } else {
                this.items[index].sku = '';
                this.items[index].name = '';
                this.items[index].unit = '';
                this.items[index].on_hand = null;
                this.items[index].min_stock = null;
            }
        },
        validateForm(event) {
            const hasEmpty = this.items.some(i => !i.product_id || i.qty < 1);
            if (hasEmpty) {
                event.preventDefault();
                alert('Pastikan setiap baris barang telah dipilih produknya dan kuantitas minimal 1.');
                return false;
            }
        }
    };
}
</script>
@endsection