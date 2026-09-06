@extends('layouts.app')
@section('title', 'Tambah / Beli Aset Tetap')
@section('page-title', 'Tambah / Beli Aset Tetap')

@section('content')
<div class="animate-in max-w-4xl mx-auto flex flex-col gap-6 pb-12" x-data="{
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
    <div class="page-header flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Tambah / Beli Aset Tetap</h1>
            <p class="text-xs text-slate-500">Mendaftarkan unit fisik inventaris atau mencatat pembelian tunai aset baru</p>
        </div>
        <a href="{{ route('accounting.fixed-assets.index') }}" class="btn btn-secondary px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke Daftar</span>
        </a>
    </div>

    @if ($errors->any())
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm shadow-xs">
        <div class="font-bold flex items-center gap-2 mb-2">
            <i class="fa-solid fa-triangle-exclamation text-rose-600"></i> Mohon perbaiki data input berikut:
        </div>
        <ul class="list-disc pl-5 space-y-1 text-xs">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('accounting.fixed-assets.store') }}" class="flex flex-col gap-6">
        @csrf

        {{-- 1. Pilihan Jenis Perolehan (Acquisition Type) --}}
        <div class="card p-6 border border-slate-200 rounded-2xl shadow-xs bg-white">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">
                1. Pilih Skema Perolehan Aset <span class="text-rose-500">*</span>
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Opsi A: Pembelian Baru --}}
                <label class="relative flex items-start p-4 rounded-2xl border-2 cursor-pointer transition-all"
                       :class="acquisitionType === 'purchase' ? 'border-blue-600 bg-blue-50/40 shadow-sm ring-2 ring-blue-500/10' : 'border-slate-200 hover:border-slate-300 bg-white'">
                    <input type="radio" name="acquisition_type" value="purchase" x-model="acquisitionType" class="mt-1 text-blue-600 focus:ring-blue-500">
                    <div class="ml-3.5">
                        <div class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-blue-600 text-white flex items-center justify-center text-xs">
                                <i class="fa-solid fa-cart-shopping"></i>
                            </span>
                            <span>Pembelian Baru (Tunai)</span>
                        </div>
                        <div class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                            Aset dibeli hari ini. Sistem akan otomatis memotong akun <strong>Kas/Bank</strong> dan mendebit akun <strong>Aset Tetap</strong> sebesar total pembelian.
                        </div>
                    </div>
                </label>

                {{-- Opsi B: Saldo Awal / Aset Bawaan --}}
                <label class="relative flex items-start p-4 rounded-2xl border-2 cursor-pointer transition-all"
                       :class="acquisitionType === 'opening_balance' ? 'border-amber-600 bg-amber-50/40 shadow-sm ring-2 ring-amber-500/10' : 'border-slate-200 hover:border-slate-300 bg-white'">
                    <input type="radio" name="acquisition_type" value="opening_balance" x-model="acquisitionType" class="mt-1 text-amber-600 focus:ring-amber-500">
                    <div class="ml-3.5">
                        <div class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-amber-500 text-white flex items-center justify-center text-xs">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </span>
                            <span>Saldo Awal / Aset Lama</span>
                        </div>
                        <div class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                            Aset yang sudah dimiliki sebelum ERP digunakan. <strong>Tanpa mutasi kas baru</strong>, murni sebagai pencatatan unit inventaris fisik.
                        </div>
                    </div>
                </label>
            </div>
        </div>

        {{-- 2. Data Pokok Aset --}}
        <div class="card p-6 border border-slate-200 rounded-2xl shadow-xs bg-white">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4 pb-3 border-b border-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-file-invoice text-blue-600"></i> 2. Informasi Pokok & Pembukuan Aset
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                {{-- Kategori Aset --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Kategori / Group Aset <span class="text-rose-500">*</span>
                    </label>
                    <select name="category_id" class="form-control text-sm h-11 rounded-xl border-slate-200 focus:ring-2 focus:ring-blue-500/20" required>
                        <option value="">-- Pilih Kategori Aset --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }} ({{ $cat->code }}) &bull; Akun CoA: {{ $cat->account?->code }} - {{ $cat->account?->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1">Kategori menentukan kode awalan aset dan akun pembukuan di neraca.</p>
                </div>

                {{-- Nama Aset --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Nama / Deskripsi Aset <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Contoh: Laptop Lenovo ThinkPad / Honda Vario 160" class="form-control text-sm h-11 rounded-xl border-slate-200 focus:ring-2 focus:ring-blue-500/20" required>
                    <p class="text-[11px] text-slate-400 mt-1">Nama umum aset yang akan tertera pada label inventaris.</p>
                </div>

                {{-- Tanggal Perolehan --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Tanggal Perolehan / Pembelian <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="purchase_date" value="{{ old('purchase_date', date('Y-m-d')) }}" class="form-control text-sm h-11 rounded-xl border-slate-200 focus:ring-2 focus:ring-blue-500/20" required>
                </div>

                {{-- Akun Kas/Bank Pembayar --}}
                <div x-show="acquisitionType === 'purchase'" x-transition>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Akun Pembayaran (Kas/Bank) <span class="text-rose-500">*</span>
                    </label>
                    <select name="payment_account_id" class="form-control text-sm h-11 rounded-xl border-slate-200 focus:ring-2 focus:ring-blue-500/20" :required="acquisitionType === 'purchase'">
                        <option value="">-- Pilih Akun Kas / Bank --</option>
                        @foreach($paymentAccounts as $acc)
                            <option value="{{ $acc->id }}" {{ old('payment_account_id') == $acc->id ? 'selected' : '' }}>
                                {{ $acc->code }} - {{ $acc->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1">Saldo akun kas ini akan dipotong sebesar total perolehan.</p>
                </div>

                {{-- Info Box Saldo Awal --}}
                <div x-show="acquisitionType === 'opening_balance'" class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-center gap-2.5 sm:col-span-2" x-transition>
                    <i class="fa-solid fa-circle-info text-amber-600 text-lg shrink-0"></i>
                    <span><strong>Mode Saldo Awal Aktif:</strong> Tidak ada kas keluar hari ini. Nilai buku aset diambil dari pengisian Saldo Awal Neraca.</span>
                </div>
            </div>
        </div>

        {{-- 3. Kuantitas, Nilai & Generator Unit Detail --}}
        <div class="card p-6 border border-slate-200 rounded-2xl shadow-xs bg-white">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4 pb-3 border-b border-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-calculator text-blue-600"></i> 3. Kuantitas & Rincian Nilai
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Jumlah Unit (Qty) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="quantity" min="1" max="100" x-model.number="qty" class="form-control font-mono font-bold text-base h-11 rounded-xl border-slate-200 focus:ring-2 focus:ring-blue-500/20" required>
                    <p class="text-[11px] text-slate-500 mt-1.5">Sistem akan membuat <strong class="text-blue-700" x-text="qty"></strong> baris detail aset terpisah.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Harga Satuan / Unit Cost (Rp) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="unit_cost" min="0" step="1000" x-model.number="unitCost" placeholder="0" class="form-control font-mono font-bold text-base h-11 rounded-xl border-slate-200 focus:ring-2 focus:ring-blue-500/20" required>
                    <p class="text-[11px] text-slate-500 mt-1.5" x-text="formatRupiah(unitCost) + ' / unit'"></p>
                </div>

                <div class="p-4 bg-gradient-to-br from-blue-50 to-indigo-50/60 border border-blue-200/80 rounded-2xl flex flex-col justify-center">
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-blue-700">Total Nilai Perolehan:</span>
                    <span class="text-xl font-mono font-black text-blue-950 mt-1" x-text="formatRupiah(totalCost)"></span>
                    <span class="text-[11px] text-blue-600 mt-0.5" x-text="qty + ' unit × ' + formatRupiah(unitCost)"></span>
                </div>
            </div>

            {{-- Input Serial Number Opsional per Unit --}}
            <div class="mt-4 pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <div class="text-xs font-bold text-slate-800 uppercase tracking-wider">Nomor Seri / No. Rangka Spesifik (Opsional)</div>
                        <div class="text-xs text-slate-400 mt-0.5">Dapat diisi sekarang untuk masing-masing unit atau dilengkapi nanti.</div>
                    </div>
                    <button type="button" @click="showSerialInputs = !showSerialInputs" class="btn btn-secondary btn-sm text-xs rounded-lg px-3 py-1.5">
                        <i class="fa-solid fa-list-ol mr-1"></i>
                        <span x-text="showSerialInputs ? 'Tutup No. Seri' : 'Isi No. Seri per Unit'"></span>
                    </button>
                </div>

                <div x-show="showSerialInputs" x-transition class="space-y-2 mt-2 bg-slate-50 p-3.5 rounded-xl border border-slate-200 max-h-60 overflow-y-auto">
                    <template x-for="i in Math.min(qty, 100)" :key="i">
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-mono font-bold text-slate-500 w-16" x-text="'Unit #' + i + ':'"></span>
                            <input type="text" :name="'serial_numbers[]'" :placeholder="'Serial number / No. Rangka unit #' + i" class="form-control form-control-sm text-xs rounded-lg flex-1">
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- 4. Lokasi & Catatan --}}
        <div class="card p-6 border border-slate-200 rounded-2xl shadow-xs bg-white">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4 pb-3 border-b border-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-location-dot text-blue-600"></i> 4. Lokasi Penempatan & Catatan
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Lokasi Penempatan</label>
                    <input type="text" name="location" value="{{ old('location') }}" placeholder="Contoh: Kantor Pusat Lt. 2 / Gudang A" class="form-control text-sm h-11 rounded-xl border-slate-200 focus:ring-2 focus:ring-blue-500/20">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Masa Manfaat (Bulan) & Residu</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" name="useful_life_months" value="{{ old('useful_life_months') }}" placeholder="Bulan" class="form-control text-xs h-11 rounded-xl border-slate-200">
                        <input type="number" name="salvage_value" value="{{ old('salvage_value', 0) }}" placeholder="Residu Rp" class="form-control text-xs h-11 rounded-xl border-slate-200">
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Catatan Tambahan</label>
                    <textarea name="notes" rows="2" placeholder="Keterangan spesifikasi teknis, kelengkapan, dll..." class="form-control text-sm rounded-xl border-slate-200">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Tombol Submit --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('accounting.fixed-assets.index') }}" class="btn btn-secondary px-5 py-2.5 rounded-xl font-bold text-sm">
                Batal
            </a>
            <button type="submit" class="btn btn-primary px-7 py-2.5 rounded-xl font-extrabold text-sm shadow-lg shadow-blue-600/30 flex items-center gap-2">
                <i class="fa-solid fa-floppy-disk text-sm"></i>
                <span>Simpan & Daftarkan Aset</span>
            </button>
        </div>
    </form>
</div>
@endsection
