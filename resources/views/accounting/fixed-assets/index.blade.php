@extends('layouts.app')
@section('title', 'Aset Tetap & Inventaris')
@section('page-title', 'Aset Tetap & Inventaris')

@section('content')
<div class="animate-in">
    {{-- Header Section --}}
    <div class="page-header">
        <div>
            <h1>Aset Tetap & Inventaris</h1>
            <p>Pencatatan inventaris per unit, pengelompokan kategori aset, dan riwayat jurnal pembelian</p>
        </div>
        <a href="{{ route('accounting.fixed-assets.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Tambah / Beli Aset
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success mb-4">
        <i class="fa-solid fa-circle-check"></i>
        <div>
            <strong>Aksi Berhasil!</strong>
            <div>{{ session('success') }}</div>
        </div>
    </div>
    @endif

    {{-- KPI Cards Summary --}}
    <div class="grid-4" style="margin-bottom: 24px;">
        {{-- Card 1: Total Unit --}}
        <div class="stat-card">
            <div class="stat-info">
                <div class="stat-label">Total Unit Aset</div>
                <div class="stat-value">{{ number_format($summary['total_units'], 0, ',', '.') }}</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Seluruh detail unit terdaftar</div>
            </div>
            <div class="stat-icon" style="background-color: #eff6ff; color: #2563eb;">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
        </div>

        {{-- Card 2: Total Nilai --}}
        <div class="stat-card">
            <div class="stat-info">
                <div class="stat-label">Total Nilai Perolehan</div>
                <div class="stat-value" style="color: #15803d; font-family: 'Inter', monospace;">Rp {{ number_format($summary['total_value'], 0, ',', '.') }}</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Akumulasi harga perolehan</div>
            </div>
            <div class="stat-icon" style="background-color: #ecfdf5; color: #059669;">
                <i class="fa-solid fa-coins"></i>
            </div>
        </div>

        {{-- Card 3: Aset Aktif --}}
        <div class="stat-card">
            <div class="stat-info">
                <div class="stat-label">Unit Aset Aktif</div>
                <div class="stat-value" style="color: #4338ca;">{{ number_format($summary['active_units'], 0, ',', '.') }}</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Operasional & siap pakai</div>
            </div>
            <div class="stat-icon" style="background-color: #eef2ff; color: #4f46e5;">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

        {{-- Card 4: Kategori --}}
        <div class="stat-card">
            <div class="stat-info">
                <div class="stat-label">Group Kategori</div>
                <div class="stat-value" style="color: #b45309;">{{ $summary['by_category']->count() }} Kategori</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Akun aset tetap di CoA</div>
            </div>
            <div class="stat-icon" style="background-color: #fffbeb; color: #d97706;">
                <i class="fa-solid fa-layer-group"></i>
            </div>
        </div>
    </div>

    {{-- Ringkasan per Kategori --}}
    @if($summary['by_category']->isNotEmpty())
    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-chart-pie" style="color: #2563eb; margin-right: 6px;"></i> Ringkasan Aset per Kategori</h3>
            <span style="font-size: 11px; color: #94a3b8;">Total nilai & kuantitas per group akun</span>
        </div>
        <div class="card-body">
            <div class="grid-4">
                @foreach($summary['by_category'] as $cat)
                @php
                    $meta = match($cat->code) {
                        'TNH' => ['icon' => 'fa-mountain-sun', 'color' => '#059669', 'bg' => '#ecfdf5'],
                        'BGN' => ['icon' => 'fa-building', 'color' => '#2563eb', 'bg' => '#eff6ff'],
                        'KND' => ['icon' => 'fa-truck-front', 'color' => '#d97706', 'bg' => '#fffbeb'],
                        'PRL' => ['icon' => 'fa-computer', 'color' => '#7c3aed', 'bg' => '#f5f3ff'],
                        default => ['icon' => 'fa-cube', 'color' => '#64748b', 'bg' => '#f8fafc'],
                    };
                @endphp
                <div style="padding: 12px; background: #fff; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 12px;">
                    <div style="width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background-color: {{ $meta['bg'] }}; color: {{ $meta['color'] }}; flex-shrink: 0;">
                        <i class="fa-solid {{ $meta['icon'] }}"></i>
                    </div>
                    <div style="min-width: 0; flex: 1;">
                        <div style="font-size: 13px; font-weight: 700; color: #0e1b35; font-family: 'Public Sans', sans-serif;">{{ $cat->name }} <span style="font-weight: 400; color: #94a3b8;">({{ $cat->code }})</span></div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 2px; font-family: 'Inter', sans-serif;">{{ $cat->fixed_assets_count }} Unit terdaftar</div>
                        <div style="font-size: 12px; font-weight: 700; color: #1d4ed8; font-family: 'Inter', monospace; margin-top: 2px;">Rp {{ number_format($cat->fixed_assets_sum_purchase_cost ?? 0, 0, ',', '.') }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Filter Bar --}}
    <div class="card" style="margin-bottom: 0;">
        <form method="GET" action="{{ route('accounting.fixed-assets.index') }}" class="erp-filter-bar" style="display: flex; flex-wrap: wrap; align-items: center; gap: 12px;">
            <div style="flex: 1; min-width: 220px; position: relative;">
                <span class="material-symbols-outlined" style="position: absolute; left: 10px; top: 10px; color: #94a3b8; font-size: 18px;">search</span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode aset, nama barang, no seri, lokasi..." class="form-control search-input">
            </div>
            <select name="category_id" class="form-control" onchange="this.form.submit()">
                <option value="">-- Semua Kategori --</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->code }})</option>
                @endforeach
            </select>
            <select name="acquisition_type" class="form-control" onchange="this.form.submit()">
                <option value="">-- Sumber Perolehan --</option>
                <option value="purchase" {{ request('acquisition_type') == 'purchase' ? 'selected' : '' }}>Pembelian Baru</option>
                <option value="opening_balance" {{ request('acquisition_type') == 'opening_balance' ? 'selected' : '' }}>Saldo Awal</option>
            </select>
            <select name="status" class="form-control" onchange="this.form.submit()">
                <option value="">-- Status Aset --</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>Pemeliharaan</option>
                <option value="disposed" {{ request('status') == 'disposed' ? 'selected' : '' }}>Afkir / Dihapus</option>
                <option value="sold" {{ request('status') == 'sold' ? 'selected' : '' }}>Terjual</option>
            </select>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
            @if(request()->hasAny(['search', 'category_id', 'status', 'acquisition_type']))
                <a href="{{ route('accounting.fixed-assets.index') }}" class="btn btn-secondary">
                    Reset
                </a>
            @endif
        </form>
    </div>

    {{-- Asset Table --}}
    <div class="table-responsive">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Kode Aset</th>
                    <th>Nama Aset</th>
                    <th>Kategori</th>
                    <th>Perolehan</th>
                    <th>Tgl Beli</th>
                    <th style="text-align:right;">Harga Satuan</th>
                    <th>No. Seri / Rangka</th>
                    <th>Lokasi</th>
                    <th>Status</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assets as $asset)
                <tr>
                    <td>
                        <a href="{{ route('accounting.fixed-assets.show', $asset) }}" style="color: #1d4ed8; font-weight: 700; font-family: 'Inter', monospace; font-size: 12px; text-decoration: none;">
                            {{ $asset->asset_code }}
                        </a>
                    </td>
                    <td>
                        <div style="font-weight: 700; color: #0e1b35;">{{ $asset->name }}</div>
                        @if($asset->notes)
                            <div style="font-size: 11px; color: #94a3b8; margin-top: 2px; max-width: 240px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $asset->notes }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="badge" style="background-color: #f1f5f9; color: #334155; border: 1px solid #e2e8f0;">
                            {{ $asset->category->name }}
                        </span>
                    </td>
                    <td>
                        @if($asset->acquisition_type === 'purchase')
                            <span class="badge" style="background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;">
                                <i class="fa-solid fa-money-bill-wave" style="font-size: 10px;"></i> Beli Baru
                            </span>
                        @else
                            <span class="badge" style="background-color: #fffbeb; color: #92400e; border: 1px solid #fde68a;">
                                <i class="fa-solid fa-clock-rotate-left" style="font-size: 10px;"></i> Saldo Awal
                            </span>
                        @endif
                    </td>
                    <td style="font-size: 13px; color: #475569;">
                        {{ $asset->purchase_date ? $asset->purchase_date->format('d/m/Y') : '-' }}
                    </td>
                    <td style="text-align:right; font-family: 'Inter', monospace; font-weight: 700; color: #0e1b35;">
                        Rp {{ number_format($asset->purchase_cost, 0, ',', '.') }}
                    </td>
                    <td style="font-family: 'Inter', monospace; font-size: 12px; color: #475569;">
                        {{ $asset->serial_number ?: '-' }}
                    </td>
                    <td style="font-size: 13px; color: #475569;">
                        {{ $asset->location ?: '-' }}
                    </td>
                    <td>
                        <span class="badge {{ $asset->status_badge_class }}">
                            {{ $asset->status_label }}
                        </span>
                    </td>
                    <td style="text-align:center;">
                        <a href="{{ route('accounting.fixed-assets.show', $asset) }}" class="btn btn-sm btn-icon btn-secondary" title="Lihat Detail & Jurnal">
                            <i class="fa-solid fa-arrow-right" style="font-size: 11px;"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="text-align: center; padding: 48px 16px; color: #94a3b8;">
                        <div style="width: 48px; height: 48px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; color: #94a3b8;">
                            <i class="fa-solid fa-boxes-stacked" style="font-size: 20px;"></i>
                        </div>
                        <div style="font-weight: 700; color: #475569; font-size: 14px;">Belum ada aset tetap atau inventaris yang terdaftar</div>
                        <div style="font-size: 12px; color: #94a3b8; margin-top: 4px;">Gunakan tombol "+ Tambah / Beli Aset" untuk mendaftarkan aset baru.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($assets->hasPages())
    <div style="padding: 16px 0;">
        {{ $assets->links() }}
    </div>
    @endif
</div>
@endsection
