@extends('layouts.app')
@section('title', 'Buat Surat Jalan')
@section('page-title', 'Buat Surat Jalan / Pengiriman')

@section('content')
<div class="animate-in" x-data="deliveryForm()">
    <x-page-header 
        title="Buat Surat Jalan" 
        subtitle="Pilih Sales Order yang barangnya akan dikirim dari gudang ke customer"
        :back-url="route('sales.deliveries.index')"
    />

    <form method="POST" action="{{ route('sales.deliveries.store') }}" @submit="validateForm($event)">
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
                        <select name="warehouse_id" class="form-control" x-model="selectedWarehouseId" @change="onWarehouseChange()" required>
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
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                <h3>Item Barang Dikirim</h3>
                <div x-show="selectedWarehouseId" style="font-size:12.5px; color:var(--text-secondary);">
                    <i class="fa-solid fa-warehouse" style="color:var(--primary); margin-right:4px;"></i>
                    Stok diperiksa untuk: <strong x-text="getWarehouseName(selectedWarehouseId)" style="color:var(--text-primary);"></strong>
                </div>
            </div>
            <div class="table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th style="text-align:center; width:110px;">Qty Dipesan</th>
                            <th style="text-align:center; width:100px;">Sudah Dikirim</th>
                            <th style="text-align:center; width:100px;">Sisa SO</th>
                            <th style="width:230px;">Ketersediaan di Gudang Terpilih</th>
                            <th style="width:190px;">Qty Kirim Saat Ini</th>
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
                                    <template x-if="!selectedWarehouseId">
                                        <span style="font-size:12px; color:var(--text-secondary); font-style:italic;">
                                            <i class="fa-solid fa-circle-info"></i> Pilih gudang asal terlebih dahulu
                                        </span>
                                    </template>
                                    <template x-if="selectedWarehouseId">
                                        <div>
                                            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                                <span class="badge" 
                                                    :style="item.max_deliverable > 0 ? 'background:#dcfce7; color:#15803d; font-weight:700;' : 'background:#fee2e2; color:#b91c1c; font-weight:700;'">
                                                    <i class="fa-solid" :class="item.max_deliverable > 0 ? 'fa-circle-check' : 'fa-circle-xmark'"></i>
                                                    Siap Kirim: <span x-text="item.max_deliverable"></span> <span x-text="item.product.unit"></span>
                                                </span>
                                            </div>
                                            <div style="font-size:11.5px; color:var(--text-secondary); margin-top:3px;">
                                                Fisik: <strong x-text="item.warehouse_on_hand"></strong> · Teralokasi SO: <strong x-text="item.warehouse_reserved"></strong> · Bebas: <strong x-text="item.warehouse_free"></strong>
                                            </div>
                                            <template x-if="item.other_warehouses && item.other_warehouses.length > 0">
                                                <div style="margin-top:4px; font-size:11px; color:#475569; background:#f1f5f9; padding:2px 6px; border-radius:4px; display:inline-block;">
                                                    <i class="fa-solid fa-location-dot" style="color:#0284c7;"></i>
                                                    Tersedia di gudang lain: 
                                                    <span x-text="item.other_warehouses.map(w => `${w.name} (${w.deliverable})`).join(', ')"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </td>
                                <td>
                                    <input type="number" 
                                        :name="`items[${idx}][qty_delivered]`" 
                                        x-model.number="item.current_delivered" 
                                        class="form-control" 
                                        :style="(item.current_delivered > item.max_deliverable || item.current_delivered > item.qty_remaining) ? 'border-color:var(--danger); background:#fff5f5;' : ''"
                                        min="0" 
                                        :max="selectedWarehouseId ? item.max_deliverable : item.qty_remaining" 
                                        required>
                                    <div x-show="selectedWarehouseId && item.current_delivered > item.max_deliverable" style="color:var(--danger); font-size:11.5px; margin-top:4px; font-weight:500;">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Melebihi stok gudang ini (Maks: <span x-text="item.max_deliverable"></span>)
                                    </div>
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

        {{-- Auto-Generate Invoice & Confirmation Option --}}
        <div class="card" style="margin-bottom:20px; border-left: 4px solid var(--primary);" x-show="selectedSo">
            <div class="card-header" style="background:#f8fafc; display:flex !important; flex-direction:row !important; justify-content:space-between !important; align-items:center !important; flex-wrap:wrap !important; gap:16px !important;">
                <div style="flex:1 1 300px; min-width:250px;">
                    <h3 style="display:flex; align-items:center; gap:8px; margin:0; font-size:15px; font-weight:700;">
                        <i class="fa-solid fa-receipt" style="color:var(--primary);"></i>
                        Penerimaan Barang & Faktur Penjualan (Sales Invoice)
                    </h3>
                    <p style="margin:4px 0 0 0; font-size:12.5px; color:var(--text-secondary);">
                        Tentukan apakah Surat Jalan ini langsung dikonfirmasi diterima dan diterbitkan tagihannya, atau dikirim bertahap.
                    </p>
                </div>
                <div style="display:inline-flex !important; width:auto !important; flex:0 0 auto !important;">
                    <label style="display:inline-flex !important; width:auto !important; align-items:center !important; gap:10px !important; cursor:pointer !important; background:#ffffff !important; padding:8px 14px !important; border:1px solid var(--border) !important; border-radius:8px !important; font-weight:600 !important; font-size:13px !important; box-shadow:0 1px 2px rgba(0,0,0,0.05) !important; user-select:none !important; margin:0 !important; text-transform:none !important; letter-spacing:normal !important;">
                        <input type="checkbox" name="auto_generate_invoice" value="1" x-model="autoGenerateInvoice" @change="onToggleAutoInvoice()" style="width:18px !important; height:18px !important; accent-color:var(--primary); cursor:pointer; margin:0 !important;">
                        <span style="color:#0e1b35; font-size:13px; font-weight:600;">Langsung Terbitkan Invoice & Konfirmasi Diterima</span>
                    </label>
                </div>
            </div>
            <div class="card-body">
                <template x-if="!autoGenerateInvoice">
                    <div style="display:flex; align-items:center; gap:12px; padding:12px 16px; background:#eff6ff; border-radius:8px; color:#1e40af; font-size:13px;">
                        <i class="fa-solid fa-circle-info" style="font-size:18px; flex-shrink:0;"></i>
                        <div>
                            <strong>Alur Bertahap (Default):</strong> Surat Jalan akan dibuat dengan status <strong>Dalam Pengiriman (In Transit)</strong>. Invoice belum dapat dibuat sampai barang tiba di customer dan dikonfirmasi penerimaannya untuk mencegah kasus barang belum sampai tetapi sudah ditagih.
                        </div>
                    </div>
                </template>

                <template x-if="autoGenerateInvoice">
                    <div style="display:flex; flex-direction:column; gap:16px;">
                        <div style="display:flex; align-items:center; gap:12px; padding:12px 16px; background:#ecfdf5; border-radius:8px; color:#065f46; font-size:13px;">
                            <i class="fa-solid fa-check-double" style="font-size:18px; flex-shrink:0;"></i>
                            <div>
                                <strong>Alur Instan (Direct Handover / COD):</strong> Barang langsung berstatus <strong>Diterima (Delivered)</strong>. Sales Invoice dan Jurnal Piutang, Penjualan, & HPP akan langsung diposting otomatis ke buku besar.
                            </div>
                        </div>

                        <div class="form-row form-row-3">
                            <div class="form-group">
                                <label class="form-label">Tanggal Barang Diterima <span style="color:var(--danger);">*</span></label>
                                <input type="date" name="received_date" x-model="receivedDate" class="form-control" :required="autoGenerateInvoice">
                                <small style="color:var(--text-secondary); font-size:11.5px;">Tgl fisik barang sampai di tangan customer</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Tanggal Faktur / Invoice <span style="color:var(--danger);">*</span></label>
                                <input type="date" name="invoice_date" x-model="invoiceDate" @change="onInvoiceDateChange()" class="form-control" :required="autoGenerateInvoice">
                                <small style="color:var(--text-secondary); font-size:11.5px;">Tgl pengakuan piutang & penjualan</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Tanggal Jatuh Tempo <span style="color:var(--danger);">*</span></label>
                                <input type="date" name="due_date" x-model="dueDate" class="form-control" :required="autoGenerateInvoice">
                            </div>
                        </div>

                        <div class="form-row form-row-2">
                            <div class="form-group">
                                <label class="form-label">Tarif PPN (%)</label>
                                <input type="number" name="invoice_tax_rate" x-model.number="taxRate" class="form-control" step="0.01" min="0" max="100">
                                <small style="color:var(--text-secondary); font-size:11.5px;">Diambil otomatis dari tarif pajak SO</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Catatan Penerimaan / No. Resi</label>
                                <input type="text" name="receipt_notes" x-model="receiptNotes" class="form-control" placeholder="Contoh: Diterima oleh staf gudang customer">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <x-form-actions :back-url="route('sales.deliveries.index')">
            <x-slot:submitButton>
                <button type="submit" class="btn btn-primary inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold rounded-lg text-white shadow-xs">
                    <i class="fa-solid" :class="autoGenerateInvoice ? 'fa-file-invoice-dollar' : 'fa-truck-fast'"></i>
                    <span x-text="autoGenerateInvoice ? 'Cetak Surat Jalan & Terbitkan Invoice' : 'Cetak Surat Jalan & Kirim Barang'"></span>
                </button>
            </x-slot:submitButton>
        </x-form-actions>
    </form>
</div>
@endsection

@push('scripts')
<script>
const confirmedSos = @json($confirmedSos->keyBy('id'));
const stockMatrixBySo = @json($stockMatrixBySo ?? []);
const warehousesMap = @json($warehouses->keyBy('id'));
const initialSoId = "{{ old('sales_order_id', $selectedSoId ?? '') }}";
const initialWarehouseId = "{{ old('warehouse_id', '') }}";
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

function parseTermDays(term) {
    if (typeof term === 'number') return term;
    if (!term) return 30;
    if (String(term).toUpperCase().trim() === 'COD') return 0;
    const match = String(term).match(/\d+/);
    return match ? parseInt(match[0], 10) : 30;
}

function deliveryForm() {
    return {
        selectedSoId: initialSoId,
        selectedWarehouseId: initialWarehouseId,
        selectedSo: null,
        recipientName: oldRecipientName,
        shippingAddress: oldShippingAddress,
        items: [],
        isFirstLoad: true,
        autoGenerateInvoice: {{ old('auto_generate_invoice') ? 'true' : 'false' }},
        deliveryDate: "{{ old('delivery_date', date('Y-m-d')) }}",
        receivedDate: "{{ old('received_date', date('Y-m-d')) }}",
        invoiceDate: "{{ old('invoice_date', date('Y-m-d')) }}",
        dueDate: "{{ old('due_date', '') }}",
        taxRate: 11,
        paymentTerm: 30,
        paymentTermLabel: 'NET 30',
        receiptNotes: @json(old('receipt_notes', '')),

        getWarehouseName(whId) {
            return warehousesMap[whId] ? warehousesMap[whId].name : '-';
        },

        init() {
            if (this.selectedSoId) {
                this.onSoChange(false);
            }
            this.isFirstLoad = false;
        },

        onWarehouseChange() {
            this.recalculateItemStock(true);
        },

        recalculateItemStock(isManualWhChange = true) {
            if (!this.items || this.items.length === 0) return;

            const soMatrix = stockMatrixBySo[this.selectedSoId] || {};
            const whId = parseInt(this.selectedWarehouseId) || null;

            this.items.forEach(item => {
                const itemData = soMatrix[item.id] || null;
                const whInfo = (itemData && whId && itemData.warehouses && itemData.warehouses[whId])
                    ? itemData.warehouses[whId]
                    : null;

                const maxDeliverable = whInfo ? whInfo.max_deliverable : 0;
                item.warehouse_on_hand = whInfo ? whInfo.on_hand : 0;
                item.warehouse_reserved = whInfo ? whInfo.reserved_for_so : 0;
                item.warehouse_free = whInfo ? whInfo.free_stock : 0;
                item.max_deliverable = Math.min(item.qty_remaining, maxDeliverable);

                // Cari gudang lain yang punya stok deliverable
                const otherWhs = [];
                if (itemData && itemData.warehouses) {
                    Object.values(itemData.warehouses).forEach(w => {
                        if (w.warehouse_id != whId && w.max_deliverable > 0) {
                            otherWhs.push({
                                name: w.warehouse_name,
                                deliverable: w.max_deliverable
                            });
                        }
                    });
                }
                item.other_warehouses = otherWhs;

                // Batasi nilai Qty Kirim saat ini
                if (whId) {
                    if (isManualWhChange || item.current_delivered > item.max_deliverable) {
                        item.current_delivered = item.max_deliverable;
                    }
                }
            });
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

            this.taxRate = parseFloat(this.selectedSo.tax_rate) || 0;
            const cust = this.selectedSo.customer;
            this.paymentTerm = cust ? (cust.payment_term_days ?? parseTermDays(cust.payment_term)) : 30;
            this.paymentTermLabel = cust ? (cust.payment_term || (this.paymentTerm + ' hari')) : 'NET 30';

            if (!this.dueDate || isManualUserChange) {
                this.calculateDueDate();
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
                    warehouse_on_hand: 0,
                    warehouse_reserved: 0,
                    warehouse_free: 0,
                    max_deliverable: remaining,
                    other_warehouses: [],
                };
            });

            this.recalculateItemStock(!this.isFirstLoad);
        },

        onToggleAutoInvoice() {
            if (this.autoGenerateInvoice) {
                if (!this.receivedDate) this.receivedDate = this.deliveryDate;
                if (!this.invoiceDate) this.invoiceDate = this.receivedDate;
                this.calculateDueDate();
            }
        },

        onInvoiceDateChange() {
            this.calculateDueDate();
        },

        calculateDueDate() {
            if (!this.invoiceDate) return;
            const d = new Date(this.invoiceDate);
            if (isNaN(d.getTime())) return;
            const term = parseInt(this.paymentTerm) || 0;
            d.setDate(d.getDate() + term);
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            this.dueDate = `${yyyy}-${mm}-${dd}`;
        },

        validateForm(e) {
            if (!this.selectedSo) {
                alert('Pilih Sales Order (SO) terlebih dahulu.');
                e.preventDefault();
                return false;
            }
            if (!this.selectedWarehouseId) {
                alert('Pilih Gudang Asal Pengiriman terlebih dahulu.');
                e.preventDefault();
                return false;
            }
            const totalDeliver = this.items.reduce((s, it) => s + (Number(it.current_delivered) || 0), 0);
            if (totalDeliver <= 0) {
                alert('Isi minimal satu kuantitas barang yang dikirim.');
                e.preventDefault();
                return false;
            }
            if (this.items.some(it => Number(it.current_delivered) > Number(it.qty_remaining))) {
                alert('Ada jumlah pengiriman yang melebihi sisa pesanan Sales Order.');
                e.preventDefault();
                return false;
            }
            if (this.items.some(it => Number(it.current_delivered) > Number(it.max_deliverable))) {
                alert('Ada jumlah pengiriman yang melebihi stok yang tersedia di gudang.');
                e.preventDefault();
                return false;
            }
            return true;
        }
    }
}
</script>
@endpush
