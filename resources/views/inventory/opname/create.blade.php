@extends('layouts.app')
@section('title', 'Mulai Stock Opname')
@section('page-title', 'Mulai Stock Opname')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Mulai Stock Opname</h1>
            <p>Input fisik persediaan produk per gudang</p>
        </div>
        <a href="{{ route('inventory.opname.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><h3>Pilih Gudang</h3></div>
        <div class="card-body">
            <form method="GET" action="{{ route('inventory.opname.create') }}" style="display:flex; gap:12px; align-items:flex-end;">
                <div style="flex:1; max-width:320px;">
                    <label class="form-label">Gudang yang Di-opname <span style="color:var(--danger);">*</span></label>
                    <select name="warehouse_id" class="form-control" onchange="this.form.submit()" required>
                        <option value="">-- Pilih Gudang --</option>
                        @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ $selectedWarehouseId == $wh->id ? 'selected' : '' }}>{{ $wh->name }} ({{ $wh->code }})</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if($selectedWarehouseId)
    <form method="POST" action="{{ route('inventory.opname.store') }}">
        @csrf
        <input type="hidden" name="warehouse_id" value="{{ $selectedWarehouseId }}">

        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h3>Informasi Opname</h3></div>
            <div class="card-body">
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Tanggal Opname <span style="color:var(--danger);">*</span></label>
                        <input type="date" name="opname_date" value="{{ old('opname_date', date('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Catatan</label>
                        <input type="text" name="notes" class="form-control" placeholder="Keterangan opname (misal: Opname Bulanan Q1)..." value="{{ old('notes') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h3>Perhitungan Fisik Produk</h3></div>
            <div class="table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Nama Produk</th>
                            <th style="text-align:right; width:140px;">Stok Sistem</th>
                            <th style="width:160px;">Stok Fisik (Hasil Hitung)</th>
                            <th>Keterangan Selisih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $idx => $p)
                        <tr>
                            <td style="font-weight:600; color:var(--primary);">{{ $p->sku }}</td>
                            <td>
                                {{ $p->name }}
                                <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $p->id }}">
                                <input type="hidden" name="items[{{ $idx }}][system_qty]" value="{{ $p->system_qty }}">
                            </td>
                            <td style="text-align:right; font-weight:600;">
                                {{ number_format($p->system_qty) }} {{ $p->unit }}
                            </td>
                            <td>
                                <input type="number" name="items[{{ $idx }}][physical_qty]" value="{{ old("items.{$idx}.physical_qty", $p->system_qty) }}" class="form-control" min="0" required>
                            </td>
                            <td>
                                <input type="text" name="items[{{ $idx }}][notes]" value="{{ old("items.{$idx}.notes") }}" class="form-control" placeholder="Alasan selisih (rusak/hilang/dll)">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:12px;">
            <a href="{{ route('inventory.opname.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Draft Opname
            </button>
        </div>
    </form>
    @endif
</div>
@endsection
