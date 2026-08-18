@extends('layouts.app')
@section('title', isset($user) ? 'Edit User' : 'Tambah User')
@section('page-title', isset($user) ? 'Edit User' : 'Tambah User')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div><h1>{{ isset($user) ? 'Edit User: '.$user->name : 'Tambah User Baru' }}</h1></div>
        <a href="{{ route('master.users.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card" style="max-width:600px;">
        <div class="card-header"><h3>Informasi User</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ isset($user) ? route('master.users.update', $user) : route('master.users.store') }}">
                @csrf
                @if(isset($user)) @method('PUT') @endif

                <div class="form-group">
                    <label class="form-label">Nama Lengkap <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}"
                        class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Email <span style="color:var(--danger);">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}"
                            class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role <span style="color:var(--danger);">*</span></label>
                        <select name="role" class="form-control {{ $errors->has('role') ? 'is-invalid' : '' }}" required>
                            <option value="">-- Pilih Role --</option>
                            @foreach(['admin' => 'Admin', 'purchasing' => 'Purchasing', 'gudang' => 'Gudang', 'sales' => 'Sales', 'finance' => 'Finance'] as $val => $label)
                            <option value="{{ $val }}" {{ old('role', $user->role ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">
                            Password {{ isset($user) ? '(Kosongkan jika tidak diubah)' : '' }}
                            @if(!isset($user))<span style="color:var(--danger);">*</span>@endif
                        </label>
                        <input type="password" name="password"
                            class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                            {{ !isset($user) ? 'required' : '' }} placeholder="Min. 8 karakter">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" class="form-control" placeholder="Ulangi password">
                    </div>
                </div>

                {{-- Role descriptions --}}
                <div style="padding:14px; background:#f8fafc; border-radius:10px; margin-bottom:20px; font-size:13px;">
                    <div style="font-weight:600; margin-bottom:8px; color:var(--text-secondary);">Hak Akses Per Role:</div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
                        <div><span style="font-weight:600; color:#7c3aed;">Admin</span> — Akses penuh ke semua modul</div>
                        <div><span style="font-weight:600; color:#1d4ed8;">Purchasing</span> — PO & Retur Pembelian</div>
                        <div><span style="font-weight:600; color:#065f46;">Gudang</span> — GRN, Transfer, Opname</div>
                        <div><span style="font-weight:600; color:#92400e;">Sales</span> — SO, Delivery, Retur Penjualan</div>
                        <div><span style="font-weight:600; color:#991b1b;">Finance</span> — Invoice, Pembayaran, Jurnal, Laporan</div>
                    </div>
                </div>

                <div style="display:flex; gap:12px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> {{ isset($user) ? 'Perbarui' : 'Simpan' }}
                    </button>
                    <a href="{{ route('master.users.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
