@extends('layouts.app')
@section('title', 'Edit Draft Invoice - ' . $invoice->invoice_number)
@section('page-title', 'Edit Draft Invoice Penjualan')

@section('content')
<div class="animate-in">
    <x-page-header 
        :title="'Edit Draft Invoice #' . $invoice->invoice_number" 
        :subtitle="'Ref SO: ' . ($invoice->salesOrder->so_number ?? '-') . ($invoice->delivery ? ' · Ref SJ: ' . $invoice->delivery->delivery_number : '')"
    >
        <x-slot:actions>
            <a href="{{ route('sales.invoices.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </x-slot:actions>
    </x-page-header>

    <form action="{{ route('sales.invoices.update', $invoice) }}" method="POST" id="editDraftForm">
        @csrf
        @method('PUT')

        {{-- Referensi Dokumen Sumber (Read-Only) --}}
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <h3>Referensi Dokumen Sumber (Read-Only)</h3>
                <span class="badge badge-draft" style="font-weight:600;">Draft</span>
            </div>
            <div class="card-body">
                <div class="form-row form-row-3">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Customer</div>
                        <div style="font-weight:600; display:flex; align-items:center; gap:8px;">
                            <span>{{ $invoice->salesOrder->customer->name ?? '-' }}</span>
                            @if($invoice->salesOrder->customer?->isPkp())
                                <x-status-badge status="pkp" />
                            @endif
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Nomor Sales Order</div>
                        <div style="font-weight:600; color:var(--primary);">
                            {{ $invoice->salesOrder->so_number ?? '-' }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Nomor Surat Jalan (Delivery)</div>
                        <div style="font-weight:600; color:var(--primary);">
                            {{ $invoice->delivery->delivery_number ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form Edit Data Faktur --}}
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <h3>Parameter Faktur</h3>
            </div>
            <div class="card-body">
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label for="tax_rate" class="form-label">Tarif PPN (%) <span style="color:var(--danger);">*</span></label>
                        <input 
                            type="number" 
                            name="tax_rate" 
                            id="tax_rate" 
                            step="0.01" 
                            min="0" 
                            max="100" 
                            class="form-control @error('tax_rate') is-invalid @enderror"
                            value="{{ old('tax_rate', $invoice->tax_rate) }}"
                            required
                        >
                        @error('tax_rate')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">
                            Mengubah tarif PPN akan otomatis mengkalkulasi ulang nilai PPN dan total piutang invoice draft.
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="tax_invoice_number" class="form-label">No. Seri Faktur Pajak (NSFP DJP)</label>
                        <input 
                            type="text" 
                            name="tax_invoice_number" 
                            id="tax_invoice_number" 
                            class="form-control font-mono @error('tax_invoice_number') is-invalid @enderror"
                            value="{{ old('tax_invoice_number', $invoice->tax_invoice_number) }}"
                            placeholder="Contoh: 010.001-26.00000001"
                        >
                        @error('tax_invoice_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-row form-row-2" style="margin-top:12px;">
                    <div class="form-group">
                        <label for="invoice_date" class="form-label">Tanggal Invoice <span style="color:var(--danger);">*</span></label>
                        <input 
                            type="date" 
                            name="invoice_date" 
                            id="invoice_date" 
                            class="form-control @error('invoice_date') is-invalid @enderror"
                            value="{{ old('invoice_date', $invoice->invoice_date ? $invoice->invoice_date->toDateString() : date('Y-m-d')) }}"
                            required
                        >
                        @error('invoice_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="due_date" class="form-label">Tanggal Jatuh Tempo <span style="color:var(--danger);">*</span></label>
                        <input 
                            type="date" 
                            name="due_date" 
                            id="due_date" 
                            class="form-control @error('due_date') is-invalid @enderror"
                            value="{{ old('due_date', $invoice->due_date ? $invoice->due_date->toDateString() : date('Y-m-d', strtotime('+30 days'))) }}"
                            required
                        >
                        @error('due_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-group" style="margin-top:12px;">
                    <label for="notes" class="form-label">Catatan / Keterangan</label>
                    <textarea 
                        name="notes" 
                        id="notes" 
                        rows="3" 
                        class="form-control @error('notes') is-invalid @enderror"
                        placeholder="Catatan internal atau keterangan tambahan mengenai faktur ini..."
                    >{{ old('notes', $invoice->notes) }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Rincian Item --}}
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <h3>Rincian Item Ditagih (Dari Surat Jalan)</h3>
            </div>
            <div class="table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th style="text-align:center; width:120px;">Qty Ditagih</th>
                            <th style="text-align:right; width:150px;">Harga Satuan</th>
                            <th style="text-align:center; width:90px;">Diskon</th>
                            <th style="text-align:right; width:160px;">Subtotal (DPP)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $item)
                        <tr>
                            <td>
                                <div style="font-weight:600;">{{ $item->product->name ?? '-' }}</div>
                                <div style="font-size:12px; color:var(--text-secondary);">
                                    {{ $item->product->sku ?? '-' }}
                                </div>
                            </td>
                            <td style="text-align:center;">
                                <span class="badge badge-done" style="font-weight:700;">
                                    {{ number_format($item->qty_invoiced) }} {{ $item->product->unit ?? 'pcs' }}
                                </span>
                            </td>
                            <td style="text-align:right;">
                                Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                            </td>
                            <td style="text-align:center;">
                                {{ $item->discount_percent > 0 ? $item->discount_percent . '%' : '-' }}
                            </td>
                            <td style="text-align:right; font-weight:600; color:var(--primary);">
                                Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background:#f8fafc; font-weight:700;">
                            <td colspan="4" style="text-align:right;">Total DPP:</td>
                            <td style="text-align:right; color:var(--primary);">
                                Rp {{ number_format($invoice->amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <x-form-actions 
            :cancel-url="route('sales.invoices.index')" 
            submit-label="Simpan Perubahan Draft" 
        />
    </form>
</div>
@endsection
