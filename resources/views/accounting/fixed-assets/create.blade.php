@extends('layouts.app')
@section('title', 'Tambah / Beli Aset Tetap')
@section('page-title', 'Tambah / Beli Aset Tetap')

@section('content')
<div class="animate-in" x-data="{
    acquisitionType: '{{ old('acquisition_type', 'purchase') }}',
    qty: {{ old('quantity', 1) }},
    unitCost: {{ old('unit_cost', 0) }},
    showSerialInputs: false,
    get totalCost() {
        return (parseFloat(this.qty) || 0) * (parseFloat(this.unitCost) || 0);
    },
    formatRupiah(num) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(num || 0);
    }
}">
    {{-- Header Section --}}
    <div class="page-header">
        <div>
            <h1>Tambah / Beli Aset Tetap</h1>
            <p>Mendaftarkan unit fisik inventaris atau mencatat pembelian tunai aset baru</p>
        </div>
        <a href="{{ route('accounting.fixed-assets.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar
        </a>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger" style="margin-bottom: 16px;">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div>
            <strong>Mohon perbaiki data input berikut:</strong>
            <ul style="margin-top: 4px; padding-left: 16px; list-style: disc;">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('accounting.fixed-assets.store') }}">
        @csrf

        {{-- 1. Pilihan Jenis Perolehan --}}
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-route" style="color: #2563eb; margin-right: 6px;"></i> 1. Pilih Skema Perolehan Aset <span style="color: #e11d48;">*</span></h3>
            </div>
            <div class="card-body">
                <div class="form-row form-row-2">
                    {{-- Opsi A: Pembelian Baru --}}
                    <label style="position: relative; display: flex; align-items: flex-start; padding: 14px; border-radius: 8px; cursor: pointer; transition: all 0.15s;"
                           :style="acquisitionType === 'purchase' ? 'border: 2px solid #2563eb; background: rgba(239,246,255,0.5);' : 'border: 2px solid #e2e8f0; background: #fff;'">
                        <input type="radio" name="acquisition_type" value="purchase" x-model="acquisitionType" style="margin-top: 3px;">
                        <div style="margin-left: 12px;">
                            <div style="font-weight: 700; font-size: 14px; color: #0e1b35; display: flex; align-items: center; gap: 8px; font-family: 'Public Sans', sans-serif;">
                                <span style="width: 24px; height: 24px; border-radius: 6px; background: #2563eb; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 11px;">
                                    <i class="fa-solid fa-money-bill-wave"></i>
                                </span>
                                <span>Pembelian Baru (Tunai)</span>
                            </div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 6px; line-height: 1.5; font-family: 'Inter', sans-serif;">
                                Aset dibeli hari ini. Sistem akan otomatis memotong akun <strong>Kas/Bank</strong> dan mendebit akun <strong>Aset Tetap</strong> sebesar total pembelian.
                            </div>
                        </div>
                    </label>

                    {{-- Opsi B: Saldo Awal --}}
                    <label style="position: relative; display: flex; align-items: flex-start; padding: 14px; border-radius: 8px; cursor: pointer; transition: all 0.15s;"
                           :style="acquisitionType === 'opening_balance' ? 'border: 2px solid #d97706; background: rgba(255,251,235,0.5);' : 'border: 2px solid #e2e8f0; background: #fff;'">
                        <input type="radio" name="acquisition_type" value="opening_balance" x-model="acquisitionType" style="margin-top: 3px;">
                        <div style="margin-left: 12px;">
                            <div style="font-weight: 700; font-size: 14px; color: #0e1b35; display: flex; align-items: center; gap: 8px; font-family: 'Public Sans', sans-serif;">
                                <span style="width: 24px; height: 24px; border-radius: 6px; background: #d97706; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 11px;">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                </span>
                                <span>Saldo Awal / Aset Lama</span>
                            </div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 6px; line-height: 1.5; font-family: 'Inter', sans-serif;">
                                Aset yang sudah dimiliki sebelum ERP digunakan. <strong>Tanpa mutasi kas baru</strong>, murni sebagai pencatatan unit inventaris fisik.
                            </div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        {{-- 2. Data Pokok Aset --}}
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-file-invoice" style="color: #2563eb; margin-right: 6px;"></i> 2. Informasi Pokok & Pembukuan Aset</h3>
            </div>
            <div class="card-body">
                <div class="form-row form-row-2">
                    {{-- Kategori Aset --}}
                    <div class="form-group">
                        <label class="form-label">Kategori / Group Aset <span style="color: #e11d48;">*</span></label>
                        <select name="category_id" class="form-control" required>
                            <option value="">-- Pilih Kategori Aset --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }} ({{ $cat->code }}) &bull; Akun CoA: {{ $cat->account?->code }} - {{ $cat->account?->name }}
                                </option>
                            @endforeach
                        </select>
                        <p style="font-size: 11px; color: #94a3b8; margin-top: 4px; font-family: 'Inter', sans-serif;">Kategori menentukan kode awalan aset dan akun pembukuan di neraca.</p>
                    </div>

                    {{-- Nama Aset --}}
                    <div class="form-group">
                        <label class="form-label">Nama / Deskripsi Aset <span style="color: #e11d48;">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Contoh: Laptop Lenovo ThinkPad / Honda Vario 160" class="form-control" required>
                        <p style="font-size: 11px; color: #94a3b8; margin-top: 4px; font-family: 'Inter', sans-serif;">Nama umum aset yang akan tertera pada label inventaris.</p>
                    </div>

                    {{-- Tanggal Perolehan --}}
                    <div class="form-group">
                        <label class="form-label">Tanggal Perolehan / Pembelian <span style="color: #e11d48;">*</span></label>
                        <input type="date" name="purchase_date" value="{{ old('purchase_date', date('Y-m-d')) }}" class="form-control" required>
                    </div>

                    {{-- Akun Kas/Bank Pembayar --}}
                    <div class="form-group" x-show="acquisitionType === 'purchase'" x-transition>
                        <label class="form-label">Akun Pembayaran (Kas/Bank) <span style="color: #e11d48;">*</span></label>
                        <select name="payment_account_id" class="form-control" :required="acquisitionType === 'purchase'">
                            <option value="">-- Pilih Akun Kas / Bank --</option>
                            @foreach($paymentAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ old('payment_account_id') == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->code }} - {{ $acc->name }}
                                </option>
                            @endforeach
                        </select>
                        <p style="font-size: 11px; color: #94a3b8; margin-top: 4px; font-family: 'Inter', sans-serif;">Saldo akun kas ini akan dipotong sebesar total perolehan.</p>
                    </div>
                </div>

                {{-- Info Box Saldo Awal --}}
                <div x-show="acquisitionType === 'opening_balance'" x-transition
                     style="padding: 12px 14px; border-radius: 6px; background: #fffbeb; border: 1px solid #fde68a; color: #92400e; font-size: 13px; display: flex; align-items: center; gap: 10px; margin-top: 8px; font-family: 'Inter', sans-serif;">
                    <i class="fa-solid fa-circle-info" style="color: #d97706; font-size: 16px; flex-shrink: 0;"></i>
                    <span><strong>Mode Saldo Awal Aktif:</strong> Tidak ada kas keluar hari ini. Nilai buku aset diambil dari pengisian Saldo Awal Neraca.</span>
                </div>
            </div>
        </div>

        {{-- 3. Kuantitas, Nilai & Generator Unit Detail --}}
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-calculator" style="color: #2563eb; margin-right: 6px;"></i> 3. Kuantitas & Rincian Nilai</h3>
            </div>
            <div class="card-body">
                <div class="form-row form-row-3">
                    <div class="form-group">
                        <label class="form-label">Jumlah Unit (Qty) <span style="color: #e11d48;">*</span></label>
                        <input type="number" name="quantity" min="1" max="100" x-model.number="qty" class="form-control" style="font-family: 'Inter', monospace; font-weight: 700;" required>
                        <p style="font-size: 11px; color: #64748b; margin-top: 4px; font-family: 'Inter', sans-serif;">Sistem akan membuat <strong style="color: #1d4ed8;" x-text="qty"></strong> baris detail aset terpisah.</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Harga Satuan / Unit Cost (Rp) <span style="color: #e11d48;">*</span></label>
                        <input type="number" name="unit_cost" min="0" step="1000" x-model.number="unitCost" placeholder="0" class="form-control" style="font-family: 'Inter', monospace; font-weight: 700;" required>
                        <p style="font-size: 11px; color: #64748b; margin-top: 4px; font-family: 'Inter', sans-serif;" x-text="formatRupiah(unitCost) + ' / unit'"></p>
                    </div>

                    <div style="padding: 14px; background: linear-gradient(135deg, #eff6ff, #eef2ff); border: 1px solid #bfdbfe; border-radius: 8px; display: flex; flex-direction: column; justify-content: center;">
                        <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #1d4ed8; font-family: 'Public Sans', sans-serif;">Total Nilai Perolehan:</span>
                        <span style="font-size: 18px; font-family: 'Inter', monospace; font-weight: 800; color: #0e1b35; margin-top: 4px;" x-text="formatRupiah(totalCost)"></span>
                        <span style="font-size: 11px; color: #2563eb; margin-top: 2px; font-family: 'Inter', sans-serif;" x-text="qty + ' unit × ' + formatRupiah(unitCost)"></span>
                    </div>
                </div>

                {{-- Input Serial Number Opsional per Unit --}}
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <div>
                            <div style="font-size: 12px; font-weight: 700; color: #0e1b35; text-transform: uppercase; letter-spacing: 0.05em; font-family: 'Public Sans', sans-serif;">Nomor Seri / No. Rangka Spesifik (Opsional)</div>
                            <div style="font-size: 12px; color: #94a3b8; margin-top: 2px; font-family: 'Inter', sans-serif;">Dapat diisi sekarang untuk masing-masing unit atau dilengkapi nanti.</div>
                        </div>
                        <button type="button" @click="showSerialInputs = !showSerialInputs" class="btn btn-sm btn-secondary">
                            <i class="fa-solid fa-list-ol"></i>
                            <span x-text="showSerialInputs ? 'Tutup No. Seri' : 'Isi No. Seri per Unit'"></span>
                        </button>
                    </div>

                    <div x-show="showSerialInputs" x-transition style="background: #f8fafc; padding: 14px; border-radius: 8px; border: 1px solid #e2e8f0; max-height: 240px; overflow-y: auto;">
                        <template x-for="i in Math.min(qty, 100)" :key="i">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                <span style="font-size: 12px; font-family: 'Inter', monospace; font-weight: 700; color: #64748b; width: 60px;" x-text="'Unit #' + i + ':'"></span>
                                <input type="text" :name="'serial_numbers[]'" :placeholder="'Serial number / No. Rangka unit #' + i" class="form-control" style="flex: 1;">
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. Lokasi & Catatan --}}
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-location-dot" style="color: #2563eb; margin-right: 6px;"></i> 4. Lokasi Penempatan & Catatan</h3>
            </div>
            <div class="card-body">
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Lokasi Penempatan</label>
                        <input type="text" name="location" value="{{ old('location') }}" placeholder="Contoh: Kantor Pusat Lt. 2 / Gudang A" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Masa Manfaat (Bulan) & Residu</label>
                        <div class="form-row form-row-2">
                            <input type="number" name="useful_life_months" value="{{ old('useful_life_months') }}" placeholder="Bulan" class="form-control">
                            <input type="number" name="salvage_value" value="{{ old('salvage_value', 0) }}" placeholder="Residu Rp" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan Tambahan</label>
                    <textarea name="notes" rows="2" placeholder="Keterangan spesifikasi teknis, kelengkapan, dll..." class="form-control">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Tombol Submit --}}
        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; margin-top: 8px; margin-bottom: 32px;">
            <a href="{{ route('accounting.fixed-assets.index') }}" class="btn btn-secondary">
                Batal
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Simpan & Daftarkan Aset</span>
            </button>
        </div>
    </form>
</div>
@endsection
