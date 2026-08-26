@extends('layouts.app')
@section('title', isset($chartOfAccount) ? 'Edit Akun CoA' : 'Tambah Akun CoA')
@section('page-title', isset($chartOfAccount) ? 'Edit Akun CoA' : 'Tambah Akun CoA')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div><h1>{{ isset($chartOfAccount) ? 'Edit Akun CoA' : 'Tambah Akun CoA Baru' }}</h1></div>
        <a href="{{ route('master.chart-of-accounts.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card w-full">
        <div class="card-header"><h3>Informasi Akun (Chart of Account)</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ isset($chartOfAccount) ? route('master.chart-of-accounts.update', $chartOfAccount) : route('master.chart-of-accounts.store') }}" id="coaForm">
                @csrf
                @if(isset($chartOfAccount)) @method('PUT') @endif

                <!-- Row 1: 3 Kolom (Kode Akun, Nama Akun, Tipe Akun) -->
                <div class="form-row form-row-3">
                    <div class="form-group">
                        <label class="form-label">Kode Akun <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="code" value="{{ old('code', $chartOfAccount->code ?? '') }}"
                            class="form-control {{ $errors->has('code') ? 'is-invalid' : '' }}"
                            required placeholder="Contoh: 1-1-001, 5-1-002" style="font-family:monospace; text-transform:uppercase;">
                        @if(!$errors->has('code'))
                        <span class="form-text" style="font-size:11px; color:var(--text-secondary); margin-top:3px;">Kode unik standar bagan akun.</span>
                        @endif
                        @error('code')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nama Akun <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $chartOfAccount->name ?? '') }}"
                            class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" required placeholder="Contoh: Kas Utama, Piutang Usaha">
                        @error('name')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tipe Akun <span style="color:var(--danger);">*</span></label>
                        <select name="type" id="accountTypeSelect" class="form-control {{ $errors->has('type') ? 'is-invalid' : '' }}" required onchange="handleTypeChange(this.value)">
                            <option value="">-- Pilih Tipe Akun --</option>
                            @foreach(['asset' => 'Aset (Aktiva)', 'liability' => 'Kewajiban (Hutang)', 'equity' => 'Ekuitas (Modal)', 'revenue' => 'Pendapatan (Penjualan)', 'expense' => 'Beban / Biaya'] as $val => $label)
                            <option value="{{ $val }}" {{ old('type', $chartOfAccount->type ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                </div>

                <!-- Row 2: 2 Kolom (Normal Balance & Catatan) -->
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Normal Balance <span style="color:var(--danger);">*</span></label>
                        <div style="display:flex; gap:20px; padding:9px 14px; background:#f8fafc; border-radius:8px; border:1px solid var(--border);">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:500;">
                                <input type="radio" name="normal_balance" id="radioDebit" value="debit"
                                    {{ old('normal_balance', $chartOfAccount->normal_balance ?? '') === 'debit' ? 'checked' : '' }} required>
                                <span style="color:#2563eb;">Debit</span> <span style="font-size:11px; color:var(--text-secondary);">(Aset, Beban)</span>
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:500;">
                                <input type="radio" name="normal_balance" id="radioCredit" value="credit"
                                    {{ old('normal_balance', $chartOfAccount->normal_balance ?? '') === 'credit' ? 'checked' : '' }} required>
                                <span style="color:#dc2626;">Kredit</span> <span style="font-size:11px; color:var(--text-secondary);">(Hutang, Modal, Omset)</span>
                            </label>
                        </div>
                        @error('normal_balance')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Keterangan / Catatan</label>
                        <textarea name="description" class="form-control" rows="1" placeholder="Catatan tambahan fungsi akun...">{{ old('description', $chartOfAccount->description ?? '') }}</textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <input type="checkbox" name="is_active" value="1"
                            {{ old('is_active', $chartOfAccount->is_active ?? true) ? 'checked' : '' }}
                            style="width:16px; height:16px; cursor:pointer;">
                        <span class="form-label" style="margin-bottom:0;">Akun Aktif</span>
                    </label>
                </div>

                <div style="display:flex; gap:12px; margin-top:20px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> {{ isset($chartOfAccount) ? 'Perbarui Akun' : 'Simpan Akun' }}
                    </button>
                    <a href="{{ route('master.chart-of-accounts.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function handleTypeChange(val) {
    const radioDebit = document.getElementById('radioDebit');
    const radioCredit = document.getElementById('radioCredit');
    
    // Auto default normal balance based on accounting principle
    if (val === 'asset' || val === 'expense') {
        radioDebit.checked = true;
    } else if (val === 'liability' || val === 'equity' || val === 'revenue') {
        radioCredit.checked = true;
    }
}
</script>
@endsection
