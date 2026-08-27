@extends('layouts.app')
@section('title', $customer->name)
@section('page-title', 'Detail Customer')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>{{ $customer->name }}</h1>
            <p>Kode: <strong style="color:var(--primary);">{{ $customer->code }}</strong> &nbsp;·&nbsp;
                <span class="badge {{ $customer->is_active ? 'badge-done' : 'badge-cancelled' }}">
                    {{ $customer->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </p>
        </div>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <form method="POST" action="{{ route('master.customers.toggle-status', $customer) }}" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin {{ $customer->is_active ? 'menonaktifkan' : 'mengaktifkan' }} customer ini?');">
                @csrf
                @method('PATCH')
                @if($customer->is_active)
                    <button type="submit" class="btn btn-secondary" style="color:#b91c1c; border-color:#fca5a5;" title="Nonaktifkan Customer">
                        <i class="fa-solid fa-ban"></i> Nonaktifkan
                    </button>
                @else
                    <button type="submit" class="btn btn-primary" style="background:#16a34a; border-color:#16a34a;" title="Aktifkan Customer">
                        <i class="fa-solid fa-check"></i> Aktifkan
                    </button>
                @endif
            </form>

            <a href="{{ route('master.customers.edit', $customer) }}" class="btn btn-secondary">
                <i class="fa-solid fa-pen"></i> Edit
            </a>

            <button type="button" data-confirm-delete="del-cust-show" data-name="{{ $customer->name }} ({{ $customer->code }})" class="btn btn-danger" title="Hapus Customer">
                <i class="fa-solid fa-trash"></i> Hapus
            </button>
            <form id="del-cust-show" method="POST" action="{{ route('master.customers.destroy', $customer) }}" style="display:none;">
                @csrf
                @method('DELETE')
            </form>

            <a href="{{ route('master.customers.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-3" style="margin-bottom:24px;">
        <div class="card" style="grid-column:span 2;">
            <div class="card-header"><h3>Informasi Customer</h3></div>
            <div class="card-body">
                <div class="form-row form-row-2">
                    <div><div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Contact Person</div><div style="font-weight:600;">{{ $customer->contact_person ?? '-' }}</div></div>
                    <div><div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Telepon</div><div style="font-weight:600;">{{ $customer->phone ?? '-' }}</div></div>
                    <div><div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Email</div><div>{{ $customer->email ?? '-' }}</div></div>
                    <div><div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Payment Term</div>
                        @if($customer->payment_term)<span class="badge badge-confirmed">{{ $customer->payment_term }}</span>@else<span>-</span>@endif
                    </div>
                    <div><div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Tipe Customer</div>
                        <div><x-status-badge :status="$customer->tax_type ?? 'non_pkp'" /></div>
                    </div>
                    <div><div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">NPWP / NIK</div>
                        <div style="font-weight:{{ ($customer->npwp || $customer->nik) ? '600' : 'normal' }};">
                            @if($customer->npwp)
                                <span>NPWP: {{ $customer->npwp }}</span>
                            @endif
                            @if($customer->nik)
                                <div style="font-size:12px; color:#475569; margin-top:2px;">NIK: {{ $customer->nik }}</div>
                            @endif
                            @if(!$customer->npwp && !$customer->nik)
                                <span>-</span>
                            @endif
                        </div>
                    </div>
                    <div><div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Sales PIC (Account Owner)</div>
                        <div style="font-weight:600;">
                            @if($customer->salesPerson)
                                <i class="fa-solid fa-user-tie" style="color:var(--primary); margin-right:4px;"></i>
                                {{ $customer->salesPerson->name }} <span style="font-size:12px; color:var(--text-secondary); font-weight:normal;">({{ $customer->salesPerson->email }})</span>
                            @else
                                <span style="color:var(--text-secondary); font-style:italic; font-weight:normal;">Belum di-assign</span>
                            @endif
                        </div>
                    </div>
                    <div><div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Credit Limit</div>
                        <div style="font-weight:600;">{{ $customer->credit_limit ? 'Rp '.number_format($customer->credit_limit, 0, ',', '.') : 'Tidak ada limit' }}</div>
                    </div>
                </div>
                @if($customer->address)
                <div style="margin-top:16px; padding:12px; background:#f8fafc; border-radius:10px;">
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Alamat</div>
                    <div>{{ $customer->address }}</div>
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Statistik</h3></div>
            <div class="card-body">
                <div style="display:flex; flex-direction:column; gap:16px;">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary);">Total Penjualan</div>
                        <div style="font-size:20px; font-weight:700; color:var(--primary);">Rp {{ number_format($totalSales, 0, ',', '.') }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary);">Piutang Belum Lunas</div>
                        <div style="font-size:20px; font-weight:700; color:{{ $outstandingDebt > 0 ? 'var(--danger)' : 'var(--success)' }};">
                            Rp {{ number_format($outstandingDebt, 0, ',', '.') }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary);">Jumlah SO</div>
                        <div style="font-size:20px; font-weight:700;">{{ $customer->salesOrders->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($customer->salesOrders->count() > 0)
    <div class="card">
        <div class="card-header">
            <h3>Sales Order Terakhir</h3>
            <a href="{{ route('sales.orders.index') }}" class="btn btn-secondary btn-sm">Lihat Semua</a>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>No. SO</th>
                        <th>Tanggal</th>
                        <th style="text-align:right;">Total</th>
                        <th style="text-align:center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customer->salesOrders as $so)
                    <tr>
                        <td><a href="{{ route('sales.orders.show', $so) }}" style="color:var(--primary); font-weight:600; text-decoration:none;">{{ $so->so_number }}</a></td>
                        <td>{{ $so->order_date->format('d/m/Y') }}</td>
                        <td style="text-align:right; font-weight:600;">Rp {{ number_format($so->total_amount, 0, ',', '.') }}</td>
                        <td style="text-align:center;"><span class="badge badge-{{ $so->status }}">{{ ucfirst(str_replace('_',' ',$so->status)) }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
