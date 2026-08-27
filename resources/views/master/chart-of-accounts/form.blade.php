@extends('layouts.app')
@section('title', isset($chartOfAccount) ? 'Edit Akun CoA' : 'Tambah Akun CoA')
@section('page-title', isset($chartOfAccount) ? 'Edit Akun CoA' : 'Tambah Akun CoA')

@section('content')
<div class="animate-in" x-data="{
    code: '{{ old('code', $chartOfAccount->code ?? '') }}',
    name: '{{ old('name', $chartOfAccount->name ?? '') }}',
    type: '{{ old('type', $chartOfAccount->type ?? 'asset') }}',
    normalBalance: '{{ old('normal_balance', $chartOfAccount->normal_balance ?? 'debit') }}',
    parentId: '{{ old('parent_id', $chartOfAccount->parent_id ?? '') }}',
    parentName: '{{ isset($chartOfAccount) && $chartOfAccount->parent ? $chartOfAccount->parent->name : '' }}',
    parentCode: '{{ isset($chartOfAccount) && $chartOfAccount->parent ? $chartOfAccount->parent->code : '' }}',
    description: '{{ old('description', $chartOfAccount->description ?? '') }}',
    isActive: {{ old('is_active', $chartOfAccount->is_active ?? true) ? 'true' : 'false' }},

    onParentChange(e) {
        const sel = e.target;
        const opt = sel.options[sel.selectedIndex];
        this.parentId = sel.value;
        this.parentName = opt.getAttribute('data-name') || '';
        this.parentCode = opt.getAttribute('data-code') || '';
        const pType = opt.getAttribute('data-type');
        const pBal = opt.getAttribute('data-balance');
        if (pType) { this.type = pType; }
        if (pBal) { this.normalBalance = pBal; }
    },
    onTypeChange(newType) {
        this.type = newType;
        if (newType === 'asset' || newType === 'expense') {
            this.normalBalance = 'debit';
        } else {
            this.normalBalance = 'credit';
        }
    }
}">
    <div class="page-header">
        <div>
            <h1>{{ isset($chartOfAccount) ? 'Edit Akun: ' . $chartOfAccount->name : 'Tambah Akun CoA Baru' }}</h1>
            <p>Konfigurasikan kode akun, hierarki induk, tipe kategori, dan saldo normal pembukuan</p>
        </div>
        <a href="{{ route('master.chart-of-accounts.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar CoA
        </a>
    </div>

    <div style="display:flex; flex-wrap:wrap; gap:20px; align-items:flex-start;">
        {{-- Form Column --}}
        <div class="card" style="flex:1 1 560px; min-width:320px;">
            <div class="card-header" style="background:#fafbfc;">
                <h3><i class="fa-solid fa-file-pen" style="color:var(--primary); margin-right:8px;"></i> Formulir Informasi Akun</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ isset($chartOfAccount) ? route('master.chart-of-accounts.update', $chartOfAccount) : route('master.chart-of-accounts.store') }}" id="coaForm">
                    @csrf
                    @if(isset($chartOfAccount)) @method('PUT') @endif

                    {{-- Row 1: Akun Induk & Kode Akun --}}
                    <div class="form-row form-row-2">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fa-solid fa-sitemap text-primary"></i> Akun Induk (Parent Account)
                            </label>
                            <select name="parent_id" class="form-control {{ $errors->has('parent_id') ? 'is-invalid' : '' }}" @change="onParentChange($event)">
                                <option value="" data-name="" data-code="" data-type="" data-balance="">-- Tidak Ada (Akun Utama / Tingkat Teratas) --</option>
                                @if(isset($parentAccounts))
                                @foreach($parentAccounts as $p)
                                <option value="{{ $p->id }}" data-name="{{ $p->name }}" data-code="{{ $p->code }}" data-type="{{ $p->type }}" data-balance="{{ $p->normal_balance }}"
                                    {{ old('parent_id', $chartOfAccount->parent_id ?? '') == $p->id ? 'selected' : '' }}>
                                    [{{ $p->code }}] {{ $p->name }} ({{ ucfirst($p->type) }})
                                </option>
                                @endforeach
                                @endif
                            </select>
                            <span class="form-text" style="font-size:11px; color:var(--text-secondary); margin-top:3px; display:block;">Pilih akun induk jika akun ini merupakan sub-akun.</span>
                            @error('parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Kode Akun <span style="color:var(--danger);">*</span></label>
                            <input type="text" name="code" x-model="code" value="{{ old('code', $chartOfAccount->code ?? '') }}"
                                class="form-control {{ $errors->has('code') ? 'is-invalid' : '' }}"
                                required placeholder="Contoh: 1-1100, 1-1110, 5-2100" style="font-family:monospace; font-weight:700; text-transform:uppercase;">
                            @if(!$errors->has('code'))
                            <span class="form-text" style="font-size:11px; color:var(--text-secondary); margin-top:3px; display:block;">Format standar: <code>1-xxxx</code> (Aset), <code>2-xxxx</code> (Kewajiban), dst.</span>
                            @endif
                            @error('code')
                            <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca;">
                                <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                                <span>{{ $message }}</span>
                            </div>
                            @enderror
                        </div>
                    </div>

                    {{-- Row 2: Nama Akun & Tipe Akun --}}
                    <div class="form-row form-row-2" style="margin-top:12px;">
                        <div class="form-group">
                            <label class="form-label">Nama Akun <span style="color:var(--danger);">*</span></label>
                            <input type="text" name="name" x-model="name" value="{{ old('name', $chartOfAccount->name ?? '') }}"
                                class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" required placeholder="Contoh: Bank BCA, Kas Toko, Piutang Dagang">
                            @error('name')
                            <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca;">
                                <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                                <span>{{ $message }}</span>
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tipe Akun (Kategori) <span style="color:var(--danger);">*</span></label>
                            <select name="type" x-model="type" @change="onTypeChange($event.target.value)" class="form-control {{ $errors->has('type') ? 'is-invalid' : '' }}" required>
                                <option value="asset">Aset (Aktiva)</option>
                                <option value="liability">Kewajiban (Hutang)</option>
                                <option value="equity">Ekuitas (Modal)</option>
                                <option value="revenue">Pendapatan (Penjualan)</option>
                                <option value="expense">Beban / Biaya Operasional</option>
                            </select>
                            @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Row 3: Normal Balance & Keterangan --}}
                    <div class="form-row form-row-2" style="margin-top:12px;">
                        <div class="form-group">
                            <label class="form-label">Normal Balance (Saldo Normal) <span style="color:var(--danger);">*</span></label>
                            <div style="display:flex; gap:16px; padding:10px 14px; background:#f8fafc; border-radius:8px; border:1px solid var(--border);">
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600;">
                                    <input type="radio" name="normal_balance" value="debit" x-model="normalBalance" required>
                                    <span style="color:#2563eb;"><i class="fa-solid fa-arrow-down-left" style="font-size:11px; margin-right:2px;"></i> Debit</span>
                                    <span style="font-size:11px; color:var(--text-secondary); font-weight:400;">(Aset & Beban)</span>
                                </label>
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600;">
                                    <input type="radio" name="normal_balance" value="credit" x-model="normalBalance" required>
                                    <span style="color:#dc2626;"><i class="fa-solid fa-arrow-up-right" style="font-size:11px; margin-right:2px;"></i> Kredit</span>
                                    <span style="font-size:11px; color:var(--text-secondary); font-weight:400;">(Hutang, Modal, Omset)</span>
                                </label>
                            </div>
                            @error('normal_balance')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Keterangan / Fungsi Akun</label>
                            <textarea name="description" x-model="description" class="form-control" rows="2" placeholder="Catatan kegunaan akun dalam transaksi...">{{ old('description', $chartOfAccount->description ?? '') }}</textarea>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:14px;">
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer; background:#f8fafc; padding:10px 14px; border-radius:8px; border:1px solid var(--border); width:fit-content;">
                            <input type="checkbox" name="is_active" value="1" x-model="isActive" style="width:18px; height:18px; cursor:pointer;">
                            <span style="font-size:14px; font-weight:600; color:var(--text-primary);">Status Akun Aktif (Dapat Dijurnal)</span>
                        </label>
                    </div>

                    <div style="display:flex; gap:12px; margin-top:24px; padding-top:16px; border-top:1px solid var(--border);">
                        <button type="submit" class="btn btn-primary" style="padding:10px 24px; font-weight:600;">
                            <i class="fa-solid fa-floppy-disk"></i> {{ isset($chartOfAccount) ? 'Perbarui Akun' : 'Simpan Akun CoA' }}
                        </button>
                        <a href="{{ route('master.chart-of-accounts.index') }}" class="btn btn-secondary" style="padding:10px 20px;">Batal</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Interactive Live Preview & Accounting Guide --}}
        <div style="flex:0 0 340px; min-width:280px; display:flex; flex-direction:column; gap:20px;">
            {{-- Live Mockup Preview Card --}}
            <div class="card" style="border-top:3px solid var(--primary); background:#ffffff;">
                <div class="card-header" style="background:#fafbfc;">
                    <h3 style="font-size:14px; display:flex; align-items:center; gap:6px;">
                        <i class="fa-solid fa-eye" style="color:var(--primary);"></i> Live Preview Akun
                    </h3>
                </div>
                <div class="card-body" style="padding:18px;">
                    <div style="padding:14px; background:#f8fafc; border-radius:10px; border:1px solid var(--border);">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
                            <span style="font-family:monospace; font-size:14px; font-weight:800; color:var(--primary);" x-text="code || 'KODE-AKUN'"></span>
                            <span class="badge" style="font-size:10px; text-transform:uppercase;"
                                :class="normalBalance === 'debit' ? 'badge-confirmed' : 'badge-pending'"
                                x-text="normalBalance.toUpperCase()"></span>
                        </div>
                        <div style="font-size:15px; font-weight:700; color:#1e293b; margin-bottom:6px;" x-text="name || 'Nama Akun Akan Tampil Di Sini'"></div>
                        
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:8px;">
                            <span style="display:inline-block; font-weight:600; text-transform:capitalize;" x-text="type"></span>
                            <template x-if="parentName">
                                <span> · Sub-akun dari: <strong style="color:var(--primary);" x-text="'[' + parentCode + '] ' + parentName"></strong></span>
                            </template>
                            <template x-if="!parentName">
                                <span style="color:#64748b;"> · Akun Induk Level 1</span>
                            </template>
                        </div>

                        <div style="font-size:12px; color:#64748b; font-style:italic;" x-text="description || 'Belum ada keterangan'"></div>

                        <div style="margin-top:10px; padding-top:8px; border-top:1px dashed #cbd5e1; display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-size:11px; color:#64748b;">Status:</span>
                            <span class="badge" :class="isActive ? 'badge-done' : 'badge-cancelled'" x-text="isActive ? 'Aktif' : 'Nonaktif'"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Accounting Tip Card --}}
            <div class="card" style="background:#f0f9ff; border:1px solid #bae6fd;">
                <div class="card-body" style="padding:16px;">
                    <div style="font-size:13px; font-weight:700; color:#0369a1; margin-bottom:6px; display:flex; align-items:center; gap:6px;">
                        <i class="fa-solid fa-lightbulb" style="color:#0284c7;"></i> Panduan Akuntansi (CoA)
                    </div>
                    <ul style="font-size:12px; color:#0c4a6e; margin:0; padding-left:18px; line-height:1.6;">
                        <li><strong>Aset & Beban</strong> bertambah di posisi <strong>Debit</strong>.</li>
                        <li><strong>Kewajiban, Modal & Pendapatan</strong> bertambah di posisi <strong>Kredit</strong>.</li>
                        <li>Memilih <strong>Akun Induk</strong> otomatis menyesuaikan Tipe & Normal Balance akun.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
