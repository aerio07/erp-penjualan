@extends('layouts.app')
@section('title', 'Edit Jurnal - ' . $journal->entry_number)
@section('page-title', 'Edit Jurnal Umum')

@section('content')
<div class="animate-in" x-data="journalEditForm()">
    <x-page-header 
        :title="'Edit Jurnal: ' . $journal->entry_number" 
        subtitle="Edit rincian akun dan nominal jurnal umum draft"
        :back-url="route('accounting.journals.index')"
    />

    @if(session('error'))
    <div class="alert alert-danger" style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size: 18px;"></i>
        <div>{{ session('error') }}</div>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger" style="margin-bottom: 20px;">
        <div style="font-weight: 700; margin-bottom: 6px;">Terdapat kesalahan pengisian data:</div>
        <ul style="margin: 0; padding-left: 20px;">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('accounting.journals.update', $journal) }}" @submit="validateForm($event)">
        @csrf
        @method('PUT')

        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <h3>Informasi Entri Jurnal</h3>
                <span class="badge badge-draft">Draft</span>
            </div>
            <div class="card-body">
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Tanggal Transaksi <span style="color:var(--danger);">*</span></label>
                        <input type="date" name="entry_date" value="{{ old('entry_date', $journal->entry_date ? $journal->entry_date->format('Y-m-d') : date('Y-m-d')) }}" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Keterangan Transaksi <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="description" value="{{ old('description', $journal->description) }}" class="form-control" required placeholder="Contoh: Beban sewa gedung kantor bulan September">
                    </div>
                </div>
            </div>
        </div>

        {{-- Rows Table --}}
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3>Rincian Akun (Debit & Kredit)</h3>
                <button type="button" class="btn btn-primary btn-sm" @click="addRow()">
                    <i class="fa-solid fa-plus"></i> Tambah Baris Akun
                </button>
            </div>
            <div class="table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th style="min-width: 320px;">Akun COA <span style="color:var(--danger);">*</span></th>
                            <th>Keterangan Baris (Opsional)</th>
                            <th style="width:180px; text-align:right;">Debit (Rp)</th>
                            <th style="width:180px; text-align:right;">Kredit (Rp)</th>
                            <th style="width:50px; text-align:center;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, idx) in rows" :key="idx">
                            <tr>
                                {{-- Searchable COA Select --}}
                                <td style="vertical-align: top !important;">
                                    <div style="position: relative; width: 100%;" 
                                         x-data="{ openCoa: false, coaSearch: '' }" 
                                         @click.outside="openCoa = false" 
                                         @keydown.escape.window="openCoa = false">
                                        <input type="hidden" :name="`lines[${idx}][chart_of_account_id]`" :value="row.chart_of_account_id" required>
                                        
                                        <div 
                                            @click="openCoa = !openCoa; if(openCoa) $nextTick(() => $refs.coaSearchInput?.focus())"
                                            class="form-control searchable-select-trigger"
                                            style="display: flex !important; flex-wrap: nowrap !important; align-items: center !important; justify-content: space-between !important; cursor: pointer; min-height: 38px !important; height: 38px !important; background: #fff; padding: 0 0.75rem !important; border-radius: 0.5rem; box-sizing: border-box !important;"
                                        >
                                            <span x-text="accountsMap[row.chart_of_account_id] ? accountsMap[row.chart_of_account_id].label : '-- Cari & Pilih Akun COA --'"
                                                  :class="!row.chart_of_account_id ? 'text-slate-400' : 'text-slate-900 font-semibold'"
                                                  style="white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; padding-right: 6px; flex: 1 1 auto; min-width: 0; font-size: 13px;"
                                            ></span>
                                            <i class="fa-solid fa-chevron-down text-slate-400" :style="openCoa ? 'transform: rotate(180deg);' : ''" style="font-size: 11px; transition: transform 0.2s; flex-shrink: 0; margin-left: auto;"></i>
                                        </div>

                                        {{-- Dropdown Menu --}}
                                        <div 
                                            x-show="openCoa" 
                                            x-transition:enter="transition ease-out duration-150"
                                            x-transition:enter-start="opacity-0 translate-y-1"
                                            x-transition:enter-end="opacity-100 translate-y-0"
                                            x-transition:leave="transition ease-in duration-100"
                                            x-transition:leave-start="opacity-100 translate-y-0"
                                            x-transition:leave-end="opacity-0 translate-y-1"
                                            style="position: absolute; top: calc(100% + 4px); left: 0; width: 100%; min-width: 340px; z-index: 1060; background: #fff; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); border: 1px solid #e2e8f0; max-height: 280px; display: flex; flex-direction: column; overflow: hidden;"
                                            x-cloak
                                        >
                                            <div style="padding: 6px 8px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; position: relative;">
                                                <div style="position: relative; display: flex; align-items: center;">
                                                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 12px; pointer-events: none; z-index: 2;"></i>
                                                    <input 
                                                        type="text" 
                                                        x-ref="coaSearchInput"
                                                        x-model="coaSearch" 
                                                        placeholder="Cari kode akun atau nama akun..." 
                                                        class="form-control searchable-select-search-input" 
                                                        style="width: 100% !important; padding-left: 30px !important; padding-right: 24px !important; font-size: 12px !important; height: 32px !important; border-radius: 6px !important;"
                                                        @click.stop
                                                    >
                                                    <template x-if="coaSearch">
                                                        <button 
                                                            type="button" 
                                                            @click="coaSearch = ''; $refs.coaSearchInput.focus()"
                                                            style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 2px 4px; font-size: 11px; z-index: 2;"
                                                        >
                                                            <i class="fa-solid fa-circle-xmark"></i>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                            <div style="overflow-y: auto; max-height: 220px;">
                                                <template x-for="a in accountsList.filter(acc => !coaSearch || acc.label.toLowerCase().includes(coaSearch.toLowerCase()) || acc.code.toLowerCase().includes(coaSearch.toLowerCase()))" :key="a.id">
                                                    <div 
                                                        @click="row.chart_of_account_id = a.id; openCoa = false; coaSearch = '';"
                                                        :class="{ 'bg-blue-50 font-semibold text-blue-700': String(row.chart_of_account_id) === String(a.id) }"
                                                        style="padding: 8px 12px; cursor: pointer; font-size: 13px; border-bottom: 1px solid #f8fafc; display: flex; justify-content: space-between; align-items: center;"
                                                        onmouseover="this.style.backgroundColor='#f1f5f9'"
                                                        onmouseout="if(String(row.chart_of_account_id) !== String(a.id)) this.style.backgroundColor='transparent'"
                                                    >
                                                        <div>
                                                            <span style="font-family: monospace; font-weight: 700; color: var(--primary);" x-text="a.code"></span>
                                                            <span style="margin-left: 6px; color: #1e293b;" x-text="a.name"></span>
                                                        </div>
                                                        <span class="badge" style="font-size: 10px; background: #e0f2fe; color: #0369a1;" x-text="a.type"></span>
                                                    </div>
                                                </template>
                                                <div x-show="accountsList.filter(acc => !coaSearch || acc.label.toLowerCase().includes(coaSearch.toLowerCase()) || acc.code.toLowerCase().includes(coaSearch.toLowerCase())).length === 0" style="padding: 16px; text-align: center; color: #94a3b8; font-size: 12px;">
                                                    Tidak ditemukan akun yang cocok
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Description --}}
                                <td style="vertical-align: top !important;">
                                    <input type="text" :name="`lines[${idx}][description]`" x-model="row.description" class="form-control" placeholder="Keterangan baris (opsional)" style="height: 38px;">
                                </td>

                                {{-- Debit Input --}}
                                <td style="vertical-align: top !important;">
                                    <input type="text" 
                                           inputmode="numeric" 
                                           :name="`lines[${idx}][debit]`" 
                                           :value="row.debit ? (window.formatRupiah ? window.formatRupiah(row.debit) : row.debit) : ''"
                                           @input="row.debit = window.unformatRupiah ? window.unformatRupiah($event.target.value) : (parseInt($event.target.value) || 0); if(window.applyMaskWithCursor) window.applyMaskWithCursor($event.target);" 
                                           @focus="$event.target.select()" 
                                           @blur="if(window.formatRupiah && row.debit) $event.target.value = window.formatRupiah(row.debit)" 
                                           class="form-control format-currency" 
                                           placeholder="0"
                                           style="height: 38px; text-align: right; font-family: 'Inter', monospace; font-weight: 600;">
                                </td>

                                {{-- Credit Input --}}
                                <td style="vertical-align: top !important;">
                                    <input type="text" 
                                           inputmode="numeric" 
                                           :name="`lines[${idx}][credit]`" 
                                           :value="row.credit ? (window.formatRupiah ? window.formatRupiah(row.credit) : row.credit) : ''"
                                           @input="row.credit = window.unformatRupiah ? window.unformatRupiah($event.target.value) : (parseInt($event.target.value) || 0); if(window.applyMaskWithCursor) window.applyMaskWithCursor($event.target);" 
                                           @focus="$event.target.select()" 
                                           @blur="if(window.formatRupiah && row.credit) $event.target.value = window.formatRupiah(row.credit)" 
                                           class="form-control format-currency" 
                                           placeholder="0"
                                           style="height: 38px; text-align: right; font-family: 'Inter', monospace; font-weight: 600;">
                                </td>

                                {{-- Delete Row --}}
                                <td style="vertical-align: top !important; text-align:center;">
                                    <div style="height: 38px; display: flex; align-items: center; justify-content: center;">
                                        <template x-if="rows.length > 2">
                                            <button type="button" class="btn btn-danger btn-sm btn-icon" @click="removeRow(idx)" title="Hapus Baris">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr style="background:#f8fafc; font-weight:700; font-size: 14px;">
                            <td colspan="2" style="text-align:right;">TOTAL KESELURUHAN:</td>
                            <td style="text-align:right; font-family: monospace; font-size: 15px;" :style="{ color: isBalanced ? 'var(--success)' : 'var(--danger)' }" x-text="'Rp ' + formatNum(totalDebit)"></td>
                            <td style="text-align:right; font-family: monospace; font-size: 15px;" :style="{ color: isBalanced ? 'var(--success)' : 'var(--danger)' }" x-text="'Rp ' + formatNum(totalCredit)"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Balance Status Message --}}
            <div style="padding: 14px 20px; border-top: 1px solid var(--border-color, #e2e8f0);">
                <template x-if="isBalanced">
                    <div style="display: flex; align-items: center; gap: 8px; color: #16a34a; font-weight: 600; font-size: 13.5px;">
                        <i class="fa-solid fa-circle-check" style="font-size: 18px;"></i>
                        <span>Jurnal SEIMBANG (Balanced) - Total: Rp <span x-text="formatNum(totalDebit)"></span></span>
                    </div>
                </template>
                <template x-if="!isBalanced">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; background: #fff1f2; border: 1px solid #fecdd3; padding: 10px 14px; border-radius: 6px; color: #be123c; font-size: 13px; font-weight: 500;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-triangle-exclamation" style="font-size: 16px;"></i>
                            <span>
                                <strong>Jurnal TIDAK BALANCE!</strong> 
                                Total Debit: <strong>Rp <span x-text="formatNum(totalDebit)"></span></strong> | 
                                Total Kredit: <strong>Rp <span x-text="formatNum(totalCredit)"></span></strong>
                            </span>
                        </div>
                        <span class="badge badge-cancelled" style="font-size: 12px; font-weight: 700;">
                            Selisih: Rp <span x-text="formatNum(difference)"></span>
                        </span>
                    </div>
                </template>
            </div>
        </div>

        <x-form-actions 
            :back-url="route('accounting.journals.index')" 
            submit-label="Simpan Perubahan Jurnal" 
        />
    </form>
</div>
@endsection

@php
    $rawAccounts = $accounts->map(function ($account) {
        return [
            'id' => $account->id,
            'code' => $account->code,
            'name' => $account->name,
            'type' => ucfirst($account->type),
            'label' => $account->code . ' - ' . $account->name . ' (' . ucfirst($account->type) . ')',
        ];
    })->values();

    $initialLines = $journal->lines->map(function ($line) {
        return [
            'chart_of_account_id' => $line->chart_of_account_id,
            'description' => $line->description ?? '',
            'debit' => (float) $line->debit,
            'credit' => (float) $line->credit,
        ];
    })->values();
@endphp

@push('scripts')
<script>
function journalEditForm() {
    const rawAccounts = @json($rawAccounts);

    const accountsMap = {};
    rawAccounts.forEach(a => {
        accountsMap[a.id] = a;
    });

    const initialLines = @json($initialLines);

    if (initialLines.length < 2) {
        while (initialLines.length < 2) {
            initialLines.push({ chart_of_account_id: '', description: '', debit: 0, credit: 0 });
        }
    }

    return {
        accountsList: rawAccounts,
        accountsMap: accountsMap,
        rows: initialLines,

        addRow() { 
            this.rows.push({ chart_of_account_id: '', description: '', debit: 0, credit: 0 }); 
        },

        removeRow(idx) { 
            if (this.rows.length > 2) {
                this.rows.splice(idx, 1); 
            }
        },

        get totalDebit() { 
            return this.rows.reduce((s, r) => s + (parseFloat(r.debit) || 0), 0); 
        },

        get totalCredit() { 
            return this.rows.reduce((s, r) => s + (parseFloat(r.credit) || 0), 0); 
        },

        get difference() {
            return Math.abs(this.totalDebit - this.totalCredit);
        },

        get isBalanced() { 
            return this.difference < 0.01 && this.totalDebit > 0; 
        },

        validateForm(e) {
            for (let i = 0; i < this.rows.length; i++) {
                if (!this.rows[i].chart_of_account_id) {
                    alert('Harap pilih Akun COA pada baris ke-' + (i + 1) + '.');
                    e.preventDefault();
                    return false;
                }
            }

            if (!this.isBalanced) {
                if (this.totalDebit <= 0 && this.totalCredit <= 0) {
                    alert('Total Debit dan Kredit masih 0. Harap isi nilai nominal pada baris akun.');
                } else {
                    alert('Jurnal tidak balance! Total Debit (Rp ' + this.formatNum(this.totalDebit) + ') != Total Kredit (Rp ' + this.formatNum(this.totalCredit) + '). Selisih: Rp ' + this.formatNum(this.difference) + '.');
                }
                e.preventDefault();
                return false;
            }

            // Kirim angka mentah, bukan tampilan berformat seperti 1.500.000.
            e.target.querySelectorAll('.format-currency').forEach(input => {
                input.value = window.unformatRupiah(input.value);
            });
            return true;
        },

        formatNum(v) { 
            return new Intl.NumberFormat('id-ID').format(Math.round(v || 0)); 
        }
    }
}
</script>
@endpush
