@extends('layouts.app')
@section('title', 'Invoice Penjualan')
@section('page-title', 'Invoice Penjualan')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Invoice Penjualan</h1>
            <p>Kelola faktur tagihan ke customer dan status pelunasan piutang</p>
        </div>
        <a href="{{ route('sales.invoices.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Terbitkan Invoice
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Daftar Invoice Penjualan</h3>
            <span style="font-size:13px; color:var(--text-secondary);">{{ $invoices->total() }} invoice</span>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>No. Invoice</th>
                        <th>Customer</th>
                        <th>Ref. SO</th>
                        <th>Tgl Invoice</th>
                        <th>Jatuh Tempo</th>
                        <th style="text-align:right;">Total Tagihan</th>
                        <th style="text-align:right;">Sisa Piutang</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                    <tr>
                        <td>
                            <a href="{{ route('sales.invoices.show', $inv) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                                {{ $inv->invoice_number }}
                            </a>
                        </td>
                        <td>{{ $inv->salesOrder->customer->name ?? '-' }}</td>
                        <td>
                            <a href="{{ route('sales.orders.show', $inv->salesOrder) }}" style="color:var(--text-primary); text-decoration:none;">
                                {{ $inv->salesOrder->so_number }}
                            </a>
                        </td>
                        <td>{{ $inv->invoice_date->format('d/m/Y') }}</td>
                        <td>
                            <span style="{{ $inv->status !== 'paid' && $inv->due_date < today() ? 'color:var(--danger); font-weight:600;' : '' }}">
                                {{ $inv->due_date->format('d/m/Y') }}
                            </span>
                        </td>
                        <td style="text-align:right; font-weight:600;">Rp {{ number_format($inv->total_amount, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:600; color:{{ $inv->outstanding_amount > 0 ? 'var(--danger)' : 'var(--success)' }};">
                            Rp {{ number_format($inv->outstanding_amount, 0, ',', '.') }}
                        </td>
                        <td style="text-align:center;">
                            <span class="badge badge-{{ $inv->status }}">
                                {{ ucfirst($inv->status) }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                <a href="{{ route('sales.invoices.show', $inv) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                @if($inv->outstanding_amount > 0)
                                <a href="{{ route('sales.payments.create') }}?invoice_id={{ $inv->id }}" class="btn btn-success btn-sm btn-icon" title="Terima Pembayaran">
                                    <i class="fa-solid fa-hand-holding-dollar"></i>
                                </a>
                                @endif
                                <a href="{{ route('pdf.sales-invoice', $inv) }}" class="btn btn-secondary btn-sm btn-icon" title="Export PDF" target="_blank">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            <i class="fa-solid fa-file-invoice-dollar" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                            Belum ada invoice penjualan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border);">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
