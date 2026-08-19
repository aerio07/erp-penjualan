@extends('layouts.app')
@section('title', 'Penerimaan Piutang')
@section('page-title', 'Penerimaan Piutang Customer')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Penerimaan Piutang Customer</h1>
            <p>Catat penerimaan uang kas / bank dari pelunasan invoice penjualan</p>
        </div>
        <a href="{{ route('sales.payments.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Catat Penerimaan
        </a>
    </div>

    <x-list-filter-bar :action="route('sales.payments.index')" placeholder="Cari Ref / Resi, No. Invoice, Customer..." :showDateFilter="true">
        <select name="method" class="form-control" style="height:38px; font-size:13px; min-width:150px; border-radius:6px;">
            <option value="">Semua Metode</option>
            <option value="transfer" {{ request('method') === 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
            <option value="cash" {{ request('method') === 'cash' ? 'selected' : '' }}>Tunai / Cash</option>
            <option value="giro" {{ request('method') === 'giro' ? 'selected' : '' }}>Giro</option>
            <option value="cek" {{ request('method') === 'cek' ? 'selected' : '' }}>Cek</option>
        </select>
    </x-list-filter-bar>

    <div class="card">
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>No. Invoice</th>
                        <th>Customer</th>
                        <x-sortable-header column="payment_date" title="Tgl Bayar" />
                        <x-sortable-header column="method" title="Metode" />
                        <th>No. Ref / Resi</th>
                        <x-sortable-header column="amount" title="Jumlah Diterima" align="right" />
                        <th>Dicatat Oleh</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $p)
                    <tr>
                        <td>
                            @if($p->salesInvoice)
                            <a href="{{ route('sales.invoices.show', $p->salesInvoice) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">
                                {{ $p->salesInvoice->invoice_number }}
                            </a>
                            @else
                            -
                            @endif
                        </td>
                        <td>{{ $p->salesInvoice->salesOrder->customer->name ?? '-' }}</td>
                        <td>{{ $p->payment_date ? $p->payment_date->format('d/m/Y') : '-' }}</td>
                        <td><span class="badge badge-confirmed">{{ strtoupper($p->method) }}</span></td>
                        <td>{{ $p->reference_number ?? '-' }}</td>
                        <td style="text-align:right; font-weight:600; color:var(--success);">
                            Rp {{ number_format($p->amount, 0, ',', '.') }}
                        </td>
                        <td>{{ $p->user->name ?? '-' }}</td>
                        <td style="text-align:center;">
                            <a href="{{ route('sales.payments.show', $p) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:48px; color:var(--text-secondary);">
                            <i class="fa-solid fa-hand-holding-dollar" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                            Belum ada riwayat penerimaan piutang yang sesuai filter
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payments->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border);">
            {{ $payments->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
