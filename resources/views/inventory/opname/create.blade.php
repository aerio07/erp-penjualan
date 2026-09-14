@extends('layouts.app')
@section('title', 'Mulai Stock Opname')
@section('page-title', 'Mulai Stock Opname')

@section('content')
<div class="animate-in">
    <x-page-header 
        title="Mulai Stock Opname" 
        subtitle="Input fisik persediaan produk per gudang" 
        :back-url="route('inventory.opname.index')"
    />

    <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><h3>Pilih Gudang</h3></div>
        <div class="card-body">
            <form method="GET" action="{{ route('inventory.opname.create') }}" style="display:flex; gap:12px; align-items:flex-end;">
                <div style="flex:1; max-width:320px;">
                    <label class="form-label">Gudang yang Di-opname <span style="color:var(--danger);">*</span></label>
                    <x-searchable-select 
                        name="warehouse_id" 
                        :options="$warehouses" 
                        :value="$selectedWarehouseId" 
                        placeholder="-- Pilih Gudang --" 
                        search-placeholder="Cari nama atau kode gudang..." 
                        on-select="whId => { if(whId) { $refs.hiddenInput.closest('form').submit(); } }" 
                        required 
                    />
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
                            <th style="text-align:right; width:120px;">Stok Sistem</th>
                            <th style="width:130px;">Stok Fisik</th>
                            <th style="text-align:right; width:150px;">Selisih</th>
                            <th style="width:220px;">Alasan Selisih</th>
                            <th>Catatan Penyesuaian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $idx => $p)
                        @php
                            $initPhysical = old("items.{$idx}.physical_qty", $p->system_qty);
                            $initDiff = $initPhysical - $p->system_qty;
                            $initReason = old("items.{$idx}.adjustment_reason");
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
                                <select name="items[{{ $idx }}][adjustment_reason]" class="form-control reason-select" style="{{ $initDiff == 0 ? 'display:none;' : '' }}">
                                    <option value="">-- Pilih Alasan --</option>
                                    <optgroup label="Defisit (Kekurangan Stok)">
                                        <option value="lost" {{ $initReason === 'lost' ? 'selected' : '' }}>Barang Hilang</option>
                                        <option value="damaged_unrecorded" {{ $initReason === 'damaged_unrecorded' ? 'selected' : '' }}>Rusak Belum Tercatat</option>
                                    </optgroup>
                                    <optgroup label="Surplus (Kelebihan Stok)">
                                        <option value="found_extra" {{ $initReason === 'found_extra' ? 'selected' : '' }}>Ditemukan Lebih</option>
                                    </optgroup>
                                    <optgroup label="Umum">
                                        <option value="counting_error" {{ $initReason === 'counting_error' ? 'selected' : '' }}>Salah Hitung</option>
                                        <option value="other" {{ $initReason === 'other' ? 'selected' : '' }}>Lainnya</option>
                                    </optgroup>
                                </select>
                                <span class="no-reason-text" style="{{ $initDiff != 0 ? 'display:none;' : '' }} color:var(--text-secondary); font-size:12.5px;">- Sesuai -</span>
                                @error("items.{$idx}.adjustment_reason")
                                    <div style="color:var(--danger); font-size:12px; margin-top:4px;">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <input type="text" name="items[{{ $idx }}][adjustment_note]" value="{{ old("items.{$idx}.adjustment_note") }}" class="form-control note-input" placeholder="Keterangan tambahan..." style="{{ $initDiff == 0 ? 'display:none;' : '' }}">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <x-form-actions 
            :back-url="route('inventory.opname.index')" 
            submit-label="Simpan Draft Opname" 
        />
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
        const reasonSelect = row.querySelector('.reason-select');
        const noReasonText = row.querySelector('.no-reason-text');
        const noteInput = row.querySelector('.note-input');
        
        if (!sysInput || !physInput || !diffCell) return;

        function updateDiff() {
            const sys = parseInt(sysInput.value) || 0;
            const phys = parseInt(physInput.value) || 0;
            const diff = phys - sys;
            
            if (diff > 0) {
                diffCell.innerHTML = `<span style="color:var(--success);"><i class="fa-solid fa-arrow-trend-up"></i> +${diff} (Surplus)</span>`;
                if (reasonSelect) {
                    reasonSelect.style.display = 'block';
                    reasonSelect.required = true;
                }
                if (noReasonText) noReasonText.style.display = 'none';
                if (noteInput) noteInput.style.display = 'block';
            } else if (diff < 0) {
                diffCell.innerHTML = `<span style="color:var(--danger);"><i class="fa-solid fa-arrow-trend-down"></i> ${diff} (Defisit)</span>`;
                if (reasonSelect) {
                    reasonSelect.style.display = 'block';
                    reasonSelect.required = true;
                }
                if (noReasonText) noReasonText.style.display = 'none';
                if (noteInput) noteInput.style.display = 'block';
            } else {
                diffCell.innerHTML = `<span style="color:var(--text-secondary);">0 (Sesuai)</span>`;
                if (reasonSelect) {
                    reasonSelect.style.display = 'none';
                    reasonSelect.required = false;
                    reasonSelect.value = '';
                }
                if (noReasonText) noReasonText.style.display = 'inline';
                if (noteInput) {
                    noteInput.style.display = 'none';
                    noteInput.value = '';
                }
            }
        }

        physInput.addEventListener('input', updateDiff);
    });
});
</script>
@endsection
