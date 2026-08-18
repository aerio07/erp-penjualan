@extends('layouts.app')
@section('title', isset($supplier) ? 'Edit Supplier' : 'Tambah Supplier')
@section('page-title', isset($supplier) ? 'Edit Supplier' : 'Tambah Supplier')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div><h1>{{ isset($supplier) ? 'Edit Supplier' : 'Tambah Supplier Baru' }}</h1></div>
        <a href="{{ route('master.suppliers.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card" style="max-width:700px;">
        <div class="card-header"><h3>Informasi Supplier</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ isset($supplier) ? route('master.suppliers.update', $supplier) : route('master.suppliers.store') }}">
                @csrf
                @if(isset($supplier)) @method('PUT') @endif

                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Kode Supplier <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="code" value="{{ old('code', $supplier->code ?? '') }}"
                            class="form-control {{ $errors->has('code') ? 'is-invalid' : '' }}" required placeholder="SUP-001">
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Supplier <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $supplier->name ?? '') }}"
                            class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" value="{{ old('contact_person', $supplier->contact_person ?? '') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telepon</label>
                        <input type="text" name="phone" value="{{ old('phone', $supplier->phone ?? '') }}" class="form-control" placeholder="08xx-xxxx-xxxx">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ old('email', $supplier->email ?? '') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Term</label>
                        <select name="payment_term" class="form-control">
                            <option value="">-- Pilih --</option>
                            @foreach(['COD','NET 7','NET 14','NET 30','NET 45','NET 60'] as $term)
                            <option value="{{ $term }}" {{ old('payment_term', $supplier->payment_term ?? '') === $term ? 'selected' : '' }}>{{ $term }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">NPWP</label>
                    <input type="text" name="npwp" value="{{ old('npwp', $supplier->npwp ?? '') }}" class="form-control" placeholder="00.000.000.0-000.000">
                </div>

                <div class="form-group">
                    <label class="form-label">Alamat</label>
                    <textarea name="address" class="form-control" rows="3">{{ old('address', $supplier->address ?? '') }}</textarea>
                </div>

                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <input type="checkbox" name="is_active" value="1"
                            {{ old('is_active', $supplier->is_active ?? true) ? 'checked' : '' }}
                            style="width:16px; height:16px; cursor:pointer;">
                        <span class="form-label" style="margin-bottom:0;">Supplier Aktif</span>
                    </label>
                </div>

                <div style="display:flex; gap:12px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> {{ isset($supplier) ? 'Perbarui' : 'Simpan' }}
                    </button>
                    <a href="{{ route('master.suppliers.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
