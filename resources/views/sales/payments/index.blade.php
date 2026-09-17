@extends('layouts.app')
@section('title', 'Pembayaran Piutang')
@section('page-title', 'Pembayaran Piutang Customer')

@section('content')
<div class="animate-in">
    <x-page-header
        title="Pembayaran Piutang Customer"
        subtitle="Catat pembayaran kas / bank untuk pelunasan invoice penjualan customer">
        <x-slot:actions>
            <a href="{{ route('sales.payments.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Catat Pembayaran
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-list-filter-bar :action="route('sales.payments.index')" placeholder="Cari Ref / Resi, No. Invoice, Customer..." :showDateFilter="true">
        <select name="method" class="form-control filter-select">
            <option value="">Semua Metode</option>
            <option value="transfer" {{ request('method') === 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
            <option value="cash" {{ request('method') === 'cash' ? 'selected' : '' }}>Tunai / Cash</option>
            <option value="giro" {{ request('method') === 'giro' ? 'selected' : '' }}>Giro</option>
            <option value="cek" {{ request('method') === 'cek' ? 'selected' : '' }}>Cek</option>
        </select>
    </x-list-filter-bar>

    <x-data-table :paginator="$payments">
        <x-slot:head>
            <tr>
                <th>No. Invoice</th>
                <th>Customer</th>
                <x-sortable-header column="payment_date" title="Tgl Bayar" :isDefault="true" />
                <x-sortable-header column="method" title="Metode" />
                <th>No. Ref / Resi</th>
                <x-sortable-header column="amount" title="Jumlah Dibayar" align="right" />
                <th>Dicatat Oleh</th>
                <th class="text-center col-actions">Aksi</th>
            </tr>
        </x-slot:head>

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
            <td>{{ $p->payment_date ? $p->payment_date->format('m/d/Y') : '-' }}</td>
            <td><span class="badge badge-confirmed">{{ strtoupper($p->method) }}</span></td>
            <td>{{ $p->reference_number ?? '-' }}</td>
            <td style="text-align:right; font-weight:600; color:var(--success);">
                Rp {{ number_format($p->amount, 0, ',', '.') }}
            </td>
            <td>{{ $p->user->name ?? '-' }}</td>
            <td class="col-actions text-center">
                <div style="display:flex; gap:4px; justify-content:center;">
                    <a href="{{ route('sales.payments.show', $p) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                        <i class="fa-solid fa-eye"></i>
                    </a>
                    <a href="{{ route('sales.payments.edit', $p) }}" class="btn btn-warning btn-sm btn-icon" title="Edit Pembayaran">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" style="text-align:center; padding:48px; color:var(--text-secondary);">
                <i class="fa-solid fa-hand-holding-dollar" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                Belum ada riwayat pembayaran piutang yang sesuai filter
            </td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection
