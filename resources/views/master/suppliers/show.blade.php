@extends('layouts.app')
@section('title', $supplier->name)
@section('page-title', 'Detail Supplier')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>{{ $supplier->name }}</h1>
            <p>{{ $supplier->code }} &nbsp;·&nbsp;
                <span class="badge {{ $supplier->is_active ? 'badge-done' : 'badge-cancelled' }}">
                    {{ $supplier->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </p>
        </div>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('master.suppliers.edit', $supplier) }}" class="btn btn-secondary"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="{{ route('master.suppliers.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        </div>
    </div>

    <div class="grid grid-3" style="margin-bottom:24px;">
        {{-- Info Card --}}
        <div class="card" style="grid-column:span 2;">
            <div class="card-header"><h3>Informasi Supplier</h3></div>
            <div class="card-body">
                <div class="form-row form-row-2">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Contact Person</div>
                        <div style="font-weight:600;">{{ $supplier->contact_person ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Telepon</div>
                        <div style="font-weight:600;">{{ $supplier->phone ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Email</div>
                        <div>{{ $supplier->email ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Payment Term</div>
                        @if($supplier->payment_term)
                        <span class="badge badge-confirmed">{{ $supplier->payment_term }}</span>
                        @else
                        <span>-</span>
                        @endif
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">NPWP</div>
                        <div>{{ $supplier->npwp ?? '-' }}</div>
                    </div>
                </div>
                @if($supplier->address)
                <div style="margin-top:16px; padding:12px; background:#f8fafc; border-radius:10px;">
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Alamat</div>
                    <div style="font-size:14px;">{{ $supplier->address }}</div>
                </div>
                @endif
            </div>
        </div>

        {{-- Stat --}}
        <div class="card">
            <div class="card-header"><h3>Statistik</h3></div>
            <div class="card-body">
                <div style="display:flex; flex-direction:column; gap:16px;">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary);">Total Pembelian</div>
                        <div style="font-size:20px; font-weight:700; color:var(--primary);">
                            Rp {{ number_format($totalPurchase, 0, ',', '.') }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary);">Jumlah PO</div>
                        <div style="font-size:20px; font-weight:700;">{{ $supplier->purchaseOrders->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent POs --}}
    @if($supplier->purchaseOrders->count() > 0)
    <div class="card">
        <div class="card-header">
            <h3>Purchase Order Terakhir</h3>
            <a href="{{ route('purchase.orders.index') }}?supplier={{ $supplier->id }}" class="btn btn-secondary btn-sm">Lihat Semua</a>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>No. PO</th>
                        <th>Tanggal</th>
                        <th style="text-align:right;">Total</th>
                        <th style="text-align:center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($supplier->purchaseOrders as $po)
                    <tr>
                        <td><a href="{{ route('purchase.orders.show', $po) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">{{ $po->po_number }}</a></td>
                        <td>{{ $po->order_date->format('d/m/Y') }}</td>
                        <td style="text-align:right; font-weight:600;">Rp {{ number_format($po->total_amount, 0, ',', '.') }}</td>
                        <td style="text-align:center;"><span class="badge badge-{{ $po->status }}">{{ ucfirst(str_replace('_',' ',$po->status)) }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
