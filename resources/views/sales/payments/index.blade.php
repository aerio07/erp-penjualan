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

    @if(session('success'))
    <div class="alert alert-success" style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-circle-check" style="font-size: 18px;"></i>
        <div>{{ session('success') }}</div>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger" style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size: 18px;"></i>
        <div>{{ session('error') }}</div>
    </div>
    @endif

    <x-list-filter-bar :action="route('sales.payments.index')" placeholder="Cari No. Invoice, Customer, Catatan..." :showDateFilter="true">
        <select name="status" class="form-control filter-select" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
            <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>Posted</option>
        </select>
        <select name="payment_type" class="form-control filter-select" onchange="this.form.submit()">
            <option value="">Semua Tipe</option>
            <option value="bank" {{ request('payment_type') === 'bank' ? 'selected' : '' }}>Bank (BBM)</option>
            <option value="kas" {{ request('payment_type') === 'kas' ? 'selected' : '' }}>Kas (BKM)</option>
        </select>
        <select name="chart_of_account_id" class="form-control filter-select" onchange="this.form.submit()">
            <option value="">Semua Akun COA</option>
            @foreach($coas as $c)
                <option value="{{ $c->id }}" {{ request('chart_of_account_id') == $c->id ? 'selected' : '' }}>
                    [{{ $c->code }}] {{ $c->name }}
                </option>
            @endforeach
        </select>
        <select name="method" class="form-control filter-select" onchange="this.form.submit()">
            <option value="">Semua Metode</option>
            <option value="transfer" {{ request('method') === 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
            <option value="cash" {{ request('method') === 'cash' ? 'selected' : '' }}>Tunai / Cash</option>
            <option value="cek" {{ request('method') === 'cek' ? 'selected' : '' }}>Cek</option>
        </select>
    </x-list-filter-bar>

    <x-data-table :paginator="$payments">
        <x-slot:head>
            <tr>
                <x-sortable-header column="payment_number" title="No. Pembayaran" />
                <th>No. Invoice</th>
                <th>Customer</th>
                <x-sortable-header column="payment_date" title="Tgl Bayar" :isDefault="true" />
                <th style="min-width:180px;">Akun Kas/Bank (COA)</th>
                <x-sortable-header column="method" title="Metode" />
                <x-sortable-header column="amount" title="Jumlah Dibayar" align="right" />
                <x-sortable-header column="status" title="Status" align="center" />
                <th>Dicatat Oleh</th>
                <th class="text-center col-actions">Aksi</th>
            </tr>
        </x-slot:head>

        @forelse($payments as $p)
        <tr>
            <td>
                <a href="{{ route('sales.payments.show', $p) }}" style="color:var(--primary); font-weight:700; text-decoration:none; font-family: 'Inter', monospace;">
                    {{ $p->voucher_number }}
                </a>
            </td>
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
            <td>
                @if($p->chartOfAccount)
                    <span style="font-size:12px; font-weight:600; color:var(--primary); display:inline-flex; align-items:center; gap:4px;">
                        <i class="fa-solid fa-book-bookmark" style="font-size:11px; opacity:0.7;"></i>
                        [{{ $p->chartOfAccount->code }}] {{ $p->chartOfAccount->name }}
                    </span>
                @else
                    <span style="color:var(--text-secondary); font-size:12px;">-</span>
                @endif
            </td>
            <td><span class="badge badge-confirmed">{{ strtoupper($p->method) }}</span></td>
            <td style="text-align:right; font-weight:600; color:var(--success); font-family: 'Inter', monospace;">
                Rp {{ number_format($p->amount, 0, ',', '.') }}
            </td>
            <td style="text-align:center;">
                <x-status-badge :status="$p->status" />
            </td>
            <td>{{ $p->user->name ?? '-' }}</td>
            <td class="col-actions text-center">
                <div style="display:flex; gap:4px; justify-content:center;">
                    <a href="{{ route('sales.payments.show', $p) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                        <i class="fa-solid fa-eye"></i>
                    </a>
                    @if($p->isDraft())
                    <a href="{{ route('sales.payments.edit', $p) }}" class="btn btn-warning btn-sm btn-icon" title="Edit Draft">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                    <form method="POST" action="{{ route('sales.payments.destroy', $p) }}" style="display:inline;" onsubmit="return confirm('Hapus draft penerimaan piutang ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Hapus Draft">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                    @elseif($p->isPosted())
                    <a href="{{ route('pdf.sales-payment', $p) }}" target="_blank" class="btn btn-secondary btn-sm btn-icon" title="Cetak Bukti Penerimaan (PDF)">
                        <i class="fa-solid fa-file-pdf"></i>
                    </a>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="9" style="text-align:center; padding:48px; color:var(--text-secondary);">
                <i class="fa-solid fa-hand-holding-dollar" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                Belum ada riwayat pembayaran piutang yang sesuai filter
            </td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection
