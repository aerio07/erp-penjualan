@extends('layouts.app')
@section('title', $supplier->name)
@section('page-title', 'Detail Supplier')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>{{ $supplier->name }}</h1>
            <p>Kode: <strong style="color:var(--primary);">{{ $supplier->code }}</strong> &nbsp;·&nbsp;
                <span class="badge {{ $supplier->is_active ? 'badge-done' : 'badge-cancelled' }}">
                    {{ $supplier->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </p>
        </div>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <a href="{{ route('accounting.reports.ledger-payable', $supplier) }}" class="btn btn-secondary" style="color:var(--primary); border-color:var(--primary);" title="Lihat Buku Pembantu / Kartu Hutang">
                <i class="fa-solid fa-book"></i> Kartu Hutang
            </a>

            <form method="POST" action="{{ route('master.suppliers.toggle-status', $supplier) }}" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin {{ $supplier->is_active ? 'menonaktifkan' : 'mengaktifkan' }} supplier ini?');">
                @csrf
                @method('PATCH')
                @if($supplier->is_active)
                    <button type="submit" class="btn btn-secondary" style="color:#b91c1c; border-color:#fca5a5;" title="Nonaktifkan Supplier">
                        <i class="fa-solid fa-ban"></i> Nonaktifkan
                    </button>
                @else
                    <button type="submit" class="btn btn-primary" style="background:#16a34a; border-color:#16a34a;" title="Aktifkan Supplier">
                        <i class="fa-solid fa-check"></i> Aktifkan
                    </button>
                @endif
            </form>

            <a href="{{ route('master.suppliers.edit', $supplier) }}" class="btn btn-secondary">
                <i class="fa-solid fa-pen"></i> Edit
            </a>

            <button type="button" data-confirm-delete="del-sup-show" data-name="{{ $supplier->name }} ({{ $supplier->code }})" class="btn btn-danger" title="Hapus Supplier">
                <i class="fa-solid fa-trash"></i> Hapus
            </button>
            <form id="del-sup-show" method="POST" action="{{ route('master.suppliers.destroy', $supplier) }}" style="display:none;">
                @csrf
                @method('DELETE')
            </form>

            <a href="{{ route('master.suppliers.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-3" style="margin-bottom:24px;">
        {{-- Info Card --}}
        <div class="card" style="grid-column:span 2;">
            <div class="card-header"><h3>Informasi Supplier</h3></div>
            <div class="card-body">
                <div class="form-row form-row-3">
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
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Nomor NPWP</div>
                        <div style="font-weight:500;">{{ $supplier->npwp ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Nomor KTP / NIK</div>
                        <div style="font-weight:500; font-family:monospace;">{{ $supplier->ktp_number ?? '-' }}</div>
                    </div>
                </div>

                {{-- Bank Account Information --}}
                <div style="margin-top:16px; padding:14px 16px; background:#f0f4ff; border-radius:10px; border:1px solid #d8e2ff;">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span class="material-symbols-outlined text-[20px] text-primary">account_balance</span>
                            <strong style="font-size:13px; color:#0e1b35;">Informasi Rekening Bank</strong>
                        </div>
                        @if($supplier->bank_name || $supplier->bank_account_number)
                            <span class="badge badge-done" style="font-size:10px;">Tersedia</span>
                        @else
                            <span class="badge badge-neutral" style="font-size:10px;">Belum Diatur</span>
                        @endif
                    </div>

                    @if($supplier->bank_name || $supplier->bank_account_number)
                    <div class="form-row form-row-3 mb-0" style="gap:12px;">
                        <div>
                            <div style="font-size:11px; color:#6B7280; text-transform:uppercase; font-weight:700; margin-bottom:2px;">Nama Bank</div>
                            <div style="font-size:14px; font-weight:600; color:#0e1b35;">{{ $supplier->bank_name ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size:11px; color:#6B7280; text-transform:uppercase; font-weight:700; margin-bottom:2px;">Nomor Rekening</div>
                            <div style="font-size:14px; font-weight:700; font-family:monospace; color:#03193c; letter-spacing:0.5px;">
                                {{ $supplier->bank_account_number ?? '-' }}
                            </div>
                        </div>
                        <div>
                            <div style="font-size:11px; color:#6B7280; text-transform:uppercase; font-weight:700; margin-bottom:2px;">Nama Pemilik Rekening</div>
                            <div style="font-size:14px; font-weight:600; color:#0e1b35;">{{ $supplier->bank_account_holder ? 'a/n ' . $supplier->bank_account_holder : '-' }}</div>
                        </div>
                    </div>
                    @else
                    <div style="font-size:12.5px; color:#6B7280; font-style:italic;">
                        Belum ada data rekening bank yang disimpan untuk supplier ini.
                    </div>
                    @endif
                </div>

                @if($supplier->address)
                <div style="margin-top:12px; padding:12px 16px; background:#f8fafc; border-radius:10px; border:1px solid #E2E8F0;">
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Alamat Lengkap</div>
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
