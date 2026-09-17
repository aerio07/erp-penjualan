@extends('layouts.app')
@section('title', 'Detail Periode: ' . $period->period_name)
@section('page-title', 'Detail Periode Akuntansi')

@section('content')
<div class="animate-in flex flex-col gap-6" x-data="{ showReopenModal: false }">
    {{-- Header Section --}}
    <div class="page-header flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('accounting.periods.index', ['year' => $period->fiscal_year]) }}" class="hover:text-blue-600 transition-colors">Periode Akuntansi</a>
                <i class="fa-solid fa-chevron-right text-[9px] text-slate-400"></i>
                <span class="text-slate-700 font-bold">{{ $period->period_name }}</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Periode: {{ $period->period_name }}</h1>
            <p class="text-xs text-slate-500">Rentang pembukuan: {{ $period->start_date->format('m/d/Y') }} s/d {{ $period->end_date->format('m/d/Y') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('accounting.periods.index', ['year' => $period->fiscal_year]) }}" class="btn btn-secondary px-4 py-2 rounded-xl text-xs font-bold">
                <i class="fa-solid fa-arrow-left mr-1.5"></i>
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
            <div class="font-bold">Berhasil!</div>
            <div class="text-xs text-emerald-700 mt-0.5">{{ session('success') }}</div>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-sm flex items-center gap-3 shadow-xs">
        <div class="w-8 h-8 rounded-lg bg-rose-100 flex items-center justify-center text-rose-600 shrink-0">
            <i class="fa-solid fa-triangle-exclamation text-base"></i>
        </div>
        <div>
            <div class="font-bold">Gagal Melakukan Aksi</div>
            <div class="text-xs text-rose-700 mt-0.5">{{ session('error') }}</div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Kolom Kiri: Status & Audit Trail --}}
        <div class="lg:col-span-1 flex flex-col gap-6">
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Status Pembukuan</div>
                <div class="flex items-center gap-3 mb-6">
                    @if($period->isClosed())
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-extrabold bg-rose-100 text-rose-700">
                        <i class="fa-solid fa-lock text-xs"></i>
                        <span>Periode Ditutup (Locked)</span>
                    </span>
                    @else
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-700">
                        <i class="fa-solid fa-lock-open text-xs"></i>
                        <span>Periode Terbuka (Open)</span>
                    </span>
                    @endif
                </div>

                <div class="space-y-3.5 text-xs border-t border-slate-100 pt-4">
                    <div>
                        <span class="text-slate-400 block mb-0.5">Tahun Fiskal</span>
                        <span class="font-bold text-slate-900">{{ $period->fiscal_year }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-0.5">Bulan Ke</span>
                        <span class="font-bold text-slate-900">{{ $period->month }} ({{ $period->start_date->format('F') }})</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-0.5">Tanggal Mulai</span>
                        <span class="font-semibold text-slate-700">{{ $period->start_date->format('m/d/Y') }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-0.5">Tanggal Berakhir</span>
                        <span class="font-semibold text-slate-700">{{ $period->end_date->format('m/d/Y') }}</span>
                    </div>
                </div>

                @if($period->isClosed())
                <div class="mt-6 pt-4 border-t border-slate-100 text-xs text-slate-600 bg-rose-50/50 p-3.5 rounded-xl border border-rose-100">
                    <div class="font-bold text-rose-800 flex items-center gap-1.5 mb-1">
                        <i class="fa-solid fa-shield-halved text-[11px]"></i>
                        <span>Informasi Penutupan</span>
                    </div>
                    <div class="mt-2 space-y-1 text-[11px]">
                        <div>Ditutup oleh: <strong class="text-slate-800">{{ $period->closedBy?->name ?? 'Admin' }}</strong></div>
                        <div>Waktu: <span class="text-slate-700">{{ $period->closed_at?->format('d M Y H:i') ?? '-' }}</span></div>
                    </div>
                </div>
                @endif

                @if($period->reopened_at)
                <div class="mt-4 text-xs text-slate-600 bg-amber-50/50 p-3.5 rounded-xl border border-amber-200">
                    <div class="font-bold text-amber-800 flex items-center gap-1.5 mb-1">
                        <i class="fa-solid fa-clock-rotate-left text-[11px]"></i>
                        <span>Riwayat Buka Kembali</span>
                    </div>
                    <div class="mt-2 space-y-1 text-[11px]">
                        <div>Dibuka oleh: <strong class="text-slate-800">{{ $period->reopenedBy?->name ?? 'Admin' }}</strong></div>
                        <div>Waktu: <span class="text-slate-700">{{ $period->reopened_at->format('d M Y H:i') }}</span></div>
                        <div class="pt-1 text-slate-600 italic">"{{ $period->reopen_reason }}"</div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Action Box --}}
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Tindakan Periode</div>

                @if($period->isOpen())
                    @if($checklist['can_close'])
                    <form method="POST" action="{{ route('accounting.periods.close', $period) }}" 
                          onsubmit="return confirm('Apakah Anda yakin ingin menutup periode {{ $period->period_name }}? Transaksi baru bertanggal periode ini tidak dapat dibuat lagi.')">
                        @csrf
                        <button type="submit" class="w-full btn btn-primary py-3 rounded-xl font-bold flex items-center justify-center gap-2 bg-rose-600 hover:bg-rose-700 border-rose-600 shadow-md shadow-rose-600/20">
                            <i class="fa-solid fa-lock text-sm"></i>
                            <span>Tutup Periode Ini</span>
                        </button>
                    </form>
                    <p class="text-[11px] text-slate-400 text-center mt-2.5">
                        Semua checklist pra-closing telah terpenuhi. Periode siap ditutup.
                    </p>
                    @else
                    <button type="button" disabled class="w-full py-3 rounded-xl font-bold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed flex items-center justify-center gap-2">
                        <i class="fa-solid fa-lock text-sm"></i>
                        <span>Tutup Periode (Belum Siap)</span>
                    </button>
                    <p class="text-[11px] text-rose-500 text-center mt-2.5">
                        Lengkapi checklist di sebelah kanan sebelum menutup periode ini.
                    </p>
                    @endif
                @else
                    <button @click="showReopenModal = true" type="button" class="w-full btn btn-secondary py-3 rounded-xl font-bold flex items-center justify-center gap-2 text-amber-700 bg-amber-50 hover:bg-amber-100 border-amber-200 shadow-xs">
                        <i class="fa-solid fa-lock-open text-sm"></i>
                        <span>Buka Kembali Periode</span>
                    </button>
                    <p class="text-[11px] text-slate-400 text-center mt-2.5">
                        Membuka kembali periode memerlukan alasan koreksi untuk kebutuhan audit trail.
                    </p>
                @endif
            </div>
        </div>

        {{-- Kolom Kanan: Checklist Pra-Closing --}}
        <div class="lg:col-span-2 flex flex-col gap-6">
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Checklist Pra-Closing Sederhana</h2>
                        <p class="text-xs text-slate-500">2 syarat mutlak yang harus dipenuhi sebelum pembukuan bulan ini dapat dikunci secara resmi.</p>
                    </div>
                </div>

                <div class="space-y-4 mt-6">
                    {{-- Syarat 1: Draft Journals --}}
                    <div class="p-4 rounded-xl border {{ $checklist['drafts_valid'] ? 'border-emerald-200 bg-emerald-50/40' : 'border-rose-200 bg-rose-50/40' }} flex items-start gap-4">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 {{ $checklist['drafts_valid'] ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' }}">
                            @if($checklist['drafts_valid'])
                            <i class="fa-solid fa-check text-base"></i>
                            @else
                            <i class="fa-solid fa-xmark text-base"></i>
                            @endif
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-slate-900">1. Tidak Ada Jurnal Berstatus Draft</h3>
                                <span class="text-xs font-bold {{ $checklist['drafts_valid'] ? 'text-emerald-700' : 'text-rose-700' }}">
                                    {{ $checklist['draft_count'] }} Draft
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">
                                Seluruh entri jurnal dalam rentang {{ $period->start_date->format('m/d/Y') }} s/d {{ $period->end_date->format('m/d/Y') }} harus sudah diposting atau dihapus.
                            </p>
                            @if(!$checklist['drafts_valid'])
                            <div class="mt-2.5">
                                <a href="{{ route('accounting.journals.index', ['date_from' => $period->start_date->toDateString(), 'date_to' => $period->end_date->toDateString(), 'status' => 'draft']) }}" 
                                   class="text-xs font-bold text-blue-600 hover:underline flex items-center gap-1">
                                    <span>Tinjau jurnal draft yang belum diposting</span>
                                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Syarat 2: Trial Balance Balance --}}
                    <div class="p-4 rounded-xl border {{ $checklist['is_balanced'] ? 'border-emerald-200 bg-emerald-50/40' : 'border-rose-200 bg-rose-50/40' }} flex items-start gap-4">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 {{ $checklist['is_balanced'] ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' }}">
                            @if($checklist['is_balanced'])
                            <i class="fa-solid fa-check text-base"></i>
                            @else
                            <i class="fa-solid fa-xmark text-base"></i>
                            @endif
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-slate-900">2. Keseimbangan Neraca Saldo (Trial Balance)</h3>
                                <span class="text-xs font-bold {{ $checklist['is_balanced'] ? 'text-emerald-700' : 'text-rose-700' }}">
                                    {{ $checklist['is_balanced'] ? 'Seimbang (Balanced)' : 'Tidak Seimbang' }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">
                                Total mutasi Debit harus tepat sama dengan total mutasi Kredit untuk seluruh transaksi posted pada periode ini.
                            </p>
                            <div class="grid grid-cols-2 gap-3 mt-3 bg-white p-3 rounded-lg border border-slate-200 text-xs">
                                <div>
                                    <span class="text-slate-400 block text-[10px] uppercase">Total Debit Periode</span>
                                    <span class="font-bold text-slate-900">Rp {{ number_format($checklist['total_debit'], 2, ',', '.') }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10px] uppercase">Total Kredit Periode</span>
                                    <span class="font-bold text-slate-900">Rp {{ number_format($checklist['total_credit'], 2, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Catatan Penjelas Desain Minimal --}}
                <div class="mt-6 p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600">
                    <div class="font-bold text-slate-800 mb-1 flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-info text-blue-500"></i>
                        <span>Prinsip Saldo Berjalan Dinamis</span>
                    </div>
                    <p class="text-slate-500 leading-relaxed">
                        Penutupan periode ini berfungsi sebagai <strong>gembok keamanan tanggal</strong> agar tidak ada invoice, pembayaran, atau jurnal baru yang disusupkan ke bulan ini. Sistem tidak melakukan mutasi pemindahan saldo Laba/Rugi ke Laba Ditahan karena laporan keuangan dihitung dinamis secara real-time.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Reopen Periode --}}
    <div x-show="showReopenModal" 
         x-cloak 
         style="position: fixed !important; inset: 0 !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; background: rgba(15, 23, 42, 0.65) !important; backdrop-filter: blur(4px); z-index: 99999 !important; display: flex !important; align-items: center !important; justify-content: center !important; padding: 20px !important; box-sizing: border-box !important;">
        <div @click.outside="showReopenModal = false" 
             style="background: #ffffff !important; border-radius: 16px !important; max-width: 480px !important; width: 100% !important; margin: auto !important; padding: 24px !important; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35) !important; border: 1px solid #e2e8f0 !important; position: relative !important;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 15px;">
                        <i class="fa-solid fa-lock-open"></i>
                    </div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Buka Kembali Periode</h3>
                </div>
                <button @click="showReopenModal = false" type="button" style="background: none; border: none; color: #94a3b8; font-size: 20px; cursor: pointer; padding: 4px; line-height: 1;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <p style="font-size: 12.5px; color: #64748b; margin: 0 0 16px 0; line-height: 1.5;">
                Membuka kembali periode <strong>{{ $period->period_name }}</strong> akan mengizinkan pembuatan dan pengeditan transaksi bertanggal di periode ini. Harap cantumkan alasan yang jelas untuk audit trail.
            </p>
            <form method="POST" action="{{ route('accounting.periods.reopen', $period) }}">
                @csrf
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 8px;">
                        Alasan Pembukaan Kembali <span style="color: #ef4444;">*</span>
                    </label>
                    <textarea name="reopen_reason" rows="3" required placeholder="Contoh: Perlu menginput faktur pembelian retur yang terlambat diterima dari vendor..." class="form-control"
                              style="width: 100%; min-height: 80px; padding: 10px 14px; font-size: 13px; border-radius: 8px; border: 1px solid #cbd5e1; box-sizing: border-box;"></textarea>
                </div>
                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                    <button @click="showReopenModal = false" type="button" class="btn btn-secondary btn-sm" style="padding: 8px 16px; border-radius: 8px; font-weight: 600;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-warning btn-sm" style="padding: 8px 18px; border-radius: 8px; font-weight: 600; box-shadow: 0 4px 6px -1px rgba(217, 119, 6, 0.2);">
                        <i class="fa-solid fa-lock-open" style="margin-right: 6px;"></i> Konfirmasi Buka Kembali
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
