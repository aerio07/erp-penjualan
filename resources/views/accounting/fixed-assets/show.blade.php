@extends('layouts.app')
@section('title', 'Detail Aset #' . $fixedAsset->asset_code)
@section('page-title', 'Detail Aset #' . $fixedAsset->asset_code)

@section('content')
<div class="animate-in">
    {{-- Header Section --}}
    <x-detail-header 
        :title="$fixedAsset->name"
        :back-url="route('accounting.fixed-assets.index')"
        :badge-text="$fixedAsset->status_label"
        :badge-class="$fixedAsset->status_badge_class"
    >
        <x-slot:subtitle>
            <span style="font-family: 'Inter', monospace; font-size: 12px; padding: 2px 8px; border-radius: 4px; background-color: #eff6ff; color: #1e40af; font-weight: 700; border: 1px solid #bfdbfe; margin-right: 8px;">{{ $fixedAsset->asset_code }}</span>
            Kategori: <strong>{{ $fixedAsset->category->name }}</strong> &bull; Akun Pembukuan: <span style="font-family: 'Inter', monospace;">{{ $fixedAsset->category->account?->code }} - {{ $fixedAsset->category->account?->name }}</span>
        </x-slot:subtitle>
    </x-detail-header>

    @if(session('success'))
    <div class="alert alert-success mb-4">
        <i class="fa-solid fa-circle-check"></i>
        <div>
            <strong>Berhasil Diperbarui!</strong>
            <div>{{ session('success') }}</div>
        </div>
    </div>
    @endif

    <div class="form-row form-row-3" style="align-items: start;">
        {{-- Kolom Kiri (span 2): Detail Informasi & Jurnal --}}
        <div style="grid-column: span 2; display: flex; flex-direction: column; gap: 24px;">
            {{-- Kartu Spesifikasi & Perolehan --}}
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-circle-info" style="color: #2563eb; margin-right: 6px;"></i> Rincian Spesifikasi & Perolehan</h3>
                </div>
                <div class="card-body">
                    <div class="detail-grid">
                        <div class="detail-group">
                            <div class="detail-label">Harga Perolehan</div>
                            <div class="detail-value" style="font-family: 'Inter', monospace;">Rp {{ number_format($fixedAsset->purchase_cost, 0, ',', '.') }}</div>
                        </div>

                        <div class="detail-group">
                            <div class="detail-label">Tanggal Perolehan</div>
                            <div class="detail-value">{{ $fixedAsset->purchase_date ? $fixedAsset->purchase_date->format('d M Y') : '-' }}</div>
                        </div>

                        <div class="detail-group">
                            <div class="detail-label">Sumber Perolehan</div>
                            <div class="detail-value">
                                @if($fixedAsset->acquisition_type === 'purchase')
                                    <span class="badge" style="background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;">
                                        <i class="fa-solid fa-money-bill-wave" style="font-size: 10px;"></i> Beli Baru
                                    </span>
                                @else
                                    <span class="badge" style="background-color: #fffbeb; color: #92400e; border: 1px solid #fde68a;">
                                        <i class="fa-solid fa-clock-rotate-left" style="font-size: 10px;"></i> Saldo Awal
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="detail-group">
                            <div class="detail-label">No. Seri / Rangka</div>
                            <div class="detail-value" style="font-family: 'Inter', monospace;">{{ $fixedAsset->serial_number ?: '-' }}</div>
                        </div>

                        <div class="detail-group">
                            <div class="detail-label">Lokasi Penempatan</div>
                            <div class="detail-value">{{ $fixedAsset->location ?: '-' }}</div>
                        </div>

                        <div class="detail-group">
                            <div class="detail-label">Batch Referensi</div>
                            <div class="detail-value" style="font-family: 'Inter', monospace; font-size: 12px;">{{ $fixedAsset->batch_reference ?: '-' }}</div>
                        </div>

                        <div class="detail-group">
                            <div class="detail-label">Masa Manfaat</div>
                            <div class="detail-value">{{ $fixedAsset->useful_life_months ?: 0 }} Bulan</div>
                        </div>

                        <div class="detail-group">
                            <div class="detail-label">Nilai Residu</div>
                            <div class="detail-value" style="font-family: 'Inter', monospace;">Rp {{ number_format($fixedAsset->salvage_value, 0, ',', '.') }}</div>
                        </div>

                        <div class="detail-group">
                            <div class="detail-label">Didaftarkan Oleh</div>
                            <div class="detail-value">{{ $fixedAsset->creator?->name ?: 'Sistem' }}</div>
                        </div>
                    </div>

                    @if($fixedAsset->notes)
                    <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                        <div style="font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; font-family: 'Public Sans', sans-serif;">Catatan Khusus:</div>
                        <div style="font-size: 13px; color: #334155; background: #f8fafc; padding: 12px 14px; border-radius: 6px; border: 1px solid #e2e8f0; line-height: 1.6; font-family: 'Inter', sans-serif;">{{ $fixedAsset->notes }}</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Jurnal Transaksi Pembukuan --}}
            @if($fixedAsset->journalEntry)
            <div class="card">
                <div class="card-header">
                    <h3>
                        <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #059669; margin-right: 8px;"></span>
                        Jurnal Pembelian Kas Terkait
                    </h3>
                    <a href="{{ route('accounting.journals.show', $fixedAsset->journalEntry) }}" style="font-size: 12px; font-weight: 700; color: #2563eb; text-decoration: none; font-family: 'Inter', sans-serif;">
                        Buka Entri Jurnal #{{ $fixedAsset->journalEntry->entry_number }} <i class="fa-solid fa-arrow-right" style="font-size: 10px;"></i>
                    </a>
                </div>
                <div class="card-body">
                    <div style="font-size: 13px; color: #64748b; margin-bottom: 16px; display: flex; flex-wrap: wrap; gap: 16px; font-family: 'Inter', sans-serif;">
                        <span>No. Entri: <strong style="font-family: 'Inter', monospace; color: #0e1b35;">{{ $fixedAsset->journalEntry->entry_number }}</strong></span>
                        <span>Tanggal: <strong style="color: #0e1b35;">{{ $fixedAsset->journalEntry->entry_date->format('d/m/Y') }}</strong></span>
                        <span>Status: <span class="badge" style="background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; font-size: 10px; text-transform: uppercase;">{{ $fixedAsset->journalEntry->status }}</span></span>
                    </div>

                    <div class="table-responsive">
                        <table class="erp-table">
                            <thead>
                                <tr>
                                    <th>Akun Pembukuan</th>
                                    <th style="text-align:right;">Debit</th>
                                    <th style="text-align:right;">Kredit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($fixedAsset->journalEntry->lines as $line)
                                <tr>
                                    <td>
                                        <div style="font-weight: 700;">{{ $line->chartOfAccount?->code }} - {{ $line->chartOfAccount?->name }}</div>
                                        <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">{{ $line->description }}</div>
                                    </td>
                                    <td style="text-align:right; font-family: 'Inter', monospace; font-weight: 700; color: #1d4ed8;">
                                        {{ $line->debit > 0 ? 'Rp ' . number_format($line->debit, 0, ',', '.') : '-' }}
                                    </td>
                                    <td style="text-align:right; font-family: 'Inter', monospace; font-weight: 700; color: #7c3aed;">
                                        {{ $line->credit > 0 ? 'Rp ' . number_format($line->credit, 0, ',', '.') : '-' }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @elseif($fixedAsset->acquisition_type === 'opening_balance')
            <div class="card" style="background: linear-gradient(135deg, #fffbeb, #fff7ed); border-color: #fde68a;">
                <div class="card-body" style="display: flex; align-items: flex-start; gap: 14px;">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: #d97706; color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; font-size: 14px; color: #451a03; font-family: 'Public Sans', sans-serif;">Aset Saldo Awal Neraca</div>
                        <div style="font-size: 13px; color: #78350f; margin-top: 4px; line-height: 1.6; font-family: 'Inter', sans-serif;">
                            Aset ini didaftarkan dari saldo awal perusahaan. Nilai perolehannya telah diakui melalui pengisian <strong>Saldo Awal Neraca</strong> sehingga tidak ada jurnal pengeluaran kas tambahan hari ini.
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Kolom Kanan: Form Update Metadata Aset --}}
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header">
                <h3><i class="fa-solid fa-pen-to-square" style="color: #2563eb; margin-right: 6px;"></i> Kelola Status & Lokasi</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('accounting.fixed-assets.update', $fixedAsset) }}">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label class="form-label">Status Unit Aset</label>
                        <select name="status" class="form-control">
                            <option value="active" {{ $fixedAsset->status === 'active' ? 'selected' : '' }}>Aktif / Digunakan</option>
                            <option value="maintenance" {{ $fixedAsset->status === 'maintenance' ? 'selected' : '' }}>Dalam Perbaikan / Servis</option>
                            <option value="disposed" {{ $fixedAsset->status === 'disposed' ? 'selected' : '' }}>Afkir / Dihapus</option>
                            <option value="sold" {{ $fixedAsset->status === 'sold' ? 'selected' : '' }}>Terjual</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Lokasi Penempatan</label>
                        <input type="text" name="location" value="{{ old('location', $fixedAsset->location) }}" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nomor Seri / Rangka</label>
                        <input type="text" name="serial_number" value="{{ old('serial_number', $fixedAsset->serial_number) }}" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Catatan Tambahan</label>
                        <textarea name="notes" rows="3" class="form-control">{{ old('notes', $fixedAsset->notes) }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 4px;">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
