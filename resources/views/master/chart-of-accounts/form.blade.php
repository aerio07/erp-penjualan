@extends('layouts.app')
@section('title', isset($chartOfAccount) ? 'Edit Akun' : 'Tambah Akun')
@section('page-title', isset($chartOfAccount) ? 'Edit Akun COA' : 'Tambah Akun COA')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div><h1>{{ isset($chartOfAccount) ? 'Edit Akun' : 'Tambah Akun Baru' }}</h1></div>
        <a href="{{ route('master.chart-of-accounts.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card" style="max-width:600px;">
        <div class="card-header"><h3>Informasi Akun</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ isset($chartOfAccount) ? route('master.chart-of-accounts.update', $chartOfAccount) : route('master.chart-of-accounts.store') }}">
                @csrf
                @if(isset($chartOfAccount)) @method('PUT') @endif

                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Kode Akun <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="code" value="{{ old('code', $chartOfAccount->code ?? '') }}"
                            class="form-control {{ $errors->has('code') ? 'is-invalid' : '' }}"
                            required placeholder="1-1-001" style="font-family:monospace;">
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipe Akun <span style="color:var(--danger);">*</span></label>
                        <select name="type" class="form-control {{ $errors->has('type') ? 'is-invalid' : '' }}" required>
                            <option value="">-- Pilih Tipe --</option>
                            @foreach(['asset' => 'Aset', 'liability' => 'Kewajiban', 'equity' => 'Ekuitas', 'revenue' => 'Pendapatan', 'expense' => 'Beban/Biaya'] as $val => $label)
                            <option value="{{ $val }}" {{ old('type', $chartOfAccount->type ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Akun <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $chartOfAccount->name ?? '') }}"
                        class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Normal Balance <span style="color:var(--danger);">*</span></label>
                    <div style="display:flex; gap:16px; margin-top:6px;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="radio" name="normal_balance" value="debit"
                                {{ old('normal_balance', $chartOfAccount->normal_balance ?? '') === 'debit' ? 'checked' : '' }}>
                            <span>Debit (Aset, Beban)</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="radio" name="normal_balance" value="credit"
                                {{ old('normal_balance', $chartOfAccount->normal_balance ?? '') === 'credit' ? 'checked' : '' }}>
                            <span>Kredit (Kewajiban, Ekuitas, Pendapatan)</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Keterangan</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $chartOfAccount->description ?? '') }}</textarea>
                </div>

                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <input type="checkbox" name="is_active" value="1"
                            {{ old('is_active', $chartOfAccount->is_active ?? true) ? 'checked' : '' }}
                            style="width:16px; height:16px; cursor:pointer;">
                        <span class="form-label" style="margin-bottom:0;">Akun Aktif</span>
                    </label>
                </div>

                <div style="display:flex; gap:12px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> {{ isset($chartOfAccount) ? 'Perbarui' : 'Simpan' }}
                    </button>
                    <a href="{{ route('master.chart-of-accounts.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
