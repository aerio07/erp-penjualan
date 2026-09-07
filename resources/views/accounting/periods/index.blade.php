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
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.outside="showGenerateModal = false" 
             class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 animate-in">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-extrabold text-slate-900">Buat Periode Akuntansi Baru</h3>
                <button @click="showGenerateModal = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <p class="text-xs text-slate-500 mb-5">
                Masukkan tahun fiskal. Sistem akan secara otomatis menginisialisasi 12 bulan (Januari s/d Desember) berstatus terbuka (*open*).
            </p>
            <form method="POST" action="{{ route('accounting.periods.generate') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Tahun Fiskal</label>
                    <input type="number" name="year" value="{{ $selectedYear }}" min="2000" max="2099" required 
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button @click="showGenerateModal = false" type="button" class="btn btn-secondary px-4 py-2 rounded-xl text-xs font-bold">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-xl text-xs font-bold shadow-md shadow-blue-600/20">Generate 12 Bulan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
