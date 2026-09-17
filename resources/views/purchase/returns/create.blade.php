@extends('layouts.app')
@section('title', 'Buat Retur Pembelian')
@section('page-title', 'Buat Retur Pembelian')

@section('content')
<div class="animate-in" x-data="returnForm()">
    <x-page-header 
        title="Buat Retur Pembelian" 
        subtitle="Pilih bukti Penerimaan Barang (GRN) yang barangnya akan dikembalikan ke supplier"
        :back-url="route('purchase.returns.index')"
    />

    <form method="POST" action="{{ route('purchase.returns.store') }}" @submit="validateForm($event)">
        @csrf

        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h3>Informasi Retur</h3></div>
            <div class="card-body">
                <div class="form-row form-row-3">
                    <div class="form-group">
                        <label class="form-label">Purchase Order (PO) <span style="color:var(--danger);">*</span></label>
                        <x-searchable-select 
                            name="purchase_order_id" 
                            :options="$purchaseOrders->map(fn($po) => ['id' => $po->id, 'name' => $po->po_number . ' — ' . ($po->supplier->name ?? '-') . ' (' . ($po->order_date ? $po->order_date->format('m/d/Y') : '-') . ')'])" 
                            placeholder="-- Pilih Purchase Order --" 
                            search-placeholder="Cari nomor PO atau nama supplier..." 
                            on-select="poId => { selectedPoId = poId; onPoChange(); }" 
                            required 
                        />
                        <small style="color:var(--text-secondary); font-size:11.5px; display:block; margin-top:3px;">Pilih PO untuk memfilter bukti penerimaan / invoice</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Bukti Penerimaan (LPB) / Faktur <span style="color:var(--danger);">*</span></label>
                        <select name="goods_receipt_id" class="form-control" x-model="selectedGrnId" @change="onGrnChange()" :disabled="!selectedPoId" required>
                            <option value="">-- Pilih LPB / Invoice --</option>
                            <template x-for="r in filteredReceipts" :key="r.id">
                                <option :value="r.id" x-text="`${r.receipt_number} (${formatDate(r.received_date)}) ${r.purchase_invoice ? '• Faktur: #' + r.purchase_invoice.invoice_number : '• Belum Di-invoice'}`"></option>
                            </template>
                        </select>
                        <small style="color:var(--text-secondary); font-size:11.5px;" x-text="selectedPoId ? (filteredReceipts.length + ' LPB ditemukan untuk PO ini') : 'Pilih PO terlebih dahulu'"></small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tanggal Retur <span style="color:var(--danger);">*</span></label>
                        <input type="date" name="return_date" value="{{ old('return_date', date('Y-m-d')) }}" class="form-control" required>
                    </div>
                </div>

                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Alasan Retur Utama</label>
                        <input type="text" name="reason" value="{{ old('reason') }}" class="form-control" placeholder="Contoh: Cacat pabrik / Rusak / Klaim garansi">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Catatan Tambahan</label>
                        <input type="text" name="notes" value="{{ old('notes') }}" class="form-control" placeholder="Catatan pengiriman retur...">
                    </div>
                </div>
            </div>
        </div>

        {{-- Alert Invoice Lunas Penuh --}}
        <div style="background:#fffbeb; border:1px solid #fde68a; color:#92400e; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:13.5px;" x-show="selectedGrn && isFullyPaid">
            <div style="display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size:16px;"></i>
                <div>
                    <strong>Invoice pembelian ini sudah lunas dibayar penuh ke supplier.</strong> Retur tidak dapat diproses otomatis karena akan menyebabkan kelebihan pembayaran dari sisi kita. Silakan koordinasikan dengan supplier untuk penanganan manual.
                </div>
            </div>
        </div>

        {{-- Items Table --}}
        <div class="card" style="margin-bottom:20px;" x-show="selectedGrn">
            <div class="card-header">
                <h3>Pilih Barang yang Dikembalikan</h3>
                <p style="font-size:12.5px; color:var(--text-secondary); margin:2px 0 0 0;">
                    Kuantitas retur dibatasi oleh sisa fisik yang ada di gudang dan sisa hutang invoice yang belum dibayar untuk mencegah overpayment ke supplier.
                </p>
            </div>
            <div class="table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th style="text-align:center; width:120px;">Jenis Barang</th>
                            <th style="text-align:center; width:95px;">Sisa GRN</th>
                            <th style="text-align:center; width:100px; color:#15803d;">Stok Fisik</th>
                            <th style="text-align:center; width:120px; color:#c2410c;">Batas Sisa Hutang</th>
                            <th style="text-align:center; width:100px; font-weight:700;">Maks Retur</th>
                            <th style="width:120px;">Qty Retur</th>
                            <th style="width:140px;">Harga Satuan</th>
                            <th>Alasan Item</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, idx) in items" :key="item.row_key">
                            <tr :style="item.final_max === 0 ? 'opacity: 0.65; background:#fef2f2;' : ''">
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
                                <td style="text-align:center; font-weight:600;" x-text="item.qty_grn_available + ' ' + item.product.unit"></td>
                                <td style="text-align:center; font-weight:600; color:#15803d;" x-text="item.source_type === 'accepted' ? item.physical_available + ' ' + item.product.unit : '-'"></td>
                                <td style="text-align:center; font-size:12px;">
                                    <template x-if="item.max_financial === null">
                                        <span class="badge badge-draft" style="font-size:11px;">Belum Di-invoice</span>
                                    </template>
                                    <template x-if="item.max_financial !== null">
                                        <span style="font-weight:600; color:#c2410c;" x-text="item.max_financial + ' ' + item.product.unit"></span>
                                    </template>
                                </td>
                                <td style="text-align:center; font-weight:700;">
                                    <template x-if="item.final_max > 0">
                                        <span style="color:#0284c7;" x-text="item.final_max + ' ' + item.product.unit"></span>
                                    </template>
                                    <template x-if="item.final_max === 0">
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
                                           :max="item.final_max"
                                           :disabled="item.final_max === 0 || isFullyPaid"
                                           placeholder="0">
                                </td>
                                <td>
                                    <input type="number" :name="`items[${idx}][unit_cost]`" x-model.number="item.unit_cost" class="form-control" min="0" readonly>
                                </td>
                                <td>
                                    <input type="text" :name="`items[${idx}][reason]`" class="form-control" placeholder="Alasan retur..." :disabled="item.final_max === 0 || isFullyPaid">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <x-form-actions 
            :cancel-url="route('purchase.returns.index')" 
            submit-label="Simpan Draft Retur" 
        />
    </form>
</div>
@endsection

@push('scripts')
<script>
const allReceipts = @json($receipts->values());
const initialPoId = "{{ old('purchase_order_id', $selectedPoId ?? '') }}";
const initialGrnId = "{{ old('goods_receipt_id', $selectedGrnId ?? '') }}";

function formatDate(dateStr) {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        return `${day}/${month}/${year}`;
    } catch (e) {
        return dateStr;
    }
}

function returnForm() {
    return {
        selectedPoId: initialPoId,
        selectedGrnId: initialGrnId,
        selectedGrn: null,
        invoice: null,
        isFullyPaid: false,
        items: [],

        init() {
            if (this.selectedGrnId) {
                const found = allReceipts.find(r => r.id == this.selectedGrnId);
                if (found && !this.selectedPoId) {
                    this.selectedPoId = String(found.purchase_order_id);
                }
                this.onGrnChange();
            } else if (this.selectedPoId) {
                this.onPoChange();
            }
        },

        get filteredReceipts() {
            if (!this.selectedPoId) return [];
            return allReceipts.filter(r => r.purchase_order_id == this.selectedPoId);
        },

        onPoChange() {
            const available = this.filteredReceipts;
            if (!available.some(r => r.id == this.selectedGrnId)) {
                this.selectedGrnId = '';
                this.selectedGrn = null;
                this.invoice = null;
                this.isFullyPaid = false;
                this.items = [];
            }
            if (available.length === 1) {
                this.selectedGrnId = String(available[0].id);
                this.onGrnChange();
            }
        },

        onGrnChange() {
            if (!this.selectedGrnId) {
                this.selectedGrn = null;
                this.invoice = null;
                this.isFullyPaid = false;
                this.items = [];
                return;
            }

            const found = allReceipts.find(r => r.id == this.selectedGrnId);
            if (!found) {
                this.selectedGrn = null;
                this.invoice = null;
                this.isFullyPaid = false;
                this.items = [];
                return;
            }

            this.selectedGrn = found;
            if (this.selectedGrn.purchase_order_id) {
                this.selectedPoId = String(this.selectedGrn.purchase_order_id);
            }
            this.invoice = this.selectedGrn.purchase_invoice || null;

            let sisaBelumBayar = null;
            if (this.invoice) {
                const totalPaid = (this.invoice.payments || []).reduce((acc, p) => acc + (Number(p.amount) || 0), 0);
                const effectiveTotal = Math.max(0, (Number(this.invoice.total_amount) || 0) - (Number(this.invoice.total_reversed_amount) || 0));
                sisaBelumBayar = Math.max(0, effectiveTotal - totalPaid);
                this.isFullyPaid = sisaBelumBayar <= 0.01;
            } else {
                this.isFullyPaid = false;
            }

            this.items = this.selectedGrn.items.flatMap(item => {
                const product = item.purchase_order_item ? item.purchase_order_item.product : { name: 'Produk', sku: '-', unit: 'pcs' };
                const unitCost = item.unit_cost || (item.purchase_order_item ? item.purchase_order_item.unit_price : 0);
                const rows = [];

                if (item.qty_available_for_return_accepted > 0) {
                    const physicalAvailable = item.physical_available ?? 0;
                    const maxReturnable = Math.min(item.qty_available_for_return_accepted, Math.max(0, physicalAvailable));

                    let maxFinancial = null;
                    let finalMax = maxReturnable;

                    if (this.invoice) {
                        if (this.isFullyPaid) {
                            maxFinancial = 0;
                            finalMax = 0;
                        } else {
                            const invItem = (this.invoice.items || []).find(i => i.goods_receipt_item_id == item.id) || item.purchase_invoice_item;
                            if (invItem && Number(invItem.qty_invoiced) > 0) {
                                const effectiveRate = ((Number(invItem.subtotal) || 0) + (Number(invItem.tax_amount) || 0)) / Number(invItem.qty_invoiced);
                                if (effectiveRate > 0) {
                                    maxFinancial = Math.floor(sisaBelumBayar / effectiveRate);
                                    finalMax = Math.min(maxReturnable, maxFinancial);
                                }
                            }
                        }
                    }

                    rows.push({
                        row_key: `${item.id}-accepted`,
                        id: item.id,
                        product: product,
                        source_type: 'accepted',
                        source_label: 'Diterima Stok',
                        qty_grn_available: item.qty_available_for_return_accepted,
                        physical_available: physicalAvailable,
                        max_returnable: maxReturnable,
                        max_financial: maxFinancial,
                        final_max: finalMax,
                        qty_return: 0,
                        unit_cost: unitCost,
                    });
                }

                if (item.qty_available_for_return_rejected > 0) {
                    const maxReturnable = item.qty_available_for_return_rejected;
                    let maxFinancial = null;
                    let finalMax = maxReturnable;

                    if (this.invoice) {
                        if (this.isFullyPaid) {
                            maxFinancial = 0;
                            finalMax = 0;
                        } else {
                            const invItem = (this.invoice.items || []).find(i => i.goods_receipt_item_id == item.id) || item.purchase_invoice_item;
                            if (invItem && Number(invItem.qty_invoiced) > 0) {
                                const effectiveRate = ((Number(invItem.subtotal) || 0) + (Number(invItem.tax_amount) || 0)) / Number(invItem.qty_invoiced);
                                if (effectiveRate > 0) {
                                    maxFinancial = Math.floor(sisaBelumBayar / effectiveRate);
                                    finalMax = Math.min(maxReturnable, maxFinancial);
                                }
                            }
                        }
                    }

                    rows.push({
                        row_key: `${item.id}-rejected`,
                        id: item.id,
                        product: product,
                        source_type: 'rejected',
                        source_label: 'Rusak / Reject',
                        qty_grn_available: item.qty_available_for_return_rejected,
                        physical_available: 0,
                        max_returnable: maxReturnable,
                        max_financial: maxFinancial,
                        final_max: finalMax,
                        qty_return: finalMax > 0 ? finalMax : 0,
                        unit_cost: unitCost,
                    });
                }

                return rows;
            });
        },

        get hasAnyReturnQty() {
            return this.items.some(it => (Number(it.qty_return) || 0) > 0);
        },

        get isReturnInvalid() {
            return !this.selectedGrn || !this.hasAnyReturnQty || this.isFullyPaid;
        },

        validateForm(e) {
            if (!this.selectedGrn) {
                alert('Pilih Surat Penerimaan Barang (LPB) terlebih dahulu.');
                e.preventDefault();
                return false;
            }
            if (this.isFullyPaid) {
                alert('Invoice untuk LPB ini sudah lunas penuh sehingga retur tidak dapat diproses.');
                e.preventDefault();
                return false;
            }
            if (!this.hasAnyReturnQty) {
                alert('Isi minimal 1 jumlah barang yang ingin diretur.');
                e.preventDefault();
                return false;
            }
            return true;
        }
    }
}
</script>
@endpush
