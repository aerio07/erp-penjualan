@extends('layouts.app')
@section('title', 'Detail Aset #' . $fixedAsset->asset_code)
@section('page-title', 'Detail Aset #' . $fixedAsset->asset_code)

@section('content')
<div class="animate-in max-w-5xl mx-auto flex flex-col gap-6 pb-12">
    {{-- Header Section --}}
    <div class="page-header flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ $fixedAsset->name }}</h1>
                <span class="font-mono text-xs px-2.5 py-1 rounded-lg bg-blue-50 text-blue-800 font-extrabold border border-blue-200">
                    {{ $fixedAsset->asset_code }}
                </span>
                <span class="badge {{ $fixedAsset->status_badge_class }}">
                    {{ $fixedAsset->status_label }}
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Kategori: <strong>{{ $fixedAsset->category->name }}</strong> &bull; Akun Pembukuan: <span class="font-mono">{{ $fixedAsset->category->account?->code }} - {{ $fixedAsset->category->account?->name }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('accounting.fixed-assets.index') }}" class="btn btn-secondary px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm flex items-center gap-3 shadow-xs">
        <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
            <i class="fa-solid fa-circle-check text-base"></i>
        </div>
        <div>
            <div class="font-bold">Berhasil Diperbarui!</div>
            <div class="text-xs text-emerald-700 mt-0.5">{{ session('success') }}</div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        {{-- Kolom Kiri: Detail Informasi & Jurnal --}}
        <div class="lg:col-span-2 flex flex-col gap-6">
            {{-- Kartu Spesifikasi & Perolehan --}}
            <div class="card p-6 border border-slate-200 rounded-2xl shadow-xs bg-white">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4 pb-3 border-b border-slate-100 flex items-center gap-2">
                    <i class="fa-solid fa-circle-info text-blue-600"></i> Rincian Spesifikasi & Perolehan
                </h3>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-5">
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Harga Perolehan</div>
                        <div class="text-base font-extrabold text-slate-900 font-mono mt-1">Rp {{ number_format($fixedAsset->purchase_cost, 0, ',', '.') }}</div>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tanggal Perolehan</div>
                        <div class="text-sm font-bold text-slate-800 mt-1">{{ $fixedAsset->purchase_date ? $fixedAsset->purchase_date->format('d M Y') : '-' }}</div>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Sumber Perolehan</div>
                        <div class="mt-1">
                            @if($fixedAsset->acquisition_type === 'purchase')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-blue-100 text-blue-800">
                                    <i class="fa-solid fa-cart-shopping text-[10px]"></i> Beli Baru
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800">
                                    <i class="fa-solid fa-clock-rotate-left text-[10px]"></i> Saldo Awal
                                </span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-400 font-medium">No. Seri / Rangka</div>
                        <div class="text-sm font-mono font-bold text-slate-800 mt-0.5">{{ $fixedAsset->serial_number ?: '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-400 font-medium">Lokasi Penempatan</div>
                        <div class="text-sm font-semibold text-slate-800 mt-0.5">{{ $fixedAsset->location ?: '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-400 font-medium">Batch Referensi</div>
                        <div class="text-xs font-mono text-slate-500 mt-0.5">{{ $fixedAsset->batch_reference ?: '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-400 font-medium">Masa Manfaat</div>
                        <div class="text-sm font-semibold text-slate-800 mt-0.5">{{ $fixedAsset->useful_life_months ?: 0 }} Bulan</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-400 font-medium">Nilai Residu</div>
                        <div class="text-sm font-semibold text-slate-800 font-mono mt-0.5">Rp {{ number_format($fixedAsset->salvage_value, 0, ',', '.') }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-slate-400 font-medium">Didaftarkan Oleh</div>
                        <div class="text-sm font-semibold text-slate-800 mt-0.5">{{ $fixedAsset->creator?->name ?: 'Sistem' }}</div>
                    </div>
                </div>

                @if($fixedAsset->notes)
                <div class="mt-5 pt-4 border-t border-slate-100">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Catatan Khusus:</div>
                    <div class="text-xs text-slate-700 bg-slate-50 p-3.5 rounded-xl border border-slate-200 leading-relaxed">{{ $fixedAsset->notes }}</div>
                </div>
                @endif
            </div>

            {{-- Jurnal Transaksi Pembukuan --}}
            @if($fixedAsset->journalEntry)
            <div class="card p-6 border border-slate-200 rounded-2xl shadow-xs bg-white">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                            Jurnal Pembelian Kas Terkait
                        </h3>
                    </div>
                    <a href="{{ route('accounting.journals.show', $fixedAsset->journalEntry) }}" class="text-xs font-bold text-blue-600 hover:underline flex items-center gap-1">
                        <span>Buka Entri Jurnal #{{ $fixedAsset->journalEntry->entry_number }}</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>

                <div class="text-xs text-slate-500 mb-3.5 flex flex-wrap items-center gap-5">
                    <span>No. Entri: <strong class="font-mono text-slate-800">{{ $fixedAsset->journalEntry->entry_number }}</strong></span>
                    <span>Tanggal: <strong class="text-slate-800">{{ $fixedAsset->journalEntry->entry_date->format('d/m/Y') }}</strong></span>
                    <span>Status: <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800">{{ $fixedAsset->journalEntry->status }}</span></span>
                </div>

                <div class="table-responsive rounded-xl border border-slate-200 overflow-hidden">
                    <table class="erp-table text-xs">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="py-2.5 px-4 font-bold text-slate-700">Akun Pembukuan</th>
                                <th style="text-align:right;" class="py-2.5 px-4 font-bold text-slate-700">Debit</th>
                                <th style="text-align:right;" class="py-2.5 px-4 font-bold text-slate-700">Kredit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fixedAsset->journalEntry->lines as $line)
                            <tr class="border-b border-slate-100">
                                <td class="py-2.5 px-4">
                                    <div class="font-bold text-slate-900">{{ $line->chartOfAccount?->code }} - {{ $line->chartOfAccount?->name }}</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">{{ $line->description }}</div>
                                </td>
                                <td style="text-align:right;" class="py-2.5 px-4 font-mono font-bold text-blue-700">
                                    {{ $line->debit > 0 ? 'Rp ' . number_format($line->debit, 0, ',', '.') : '-' }}
                                </td>
                                <td style="text-align:right;" class="py-2.5 px-4 font-mono font-bold text-purple-700">
                                    {{ $line->credit > 0 ? 'Rp ' . number_format($line->credit, 0, ',', '.') : '-' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @elseif($fixedAsset->acquisition_type === 'opening_balance')
            <div class="card p-5 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-2xl">
                <div class="flex items-start gap-3.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div>
                        <div class="font-bold text-sm text-amber-950">Aset Saldo Awal Neraca</div>
                        <div class="text-xs text-amber-800 mt-1 leading-relaxed">
                            Aset ini didaftarkan dari saldo awal perusahaan. Nilai perolehannya telah diakui melalui pengisian <strong>Saldo Awal Neraca</strong> sehingga tidak ada jurnal pengeluaran kas tambahan hari ini.
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Kolom Kanan: Form Update Metadata Aset --}}
        <div class="card p-6 border border-slate-200 rounded-2xl shadow-xs bg-white">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4 pb-3 border-b border-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-blue-600"></i> Kelola Status & Lokasi
            </h3>

            <form method="POST" action="{{ route('accounting.fixed-assets.update', $fixedAsset) }}" class="flex flex-col gap-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Status Unit Aset</label>
                    <select name="status" class="form-control text-sm h-10 rounded-xl border-slate-200">
                        <option value="active" {{ $fixedAsset->status === 'active' ? 'selected' : '' }}>Aktif / Digunakan</option>
                        <option value="maintenance" {{ $fixedAsset->status === 'maintenance' ? 'selected' : '' }}>Dalam Perbaikan / Servis</option>
                        <option value="disposed" {{ $fixedAsset->status === 'disposed' ? 'selected' : '' }}>Afkir / Dihapus</option>
                        <option value="sold" {{ $fixedAsset->status === 'sold' ? 'selected' : '' }}>Terjual</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Lokasi Penempatan</label>
                    <input type="text" name="location" value="{{ old('location', $fixedAsset->location) }}" class="form-control text-sm h-10 rounded-xl border-slate-200">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Nomor Seri / Rangka</label>
                    <input type="text" name="serial_number" value="{{ old('serial_number', $fixedAsset->serial_number) }}" class="form-control text-sm h-10 rounded-xl border-slate-200">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Catatan Tambahan</label>
                    <textarea name="notes" rows="3" class="form-control text-xs rounded-xl border-slate-200">{{ old('notes', $fixedAsset->notes) }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary w-full py-2.5 rounded-xl font-bold text-xs shadow-md shadow-blue-600/20 mt-1 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Simpan Perubahan</span>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
