@extends('layouts.app')
@section('title', 'Edit Pembayaran Hutang - ' . ($payment->reference_number ?: 'Ref #' . $payment->id))
@section('page-title', 'Edit Pembayaran Hutang Supplier')

@section('content')
<div class="animate-in" x-data="paymentEditForm()">
    <x-page-header 
        :title="'Edit Pembayaran Hutang: ' . ($payment->reference_number ?: 'Ref #' . $payment->id)" 
        subtitle="Perbarui nominal atau data transaksi pembayaran hutang supplier"
        :back-url="route('purchase.payments.show', $payment)"
    />

    <form method="POST" action="{{ route('purchase.payments.update', $payment) }}" @submit="validateForm($event)">
        @csrf
        @method('PUT')

        <div class="grid grid-3" style="margin-bottom:20px;">
            <div class="card" style="grid-column:span 2;">
                <div class="card-header"><h3>Form Edit Pembayaran</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Invoice Pembelian Terkait</label>
                        <div style="padding:10px 14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <span style="font-weight:700; color:var(--primary);">{{ $invoice->invoice_number }}</span>
                                <span style="color:var(--text-secondary); font-size:13px; margin-left:6px;">— {{ $invoice->purchaseOrder->supplier->name ?? '-' }}</span>
                            </div>
                            <a href="{{ route('purchase.invoices.show', $invoice) }}" target="_blank" class="btn btn-secondary btn-sm" style="font-size:11px; padding:2px 8px;">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i> Lihat Invoice
                            </a>
                        </div>
                    </div>

                    <div class="form-row form-row-4">
                        <div class="form-group">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.375rem;">
                                <label class="form-label" style="margin-bottom:0; width:auto;">Nominal Bayar (Rp) <span style="color:var(--danger);">*</span></label>
                                <button type="button" class="btn btn-secondary btn-sm" @click="payFull()" style="font-size:10px; padding:1px 6px; height:20px; line-height:1;" title="Isi otomatis sisa hutang maksimal">
                                    <i class="fa-solid fa-bolt"></i> Sisa Lunas
                                </button>
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
                                    <i class="fa-solid fa-circle-exclamation"></i> Melebihi batas maksimal tagihan!
                                </div>
                            </template>
                            <template x-if="amount !== null && amount !== '' && amount <= 0">
                                <div style="color:var(--danger); font-size:11px; font-weight:600; margin-top:4px;">
                                    <i class="fa-solid fa-circle-exclamation"></i> Nominal harus > 0!
                                </div>
                            </template>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Metode Pembayaran <span style="color:var(--danger);">*</span></label>
                            <select name="method" class="form-control" required>
                                <option value="transfer" {{ old('method', $payment->method) === 'transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="cash" {{ old('method', $payment->method) === 'cash' ? 'selected' : '' }}>Kas / Tunai</option>
                                <option value="giro" {{ old('method', $payment->method) === 'giro' ? 'selected' : '' }}>Giro</option>
                                <option value="cek" {{ old('method', $payment->method) === 'cek' ? 'selected' : '' }}>Cek Bank</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tanggal Bayar <span style="color:var(--danger);">*</span></label>
                            <input type="date" name="payment_date" value="{{ old('payment_date', $payment->payment_date ? $payment->payment_date->format('Y-m-d') : date('Y-m-d')) }}" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">No. Referensi / Bukti</label>
                            <input type="text" name="reference_number" value="{{ old('reference_number', $payment->reference_number) }}" class="form-control" placeholder="TRF-BCA-98124">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="1" placeholder="Catatan pembayaran...">{{ old('notes', $payment->notes) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Ringkasan Tagihan & Sisa Card --}}
            <div class="card">
                <div class="card-header"><h3>Status Tagihan & Sisa</h3></div>
                <div class="card-body">
                    <div style="display:flex; flex-direction:column; gap:12px; font-size:14px;">
                        <div>
                            <div style="font-size:12px; color:var(--text-secondary);">Supplier</div>
                            <div style="font-weight:600;">{{ $invoice->purchaseOrder->supplier->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size:12px; color:var(--text-secondary);">Jatuh Tempo</div>
                            <div style="font-weight:600;">{{ $invoice->due_date ? $invoice->due_date->format('d F Y') : '-' }}</div>
                        </div>
                        <hr style="border:none; border-top:1px solid var(--border);">
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:var(--text-secondary);">Total Tagihan Efektif</span>
                            <span style="font-weight:600;">Rp {{ number_format($invoice->effective_total_amount, 0, ',', '.') }}</span>
                        </div>
                        @if($invoice->total_reversed_amount > 0)
                            <div style="display:flex; justify-content:space-between; color:var(--danger);">
                                <span>Pengurang Retur</span>
                                <span style="font-weight:600;">- Rp {{ number_format($invoice->total_reversed_amount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        @if($otherPaid > 0)
                            <div style="display:flex; justify-content:space-between; color:var(--success);">
                                <span>Pembayaran Lain Lalu</span>
                                <span style="font-weight:600;">Rp {{ number_format($otherPaid, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div style="display:flex; justify-content:space-between; color:var(--danger); font-size:14.5px; font-weight:700;">
                            <span>Maksimal Bayar Sisa</span>
                            <span>Rp {{ number_format($maxAllowed, 0, ',', '.') }}</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; color:var(--primary); font-size:14.5px; font-weight:600;">
                            <span>Nominal Pembayaran Ini</span>
                            <span x-text="amount > 0 ? 'Rp ' + formatNum(amount) : 'Rp 0'"></span>
                        </div>
                        <hr style="border:none; border-top:1px dashed var(--border);">
                        
                        {{-- Simulasi Sisa Tagihan --}}
                        <div style="display:flex; justify-content:space-between; font-size:14px;">
                            <span style="font-weight:600;">Sisa Hutang Akhir</span>
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
                                    <i class="fa-solid fa-clock-rotate-left"></i> Status Tagihan: Cicilan
                                </span>
                            </template>
                            <template x-if="amount >= maxAmount && amount > 0">
                                <span class="badge badge-done" style="font-size:12px; padding:6px 12px; display:inline-block;">
                                    <i class="fa-solid fa-circle-check"></i> Status Tagihan: Lunas Penuh
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <x-form-actions 
            :cancel-url="route('purchase.payments.show', $payment)" 
            submit-label="Simpan Perubahan & Update Jurnal" 
            submit-icon="fa-solid fa-save"
        />
    </form>
</div>
@endsection

@push('scripts')
<script>
const maxAmountVal = {{ (float) $maxAllowed }};
const initialAmount = {{ (float) old('amount', $payment->amount) }};

function paymentEditForm() {
    return {
        amount: initialAmount,
        maxAmount: maxAmountVal,

        payFull() {
            this.amount = this.maxAmount;
        },

        get simulatedRemaining() {
            const pay = Math.max(0, Number(this.amount) || 0);
            return Math.max(0, this.maxAmount - pay);
        },

        validateForm(e) {
            if (!this.amount || this.amount <= 0) {
                alert('Ketik nominal pembayaran yang valid (> 0).');
                e.preventDefault();
                return false;
            }
            if (this.amount > this.maxAmount) {
                alert('Nominal pembayaran melebihi batas sisa hutang (Maks: Rp ' + this.formatNum(this.maxAmount) + ').');
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
