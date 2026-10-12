@extends('layouts.app')
@section('title', 'Edit Cash Bank Receipt - ' . $cashBankReceipt->receipt_number)
@section('page-title', 'Edit Cash Bank Receipt')

@section('content')
<div class="animate-in" x-data="cashBankEditForm({{ json_encode($cashBankReceipt->items->map(fn($i) => ['chart_of_account_id' => $i->chart_of_account_id, 'description' => $i->description, 'amount' => $i->amount, 'notes' => $i->notes])) }})">
    <x-page-header title="Edit Receipt #{{ $cashBankReceipt->receipt_number }}" subtitle="Perbarui data penerimaan kas/bank" :back-url="route('accounting.cash-bank-receipts.show', $cashBankReceipt)" />

    @if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:20px;">
        <strong><i class="fa-solid fa-triangle-exclamation"></i> Terdapat kesalahan input:</strong>
        <ul style="margin:8px 0 0 16px; padding:0;">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('accounting.cash-bank-receipts.update', $cashBankReceipt) }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h3>Informasi Penerimaan</h3></div>
            <div class="card-body">
                <div class="form-row form-row-2" style="gap:20px;">
                    <div style="display:flex; flex-direction:column; gap:14px;">
                        <div class="form-group">
                            <label class="form-label">Company <span style="color:var(--danger);">*</span></label>
                            <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $cashBankReceipt->company_name) }}" placeholder="Company" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Upload Document (Opsional, biarkan kosong jika tidak diubah)</label>
                            <input type="file" name="document_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            @if($cashBankReceipt->document_file)
                            <div style="font-size:12px; margin-top:4px;"><a href="{{ Storage::url($cashBankReceipt->document_file) }}" target="_blank" style="color:var(--primary);">Lihat file saat ini</a></div>
                            @endif
                        </div>
                        <div class="form-group">
                            <label class="form-label">No. Receipt</label>
                            <input type="text" class="form-control" value="{{ $cashBankReceipt->receipt_number }}" readonly style="background:#f8fafc; font-weight:700; color:var(--primary);">
                        </div>
                        <div class="form-group">
                            <label class="form-label">No. Kas/Bank/Giro</label>
                            <input type="text" name="voucher_number" class="form-control" value="{{ old('voucher_number', $cashBankReceipt->voucher_number) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Type <span style="color:var(--danger);">*</span></label>
                            <select name="type" class="form-control" required>
                                @foreach(['KAS','BANK','DP'] as $t)
                                <option value="{{ $t }}" {{ old('type', $cashBankReceipt->type) === $t ? 'selected' : '' }}>{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Notes <span style="color:var(--danger);">*</span></label>
                            <textarea name="notes" class="form-control" rows="3" required>{{ old('notes', $cashBankReceipt->notes) }}</textarea>
                        </div>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:14px;">
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <input type="text" class="form-control" value="{{ ucfirst($cashBankReceipt->status) }}" readonly style="background:#f8fafc;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date</label>
                            <input type="date" name="transaction_date" class="form-control" value="{{ old('transaction_date', $cashBankReceipt->transaction_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Group</label>
                            <select name="group" class="form-control">
                                <option value="">Select Group</option>
                                @foreach(['PENJUALAN','PEMBELIAN','OPERASIONAL','LAINNYA'] as $g)
                                <option value="{{ $g }}" {{ old('group', $cashBankReceipt->group) === $g ? 'selected' : '' }}>{{ $g }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account <span style="color:var(--danger);">*</span></label>
                            <select name="chart_of_account_id" class="form-control" required>
                                <option value="">Select Akun</option>
                                @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ old('chart_of_account_id', $cashBankReceipt->chart_of_account_id) == $acc->id ? 'selected' : '' }}>
                                    [{{ $acc->code }}] {{ $acc->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">No. Reference</label>
                            <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number', $cashBankReceipt->reference_number) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Amount</label>
                            <div class="form-control" style="background:#f8fafc; font-weight:700; color:var(--success);">IDR <span x-text="formatAmount(totalAmount)"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom:20px;">
            <div class="card-body" style="padding:20px;">
                <h3 style="margin:0 0 16px; font-size:16px; font-weight:700;">Cash Bank Receipt Detail</h3>
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
                                    <td style="text-align:center;" x-text="idx+1"></td>
                                    <td>
                                        <select :name="`items[${idx}][chart_of_account_id]`" class="form-control" x-model="item.chart_of_account_id" required style="min-width:200px;">
                                            <option value="">-- Pilih Akun --</option>
                                            @foreach($accounts as $acc)
                                            <option value="{{ $acc->id }}">[{{ $acc->code }}] {{ $acc->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" :name="`items[${idx}][description]`" class="form-control" x-model="item.description" placeholder="Deskripsi..."></td>
                                    <td><input type="number" :name="`items[${idx}][amount]`" class="form-control" x-model.number="item.amount" @input="calcTotal()" min="0" step="1" placeholder="0" style="text-align:right; min-width:130px;"></td>
                                    <td><input type="text" :name="`items[${idx}][notes]`" class="form-control" x-model="item.notes" placeholder="Catatan..." required style="min-width:160px;"></td>
                                    <td style="text-align:center;"><button type="button" class="btn btn-danger btn-sm btn-icon" @click="removeItem(idx)"><i class="fa-solid fa-trash"></i></button></td>
                                </tr>
                            </template>
                            <template x-if="items.length === 0">
                                <tr><td colspan="6" style="text-align:center; padding:28px; color:var(--text-secondary);">No detail items added yet</td></tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:10px; margin-bottom:32px;">
            <a href="{{ route('accounting.cash-bank-receipts.show', $cashBankReceipt) }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function cashBankEditForm(existingItems) {
    return {
        items: existingItems.map((i, idx) => ({ ...i, uid: idx })),
        totalAmount: existingItems.reduce((s, i) => s + parseFloat(i.amount || 0), 0),
        uid: existingItems.length,
        addItem() { this.items.push({ uid: this.uid++, chart_of_account_id: '', description: '', amount: 0, notes: '' }); },
        removeItem(idx) { this.items.splice(idx, 1); this.calcTotal(); },
        calcTotal() { this.totalAmount = this.items.reduce((s, i) => s + (parseFloat(i.amount) || 0), 0); },
        formatAmount(val) { return new Intl.NumberFormat('id-ID').format(val || 0); }
    }
}
</script>
@endpush
