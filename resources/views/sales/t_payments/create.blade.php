@extends('layouts.app')
@section('title', 'Catat Pembayaran Piutang Customer')
@section('page-title', 'Catat Pembayaran Piutang Customer')

@section('content')
<div class="animate-in" x-data="paymentForm()">
    <x-page-header 
        title="Catat Pembayaran Piutang (Cicilan / Pelunasan)" 
        subtitle="Pilih Invoice Penjualan customer dan ketik jumlah nominal rupiah yang dibayarkan."
        :back-url="route('sales.payments.index')"
    />

    <form method="POST" action="{{ route('sales.payments.store') }}" @submit="validateForm($event)">
        @csrf

        <div class="grid grid-3" style="margin-bottom:20px;">
            <div class="card" style="grid-column:span 2;">
                <div class="card-header"><h3>Form Pembayaran Piutang</h3></div>
                <div class="card-body">
                    <div class="form-row form-row-2" style="margin-bottom:14px;">
                        <div class="form-group">
                            <label class="form-label">No. Pembayaran</label>
                            <input type="text" class="form-control" value="[ Otomatis oleh Sistem (BBM / BKM) ]" readonly style="background:var(--bg-muted, #f1f5f9); color:var(--text-secondary); font-weight:600;">
                            <small style="color:var(--text-secondary); font-size:11px;">Nomor bukti otomatis dihasilkan sistem saat disimpan (Bank: BBM, Kas: BKM).</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tanggal Diterima <span style="color:var(--danger);">*</span></label>
                            <input type="date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Invoice Penjualan <span style="color:var(--danger);">*</span></label>
                        <x-searchable-select 
                            name="sales_invoice_id" 
                            :options="$unpaidInvoices->map(fn($inv) => ['id' => $inv->id, 'name' => $inv->invoice_number . ' - ' . ($inv->salesOrder->customer->name ?? '-') . ' (Sisa Piutang: Rp ' . number_format($inv->outstanding_amount, 0, ',', '.') . ')'])" 
                            placeholder="-- Pilih Invoice Tagihan Customer --" 
                            search-placeholder="Cari nomor invoice atau nama customer..." 
                            on-select="invId => { selectedInvoiceId = invId; onInvoiceChange(); }" 
                            required 
                        />
                    </div>

                    <div class="form-group">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.375rem;">
                            <label class="form-label" style="margin-bottom:0; width:auto;">Nominal Diterima (Rp) <span style="color:var(--danger);">*</span></label>
                            <template x-if="selectedInvoice">
                                <button type="button" class="btn btn-secondary btn-sm" @click="payFull()" style="font-size:10px; padding:1px 6px; height:20px; line-height:1;" title="Isi otomatis sisa piutang">
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
                                <i class="fa-solid fa-circle-exclamation"></i> Melebihi sisa piutang!
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
                            <label class="form-label">Tipe Pembayaran <span style="color:var(--danger);">*</span></label>
                            <select name="payment_type" x-model="paymentType" @change="onPaymentTypeChange()" class="form-control" required>
                                <option value="bank">Bank (BBM)</option>
                                <option value="kas">Kas (BKM)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Metode Pembayaran <span style="color:var(--danger);">*</span></label>
                            <select name="method" x-model="method" class="form-control" required>
                                <option value="transfer" x-show="paymentType === 'bank'">Bank Transfer (BBM)</option>
                                <option value="cek" x-show="paymentType === 'bank'">Cek Bank (BBM)</option>
                                <option value="cash" x-show="paymentType === 'kas'">Kas / Tunai (BKM)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Akun Kas / Bank (COA) <span style="color:var(--danger);">*</span></label>
                        <x-searchable-select 
                            name="chart_of_account_id" 
                            :options="$coas->map(fn($c) => [
                                'id' => $c->id, 
                                'name' => '[' . $c->code . '] ' . $c->name . ($c->is_cash_bank ? ' (Kas & Bank)' : ''), 
                                'subtext' => $c->code
                            ])" 
                            :value="old('chart_of_account_id', '')" 
                            placeholder="-- Pilih Akun Kas / Bank (COA) --" 
                            search-placeholder="Ketik kode atau nama akun COA..." 
                            required 
                        />
                        <small style="color:var(--text-secondary); font-size:11.5px; display:block; margin-top:4px;">
                            Akun COA ini akan didebitkan pada Jurnal Penerimaan Kas/Bank otomatis.
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">No. Referensi / Bukti Transfer</label>
                        <input type="text" name="reference_number" value="{{ old('reference_number') }}" class="form-control" placeholder="cth: TRF-BCA-123456">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="1" placeholder="Catatan penerimaan...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Ringkasan Piutang & Sisa Card --}}
            <div class="card">
                <div class="card-header"><h3>Status Piutang & Sisa</h3></div>
                <div class="card-body">
                    <template x-if="selectedInvoice">
                        <div style="display:flex; flex-direction:column; gap:12px; font-size:14px;">
                            <div>
                                <div style="font-size:12px; color:var(--text-secondary);">Customer</div>
                                <div style="font-weight:600;" x-text="selectedInvoice.sales_order?.customer?.name || '-'"></div>
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
                                <span>Sudah Diterima Lalu</span>
                                <span style="font-weight:600;" x-text="'Rp ' + formatNum(selectedInvoice.total_paid || 0)"></span>
                            </div>
                            <div style="display:flex; justify-content:space-between; color:var(--danger); font-size:15px; font-weight:700;">
                                 <span>Sisa Piutang Saat Ini</span>
                                 <span x-text="'Rp ' + formatNum(maxAmount)"></span>
                            </div>
                            <div style="display:flex; justify-content:space-between; color:var(--primary); font-size:14.5px; font-weight:600;">
                                <span>Yang Diterima Sekarang</span>
                                <span x-text="amount > 0 ? 'Rp ' + formatNum(amount) : 'Rp 0'"></span>
                            </div>
                            <hr style="border:none; border-top:1px dashed var(--border);">

                            {{-- Simulasi Sisa Piutang --}}
                            <div style="display:flex; justify-content:space-between; font-size:14px;">
                                <span style="font-weight:600;">Sisa Piutang Nanti</span>
                                <span :style="simulatedRemaining <= 0 && amount > 0 ? 'color:var(--success); font-weight:700; font-size:16px;' : 'color:var(--danger); font-weight:700; font-size:15px;'" x-text="'Rp ' + formatNum(simulatedRemaining)"></span>
                            </div>

                            <template x-if="simulatedRemaining <= 0 && amount > 0">
                                <span class="badge badge-success" style="align-self:center; margin-top:8px; padding:6px 14px; font-size:12.5px;">
                                    <i class="fa-solid fa-circle-check"></i> Status Tagihan: Lunas Penuh
                                </span>
                            </template>
                        </div>
                    </template>
                    <template x-if="!selectedInvoice">
                        <div style="color:var(--text-secondary); font-size:13px; text-align:center; padding:30px 0;">
                            <i class="fa-solid fa-receipt" style="font-size:32px; display:block; margin-bottom:8px; opacity:0.3;"></i>
                            Pilih invoice penjualan untuk melihat ringkasan sisa piutang.
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px;">
            <a href="{{ route('sales.payments.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
            <div>
                <button type="submit" class="btn btn-primary" style="padding: 9px 20px; font-weight: 600;">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Draft Penerimaan
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
const coasData = @json($coas);
const defaultKasCoa = coasData.find(c => c.name.toLowerCase().includes('kas') || c.code === '1-1100');
const defaultBankCoa = coasData.find(c => c.name.toLowerCase().includes('bank') && c.code !== '1-1100') || coasData.find(c => c.code === '1-1110');

function paymentForm() {
    return {
        selectedInvoiceId: initialInvoiceId,
        selectedInvoice: null,
        amount: null,
        paymentType: "{{ old('payment_type', 'bank') }}",
        method: "{{ old('method', 'transfer') }}",
        selectedCoaId: "{{ old('chart_of_account_id', '') }}" || (defaultBankCoa ? defaultBankCoa.id : ''),

        init() {
            if (this.selectedInvoiceId) {
                this.onInvoiceChange();
            }
            this.$nextTick(() => {
                if (this.selectedCoaId) {
                    window.dispatchEvent(new CustomEvent('set-selected', { detail: { targetName: 'chart_of_account_id', value: this.selectedCoaId } }));
                } else {
                    this.onPaymentTypeChange();
                }
            });
        },

        onPaymentTypeChange() {
            if (this.paymentType === 'kas') {
                this.method = 'cash';
                if (defaultKasCoa) {
                    this.selectedCoaId = defaultKasCoa.id;
                    window.dispatchEvent(new CustomEvent('set-selected', { detail: { targetName: 'chart_of_account_id', value: defaultKasCoa.id } }));
                }
            } else {
                if (this.method === 'cash') this.method = 'transfer';
                if (defaultBankCoa) {
                    this.selectedCoaId = defaultBankCoa.id;
                    window.dispatchEvent(new CustomEvent('set-selected', { detail: { targetName: 'chart_of_account_id', value: defaultBankCoa.id } }));
                }
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

        get isPaymentInvalid() {
            return !this.selectedInvoice || !this.amount || this.amount <= 0 || this.amount > this.maxAmount;
        },

        validateForm(e) {
            if (!this.selectedInvoice) {
                alert('Pilih Invoice Penjualan terlebih dahulu.');
                e.preventDefault();
                return false;
            }
            if (!this.amount || this.amount <= 0) {
                alert('Ketik nominal yang diterima dari customer.');
                e.preventDefault();
                return false;
            }
            if (this.amount > this.maxAmount) {
                alert('Nominal penerimaan melebihi sisa piutang (Maks: Rp ' + this.formatNum(this.maxAmount) + ').');
                e.preventDefault();
                return false;
            }
            return true;
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
