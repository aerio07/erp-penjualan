@extends('layouts.app')
@section('title', isset($customer) ? 'Edit Customer' : 'Tambah Customer')
@section('page-title', isset($customer) ? 'Edit Customer' : 'Tambah Customer')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div><h1>{{ isset($customer) ? 'Edit Customer' : 'Tambah Customer Baru' }}</h1></div>
        <a href="{{ route('master.customers.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card" style="max-width:700px;">
        <div class="card-header"><h3>Informasi Customer</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ isset($customer) ? route('master.customers.update', $customer) : route('master.customers.store') }}">
                @csrf
                @if(isset($customer)) @method('PUT') @endif

                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Kode Customer <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="code" value="{{ old('code', $customer->code ?? '') }}"
                            class="form-control {{ $errors->has('code') ? 'is-invalid' : '' }}" required placeholder="CUST-001">
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Customer <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $customer->name ?? '') }}"
                            class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" value="{{ old('contact_person', $customer->contact_person ?? '') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telepon</label>
                        <input type="text" name="phone" value="{{ old('phone', $customer->phone ?? '') }}" class="form-control" placeholder="08xx-xxxx-xxxx">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ old('email', $customer->email ?? '') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Term</label>
                        <select name="payment_term" class="form-control">
                            <option value="">-- Pilih --</option>
                            @foreach(['COD','NET 7','NET 14','NET 30','NET 45','NET 60'] as $term)
                            <option value="{{ $term }}" {{ old('payment_term', $customer->payment_term ?? '') === $term ? 'selected' : '' }}>{{ $term }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Credit Limit (Rp)</label>
                        <input type="number" name="credit_limit" value="{{ old('credit_limit', $customer->credit_limit ?? '') }}"
                            class="form-control" min="0" step="100000" placeholder="0 = tidak ada limit">
                    </div>
                    <div class="form-group">
                        <label class="form-label">NPWP</label>
                        <input type="text" name="npwp" value="{{ old('npwp', $customer->npwp ?? '') }}" class="form-control" placeholder="00.000.000.0-000.000">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Alamat</label>
                    <textarea name="address" class="form-control" rows="3">{{ old('address', $customer->address ?? '') }}</textarea>
                </div>

                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <input type="checkbox" name="is_active" value="1"
                            {{ old('is_active', $customer->is_active ?? true) ? 'checked' : '' }}
                            style="width:16px; height:16px; cursor:pointer;">
                        <span class="form-label" style="margin-bottom:0;">Customer Aktif</span>
                    </label>
                </div>

                <div style="display:flex; gap:12px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> {{ isset($customer) ? 'Perbarui' : 'Simpan' }}
                    </button>
                    <a href="{{ route('master.customers.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
