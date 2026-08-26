@extends('layouts.app')
@section('title', 'Catat Pembayaran Hutang Supplier')
@section('page-title', 'Catat Pembayaran Hutang Supplier')

@section('content')
<div class="animate-in" x-data="paymentForm()">
    <div class="page-header">
        <div>
            <h1>Catat Pembayaran Hutang (Cicilan / Pelunasan)</h1>
            <p>Pilih Invoice Pembelian dan ketik jumlah nominal rupiah yang dibayarkan.</p>
        </div>
        <a href="{{ route('purchase.payments.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <form method="POST" action="{{ route('purchase.payments.store') }}">
        @csrf

        <div class="grid grid-3" style="margin-bottom:20px;">
            <div class="card" style="grid-column:span 2;">
                <div class="card-header"><h3>Form Pembayaran</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Invoice Pembelian <span style="color:var(--danger);">*</span></label>
                        <select name="purchase_invoice_id" class="form-control" x-model="selectedInvoiceId" @change="onInvoiceChange()" required>
                            <option value="">-- Pilih Invoice Tagihan --</option>
                            @foreach($unpaidInvoices as $inv)
                            <option value="{{ $inv->id }}">
                                {{ $inv->invoice_number }} - {{ $inv->purchaseOrder->supplier->name }} (Sisa Hutang: Rp {{ number_format($inv->outstanding_amount, 0, ',', '.') }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-row form-row-4">
                        <div class="form-group">
                            <label class="form-label">Nominal Bayar (Rp) <span style="color:var(--danger);">*</span></label>
                            <div style="position:relative;">
                                <input type="number" 
                                       name="amount" 
                                       x-model.number="amount" 
                                       class="form-control" 
                                       min="1" 
                                       :max="maxAmount" 
                                       step="1" 
                                       @keydown="if($event.key==='-'||$event.key==='e'||$event.key==='+') $event.preventDefault()"
                                       required 
                                       placeholder="Nominal (cth: 5000000)">
                            </div>
                            <template x-if="selectedInvoice">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">
                                    <button type="button" class="btn btn-secondary btn-sm" @click="payFull()" style="font-size:11px; padding:2px 6px; width:100%; justify-content:center;" title="Isi otomatis sisa hutang">
                                        <i class="fa-solid fa-bolt"></i> Isi Sisa Lunas
                                    </button>
                                </div>
                            </template>
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

                        <div class="form-group">
                            <label class="form-label">Metode Pembayaran <span style="color:var(--danger);">*</span></label>
                            <select name="method" class="form-control" required>
                                <option value="transfer">Bank Transfer</option>
                                <option value="cash">Kas / Tunai</option>
                                <option value="giro">Giro</option>
                                <option value="cek">Cek Bank</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tanggal Bayar <span style="color:var(--danger);">*</span></label>
                            <input type="date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">No. Referensi / Bukti</label>
                            <input type="text" name="reference_number" value="{{ old('reference_number') }}" class="form-control" placeholder="TRF-BCA-98124">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="1" placeholder="Catatan pembayaran...">{{ old('notes') }}</textarea>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px;">
                        <a href="{{ route('purchase.payments.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary" :disabled="!selectedInvoice || !amount || amount <= 0 || amount > maxAmount">
                            <i class="fa-solid fa-money-bill-transfer"></i> Simpan Pembayaran & Posting Jurnal
                        </button>
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

        formatNum(v) {
            return new Intl.NumberFormat('id-ID').format(Math.round(v || 0));
        }
    }
}
</script>
@endpush
