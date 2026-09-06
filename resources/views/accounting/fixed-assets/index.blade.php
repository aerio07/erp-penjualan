@extends('layouts.app')
@section('title', 'Aset Tetap & Inventaris')
@section('page-title', 'Aset Tetap & Inventaris')

@section('content')
<div class="animate-in flex flex-col gap-6">
    {{-- Header Section --}}
    <div class="page-header flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Aset Tetap & Inventaris</h1>
            <p class="text-xs text-slate-500">Pencatatan inventaris per unit, pengelompokan kategori aset, dan riwayat jurnal pembelian</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('accounting.fixed-assets.create') }}" class="btn btn-primary px-4 py-2.5 rounded-xl font-bold shadow-md shadow-blue-600/20 flex items-center gap-2">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Tambah / Beli Aset</span>
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm flex items-center gap-3 shadow-xs">
        <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
            <i class="fa-solid fa-circle-check text-base"></i>
        </div>
        <div>
            <div class="font-bold">Aksi Berhasil!</div>
            <div class="text-xs text-emerald-700 mt-0.5">{{ session('success') }}</div>
        </div>
    </div>
    @endif

    {{-- KPI Cards Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Total Unit --}}
        <div class="card p-5 border border-slate-200/80 rounded-2xl shadow-xs bg-white flex items-center justify-between hover:shadow-md transition-shadow">
            <div>
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Unit Aset</div>
                <div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($summary['total_units'], 0, ',', '.') }}</div>
                <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                    <span>Seluruh detail unit terdaftar</span>
                </div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shadow-xs">
                <i class="fa-solid fa-boxes-stacked text-xl"></i>
            </div>
        </div>

        {{-- Card 2: Total Nilai --}}
        <div class="card p-5 border border-slate-200/80 rounded-2xl shadow-xs bg-white flex items-center justify-between hover:shadow-md transition-shadow">
            <div>
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Nilai Perolehan</div>
                <div class="text-2xl font-black text-emerald-700 mt-1 font-mono">Rp {{ number_format($summary['total_value'], 0, ',', '.') }}</div>
                <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>Akumulasi harga perolehan</span>
                </div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shadow-xs">
                <i class="fa-solid fa-coins text-xl"></i>
            </div>
        </div>

        {{-- Card 3: Aset Aktif --}}
        <div class="card p-5 border border-slate-200/80 rounded-2xl shadow-xs bg-white flex items-center justify-between hover:shadow-md transition-shadow">
            <div>
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Unit Aset Aktif</div>
                <div class="text-2xl font-black text-indigo-700 mt-1">{{ number_format($summary['active_units'], 0, ',', '.') }}</div>
                <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                    <span>Operasional & siap pakai</span>
                </div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 shadow-xs">
                <i class="fa-solid fa-circle-check text-xl"></i>
            </div>
        </div>

        {{-- Card 4: Kategori Aset --}}
        <div class="card p-5 border border-slate-200/80 rounded-2xl shadow-xs bg-white flex items-center justify-between hover:shadow-md transition-shadow">
            <div>
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Group Kategori</div>
                <div class="text-2xl font-black text-amber-700 mt-1">{{ $summary['by_category']->count() }} Kategori</div>
                <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    <span>Akun aset tetap di CoA</span>
                </div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 shadow-xs">
                <i class="fa-solid fa-layer-group text-xl"></i>
            </div>
        </div>
    </div>

    {{-- Ringkasan per Kategori (Interactive Grid) --}}
    @if($summary['by_category']->isNotEmpty())
    <div class="card p-5 bg-gradient-to-br from-slate-50 to-white border border-slate-200 rounded-2xl shadow-xs">
        <div class="flex items-center justify-between mb-3.5">
            <h4 class="text-xs font-extrabold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-chart-pie text-blue-600"></i> Ringkasan Aset per Kategori:
            </h4>
            <span class="text-[11px] text-slate-400">Total nilai & kuantitas per group akun</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
            @foreach($summary['by_category'] as $cat)
            @php
                $meta = match($cat->code) {
                    'TNH' => ['icon' => 'fa-mountain-sun', 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-50'],
                    'BGN' => ['icon' => 'fa-building', 'color' => 'text-blue-600', 'bg' => 'bg-blue-50'],
                    'KND' => ['icon' => 'fa-truck-front', 'color' => 'text-amber-600', 'bg' => 'bg-amber-50'],
                    'PRL' => ['icon' => 'fa-computer', 'color' => 'text-purple-600', 'bg' => 'bg-purple-50'],
                    default => ['icon' => 'fa-cube', 'color' => 'text-slate-600', 'bg' => 'bg-slate-50'],
                };
            @endphp
            <div class="p-3.5 bg-white rounded-xl border border-slate-200 shadow-xs hover:border-blue-300 transition-colors flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $meta['bg'] }} {{ $meta['color'] }}">
                    <i class="fa-solid {{ $meta['icon'] }} text-base"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold text-slate-900 truncate">{{ $cat->name }} <span class="text-slate-400 font-normal">({{ $cat->code }})</span></div>
                    <div class="text-xs text-slate-500 mt-0.5 font-medium">{{ $cat->fixed_assets_count }} Unit terdaftar</div>
                    <div class="text-xs font-extrabold text-blue-700 font-mono mt-0.5">Rp {{ number_format($cat->fixed_assets_sum_purchase_cost ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Filter Bar --}}
    <div class="card p-4 border border-slate-200 rounded-2xl shadow-xs bg-white mb-0">
        <form method="GET" action="{{ route('accounting.fixed-assets.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[220px]">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-lg">search</span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode aset, nama barang, no seri, lokasi..." class="form-control pl-9 h-10 text-sm rounded-xl border-slate-200 focus:ring-2 focus:ring-blue-500/20">
                </div>
            </div>
            <div class="w-52">
                <select name="category_id" class="form-control h-10 text-xs rounded-xl border-slate-200" onchange="this.form.submit()">
                    <option value="">-- Semua Kategori --</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <select name="acquisition_type" class="form-control h-10 text-xs rounded-xl border-slate-200" onchange="this.form.submit()">
                    <option value="">-- Sumber Perolehan --</option>
                    <option value="purchase" {{ request('acquisition_type') == 'purchase' ? 'selected' : '' }}>Pembelian Baru</option>
                    <option value="opening_balance" {{ request('acquisition_type') == 'opening_balance' ? 'selected' : '' }}>Saldo Awal</option>
                </select>
            </div>
            <div class="w-36">
                <select name="status" class="form-control h-10 text-xs rounded-xl border-slate-200" onchange="this.form.submit()">
                    <option value="">-- Status Aset --</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>Pemeliharaan</option>
                    <option value="disposed" {{ request('status') == 'disposed' ? 'selected' : '' }}>Afkir / Dihapus</option>
                    <option value="sold" {{ request('status') == 'sold' ? 'selected' : '' }}>Terjual</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary h-10 px-4 rounded-xl text-xs font-bold">
                <i class="fa-solid fa-filter mr-1"></i> Filter
            </button>
            @if(request()->hasAny(['search', 'category_id', 'status', 'acquisition_type']))
                <a href="{{ route('accounting.fixed-assets.index') }}" class="btn btn-secondary h-10 px-3 rounded-xl text-xs font-bold">
                    Reset
                </a>
            @endif
        </form>
    </div>

    {{-- Asset Table --}}
    <div class="card overflow-hidden border border-slate-200 rounded-2xl shadow-xs bg-white">
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="py-3 px-4 text-xs font-bold text-slate-700 uppercase tracking-wider">Kode Aset</th>
                        <th class="py-3 px-4 text-xs font-bold text-slate-700 uppercase tracking-wider">Nama Aset</th>
                        <th class="py-3 px-4 text-xs font-bold text-slate-700 uppercase tracking-wider">Kategori</th>
                        <th class="py-3 px-4 text-xs font-bold text-slate-700 uppercase tracking-wider">Perolehan</th>
                        <th class="py-3 px-4 text-xs font-bold text-slate-700 uppercase tracking-wider">Tgl Beli</th>
                        <th style="text-align:right;" class="py-3 px-4 text-xs font-bold text-slate-700 uppercase tracking-wider">Harga Satuan</th>
                        <th class="py-3 px-4 text-xs font-bold text-slate-700 uppercase tracking-wider">No. Seri / Rangka</th>
                        <th class="py-3 px-4 text-xs font-bold text-slate-700 uppercase tracking-wider">Lokasi</th>
                        <th class="py-3 px-4 text-xs font-bold text-slate-700 uppercase tracking-wider">Status</th>
                        <th style="text-align:center;" class="py-3 px-4 text-xs font-bold text-slate-700 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assets as $asset)
                    <tr class="hover:bg-slate-50/70 transition-colors border-b border-slate-100">
                        <td class="py-3 px-4 font-mono font-bold text-blue-700 text-xs">
                            <a href="{{ route('accounting.fixed-assets.show', $asset) }}" class="hover:underline flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                                <span>{{ $asset->asset_code }}</span>
                            </a>
                        </td>
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-900 text-sm">{{ $asset->name }}</div>
                            @if($asset->notes)
                                <div class="text-[11px] text-slate-400 truncate max-w-xs mt-0.5">{{ $asset->notes }}</div>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                {{ $asset->category->name }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            @if($asset->acquisition_type === 'purchase')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                    <i class="fa-solid fa-cart-shopping text-[10px]"></i> Beli Baru
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    <i class="fa-solid fa-clock-rotate-left text-[10px]"></i> Saldo Awal
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-xs font-medium text-slate-600">
                            {{ $asset->purchase_date ? $asset->purchase_date->format('d/m/Y') : '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 text-sm">
                            Rp {{ number_format($asset->purchase_cost, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-xs font-mono text-slate-600">
                            {{ $asset->serial_number ?: '-' }}
                        </td>
                        <td class="py-3 px-4 text-xs text-slate-600">
                            {{ $asset->location ?: '-' }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="badge {{ $asset->status_badge_class }}">
                                {{ $asset->status_label }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <a href="{{ route('accounting.fixed-assets.show', $asset) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 hover:bg-blue-50 hover:text-blue-600 text-slate-600 transition-colors" title="Lihat Detail & Jurnal">
                                <i class="fa-solid fa-arrow-right text-xs"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-12 text-slate-400">
                            <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                <i class="fa-solid fa-boxes-stacked text-2xl"></i>
                            </div>
                            <div class="text-sm font-bold text-slate-600">Belum ada aset tetap atau inventaris yang terdaftar</div>
                            <div class="text-xs text-slate-400 mt-1">Gunakan tombol "+ Tambah / Beli Aset" untuk mendaftarkan aset baru.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($assets->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $assets->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
