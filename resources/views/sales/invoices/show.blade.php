@extends('layouts.app')
@section('title', 'Detail Invoice - ' . $invoice->invoice_number)
@section('page-title', 'Detail Invoice Penjualan')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>{{ $invoice->invoice_number }}</h1>
            <p>
                Ref SO: <a href="{{ route('sales.orders.show', $invoice->salesOrder) }}" style="color:var(--primary); font-weight:600;">{{ $invoice->salesOrder->so_number }}</a>
                @if($invoice->delivery)
                    · Ref Surat Jalan: <a href="{{ route('sales.deliveries.show', $invoice->delivery) }}" style="color:var(--primary); font-weight:600;">{{ $invoice->delivery->delivery_number }}</a>
                @endif
                · Customer: <strong>{{ $invoice->salesOrder->customer->name ?? '-' }}</strong>
            </p>
        </div>
        <div style="display:flex; gap:8px;">
            @if($invoice->outstanding_amount > 0)
            <a href="{{ route('sales.payments.create') }}?invoice_id={{ $invoice->id }}" class="btn btn-success">
                <i class="fa-solid fa-hand-holding-dollar"></i> Terima Pembayaran Piutang
            </a>
            @endif
            <a href="{{ route('pdf.sales-invoice', $invoice) }}" class="btn btn-secondary" target="_blank">
                <i class="fa-solid fa-file-pdf"></i> PDF Invoice
            </a>
            <a href="{{ route('sales.invoices.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-3" style="margin-bottom:20px;">
        <div class="card" style="grid-column:span 2;">
            <div class="card-header">
                <h3>Informasi Invoice</h3>
                <span class="badge badge-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
            </div>
            <div class="card-body">
                <div class="form-row form-row-2">
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
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Surat Jalan (Delivery)</div>
                        <div style="font-weight:600;">
                            @if($invoice->delivery)
                            <a href="{{ route('sales.deliveries.show', $invoice->delivery) }}" style="color:var(--primary); text-decoration:none;">
                                {{ $invoice->delivery->delivery_number }}
                            </a>
                            @else
                            <span style="color:var(--text-secondary);">-</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Tanggal Invoice</div>
                        <div style="font-weight:600;">{{ $invoice->invoice_date->format('d F Y') }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Tanggal Jatuh Tempo</div>
                        <div style="font-weight:600; color:{{ $invoice->status !== 'paid' && $invoice->due_date < today() ? 'var(--danger)' : 'var(--text-primary)' }};">
                            {{ $invoice->due_date->format('d F Y') }}
                        </div>
                    </div>
                </div>

                @if($invoice->salesOrder->customer?->isPkp())
                <div class="p-3.5 rounded-lg border border-indigo-100 bg-[#FAF9FF] mt-4" x-data="{ editingTax: false }">
                    <div class="flex items-center justify-between flex-wrap gap-2 mb-2">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-file-invoice text-primary text-sm"></i>
                            <span class="text-xs font-bold text-[#0e1b35]">Faktur Pajak (Customer PKP)</span>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-800 border border-green-200">PKP</span>
                        </div>
                        <template x-if="!editingTax">
                            <button type="button" @click="editingTax = true" class="btn btn-secondary btn-sm" style="padding:2px 8px; font-size:11px;">
                                <i class="fa-solid fa-pen text-[10px]"></i> {{ $invoice->tax_invoice_number ? 'Ubah No. Seri' : 'Input No. Seri' }}
                            </button>
                        </template>
                    </div>

                    <div x-show="!editingTax" class="flex items-center justify-between flex-wrap gap-2">
                        <div>
                            <div style="font-size:11px; color:var(--text-secondary);">Nomor Seri Faktur Pajak (NSFP) DJP:</div>
                            <div class="font-mono text-sm font-bold text-gray-800 mt-0.5">
                                @if($invoice->tax_invoice_number)
                                    <span class="text-primary">{{ $invoice->tax_invoice_number }}</span>
                                @else
                                    <span class="text-gray-400 font-normal italic text-xs">Belum diisi (input nomor setelah terbit di aplikasi e-Faktur)</span>
                                @endif
                            </div>
                        </div>
                        @if($invoice->salesOrder->customer->npwp)
                        <div>
                            <div style="font-size:11px; color:var(--text-secondary);">NPWP Customer:</div>
                            <div class="font-mono text-xs font-semibold text-gray-700 mt-0.5">{{ $invoice->salesOrder->customer->npwp }}</div>
                        </div>
                        @endif
                    </div>

                    <div x-show="editingTax" style="display: none;" class="mt-2 pt-2 border-t border-indigo-100">
                        <form method="POST" action="{{ route('sales.invoices.tax-invoice.update', $invoice) }}" class="flex items-center gap-2 flex-wrap">
                            @csrf
                            @method('PATCH')
                            <div class="flex-1 min-w-[240px]">
                                <input type="text" name="tax_invoice_number" value="{{ old('tax_invoice_number', $invoice->tax_invoice_number ?? '') }}" 
                                    class="form-control text-xs font-mono" 
                                    placeholder="Contoh: 010.001-26.00000001" style="height:34px;">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm" style="height:34px; padding:0 12px; font-size:12px;">
                                <i class="fa-solid fa-check"></i> Simpan
                            </button>
                            <button type="button" @click="editingTax = false" class="btn btn-secondary btn-sm" style="height:34px; padding:0 10px; font-size:12px;">
                                Batal
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                @if($invoice->notes)
                <div style="margin-top:16px; padding:12px; background:#f8fafc; border-radius:10px; font-size:13.5px; color:var(--text-secondary);">
                    <i class="fa-solid fa-note-sticky" style="margin-right:6px;"></i> {{ $invoice->notes }}
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Ringkasan Piutang</h3></div>
            <div class="card-body">
                <div style="display:flex; flex-direction:column; gap:10px; font-size:14px;">
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:var(--text-secondary);">Subtotal (DPP)</span>
                        <span>Rp {{ number_format($invoice->amount, 0, ',', '.') }}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:var(--text-secondary);">PPN ({{ $invoice->tax_rate }}%)</span>
                        <span>Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</span>
                    </div>
                    <hr style="border:none; border-top:1px solid var(--border);">
                    <div style="display:flex; justify-content:space-between; font-weight:700;">
                        <span>Total Tagihan Awal</span>
                        <span style="color:var(--primary);">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                    </div>
                    @if($invoice->total_reversed_amount > 0)
                    <div style="display:flex; justify-content:space-between; color:var(--danger);">
                        <span>Pengurang Retur</span>
                        <span>- Rp {{ number_format($invoice->total_reversed_amount, 0, ',', '.') }}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-weight:700;">
                        <span>Total Tagihan Efektif</span>
                        <span style="color:var(--primary);">Rp {{ number_format($invoice->effective_total_amount, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div style="display:flex; justify-content:space-between; color:var(--success);">
                        <span>Total Diterima</span>
                        <span>Rp {{ number_format($invoice->total_paid, 0, ',', '.') }}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:16px; font-weight:700; color:{{ $invoice->outstanding_amount > 0 ? 'var(--danger)' : 'var(--success)' }};">
                        <span>Sisa Piutang</span>
                        <span>Rp {{ number_format($invoice->outstanding_amount, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detail Item Ditagih (3-Way Match) --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <h3>Rincian Item yang Ditagihkan pada Invoice Ini</h3>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th style="text-align:center;">Qty Ditagih</th>
                        <th style="text-align:right;">Harga Satuan</th>
                        <th style="text-align:center;">Diskon</th>
                        <th style="text-align:right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $invoiceItems = $invoice->items->isNotEmpty() ? $invoice->items : $invoice->salesOrder->items;
                    @endphp
                    @foreach($invoiceItems as $item)
                    @php
                        $qty = $item->qty_invoiced ?? $item->qty_ordered ?? 0;
                        $price = $item->unit_price;
                        $discPercent = $item->discount_percent ?? 0;
                        $subtotal = $item->subtotal ?? (($qty * $price) - (($qty * $price) * ($discPercent / 100)));
                    @endphp
                    <tr>
                        <td>
                            <div style="font-weight:600;">{{ $item->product->name ?? '-' }}</div>
                            <div style="font-size:12px; color:var(--text-secondary);">
                                {{ $item->product->sku ?? '-' }}
                                @if($item->deliveryItem && $item->deliveryItem->delivery)
                                    · <span class="badge badge-pending" style="font-size:10px; padding:2px 6px;">Ref SJ: {{ $item->deliveryItem->delivery->delivery_number }}</span>
                                @endif
                            </div>
                        </td>
                        <td style="text-align:center;">
                            <span class="badge badge-done" style="font-weight:700;">
                                {{ number_format($qty) }} {{ $item->product->unit ?? 'pcs' }}
                            </span>
                        </td>
                        <td style="text-align:right;">
                            Rp {{ number_format($price, 0, ',', '.') }}
                        </td>
                        <td style="text-align:center;">
                            {{ $discPercent > 0 ? $discPercent . '%' : '-' }}
                        </td>
                        <td style="text-align:right; font-weight:600; color:var(--primary);">
                            Rp {{ number_format($subtotal, 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- History Pembayaran --}}
    <div class="card">
        <div class="card-header"><h3>Riwayat Penerimaan Piutang</h3></div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>No. Pembayaran</th>
                        <th>Tanggal Bayar</th>
                        <th>Metode</th>
                        <th>Ref / No. Cek</th>
                        <th style="text-align:right;">Jumlah Diterima</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoice->payments as $payment)
                    <tr>
                        <td style="font-weight:600; color:var(--primary);">{{ $payment->payment_number }}</td>
                        <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                        <td><span class="badge badge-confirmed">{{ strtoupper($payment->payment_method) }}</span></td>
                        <td>{{ $payment->reference_number ?? '-' }}</td>
                        <td style="text-align:right; font-weight:600; color:var(--success);">
                            Rp {{ number_format($payment->amount, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align:center; padding:36px; color:var(--text-secondary);">
                            Belum ada riwayat pembayaran untuk invoice ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
