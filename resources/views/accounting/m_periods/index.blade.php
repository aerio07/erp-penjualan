@extends('layouts.app')
@section('title', 'Periode Akuntansi & Closing')
@section('page-title', 'Periode Akuntansi & Closing')

@section('content')
<div class="animate-in flex flex-col gap-6" x-data="{ showGenerateModal: false }">
    {{-- Header Section --}}
    <div class="page-header flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Periode Akuntansi & Closing</h1>
            <p class="text-xs text-slate-500">Kunci dan kelola tanggal pembukuan keuangan bulanan agar transaksi lampau aman dari manipulasi data</p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="showGenerateModal = true" type="button" class="btn btn-primary px-4 py-2.5 rounded-xl font-bold shadow-md shadow-blue-600/20 flex items-center gap-2">
                <i class="fa-solid fa-calendar-plus text-xs"></i>
                <span>Buat Periode Baru</span>
            </button>
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

    {{-- Filter Bar --}}
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tahun Fiskal:</span>
            <div class="flex items-center gap-1.5">
                @foreach($years as $yr)
                <a href="{{ route('accounting.periods.index', ['year' => $yr]) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors {{ $selectedYear == $yr ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    {{ $yr }}
                </a>
                @endforeach
            </div>
        </div>
        <div class="text-xs text-slate-500">
            Total Bulan Terdaftar: <strong class="text-slate-900">{{ $periods->count() }}</strong>
        </div>
    </div>

    {{-- Period Cards Grid --}}
    @if($periods->isEmpty())
    <div class="bg-white p-12 rounded-2xl border border-slate-200 text-center flex flex-col items-center justify-center">
        <div class="w-16 h-16 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl mb-4">
            <i class="fa-regular fa-calendar-xmark"></i>
        </div>
        <h3 class="text-base font-bold text-slate-900">Belum Ada Periode untuk Tahun {{ $selectedYear }}</h3>
        <p class="text-xs text-slate-500 max-w-md mt-1 mb-6">
            Klik tombol di bawah untuk membuat otomatis 12 periode (Januari - Desember) untuk tahun {{ $selectedYear }}. Transaksi tetap dapat berjalan normal sesuai Aturan #1.
        </p>
        <form method="POST" action="{{ route('accounting.periods.generate') }}">
            @csrf
            <input type="hidden" name="year" value="{{ $selectedYear }}">
            <button type="submit" class="btn btn-primary px-5 py-2.5 rounded-xl font-bold shadow-md shadow-blue-600/20">
                Generate 12 Periode Tahun {{ $selectedYear }}
            </button>
        </form>
    </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($periods as $period)
        <div class="bg-white rounded-2xl border {{ $period->isClosed() ? 'border-rose-200 shadow-xs' : 'border-slate-200/80 hover:border-blue-300 transition-all hover:shadow-md' }} p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between gap-2 mb-3">
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Bulan {{ str_pad($period->month, 2, '0', STR_PAD_LEFT) }}</div>
                        <h3 class="text-lg font-black text-slate-900 mt-0.5">{{ $period->period_name }}</h3>
                    </div>
                    @if($period->isClosed())
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-rose-100 text-rose-700">
                        <i class="fa-solid fa-lock text-[10px]"></i>
                        <span>Terkunci</span>
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-emerald-100 text-emerald-700">
                        <i class="fa-solid fa-lock-open text-[10px]"></i>
                        <span>Terbuka</span>
                    </span>
                    @endif
                </div>

                <div class="space-y-1.5 text-xs text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100 mb-4">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Rentang:</span>
                        <span class="font-medium text-slate-700">{{ $period->start_date->format('d M') }} - {{ $period->end_date->format('d M Y') }}</span>
                    </div>
                    @if($period->isClosed())
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-slate-400">Ditutup oleh:</span>
                        <span class="font-medium text-slate-700">{{ $period->closedBy?->name ?? '-' }}</span>
                    </div>
                    @elseif($period->reopened_at)
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-slate-400">Pernah dibuka:</span>
                        <span class="font-medium text-amber-700">{{ $period->reopenedBy?->name ?? 'Admin' }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                <a href="{{ route('accounting.periods.show', $period) }}" class="text-xs font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1">
                    <span>Lihat Detail & Closing</span>
                    <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </a>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Modal Generate Periode Baru --}}
    <div x-show="showGenerateModal" 
         x-cloak 
         style="position: fixed !important; inset: 0 !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; background: rgba(15, 23, 42, 0.65) !important; backdrop-filter: blur(4px); z-index: 99999 !important; display: flex !important; align-items: center !important; justify-content: center !important; padding: 20px !important; box-sizing: border-box !important;">
        <div @click.outside="showGenerateModal = false" 
             style="background: #ffffff !important; border-radius: 16px !important; max-width: 440px !important; width: 100% !important; margin: auto !important; padding: 24px !important; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35) !important; border: 1px solid #e2e8f0 !important; position: relative !important;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 15px;">
                        <i class="fa-solid fa-calendar-plus"></i>
                    </div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Buat Periode Akuntansi Baru</h3>
                </div>
                <button @click="showGenerateModal = false" type="button" style="background: none; border: none; color: #94a3b8; font-size: 20px; cursor: pointer; padding: 4px; line-height: 1;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            
            <p style="font-size: 12.5px; color: #64748b; margin: 0 0 20px 0; line-height: 1.5;">
                Masukkan tahun fiskal yang ingin diinisialisasi. Sistem akan otomatis membuat 12 bulan (Januari s/d Desember) berstatus terbuka (*open*).
            </p>

            <form method="POST" action="{{ route('accounting.periods.generate') }}">
                @csrf
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 8px;">Tahun Fiskal</label>
                    <input type="number" name="year" value="{{ $selectedYear }}" min="2000" max="2099" required class="form-control"
                           style="height: 42px; font-size: 14px; font-weight: 600; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 14px; width: 100%; box-sizing: border-box;">
                </div>

                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                    <button @click="showGenerateModal = false" type="button" class="btn btn-secondary btn-sm" style="padding: 8px 16px; border-radius: 8px; font-weight: 600;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm" style="padding: 8px 18px; border-radius: 8px; font-weight: 600; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);">
                        <i class="fa-solid fa-calendar-check" style="margin-right: 6px;"></i> Generate 12 Bulan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
