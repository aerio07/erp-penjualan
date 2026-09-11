@extends('layouts.app')
@section('title', 'Buat Jurnal Umum')
@section('page-title', 'Buat Jurnal Umum')

@section('content')
<div class="animate-in" x-data="journalForm()">
    <x-page-header 
        title="Buat Jurnal Umum" 
        subtitle="Input transaksi penyesuaian/manual double-entry akuntansi"
        :back-url="route('accounting.journals.index')"
    />

    <form method="POST" action="{{ route('accounting.journals.store') }}" @submit="validateForm($event)">
        @csrf

        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h3>Informasi Entri</h3></div>
            <div class="card-body">
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Tanggal Transaksi <span style="color:var(--danger);">*</span></label>
                        <input type="date" name="entry_date" value="{{ old('entry_date', date('Y-m-d')) }}" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Keterangan Transaksi <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="description" value="{{ old('description') }}" class="form-control" required placeholder="Contoh: Beban sewa gedung bulan Agustus">
                    </div>
                </div>
            </div>
        </div>

        {{-- Rows Table --}}
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <h3>Rincian Akun (Debit & Kredit)</h3>
                <button type="button" class="btn btn-primary btn-sm" @click="addRow()">
                    <i class="fa-solid fa-plus"></i> Tambah Baris Akun
                </button>
            </div>
            <div class="table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Akun COA</th>
                            <th>Keterangan Baris</th>
                            <th style="width:160px;">Debit (Rp)</th>
                            <th style="width:160px;">Kredit (Rp)</th>
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, idx) in rows" :key="idx">
                            <tr>
                                <td>
                                    <select :name="`lines[${idx}][chart_of_account_id]`" class="form-control" x-model="row.chart_of_account_id" required>
                                        <option value="">-- Pilih Akun --</option>
                                        @foreach($accounts as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }} ({{ ucfirst($acc->type) }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" :name="`lines[${idx}][description]`" x-model="row.description" class="form-control" placeholder="Keterangan opsional">
                                </td>
                                <td>
                                    <input type="text" inputmode="numeric" :name="`lines[${idx}][debit]`" x-currency="row.debit" class="form-control format-currency" placeholder="0">
                                </td>
                                <td>
                                    <input type="text" inputmode="numeric" :name="`lines[${idx}][credit]`" x-currency="row.credit" class="form-control format-currency" placeholder="0">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm btn-icon" @click="removeRow(idx)" x-show="rows.length > 2">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr style="background:#f8fafc; font-weight:700;">
                            <td colspan="2" style="text-align:right;">TOTAL:</td>
                            <td style="text-align:right;" :style="{ color: isBalanced ? 'var(--success)' : 'var(--danger)' }" x-text="'Rp ' + formatNum(totalDebit)"></td>
                            <td style="text-align:right;" :style="{ color: isBalanced ? 'var(--success)' : 'var(--danger)' }" x-text="'Rp ' + formatNum(totalCredit)"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div style="padding:12px 20px; font-size:13px;" x-show="!isBalanced">
                <span class="badge badge-cancelled"><i class="fa-solid fa-triangle-exclamation"></i> Jurnal TIDAK BALANCE! Selisih: Rp <span x-text="formatNum(Math.abs(totalDebit - totalCredit))"></span></span>
            </div>
        </div>

        <x-form-actions 
            :back-url="route('accounting.journals.index')" 
            submit-label="Simpan Draft Jurnal" 
        />
    </form>
</div>
@endsection

@push('scripts')
<script>
function journalForm() {
    return {
        rows: [
            { chart_of_account_id: '', description: '', debit: 0, credit: 0 },
            { chart_of_account_id: '', description: '', debit: 0, credit: 0 }
        ],

        addRow() { this.rows.push({ chart_of_account_id: '', description: '', debit: 0, credit: 0 }); },
        removeRow(idx) { this.rows.splice(idx, 1); },

        get totalDebit() { return this.rows.reduce((s, r) => s + (parseFloat(r.debit) || 0), 0); },
        get totalCredit() { return this.rows.reduce((s, r) => s + (parseFloat(r.credit) || 0), 0); },
        get isBalanced() { return Math.abs(this.totalDebit - this.totalCredit) < 0.01 && this.totalDebit > 0; },

        validateForm(e) {
            if (!this.isBalanced) {
                alert('Jurnal tidak balance atau total debit/credit masih 0. Pastikan total debit sama dengan kredit.');
                e.preventDefault();
                return false;
            }
            return true;
        },

        formatNum(v) { return new Intl.NumberFormat('id-ID').format(Math.round(v)); }
    }
}
</script>
@endpush
