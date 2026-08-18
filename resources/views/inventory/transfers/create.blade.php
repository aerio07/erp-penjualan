@extends('layouts.app')
@section('title', 'Buat Transfer Gudang')
@section('page-title', 'Buat Transfer Gudang')

@section('content')
<div class="animate-in" x-data="trfForm()">
    <div class="page-header">
        <div>
            <h1>Buat Transfer Gudang</h1>
            <p>Form mutasi pemindahan fisik persediaan antar gudang</p>
        </div>
        <a href="{{ route('inventory.transfers.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <form method="POST" action="{{ route('inventory.transfers.store') }}">
        @csrf

        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h3>Lokasi & Tanggal</h3></div>
            <div class="card-body">
                <div class="form-row form-row-3">
                    <div class="form-group">
                        <label class="form-label">Gudang Asal <span style="color:var(--danger);">*</span></label>
                        <select name="from_warehouse_id" class="form-control" x-model="fromWhId" required>
                            <option value="">-- Pilih Gudang Asal --</option>
                            @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Gudang Tujuan <span style="color:var(--danger);">*</span></label>
                        <select name="to_warehouse_id" class="form-control" x-model="toWhId" required>
                            <option value="">-- Pilih Gudang Tujuan --</option>
                            @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" :disabled="fromWhId == {{ $wh->id }}">{{ $wh->name }} ({{ $wh->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tanggal Kirim <span style="color:var(--danger);">*</span></label>
                        <input type="date" name="transfer_date" value="{{ old('transfer_date', date('Y-m-d')) }}" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Catatan pengiriman transfer...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Items --}}
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <h3>Daftar Barang Ditransfer</h3>
                <button type="button" class="btn btn-primary btn-sm" @click="addRow()">
                    <i class="fa-solid fa-plus"></i> Tambah Item
                </button>
            </div>
            <div class="table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th style="width:140px;">Jumlah Transfer</th>
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, idx) in rows" :key="idx">
                            <tr>
                                <td>
                                    <select :name="`items[${idx}][product_id]`" class="form-control" x-model="row.product_id" required>
                                        <option value="">-- Pilih Produk --</option>
                                        @foreach($products as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" :name="`items[${idx}][qty_requested]`" x-model.number="row.qty" class="form-control" min="1" required>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm btn-icon" @click="removeRow(idx)" x-show="rows.length > 1">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:12px;">
            <a href="{{ route('inventory.transfers.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-truck-ramp-box"></i> Kirim Transfer & Potong Stok Asal
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function trfForm() {
    return {
        fromWhId: '',
        toWhId: '',
        rows: [{ product_id: '', qty: 1 }],

        addRow() { this.rows.push({ product_id: '', qty: 1 }); },
        removeRow(idx) { this.rows.splice(idx, 1); }
    }
}
</script>
@endpush
