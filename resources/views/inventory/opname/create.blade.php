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
                            <th style="text-align:right; width:130px;">Stok Sistem</th>
                            <th style="width:160px;">Stok Fisik (Hasil Hitung)</th>
                            <th style="text-align:right; width:160px;">Selisih</th>
                            <th>Keterangan Selisih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $idx => $p)
                        @php
                            $initPhysical = old("items.{$idx}.physical_qty", $p->system_qty);
                            $initDiff = $initPhysical - $p->system_qty;
                        @endphp
                        <tr class="opname-row">
                            <td style="font-weight:600; color:var(--primary);">{{ $p->sku }}</td>
                            <td>
                                {{ $p->name }}
                                <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $p->id }}">
                                <input type="hidden" class="system-qty-input" name="items[{{ $idx }}][system_qty]" value="{{ $p->system_qty }}">
                            </td>
                            <td style="text-align:right; font-weight:600;">
                                {{ number_format($p->system_qty) }} {{ $p->unit }}
                            </td>
                            <td>
                                <input type="number" name="items[{{ $idx }}][physical_qty]" value="{{ $initPhysical }}" class="form-control physical-qty-input" min="0" required>
                            </td>
                            <td style="text-align:right; font-weight:700;" class="diff-cell">
                                @if($initDiff > 0)
                                    <span style="color:var(--success);"><i class="fa-solid fa-arrow-trend-up"></i> +{{ number_format($initDiff) }} (Surplus)</span>
                                @elseif($initDiff < 0)
                                    <span style="color:var(--danger);"><i class="fa-solid fa-arrow-trend-down"></i> {{ number_format($initDiff) }} (Defisit)</span>
                                @else
                                    <span style="color:var(--text-secondary);">0 (Sesuai)</span>
                                @endif
                            </td>
                            <td>
                                <input type="text" name="items[{{ $idx }}][notes]" value="{{ old("items.{$idx}.notes") }}" class="form-control" placeholder="Alasan selisih (rusak/hilang/surplus/dll)">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('.opname-row');
    rows.forEach(function(row) {
        const sysInput = row.querySelector('.system-qty-input');
        const physInput = row.querySelector('.physical-qty-input');
        const diffCell = row.querySelector('.diff-cell');
        
        if (!sysInput || !physInput || !diffCell) return;

        function updateDiff() {
            const sys = parseInt(sysInput.value) || 0;
            const phys = parseInt(physInput.value) || 0;
            const diff = phys - sys;
            
            if (diff > 0) {
                diffCell.innerHTML = `<span style="color:var(--success);"><i class="fa-solid fa-arrow-trend-up"></i> +${diff} (Surplus)</span>`;
            } else if (diff < 0) {
                diffCell.innerHTML = `<span style="color:var(--danger);"><i class="fa-solid fa-arrow-trend-down"></i> ${diff} (Defisit)</span>`;
            } else {
                diffCell.innerHTML = `<span style="color:var(--text-secondary);">0 (Sesuai)</span>`;
            }
        }

        physInput.addEventListener('input', updateDiff);
    });
});
</script>
@endsection
