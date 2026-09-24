@extends('layouts.app')
@section('title', 'Buat Cash Bank Expense')
@section('page-title', 'Buat Cash Bank Expense')

@section('content')
<div class="animate-in" x-data="cashBankForm()">
    <x-page-header title="Buat Cash Bank Expense" subtitle="Catat pengeluaran kas atau bank" :back-url="route('accounting.cash-bank-expenses.index')" />

    @if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:20px;">
        <strong><i class="fa-solid fa-triangle-exclamation"></i> Terdapat kesalahan input:</strong>
        <ul style="margin:8px 0 0 16px; padding:0;">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('accounting.cash-bank-expenses.store') }}" enctype="multipart/form-data" id="expense-form">
        @csrf

        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h3>Informasi Pengeluaran</h3></div>
            <div class="card-body">
                <div class="form-row form-row-2" style="gap:20px;">
                    {{-- Kiri --}}
                    <div style="display:flex; flex-direction:column; gap:14px;">
                        <div class="form-group">
                            <label class="form-label">Company <span style="color:var(--danger);">*</span></label>
                            <input type="text" name="company_name" class="form-control" value="{{ old('company_name') }}" placeholder="Company" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Upload Document</label>
                            <input type="file" name="document_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            <div style="font-size:11px; color:var(--text-secondary); margin-top:3px;">PDF, JPG, PNG maks 5MB</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">No. Expense</label>
                            <input type="text" class="form-control" value="" placeholder="(Otomatis terisi saat disimpan)" readonly style="background:#f8fafc; border-style:dashed; color:var(--text-secondary);">
                        </div>
                        <div class="form-group">
                            <label class="form-label">No. Kas/Bank/Giro <span style="font-size:11px; color:var(--text-secondary); font-weight:400;">(by System)</span></label>
                            <input type="text" name="voucher_number" class="form-control" value="{{ old('voucher_number') }}" placeholder="Otomatis terisi saat disimpan" style="background:#f8fafc;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Type <span style="color:var(--danger);">*</span></label>
                            <select name="type" class="form-control" required>
                                <option value="">Select Tipe</option>
                                <option value="KAS" {{ old('type') === 'KAS' ? 'selected' : '' }}>KAS</option>
                                <option value="BANK" {{ old('type') === 'BANK' ? 'selected' : '' }}>BANK</option>
                                <option value="DP" {{ old('type') === 'DP' ? 'selected' : '' }}>DP</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Notes <span style="color:var(--danger);">*</span></label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Enter Notes" required>{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    {{-- Kanan --}}
                    <div style="display:flex; flex-direction:column; gap:14px;">
                        <div class="form-group">
                            <label class="form-label">Status <span style="color:var(--danger);">*</span></label>
                            <input type="text" class="form-control" value="Draft" readonly style="background:#f8fafc; color:var(--text-secondary);">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date</label>
                            <input type="date" name="transaction_date" class="form-control" value="{{ old('transaction_date', date('Y-m-d')) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Group</label>
                            <select name="group" class="form-control">
                                <option value="">Select Group</option>
                                <option value="PENJUALAN" {{ old('group') === 'PENJUALAN' ? 'selected' : '' }}>Penjualan</option>
                                <option value="PEMBELIAN" {{ old('group') === 'PEMBELIAN' ? 'selected' : '' }}>Pembelian</option>
                                <option value="OPERASIONAL" {{ old('group') === 'OPERASIONAL' ? 'selected' : '' }}>Operasional</option>
                                <option value="LAINNYA" {{ old('group') === 'LAINNYA' ? 'selected' : '' }}>Lainnya</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account <span style="color:var(--danger);">*</span></label>
                            <select name="chart_of_account_id" class="form-control" required>
                                <option value="">Select Akun</option>
                                @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ old('chart_of_account_id') == $acc->id ? 'selected' : '' }}>
                                    [{{ $acc->code }}] {{ $acc->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">No. Reference</label>
                            <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number') }}" placeholder="Enter No. Reference">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Amount</label>
                            <div class="form-control" style="background:#f8fafc; font-weight:700; color:var(--danger);">
                                IDR <span x-text="formatAmount(totalAmount)">0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detail Items --}}
        <div class="card" style="margin-bottom:20px;">
            <div class="card-body" style="padding:20px;">
                <h3 style="margin:0 0 16px; font-size:16px; font-weight:700;">Cash Bank Expense Detail</h3>
                <button type="button" class="btn btn-primary btn-sm" style="margin-bottom:16px;" @click="addItem()">
                    <i class="fa-solid fa-plus"></i> + Add to List
                </button>
                <div class="table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th style="width:50px;">No</th>
                                <th>Kode Akun</th>
                                <th>Deskripsi</th>
                                <th style="text-align:right;">Jumlah</th>
                                <th>Catatan Detail <span style="color:var(--danger);">*</span></th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, idx) in items" :key="item.uid">
                                <tr>
                                    <td style="text-align:center; color:var(--text-secondary);" x-text="idx + 1"></td>
                                    <td>
                                        <select :name="`items[${idx}][chart_of_account_id]`" class="form-control" x-model="item.chart_of_account_id" required style="min-width:200px;">
                                            <option value="">-- Pilih Akun --</option>
                                            @foreach($accounts as $acc)
                                            <option value="{{ $acc->id }}">[{{ $acc->code }}] {{ $acc->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" :name="`items[${idx}][description]`" class="form-control" x-model="item.description" placeholder="Deskripsi...">
                                    </td>
                                    <td>
                                        <input type="number" :name="`items[${idx}][amount]`" class="form-control" x-model.number="item.amount" @input="calcTotal()" min="0" step="1" placeholder="0" style="text-align:right; min-width:130px;">
                                    </td>
                                    <td>
                                        <input type="text" :name="`items[${idx}][notes]`" class="form-control" x-model="item.notes" placeholder="Catatan..." required style="min-width:160px;">
                                    </td>
                                    <td style="text-align:center;">
                                        <button type="button" class="btn btn-danger btn-sm btn-icon" @click="removeItem(idx)" title="Hapus baris">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="items.length === 0">
                                <tr>
                                    <td colspan="6" style="text-align:center; padding:28px; color:var(--text-secondary); font-size:13px;">
                                        No detail items added yet
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div style="display:flex; justify-content:flex-end; gap:10px; margin-bottom:32px;">
            <a href="{{ route('accounting.cash-bank-expenses.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-times"></i> Cancel
            </a>
            <button type="submit" name="action" value="save" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Save
            </button>
            <button type="submit" name="action" value="post" class="btn btn-warning" style="background:#f59e0b; border-color:#d97706; color:#fff;">
                <i class="fa-solid fa-paper-plane"></i> Post
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function cashBankForm() {
    return {
        items: [],
        totalAmount: 0,
        uid: 0,
        addItem() {
            this.items.push({ uid: this.uid++, chart_of_account_id: '', description: '', amount: 0, notes: '' });
        },
        removeItem(idx) {
            this.items.splice(idx, 1);
            this.calcTotal();
        },
        calcTotal() {
            this.totalAmount = this.items.reduce((s, i) => s + (parseFloat(i.amount) || 0), 0);
        },
        formatAmount(val) {
            return new Intl.NumberFormat('id-ID').format(val || 0);
        }
    }
}
</script>
@endpush
