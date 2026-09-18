@extends('layouts.app')
@section('title', 'Pembayaran Hutang')
@section('page-title', 'Pembayaran Hutang Supplier')

@section('content')
<div class="animate-in">
    <x-page-header 
        title="Pembayaran Hutang Supplier" 
        subtitle="Catat pengeluaran kas / bank untuk pelunasan invoice pembelian"
    >
        <x-slot:actions>
            <a href="{{ route('purchase.payments.create') }}" class="btn btn-primary">
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

    <x-list-filter-bar :action="route('purchase.payments.index')" placeholder="Cari No. Invoice, Supplier, Catatan..." :showDateFilter="true">
        <select name="status" class="form-control filter-select" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
            <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>Posted</option>
        </select>
        <select name="method" class="form-control filter-select" onchange="this.form.submit()">
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
                <x-sortable-header column="payment_number" title="No. Pembayaran" :isDefault="true" />
                <th>No. Invoice</th>
                <th>Supplier</th>
                <x-sortable-header column="payment_date" title="Tgl Bayar" />
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
                <a href="{{ route('purchase.payments.show', $p) }}" style="color:var(--primary); font-weight:700; text-decoration:none;">
                    {{ $p->payment_number ?? $p->voucher_number }}
                </a>
            </td>
            <td>
                @if($p->purchaseInvoice)
                <a href="{{ route('purchase.invoices.show', $p->purchaseInvoice) }}" style="color:var(--text-primary); font-weight:600; text-decoration:none;">
                    {{ $p->purchaseInvoice->invoice_number }}
                </a>
                @else
                -
                @endif
            </td>
            <td style="font-weight:500;">{{ $p->purchaseInvoice->purchaseOrder->supplier->name ?? '-' }}</td>
            <td>{{ $p->payment_date ? $p->payment_date->format('d/m/Y') : '-' }}</td>
            <td>
                <span class="badge" style="background:#f1f5f9; color:#334155; border:1px solid #cbd5e1; font-weight:600;">
                    {{ strtoupper($p->method) }}
                </span>
            </td>
            <td style="text-align:right; font-weight:600; color:var(--success); font-family: 'Inter', monospace;">
                Rp {{ number_format($p->amount, 0, ',', '.') }}
            </td>
            <td style="text-align:center;">
                <x-status-badge :status="$p->status" />
            </td>
            <td>{{ $p->user->name ?? '-' }}</td>
            <td class="col-actions text-center">
                <div style="display:flex; gap:4px; justify-content:center; align-items:center;">
                    <a href="{{ route('purchase.payments.show', $p) }}" class="btn btn-secondary btn-sm btn-icon" title="Lihat Detail">
                        <i class="fa-solid fa-eye"></i>
                    </a>

                    @if($p->status === 'draft')
                    <a href="{{ route('purchase.payments.edit', $p) }}" class="btn btn-secondary btn-sm btn-icon" title="Edit Draft" style="color:var(--primary);">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                    <form method="POST" action="{{ route('purchase.payments.destroy', $p) }}" style="display:inline;" onsubmit="return confirm('Hapus draft pembayaran ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Hapus Draft">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                    @elseif($p->status === 'posted')
                    <a href="{{ route('pdf.purchase-payment', $p) }}" target="_blank" class="btn btn-secondary btn-sm btn-icon" title="Cetak Bukti Pembayaran (PDF)">
                        <i class="fa-solid fa-file-pdf"></i>
                    </a>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="9" style="text-align:center; padding:48px; color:var(--text-secondary);">
                <i class="fa-solid fa-money-check" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.4;"></i>
                Belum ada riwayat pembayaran hutang yang sesuai filter
            </td>
        </tr>
        @endforelse
    </x-data-table>
</div>
@endsection
