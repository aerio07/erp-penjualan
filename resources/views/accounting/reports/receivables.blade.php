@extends('layouts.app')
@section('title', 'Laporan Piutang Usaha')
@section('page-title', 'Laporan Piutang Usaha (AR Report)')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Laporan Piutang Usaha</h1>
            <p>Daftar invoice penjualan belum lunas per customer</p>
        </div>
    </div>

    <div class="grid grid-2" style="margin-bottom:24px;">
        <div class="stat-card">
            <div class="icon" style="background:#dbeafe; color:#1d4ed8;"><i class="fa-solid fa-file-invoice-dollar"></i></div>
            <div class="value">Rp {{ number_format($totalOutstanding, 0, ',', '.') }}</div>
            <div class="label">Total Piutang Beredar</div>
        </div>

        <div class="stat-card">
            <div class="icon" style="background:#fee2e2; color:#991b1b;"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <div class="value" style="color:var(--danger);">Rp {{ number_format($overdue, 0, ',', '.') }}</div>
            <div class="label">Piutang Jatuh Tempo (Overdue)</div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>No. Invoice</th>
                        <th>Customer</th>
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
                        <td>{{ $inv->invoice_date->format('d/m/Y') }}</td>
                        <td>
                            <span style="{{ $inv->due_date < today() ? 'color:var(--danger); font-weight:600;' : '' }}">
                                {{ $inv->due_date->format('d/m/Y') }}
                            </span>
                        </td>
                        <td style="text-align:right;">Rp {{ number_format($inv->total_amount, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:700; color:var(--danger);">
                            Rp {{ number_format($inv->outstanding_amount, 0, ',', '.') }}
                        </td>
                        <td style="text-align:center;">
                            <span class="badge badge-{{ $inv->due_date < today() ? 'unpaid' : 'pending' }}">
                                {{ $inv->due_date < today() ? 'Jatuh Tempo' : 'Belum Lunas' }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <a href="{{ route('sales.payments.create') }}?invoice_id={{ $inv->id }}" class="btn btn-success btn-sm">
                                <i class="fa-solid fa-hand-holding-dollar"></i> Terima Bayar
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            <i class="fa-solid fa-circle-check" style="font-size:32px; color:var(--success); display:block; margin-bottom:12px;"></i>
                            Tidak ada piutang beredar. Semua invoice sudah lunas!
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
