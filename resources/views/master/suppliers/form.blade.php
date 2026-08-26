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

    <div class="card w-full">
        <div class="card-header"><h3>Informasi Supplier</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ isset($supplier) ? route('master.suppliers.update', $supplier) : route('master.suppliers.store') }}">
                @csrf
                @if(isset($supplier)) @method('PUT') @endif

                <!-- Row 1: 4 Kolom (Kode, Nama, CP, Payment Term) -->
                <div class="form-row form-row-4">
                    <div class="form-group">
                        <label class="form-label">Kode Supplier <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="code" value="{{ old('code', $supplier->code ?? '') }}"
                            class="form-control {{ $errors->has('code') ? 'is-invalid' : '' }}" required placeholder="Contoh: SUP-001, PT-ABC" style="text-transform:uppercase;">
                        @if(!$errors->has('code'))
                        <span class="form-text" style="font-size:11px; color:var(--text-secondary); margin-top:3px;">Kode unik pengenal supplier.</span>
                        @endif
                        @error('code')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Supplier <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $supplier->name ?? '') }}"
                            class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" required placeholder="Contoh: PT. Sumber Makmur Abadi">
                        @error('name')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" value="{{ old('contact_person', $supplier->contact_person ?? '') }}" class="form-control" placeholder="Nama PIC / Kontak">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Term <span style="color:var(--danger);">*</span></label>
                        <select name="payment_term" class="form-control {{ $errors->has('payment_term') ? 'is-invalid' : '' }}" required>
                            <option value="">-- Pilih Syarat --</option>
                            @foreach(['COD','NET 7','NET 14','NET 30','NET 45','NET 60'] as $term)
                            <option value="{{ $term }}" {{ old('payment_term', $supplier->payment_term ?? '') === $term ? 'selected' : '' }}>{{ $term }}</option>
                            @endforeach
                        </select>
                        @error('payment_term')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                </div>

                <!-- Row 2: 4 Kolom (Telepon, Email, NPWP, KTP / NIK) -->
                <div class="form-row form-row-4">
                    <div class="form-group">
                        <label class="form-label">No. Telepon / WA <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="phone" value="{{ old('phone', $supplier->phone ?? '') }}" class="form-control {{ $errors->has('phone') ? 'is-invalid' : '' }}" required placeholder="08xx-xxxx-xxxx">
                        @error('phone')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ old('email', $supplier->email ?? '') }}" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" placeholder="supplier@example.com">
                        @error('email')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nomor NPWP</label>
                        <input type="text" name="npwp" value="{{ old('npwp', $supplier->npwp ?? '') }}" class="form-control {{ $errors->has('npwp') ? 'is-invalid' : '' }}" placeholder="00.000.000.0-000.000">
                        <span class="form-text" style="font-size:11px; color:var(--text-secondary); margin-top:3px;">Untuk supplier badan usaha / PKP.</span>
                        @error('npwp')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nomor KTP / NIK</label>
                        <input type="text" name="ktp_number" value="{{ old('ktp_number', $supplier->ktp_number ?? '') }}" class="form-control {{ $errors->has('ktp_number') ? 'is-invalid' : '' }}" placeholder="16 digit NIK / KTP" maxlength="20">
                        <span class="form-text" style="font-size:11px; color:var(--text-secondary); margin-top:3px;">Jika perorangan tanpa NPWP.</span>
                        @error('ktp_number')
                        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                </div>

                <!-- Row 3: Alamat Lengkap (Full width) -->
                <div class="form-group">
                    <label class="form-label">Alamat Lengkap <span style="color:var(--danger);">*</span></label>
                    <textarea name="address" class="form-control {{ $errors->has('address') ? 'is-invalid' : '' }}" rows="2" required placeholder="Masukkan alamat lengkap supplier / kantor...">{{ old('address', $supplier->address ?? '') }}</textarea>
                    @error('address')
                    <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                        <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                        <span>{{ $message }}</span>
                    </div>
                    @enderror
                </div>

                <!-- ========================================================= -->
                <!-- INFORMASI REKENING BANK (SECTION) -->
                <!-- ========================================================= -->
                <div class="p-4 rounded-lg border border-border-light bg-[#FAF9FF] mb-5 w-full" 
                     x-data="{
                        open: false,
                        search: '',
                        selectedBank: '{{ old('bank_name', $supplier->bank_name ?? '') }}',
                        banks: [
                            'Bank Central Asia (BCA)',
                            'Bank Mandiri',
                            'Bank Rakyat Indonesia (BRI)',
                            'Bank Negara Indonesia (BNI)',
                            'Bank Syariah Indonesia (BSI)',
                            'Bank CIMB Niaga',
                            'Bank Permata',
                            'Bank Danamon',
                            'Bank Tabungan Negara (BTN)',
                            'Bank OCBC NISP',
                            'Bank Panin',
                            'Bank Mega',
                            'Bank Maybank Indonesia',
                            'Bank BTPN / Jenius',
                            'Bank Jago',
                            'SeaBank Indonesia',
                            'Bank Neo Commerce (BNC)',
                            'Bank Sinarmas',
                            'Bank UOB Indonesia',
                            'Bank Commonwealth',
                            'Bank Muamalat',
                            'Bank BCA Syariah',
                            'Bank DKI',
                            'Bank BJB',
                            'Bank Jateng',
                            'Bank Jatim'
                        ],
                        get filteredBanks() {
                            if (!this.search.trim()) return this.banks;
                            const q = this.search.toLowerCase();
                            return this.banks.filter(b => b.toLowerCase().includes(q));
                        },
                        selectBank(bank) {
                            this.selectedBank = bank;
                            this.open = false;
                            this.search = '';
                        },
                        clearBank() {
                            this.selectedBank = '';
                            this.search = '';
                        }
                     }">
                    
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-sm font-bold text-[#0e1b35] font-sans">Informasi Rekening Bank</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#FEE2E2] text-[#B91C1C] border border-[#B91C1C]/20">Wajib Diisi</span>
                    </div>
                    <p class="text-xs text-on-surface-variant mb-4">Rekening tujuan transfer pembayaran ke supplier ini.</p>

                    <!-- Hidden Input for Form Submission -->
                    <input type="hidden" name="bank_name" :value="selectedBank" required>

                    <!-- Grid 3: Nama Bank, Nomor Rekening, Nama Pemilik Rekening -->
                    <div class="form-row form-row-3 mb-0">
                        <!-- Nama Bank (Searchable Dropdown) -->
                        <div class="form-group mb-0 relative">
                            <label class="form-label">Nama Bank <span style="color:var(--danger);">*</span></label>
                            
                            <!-- Dropdown Trigger Button -->
                            <div class="relative">
                                <button type="button" 
                                        @click="open = !open; if(open) $nextTick(() => $refs.bankSearchInput.focus())"
                                        class="w-full h-[40px] px-3.5 rounded bg-white border border-[#CBD5E1] text-left text-sm text-[#0e1b35] flex items-center justify-between focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all {{ $errors->has('bank_name') ? 'border-red-500' : '' }}">
                                    <span x-text="selectedBank ? selectedBank : '-- Pilih / Cari Nama Bank --'" 
                                          :class="!selectedBank ? 'text-[#6B7280]' : 'font-medium text-[#0e1b35]'"></span>
                                    <div class="flex items-center gap-1">
                                        <template x-if="selectedBank">
                                            <span @click.stop="clearBank()" class="material-symbols-outlined text-[16px] text-gray-400 hover:text-red-500 cursor-pointer p-0.5">close</span>
                                        </template>
                                        <span class="material-symbols-outlined text-[20px] text-gray-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''">expand_more</span>
                                    </div>
                                </button>

                                <!-- Dropdown Menu with Search -->
                                <div x-show="open" 
                                     x-transition 
                                     @click.outside="open = false" 
                                     style="display: none;"
                                     class="absolute left-0 top-full mt-1 w-full bg-white rounded-lg shadow-xl border border-[#E2E8F0] z-50 overflow-hidden">
                                    
                                    <!-- Search Input inside dropdown -->
                                    <div class="p-2 border-b border-[#E2E8F0] bg-[#F8FAFC]">
                                        <div class="relative flex items-center">
                                            <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-[18px] pointer-events-none">search</span>
                                            <input type="text" 
                                                   x-ref="bankSearchInput"
                                                   x-model="search" 
                                                   placeholder="Ketik untuk mencari bank..." 
                                                   class="search-input w-full h-[34px] rounded border border-[#CBD5E1] bg-white text-xs text-[#0e1b35] focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                                                   style="padding-left: 2rem !important; height: 34px !important;"
                                                   @keydown.escape="open = false"
                                                   @keydown.enter.prevent="if(filteredBanks.length > 0) selectBank(filteredBanks[0]); else if(search.trim()) selectBank(search.trim())">
                                        </div>
                                    </div>

                                    <!-- Bank List Options -->
                                    <div class="max-h-56 overflow-y-auto py-1 text-sm divide-y divide-gray-50">
                                        <template x-for="b in filteredBanks" :key="b">
                                            <button type="button" 
                                                    @click="selectBank(b)"
                                                    class="w-full text-left px-3.5 py-2 hover:bg-[#F1F5F9] flex items-center justify-between text-xs text-[#0e1b35] transition-colors"
                                                    :class="selectedBank === b ? 'bg-blue-50 font-bold text-primary' : ''">
                                                <span x-text="b"></span>
                                                <template x-if="selectedBank === b">
                                                    <span class="material-symbols-outlined text-[16px] text-primary">check</span>
                                                </template>
                                            </button>
                                        </template>

                                        <!-- When no exact match found, allow custom input -->
                                        <template x-if="filteredBanks.length === 0 && search.trim()">
                                            <div class="p-3 text-center">
                                                <p class="text-xs text-gray-500 mb-2">Bank tidak ada di daftar?</p>
                                                <button type="button" 
                                                        @click="selectBank(search.trim())"
                                                        class="btn btn-secondary btn-sm w-full text-xs font-semibold">
                                                    Gunakan: "<span x-text="search.trim()"></span>"
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            @error('bank_name')
                            <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                                <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                                <span>{{ $message }}</span>
                            </div>
                            @enderror
                        </div>

                        <!-- Nomor Rekening -->
                        <div class="form-group mb-0">
                            <label class="form-label">Nomor Rekening <span style="color:var(--danger);">*</span></label>
                            <input type="text" 
                                   name="bank_account_number" 
                                   value="{{ old('bank_account_number', $supplier->bank_account_number ?? '') }}" 
                                   class="form-control {{ $errors->has('bank_account_number') ? 'is-invalid' : '' }}" 
                                   required
                                   placeholder="Contoh: 0123456789">
                            @error('bank_account_number')
                            <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                                <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                                <span>{{ $message }}</span>
                            </div>
                            @enderror
                        </div>

                        <!-- Nama Pemilik Rekening -->
                        <div class="form-group mb-0">
                            <label class="form-label">Nama Pemilik Rekening <span style="color:var(--danger);">*</span></label>
                            <input type="text" 
                                   name="bank_account_holder" 
                                   value="{{ old('bank_account_holder', $supplier->bank_account_holder ?? '') }}" 
                                   class="form-control {{ $errors->has('bank_account_holder') ? 'is-invalid' : '' }}" 
                                   required
                                   placeholder="Nama a/n di buku tabungan">
                            @error('bank_account_holder')
                            <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                                <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                                <span>{{ $message }}</span>
                            </div>
                            @enderror
                        </div>
                    </div>
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
