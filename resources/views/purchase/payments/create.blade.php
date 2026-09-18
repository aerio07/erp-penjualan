@extends('layouts.app')
@section('title', 'Catat Pembayaran Hutang Supplier')
@section('page-title', 'Catat Pembayaran Hutang Supplier')

@section('content')
<div class="animate-in" x-data="paymentForm()">
    <x-page-header 
        title="Catat Pembayaran Hutang" 
        subtitle="Pilih Invoice Pembelian dan ketik jumlah nominal rupiah yang dibayarkan."
        :back-url="route('purchase.payments.index')"
    />

    <form method="POST" action="{{ route('purchase.payments.store') }}" @submit="validateForm($event)">
        @csrf

        <div class="grid grid-3" style="margin-bottom:20px;">
            <div class="card" style="grid-column:span 2;">
                <div class="card-header"><h3>Form Pembayaran</h3></div>
                <div class="card-body">
                    <div class="form-row form-row-2" style="margin-bottom:14px;">
                        <div class="form-group">
                            <label class="form-label">No. Pembayaran</label>
                            <input type="text" class="form-control" value="[ Otomatis oleh Sistem (BBK / BKK) ]" readonly style="background:var(--bg-muted, #f1f5f9); color:var(--text-secondary); font-weight:600;">
                            <small style="color:var(--text-secondary); font-size:11px;">Nomor bukti otomatis dihasilkan sistem saat disimpan (Bank: BBK, Kas: BKK).</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tanggal Bayar <span style="color:var(--danger);">*</span></label>
                            <input type="date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Invoice Pembelian <span style="color:var(--danger);">*</span></label>
                        <x-searchable-select 
                            name="purchase_invoice_id" 
                            :options="$unpaidInvoices->map(fn($inv) => ['id' => $inv->id, 'name' => $inv->invoice_number . ' - ' . ($inv->purchaseOrder->supplier->name ?? '-') . ' (Sisa Hutang: Rp ' . number_format($inv->outstanding_amount, 0, ',', '.') . ')'])" 
                            placeholder="-- Pilih Invoice Tagihan --" 
                            search-placeholder="Cari nomor invoice atau nama supplier..." 
                            on-select="invId => { selectedInvoiceId = invId; onInvoiceChange(); }" 
                            required 
                        />
                    </div>

                    <div class="form-row form-row-2">
                        <div class="form-group">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.375rem;">
                                <label class="form-label" style="margin-bottom:0; width:auto;">Nominal Bayar (Rp) <span style="color:var(--danger);">*</span></label>
                                <template x-if="selectedInvoice">
                                    <button type="button" class="btn btn-secondary btn-sm" @click="payFull()" style="font-size:10px; padding:1px 6px; height:20px; line-height:1;" title="Isi otomatis sisa hutang">
                                        <i class="fa-solid fa-bolt"></i> Sisa Lunas
                                    </button>
                                </template>
                            </div>
                            <div style="position:relative;">
                                <input type="text" 
                                       inputmode="numeric"
                                       name="amount" 
                                       x-currency="amount" 
                                       class="form-control format-currency" 
                                       required 
                                       placeholder="cth: 5.000.000">
                            </div>
                            <template x-if="amount && amount > maxAmount">
                                <div style="color:var(--danger); font-size:11px; font-weight:600; margin-top:4px;">
                                    <i class="fa-solid fa-circle-exclamation"></i> Melebihi sisa hutang!
                                </div>
                            </template>
                            <template x-if="amount !== null && amount !== '' && amount <= 0">
                                <div style="color:var(--danger); font-size:11px; font-weight:600; margin-top:4px;">
                                    <i class="fa-solid fa-circle-exclamation"></i> Harus > 0!
                                </div>
                            </template>
                    </div>

                    <div class="form-row form-row-2">
                        <div class="form-group">
                            <label class="form-label">Metode Pembayaran <span style="color:var(--danger);">*</span></label>
                            <select name="method" class="form-control" required>
                                <option value="transfer">Bank Transfer (BBK)</option>
                                <option value="cash">Kas / Tunai (BKK)</option>
                                <option value="giro">Giro (BBK)</option>
                                <option value="cek">Cek Bank (BBK)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">No. Referensi / Bukti Transfer</label>
                            <input type="text" name="reference_number" value="{{ old('reference_number') }}" class="form-control" placeholder="cth: TRF-BCA-123456">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="1" placeholder="Catatan pembayaran...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Ringkasan Tagihan & Sisa Card --}}
            <div class="card">
                <div class="card-header"><h3>Status Tagihan & Sisa</h3></div>
                <div class="card-body">
                    <template x-if="selectedInvoice">
                        <div style="display:flex; flex-direction:column; gap:12px; font-size:14px;">
                            <div>
                                <div style="font-size:12px; color:var(--text-secondary);">Supplier</div>
                                <div style="font-weight:600;" x-text="selectedInvoice.purchase_order?.supplier?.name || '-'"></div>
                            </div>
                            <div>
                                <div style="font-size:12px; color:var(--text-secondary);">Jatuh Tempo</div>
                                <div style="font-weight:600;" x-text="selectedInvoice.due_date ? selectedInvoice.due_date.substring(0,10) : '-'"></div>
                            </div>
                            <hr style="border:none; border-top:1px solid var(--border);">
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:var(--text-secondary);">Total Tagihan Efektif</span>
                                <span style="font-weight:600;" x-text="'Rp ' + formatNum(selectedInvoice.effective_total_amount || selectedInvoice.total_amount)"></span>
                            </div>
                            <template x-if="selectedInvoice.total_reversed_amount > 0">
                                <div style="display:flex; justify-content:space-between; color:var(--danger);">
                                    <span>Pengurang Retur</span>
                                    <span style="font-weight:600;" x-text="'Rp ' + formatNum(selectedInvoice.total_reversed_amount)"></span>
                                </div>
                            </template>
                            <div style="display:flex; justify-content:space-between; color:var(--success);">
                                <span>Sudah Dibayar Lalu</span>
                                <span style="font-weight:600;" x-text="'Rp ' + formatNum(selectedInvoice.total_paid || 0)"></span>
                            </div>
                            <div style="display:flex; justify-content:space-between; color:var(--danger); font-size:15px; font-weight:700;">
                                <span>Sisa Hutang Saat Ini</span>
                                <span x-text="'Rp ' + formatNum(maxAmount)"></span>
                            </div>
                            <div style="display:flex; justify-content:space-between; color:var(--primary); font-size:14.5px; font-weight:600;">
                                <span>Yang Dibayar Sekarang</span>
                                <span x-text="amount > 0 ? 'Rp ' + formatNum(amount) : 'Rp 0'"></span>
                            </div>
                            <hr style="border:none; border-top:1px dashed var(--border);">
                            
                            {{-- Simulasi Sisa Tagihan --}}
                            <div style="display:flex; justify-content:space-between; font-size:14px;">
                                <span style="font-weight:600;">Sisa Hutang Nanti</span>
                                <span :style="simulatedRemaining <= 0 && amount > 0 ? 'color:var(--success); font-weight:700; font-size:16px;' : 'color:var(--danger); font-weight:700; font-size:15px;'" x-text="'Rp ' + formatNum(simulatedRemaining)"></span>
                            </div>

                            <div style="margin-top:6px;">
                                <template x-if="!amount || amount <= 0">
                                    <span class="badge badge-unpaid" style="font-size:12px; padding:6px 12px; display:inline-block;">
                                        Ketik nominal yang ingin Anda bayarkan
                                    </span>
                                </template>
                                <template x-if="amount > 0 && amount < maxAmount">
                                    <span class="badge badge-pending" style="font-size:12px; padding:6px 12px; display:inline-block;">
                                        <i class="fa-solid fa-clock-rotate-left"></i> Status: Cicilan (Sisa Hutang Rp <span x-text="formatNum(simulatedRemaining)"></span>)
                                    </span>
                                </template>
                                <template x-if="amount >= maxAmount && amount > 0">
                                    <span class="badge badge-done" style="font-size:12px; padding:6px 12px; display:inline-block;">
                                        <i class="fa-solid fa-circle-check"></i> Status: Lunas Penuh (Sisa Hutang Rp 0)
                                    </span>
                                </template>
                            </div>

                            {{-- Supplier Bank Account Information --}}
                            <template x-if="selectedInvoice && selectedInvoice.purchase_order && selectedInvoice.purchase_order.supplier && (selectedInvoice.purchase_order.supplier.bank_name || selectedInvoice.purchase_order.supplier.bank_account_number)">
                                <div style="margin-top:14px; padding:10px 12px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; font-size:12px;">
                                    <div style="font-weight:700; color:#1e40af; margin-bottom:3px; display:flex; align-items:center; gap:4px;">
                                        <span class="material-symbols-outlined text-[16px]">account_balance</span>
                                        Rekening Transfer Supplier:
                                    </div>
                                    <div style="color:#1e3a8a; font-weight:600;">
                                        <span x-text="selectedInvoice.purchase_order.supplier.bank_name || 'Bank'"></span>: 
                                        <span x-text="selectedInvoice.purchase_order.supplier.bank_account_number" style="font-family:monospace; font-weight:700;"></span>
                                    </div>
                                    <template x-if="selectedInvoice.purchase_order.supplier.bank_account_holder">
                                        <div style="color:#475569; font-size:11px; margin-top:2px;">
                                            a/n <span x-text="selectedInvoice.purchase_order.supplier.bank_account_holder"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="!selectedInvoice">
                        <div style="text-align:center; padding:24px 0; color:var(--text-secondary); font-size:13px;">
                            <i class="fa-solid fa-receipt" style="font-size:28px; margin-bottom:8px; display:block; opacity:0.4;"></i>
                            Pilih Invoice Pembelian terlebih dahulu
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px;">
            <a href="{{ route('purchase.payments.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
            <div>
                <button type="submit" class="btn btn-primary" style="padding: 9px 20px; font-weight: 600;">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Draft Pembayaran
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const invoices = @json($unpaidInvoices->keyBy('id'));
const initialInvoiceId = "{{ $selectedInvoiceId ?? '' }}";

function paymentForm() {
    return {
        selectedInvoiceId: initialInvoiceId,
        selectedInvoice: null,
        amount: null,

        init() {
            if (this.selectedInvoiceId) {
                this.onInvoiceChange();
            }
        },

        onInvoiceChange() {
            if (!this.selectedInvoiceId || !invoices[this.selectedInvoiceId]) {
                this.selectedInvoice = null;
                this.amount = null;
                return;
            }
            this.selectedInvoice = invoices[this.selectedInvoiceId];
            this.amount = null; // Biarkan kosong agar user mengetik sendiri nominal bayarnya
        },

        payFull() {
            if (this.selectedInvoice) {
                this.amount = this.maxAmount;
            }
        },

        get maxAmount() {
            return this.selectedInvoice ? (parseFloat(this.selectedInvoice.outstanding_amount) || 0) : 0;
        },

        get simulatedRemaining() {
            const pay = Math.max(0, Number(this.amount) || 0);
            return Math.max(0, this.maxAmount - pay);
        },

        get isPaymentInvalid() {
            return !this.selectedInvoice || !this.amount || this.amount <= 0 || this.amount > this.maxAmount;
        },

        validateForm(e) {
            const showMsg = (msg) => {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Perhatian',
                        text: msg,
                        confirmButtonColor: '#03193c',
                        confirmButtonText: 'Mengerti'
                    });
                } else {
                    alert(msg);
                }
            };

            if (!this.selectedInvoice) {
                showMsg('Pilih Invoice Pembelian terlebih dahulu.');
                e.preventDefault();
                return false;
            }
            if (!this.amount || this.amount <= 0) {
                showMsg('Ketik nominal yang ingin Anda bayarkan.');
                e.preventDefault();
                return false;
            }
            if (this.amount > this.maxAmount) {
                showMsg('Nominal pembayaran melebihi sisa hutang (Maks: Rp ' + this.formatNum(this.maxAmount) + ').');
                e.preventDefault();
                return false;
            }
            return true;
        },

        formatNum(v) {
            return new Intl.NumberFormat('id-ID').format(Math.round(v || 0));
        }
    }
}
</script>
@endpush
