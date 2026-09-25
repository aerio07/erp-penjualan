@extends('layouts.app')
@section('title', 'Edit Debet Note - ' . $debitNote->dn_number)
@section('page-title', 'Edit Debet Note')

@section('content')
<div class="animate-in" x-data="debitNoteEditForm()">
    <x-page-header :title="'Edit Debet Note: ' . $debitNote->dn_number" subtitle="Ubah penyesuaian potongan hutang supplier (AP) atau piutang pelanggan (AR)" :back-url="route('accounting.debit-notes.index')" />

    @if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:20px;">
        <strong><i class="fa-solid fa-triangle-exclamation"></i> Terdapat kesalahan input:</strong>
        <ul style="margin:8px 0 0 16px; padding:0;">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('accounting.debit-notes.update', $debitNote) }}" id="dn-form"
          @keydown.enter="if ($event.target.tagName === 'INPUT') { $event.preventDefault(); $event.target.blur(); }"
          @submit="beforeSubmit($event)">
        @csrf
        @method('PUT')

        {{-- Card Informasi Header --}}
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h3>Informasi Debet Note</h3>
                <span class="badge badge-secondary">Draft</span>
            </div>
            <div class="card-body">
                <div class="form-row form-row-2" style="gap:24px;">
                    {{-- Kolom Kiri --}}
                    <div style="display:flex; flex-direction:column; gap:14px;">
                        <div class="form-group">
                            <label class="form-label">No. Debet Note</label>
                            <input type="text" class="form-control" value="{{ $debitNote->dn_number }}" readonly style="background:#f8fafc; font-family:monospace; font-weight:700; color:var(--primary);">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tanggal Transaksi <span style="color:var(--danger);">*</span></label>
                            <input type="date" name="dn_date" class="form-control" value="{{ old('dn_date', $debitNote->dn_date?->format('Y-m-d')) }}" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tipe Debet Note</label>
                            <input type="text" class="form-control" value="{{ $debitNote->dn_type }} ({{ $debitNote->dn_type === 'AP' ? 'Potong Hutang Vendor' : 'Potong Piutang Customer' }})" readonly style="background:#f8fafc; font-weight:600;">
                            <input type="hidden" name="dn_type" value="{{ $debitNote->dn_type }}">
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                {{ $debitNote->dn_type === 'AP' ? 'Supplier' : 'Customer' }}
                            </label>
                            <input type="text" class="form-control" value="{{ $debitNote->partner_name }}" readonly style="background:#f8fafc; font-weight:600;">
                            @if($debitNote->dn_type === 'AP')
                                <input type="hidden" name="supplier_id" value="{{ $debitNote->supplier_id }}">
                            @else
                                <input type="hidden" name="customer_id" value="{{ $debitNote->customer_id }}">
                            @endif
                        </div>
                    </div>

                    {{-- Kolom Kanan --}}
                    <div style="display:flex; flex-direction:column; gap:14px;">
                        <div class="form-group">
                            <label class="form-label">Pengaturan Pajak</label>
                            <div style="display:flex; gap:10px;">
                                <select name="tax_type" x-model="taxType" @change="onTaxChange()" class="form-control" style="width:50%;">
                                    <option value="NON TAX">NON TAX</option>
                                    <option value="PPN 11%">PPN 11%</option>
                                    <option value="PPN 12%">PPN 12%</option>
                                </select>
                                <div style="position:relative; width:50%;">
                                    <input type="number" step="0.01" name="tax_rate" x-model="taxRate" @input="recalcTotals()" class="form-control" placeholder="0" style="padding-right:24px;">
                                    <span style="position:absolute; right:10px; top:8px; color:var(--text-secondary); font-size:12px;">%</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Total Potongan (DN)</label>
                            <input type="text" :value="'Rp ' + formatAmount(totalAmount)" readonly class="form-control" style="background:#f8fafc; font-weight:700; color:var(--primary); font-size:15px; text-align:right;">
                            <input type="hidden" name="total_amount" :value="totalAmount">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nilai Pajak</label>
                            <input type="text" :value="'Rp ' + formatAmount(taxAmount)" readonly class="form-control" style="background:#f8fafc; color:var(--text-secondary); text-align:right;">
                            <input type="hidden" name="tax_amount" :value="taxAmount">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Keterangan</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Contoh: Potong selisih 2% biaya kirim">{{ old('notes', $debitNote->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detail Debet Note (Tabs & Tables) --}}
        <div class="card" style="padding: 0 !important; overflow: hidden; margin-bottom: 24px;">

            {{-- Tab Switcher --}}
            <div class="card-header" style="padding: 12px 20px; margin: 0; border-bottom: 1px solid #e2e8f0;">
                <div style="display: flex; align-items: center; gap: 0;">
                    <button type="button" @click="activeTab = 'invoice'"
                            :class="activeTab === 'invoice' ? 'btn btn-primary btn-sm' : 'btn btn-light btn-sm'"
                            style="border-radius: 6px 0 0 6px; font-size: 12px; padding: 6px 16px; font-weight: 600; border-right: none;">
                        Detail Faktur Dipotong
                    </button>
                    <button type="button" @click="activeTab = 'account'"
                            :class="activeTab === 'account' ? 'btn btn-primary btn-sm' : 'btn btn-light btn-sm'"
                            style="border-radius: 0 6px 6px 0; font-size: 12px; padding: 6px 16px; font-weight: 600;">
                        Sub Detail Akun (COA)
                    </button>
                </div>

                {{-- Right side action --}}
                <div>
                    <template x-if="activeTab === 'invoice'">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 12px; color: #64748b;">
                                Faktur Dipilih: <strong style="color: #03193c;" x-text="selectedInvoices.length"></strong>
                            </span>
                            <button type="button" class="btn btn-primary btn-sm" @click="openInvoiceModal()"
                                    style="border-radius: 6px; font-size: 12px; padding: 6px 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-list-check"></i>
                                <span>Pilih Faktur dari List</span>
                            </button>
                        </div>
                    </template>
                    <template x-if="activeTab === 'account'">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <select x-model="selectedInvoiceForAccount" class="form-control form-control-sm" style="width: auto; min-width: 170px; height: 32px; font-size: 12px; border-radius: 6px;">
                                <option value="">-- Pilih Faktur --</option>
                                <template x-for="inv in selectedInvoices" :key="inv.invoice_number">
                                    <option :value="inv.invoice_number" x-text="inv.invoice_number"></option>
                                </template>
                            </select>
                            <button type="button" class="btn btn-primary btn-sm" @click="addAccountLine()"
                                    style="border-radius: 6px; font-size: 12px; padding: 6px 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-plus"></i>
                                <span>Tambah Baris Akun</span>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- TAB 1: Detail Faktur --}}
            <div x-show="activeTab === 'invoice'">
                <div class="table-responsive" style="margin-bottom: 0; border: none; border-radius: 0; overflow-x: auto;">
                    <table class="erp-table" style="width: 100%; min-width: 950px; margin: 0; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center; white-space: nowrap;">No</th>
                                <th style="min-width: 150px; white-space: nowrap;">No. Invoice</th>
                                <th style="min-width: 130px; white-space: nowrap;">No. SJ / Reff</th>
                                <th style="min-width: 100px; text-align: center; white-space: nowrap;">Tanggal</th>
                                <th style="min-width: 130px; text-align: right; white-space: nowrap;">Sisa Tagihan</th>
                                <th style="min-width: 120px; text-align: right; white-space: nowrap;">Dibayar</th>
                                <th style="min-width: 170px; text-align: right; white-space: nowrap;">Potongan DN <span style="color: #dc2626;">*</span></th>
                                <th style="min-width: 130px; text-align: right; white-space: nowrap;">Sisa Akhir</th>
                                <th style="width: 60px; text-align: center; white-space: nowrap;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(inv, idx) in selectedInvoices" :key="inv.invoice_number">
                                <tr>
                                    <td style="text-align: center; color: #64748b;" x-text="idx + 1"></td>
                                    <td style="font-weight: 700; font-family: monospace; color: #03193c;">
                                        <span x-text="inv.invoice_number"></span>
                                        <input type="hidden" :name="`invoices[${idx}][invoice_id]`" :value="inv.invoice_id || inv.id">
                                        <input type="hidden" :name="`invoices[${idx}][invoice_number]`" :value="inv.invoice_number">
                                        <input type="hidden" :name="`invoices[${idx}][delivery_order_number]`" :value="inv.sj_number">
                                        <input type="hidden" :name="`invoices[${idx}][invoice_date]`" :value="inv.invoice_date">
                                    </td>
                                    <td style="color: #64748b;" x-text="inv.sj_number || '-'"></td>
                                    <td style="text-align: center;" x-text="inv.invoice_date_formatted || '-'"></td>
                                    <td style="text-align: right; font-weight: 600;">
                                        Rp <span x-text="formatAmount(inv.remaining_amount)"></span>
                                        <input type="hidden" :name="`invoices[${idx}][remaining_amount]`" :value="inv.remaining_amount">
                                    </td>
                                    <td style="text-align: right; color: #64748b;">
                                        Rp <span x-text="formatAmount(inv.paid_amount)"></span>
                                        <input type="hidden" :name="`invoices[${idx}][paid_amount]`" :value="inv.paid_amount">
                                    </td>
                                    <td style="text-align: right;">
                                        <input type="hidden" :name="`invoices[${idx}][dn_amount]`" :value="inv.dn_amount">
                                        <input type="text"
                                               x-model="inv.formatted_dn_amount"
                                               @focus="$event.target.select()"
                                               @input="formatDnInput($event, inv)"
                                               @blur="onDnBlur($event, inv)"
                                               @keydown.enter.prevent="$event.target.blur()"
                                               class="form-control"
                                               style="text-align: right; font-weight: 700; color: #03193c; height: 35px; border-radius: 6px;" required>
                                    </td>
                                    <td style="text-align: right; font-weight: 700;">
                                        Rp <span x-text="formatAmount(inv.balance_amount)"></span>
                                        <input type="hidden" :name="`invoices[${idx}][balance_amount]`" :value="inv.balance_amount">
                                    </td>
                                    <td style="text-align: center;">
                                        <button type="button" class="btn btn-danger btn-sm btn-icon" @click="removeInvoice(idx)" title="Hapus">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="selectedInvoices.length === 0">
                                <td colspan="9" style="text-align: center; padding: 48px 16px; color: #64748b; background: #ffffff;">
                                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
                                        <i class="fa-regular fa-file-lines" style="font-size: 32px; color: #94a3b8;"></i>
                                        <span style="font-size: 13.5px; font-weight: 600; color: #334155;">Belum ada faktur yang dipilih</span>
                                        <span style="font-size: 12px; color: #94a3b8;">Klik tombol <strong>"Pilih Faktur dari List"</strong> di atas untuk memilih faktur yang akan dipotong.</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TAB 2: Sub Detail Akun --}}
            <div x-show="activeTab === 'account'" style="display:none;">
                <div class="table-responsive" style="margin-bottom: 0; border: none; border-radius: 0; overflow-x: auto;">
                    <table class="erp-table" style="width: 100%; min-width: 1050px; margin: 0; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="width: 50px; text-align: center; white-space: nowrap;">No</th>
                                    <th style="min-width: 140px; white-space: nowrap;">No. Invoice</th>
                                    <th style="min-width: 220px; white-space: nowrap;">Akun COA Debet <span style="color: #dc2626;">*</span></th>
                                    <th style="min-width: 220px; white-space: nowrap;">Akun COA Kredit <span style="color: #dc2626;">*</span></th>
                                    <th style="min-width: 130px; text-align: right; white-space: nowrap;">Outstanding</th>
                                    <th style="min-width: 150px; text-align: right; white-space: nowrap;">DN Amount <span style="color: #dc2626;">*</span></th>
                                    <th style="min-width: 130px; text-align: right; white-space: nowrap;">Pajak</th>
                                    <th style="min-width: 180px; white-space: nowrap;">Keterangan</th>
                                    <th style="width: 60px; text-align: center; white-space: nowrap;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(acc, idx) in accountLines" :key="idx">
                                    <tr>
                                        <td style="text-align: center; color: #64748b;" x-text="idx + 1"></td>
                                        <td style="font-family: monospace; font-weight: 600;">
                                            <span x-text="acc.invoice_number || '-'"></span>
                                            <input type="hidden" :name="`accounts[${idx}][invoice_number]`" :value="acc.invoice_number">
                                        </td>
                                        <td>
                                            <select :name="`accounts[${idx}][debit_chart_of_account_id]`" x-model="acc.debit_chart_of_account_id" class="form-control" required style="font-size: 12px; height: 35px; border-radius: 6px;">
                                                @foreach($accounts as $coa)
                                                    <option value="{{ $coa->id }}">[{{ $coa->code }}] {{ $coa->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select :name="`accounts[${idx}][credit_chart_of_account_id]`" x-model="acc.credit_chart_of_account_id" class="form-control" required style="font-size: 12px; height: 35px; border-radius: 6px;">
                                                @foreach($accounts as $coa)
                                                    <option value="{{ $coa->id }}">[{{ $coa->code }}] {{ $coa->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td style="text-align: right; color: #64748b;">
                                            Rp <span x-text="formatAmount(acc.outstanding_amount)"></span>
                                            <input type="hidden" :name="`accounts[${idx}][outstanding_amount]`" :value="acc.outstanding_amount">
                                        </td>
                                        <td style="text-align: right;">
                                            <input type="hidden" :name="`accounts[${idx}][dn_amount]`" :value="acc.dn_amount">
                                            <input type="text"
                                                   x-model="acc.formatted_dn_amount"
                                                   @focus="$event.target.select()"
                                                   @input="formatAccDnInput($event, acc)"
                                                   @blur="onAccDnBlur($event, acc)"
                                                   @keydown.enter.prevent="$event.target.blur()"
                                                   class="form-control"
                                                   style="text-align: right; font-weight: 700; height: 35px; border-radius: 6px;" required>
                                        </td>
                                        <td style="text-align: right;">
                                            <input type="hidden" :name="`accounts[${idx}][tax_amount]`" :value="acc.tax_amount">
                                            <input type="text"
                                                   x-model="acc.formatted_tax_amount"
                                                   @focus="$event.target.select()"
                                                   @input="formatAccTaxInput($event, acc)"
                                                   @blur="onAccTaxBlur($event, acc)"
                                                   @keydown.enter.prevent="$event.target.blur()"
                                                   class="form-control"
                                                   style="text-align: right; height: 35px; border-radius: 6px;">
                                        </td>
                                        <td>
                                            <input type="text" :name="`accounts[${idx}][notes]`" x-model="acc.notes" @keydown.enter.prevent="$event.target.blur()" class="form-control" placeholder="Keterangan alokasi..." style="height: 35px; border-radius: 6px;">
                                        </td>
                                        <td style="text-align: center;">
                                            <button type="button" class="btn btn-danger btn-sm btn-icon" @click="removeAccountLine(idx)" title="Hapus">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="accountLines.length === 0">
                                    <td colspan="9" style="text-align: center; padding: 40px 16px; color: #64748b; background: #ffffff;">
                                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
                                            <i class="fa-solid fa-book-open" style="font-size: 28px; color: #94a3b8;"></i>
                                            <span style="font-size: 13.5px; font-weight: 500;">Belum ada alokasi akun pembukuan.</span>
                                            <span style="font-size: 12px; color: #94a3b8;">Pilih Faktur di atas lalu klik <strong>"Tambah Baris Akun"</strong> untuk mengatur akun Debet & Kredit.</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <x-form-actions 
            :back-url="route('accounting.debit-notes.index')" 
            submit-label="Perbarui Draft" 
        />
    </form>

    {{-- Modal List Faktur yang Belum Lunas --}}
    <div x-cloak class="modal-backdrop"
         :style="showInvoiceModal ? 'display:flex; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:1000; align-items:center; justify-content:center;' : 'display:none;'">
        <div style="background:#fff; border-radius:10px; width:95%; max-width:900px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.2); overflow:hidden;">
            <div style="padding:16px 20px; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; background:#f8fafc;">
                <div>
                    <h3 style="margin:0; font-size:15px; font-weight:700;">Pilih Faktur Belum Lunas</h3>
                    <div style="font-size:12px; color:var(--text-secondary); margin-top:2px;">Centang faktur yang akan dipotong oleh Debet Note</div>
                </div>
                <button type="button" @click="showInvoiceModal = false" style="background:transparent; border:none; cursor:pointer; font-size:18px; color:var(--text-secondary);">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            {{-- Search bar --}}
            <div style="padding: 10px 20px; border-bottom: 1px solid #e2e8f0; background: #ffffff;">
                <div style="position: relative;">
                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px;"></i>
                    <input type="text" x-model="modalSearchQuery" @keydown.enter.prevent placeholder="Cari no. invoice atau no. SJ..."
                           class="form-control" style="padding-left: 36px !important; height: 36px; font-size: 12.5px; border-radius: 8px;">
                </div>
            </div>

            <div style="padding: 16px 20px; max-height: 400px; overflow-y: auto;">
                <div x-show="loadingInvoices" style="text-align:center; padding:30px; color:var(--text-secondary);">
                    <i class="fa-solid fa-spinner fa-spin" style="font-size:24px; margin-bottom:8px; display:block;"></i>
                    Memuat daftar faktur...
                </div>

                <div x-show="!loadingInvoices && availableInvoices.length === 0" style="text-align:center; padding:30px; color:var(--text-secondary);">
                    Tidak ada faktur dengan sisa tagihan untuk partner ini.
                </div>

                <div class="table-responsive" x-show="!loadingInvoices && availableInvoices.length > 0" style="margin-bottom: 0; border: 1px solid #e2e8f0; border-radius: 8px; overflow-x: auto;">
                    <table class="erp-table" style="width: 100%; margin: 0; border-collapse: collapse; font-size: 12.5px;">
                        <thead>
                            <tr>
                                <th style="width: 45px; text-align: center; white-space: nowrap;">Pilih</th>
                                <th style="white-space: nowrap;">No. Invoice</th>
                                <th style="white-space: nowrap;">No. SJ / Reff</th>
                                <th style="text-align: center; white-space: nowrap;">Tanggal</th>
                                <th style="text-align: right; white-space: nowrap;">Sisa Tagihan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="inv in filteredModalInvoices()" :key="inv.id">
                                <tr style="cursor: pointer;" @click="toggleInvoiceSelect(inv)">
                                    <td style="text-align: center;">
                                        <input type="checkbox" :checked="isInvoiceSelected(inv.id)" style="cursor: pointer; width: 16px; height: 16px;">
                                    </td>
                                    <td style="font-family: monospace; font-weight: 700; color: #03193c;" x-text="inv.invoice_number"></td>
                                    <td style="color: #64748b;" x-text="inv.sj_number || '-'"></td>
                                    <td style="text-align: center;" x-text="inv.invoice_date_formatted || '-'"></td>
                                    <td style="text-align: right; font-weight: 700; color: #dc2626;">
                                        Rp <span x-text="formatAmount(inv.remaining_amount)"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="padding:12px 20px; border-top:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; background:#f8fafc;">
                <span style="font-size:12px; color:var(--text-secondary);">
                    <strong x-text="modalSelectedIds.length"></strong> faktur dipilih
                </span>
                <div style="display:flex; gap:8px;">
                    <button type="button" class="btn btn-secondary btn-sm" @click="showInvoiceModal = false">Batal</button>
                    <button type="button" class="btn btn-primary btn-sm" @click="confirmInvoiceSelection()">Tambahkan ke List</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function debitNoteEditForm() {
    return {
        dnType: '{{ $debitNote->dn_type }}',
        selectedSupplierId: '{{ $debitNote->supplier_id }}',
        selectedCustomerId: '{{ $debitNote->customer_id }}',
        taxType: '{{ $debitNote->tax_type ?? 'NON TAX' }}',
        taxRate: {{ (float)$debitNote->tax_rate }},
        taxAmount: {{ (float)$debitNote->tax_amount }},
        totalAmount: {{ (float)$debitNote->total_amount }},
        activeTab: 'invoice',

        selectedInvoices: @json($existingInvoices),
        accountLines: @json($existingAccounts),
        selectedInvoiceForAccount: '',

        modalSearchQuery: '',
        showInvoiceModal: false,
        loadingInvoices: false,
        availableInvoices: [],
        modalSelectedIds: [],

        init() {
            this.modalSelectedIds = this.selectedInvoices.map(i => i.invoice_id || i.id);
        },

        onTaxChange() {
            if (this.taxType === 'PPN 11%') this.taxRate = 11;
            else if (this.taxType === 'PPN 12%') this.taxRate = 12;
            else this.taxRate = 0;
            this.recalcTotals();
        },

        openInvoiceModal() {
            const partnerId = this.dnType === 'AP' ? this.selectedSupplierId : this.selectedCustomerId;
            if (!partnerId) return;

            this.showInvoiceModal = true;
            this.loadingInvoices = true;
            this.modalSearchQuery = '';
            this.modalSelectedIds = this.selectedInvoices.map(i => i.invoice_id || i.id);

            fetch(`{{ route('accounting.debit-notes.invoices') }}?type=${this.dnType}&partner_id=${partnerId}`)
                .then(r => r.json())
                .then(res => {
                    const fetchedInvoices = res.invoices || [];
                    // Ensure current selected invoices are included in modal list even if remaining_amount was 0 or changed
                    const existingMap = new Map(fetchedInvoices.map(i => [i.id, i]));
                    this.selectedInvoices.forEach(inv => {
                        const targetId = inv.invoice_id || inv.id;
                        if (!existingMap.has(targetId)) {
                            fetchedInvoices.unshift({
                                id: targetId,
                                invoice_number: inv.invoice_number,
                                sj_number: inv.sj_number,
                                invoice_date: inv.invoice_date,
                                invoice_date_formatted: inv.invoice_date_formatted,
                                remaining_amount: inv.remaining_amount,
                                paid_amount: inv.paid_amount,
                            });
                        }
                    });
                    this.availableInvoices = fetchedInvoices;
                    this.loadingInvoices = false;
                })
                .catch(err => {
                    console.error(err);
                    this.loadingInvoices = false;
                });
        },

        filteredModalInvoices() {
            const q = (this.modalSearchQuery || '').toLowerCase().trim();
            if (!q) return this.availableInvoices;
            return this.availableInvoices.filter(inv => {
                return (inv.invoice_number || '').toLowerCase().includes(q)
                    || (inv.sj_number || '').toLowerCase().includes(q);
            });
        },

        isInvoiceSelected(id) {
            return this.modalSelectedIds.includes(id);
        },

        toggleInvoiceSelect(inv) {
            const idx = this.modalSelectedIds.indexOf(inv.id);
            if (idx > -1) {
                this.modalSelectedIds.splice(idx, 1);
            } else {
                this.modalSelectedIds.push(inv.id);
            }
        },

        confirmInvoiceSelection() {
            const existingMap = new Map(this.selectedInvoices.map(i => [i.invoice_id || i.id, i]));
            const nextList = [];

            this.availableInvoices.forEach(inv => {
                if (this.modalSelectedIds.includes(inv.id)) {
                    if (existingMap.has(inv.id)) {
                        nextList.push(existingMap.get(inv.id));
                    } else {
                        const defaultDnAmt = 0;
                        nextList.push({
                            id: inv.id,
                            invoice_id: inv.id,
                            invoice_number: inv.invoice_number,
                            sj_number: inv.sj_number,
                            invoice_date: inv.invoice_date,
                            invoice_date_formatted: inv.invoice_date_formatted,
                            remaining_amount: inv.remaining_amount,
                            paid_amount: inv.paid_amount,
                            dn_amount: defaultDnAmt,
                            formatted_dn_amount: '0',
                            balance_amount: inv.remaining_amount,
                        });
                    }
                }
            });

            this.selectedInvoices = nextList;
            this.showInvoiceModal = false;

            // Auto initialize default account line if empty
            if (this.accountLines.length === 0 && this.selectedInvoices.length > 0) {
                this.selectedInvoices.forEach(inv => {
                    this.createDefaultAccountLine(inv);
                });
            }

            this.recalcTotals();
        },

        formatDnInput(event, inv) {
            const input = event.target;
            const originalValue = input.value || '';
            const originalCursor = input.selectionEnd || 0;
            const digitsBeforeCursor = originalValue.slice(0, originalCursor).replace(/\D/g, '').length;

            const raw = originalValue.replace(/\D/g, '');
            if (!raw) {
                inv.dn_amount = 0;
                inv.formatted_dn_amount = '';
                this.onInvoiceAmountChange(inv);
                return;
            }

            let num = parseInt(raw, 10) || 0;
            if (num > inv.remaining_amount) num = inv.remaining_amount;
            if (num < 0) num = 0;

            inv.dn_amount = num;
            const formatted = this.formatAmount(num);
            inv.formatted_dn_amount = formatted;
            this.onInvoiceAmountChange(inv);

            this.$nextTick(() => {
                let newCursor = 0;
                let digitsSeen = 0;
                for (let i = 0; i < formatted.length; i++) {
                    if (/\d/.test(formatted[i])) {
                        digitsSeen++;
                    }
                    if (digitsSeen >= digitsBeforeCursor) {
                        newCursor = i + 1;
                        break;
                    }
                }
                if (digitsBeforeCursor === 0) newCursor = 0;
                input.setSelectionRange(newCursor, newCursor);
            });
        },

        onDnBlur(event, inv) {
            if (!inv.formatted_dn_amount || inv.formatted_dn_amount.trim() === '') {
                inv.dn_amount = 0;
                inv.formatted_dn_amount = this.formatAmount(0);
                this.onInvoiceAmountChange(inv);
            }
        },

        onInvoiceAmountChange(inv) {
            if (inv.dn_amount > inv.remaining_amount) {
                inv.dn_amount = inv.remaining_amount;
                inv.formatted_dn_amount = this.formatAmount(inv.dn_amount);
            }
            if (inv.dn_amount < 0) {
                inv.dn_amount = 0;
                inv.formatted_dn_amount = this.formatAmount(0);
            }
            inv.balance_amount = Math.max(0, inv.remaining_amount - inv.dn_amount);

            // Sync with account line
            const matchingAcc = this.accountLines.find(a => a.invoice_number === inv.invoice_number);
            if (matchingAcc) {
                matchingAcc.dn_amount = inv.dn_amount;
                matchingAcc.formatted_dn_amount = this.formatAmount(inv.dn_amount);
            }

            this.recalcTotals();
        },

        formatAccDnInput(event, acc) {
            const input = event.target;
            const originalValue = input.value || '';
            const originalCursor = input.selectionEnd || 0;
            const digitsBeforeCursor = originalValue.slice(0, originalCursor).replace(/\D/g, '').length;

            const raw = originalValue.replace(/\D/g, '');
            if (!raw) {
                acc.dn_amount = 0;
                acc.formatted_dn_amount = '';
                this.recalcTotals();
                return;
            }

            let num = parseInt(raw, 10) || 0;
            acc.dn_amount = num;
            const formatted = this.formatAmount(num);
            acc.formatted_dn_amount = formatted;
            this.recalcTotals();

            this.$nextTick(() => {
                let newCursor = 0;
                let digitsSeen = 0;
                for (let i = 0; i < formatted.length; i++) {
                    if (/\d/.test(formatted[i])) {
                        digitsSeen++;
                    }
                    if (digitsSeen >= digitsBeforeCursor) {
                        newCursor = i + 1;
                        break;
                    }
                }
                if (digitsBeforeCursor === 0) newCursor = 0;
                input.setSelectionRange(newCursor, newCursor);
            });
        },

        onAccDnBlur(event, acc) {
            if (!acc.formatted_dn_amount || acc.formatted_dn_amount.trim() === '') {
                acc.dn_amount = 0;
                acc.formatted_dn_amount = this.formatAmount(0);
                this.recalcTotals();
            }
        },

        formatAccTaxInput(event, acc) {
            const input = event.target;
            const originalValue = input.value || '';
            const originalCursor = input.selectionEnd || 0;
            const digitsBeforeCursor = originalValue.slice(0, originalCursor).replace(/\D/g, '').length;

            const raw = originalValue.replace(/\D/g, '');
            if (!raw) {
                acc.tax_amount = 0;
                acc.formatted_tax_amount = '';
                this.recalcTotals();
                return;
            }

            let num = parseInt(raw, 10) || 0;
            acc.tax_amount = num;
            const formatted = this.formatAmount(num);
            acc.formatted_tax_amount = formatted;
            this.recalcTotals();

            this.$nextTick(() => {
                let newCursor = 0;
                let digitsSeen = 0;
                for (let i = 0; i < formatted.length; i++) {
                    if (/\d/.test(formatted[i])) {
                        digitsSeen++;
                    }
                    if (digitsSeen >= digitsBeforeCursor) {
                        newCursor = i + 1;
                        break;
                    }
                }
                if (digitsBeforeCursor === 0) newCursor = 0;
                input.setSelectionRange(newCursor, newCursor);
            });
        },

        onAccTaxBlur(event, acc) {
            if (!acc.formatted_tax_amount || acc.formatted_tax_amount.trim() === '') {
                acc.tax_amount = 0;
                acc.formatted_tax_amount = '0';
                this.recalcTotals();
            }
        },

        removeInvoice(idx) {
            const removed = this.selectedInvoices.splice(idx, 1)[0];
            if (removed) {
                this.accountLines = this.accountLines.filter(a => a.invoice_number !== removed.invoice_number);
            }
            this.recalcTotals();
        },

        createDefaultAccountLine(inv) {
            const defaultDebitId = this.getDefaultDebitCoaId();
            const defaultCreditId = this.getDefaultCreditCoaId();

            this.accountLines.push({
                invoice_number: inv.invoice_number,
                debit_chart_of_account_id: defaultDebitId,
                credit_chart_of_account_id: defaultCreditId,
                outstanding_amount: inv.remaining_amount,
                dn_amount: inv.dn_amount,
                formatted_dn_amount: this.formatAmount(inv.dn_amount),
                tax_amount: 0,
                formatted_tax_amount: '0',
                notes: 'Potongan DN ' + inv.invoice_number,
            });
        },

        addAccountLine() {
            if (!this.selectedInvoiceForAccount) {
                alert('Pilih nomor faktur dari dropdown terlebih dahulu.');
                return;
            }

            const inv = this.selectedInvoices.find(i => i.invoice_number === this.selectedInvoiceForAccount);
            const remaining = inv ? inv.remaining_amount : 0;
            const dnAmt = inv ? inv.dn_amount : 0;

            this.accountLines.push({
                invoice_number: this.selectedInvoiceForAccount,
                debit_chart_of_account_id: this.getDefaultDebitCoaId(),
                credit_chart_of_account_id: this.getDefaultCreditCoaId(),
                outstanding_amount: remaining,
                dn_amount: dnAmt,
                formatted_dn_amount: this.formatAmount(dnAmt),
                tax_amount: 0,
                formatted_tax_amount: '0',
                notes: '',
            });
        },

        removeAccountLine(idx) {
            this.accountLines.splice(idx, 1);
            this.recalcTotals();
        },

        getDefaultDebitCoaId() {
            @php
                $utangCoa = $accounts->first(fn($a) => str_contains(strtolower($a->name), 'utang usaha') || str_contains($a->code, '2110'));
                $piutangCoa = $accounts->first(fn($a) => str_contains(strtolower($a->name), 'piutang usaha') || str_contains($a->code, '1120'));
            @endphp
            if (this.dnType === 'AP') {
                return {{ $utangCoa?->id ?? ($accounts->first()?->id ?? 1) }};
            }
            return {{ $piutangCoa?->id ?? ($accounts->first()?->id ?? 1) }};
        },

        getDefaultCreditCoaId() {
            @php
                $pphCoa = $accounts->first(fn($a) => str_contains(strtolower($a->name), 'pph') || str_contains(strtolower($a->name), 'pendapatan lain') || str_contains(strtolower($a->name), 'potongan'));
            @endphp
            return {{ $pphCoa?->id ?? ($accounts->skip(1)->first()?->id ?? 2) }};
        },

        recalcTotals() {
            const sumDnAmt = this.selectedInvoices.reduce((acc, curr) => acc + (parseFloat(curr.dn_amount) || 0), 0);
            this.totalAmount = sumDnAmt;
            if (this.taxRate > 0) {
                this.taxAmount = (this.totalAmount * (this.taxRate / 100));
            } else {
                this.taxAmount = 0;
            }
        },

        formatAmount(val) {
            return new Intl.NumberFormat('id-ID').format(val || 0);
        },

        beforeSubmit(e) {
            if (this.selectedInvoices.length === 0) {
                e.preventDefault();
                alert('Harap pilih minimal 1 faktur pada Tab "1. Detail Faktur Dipotong".');
                this.activeTab = 'invoice';
                return;
            }

            if (this.accountLines.length === 0) {
                e.preventDefault();
                alert('Harap tentukan minimal 1 baris alokasi akun pada Tab "2. Sub Detail Akun (COA)".');
                this.activeTab = 'account';
                return;
            }

            const hasZeroDn = this.selectedInvoices.some(i => (parseFloat(i.dn_amount) || 0) <= 0);
            if (hasZeroDn) {
                e.preventDefault();
                alert('Harap masukkan nominal Potongan DN (lebih besar dari 0) pada faktur yang dipilih.');
                this.activeTab = 'invoice';
                return;
            }
        }
    };
}
</script>
@endpush
@endsection
