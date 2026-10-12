@extends('layouts.app')
@section('title', 'Detail Debet Note - ' . $debitNote->dn_number)
@section('page-title', 'Detail Debet Note')

@section('content')
<div class="animate-in">
    <x-detail-header
        :title="$debitNote->dn_number"
        :subtitle="'Debet Note (' . $debitNote->dn_type . ') • ' . $debitNote->partner_name"
        :status="$debitNote->status"
        :back-url="route('accounting.debit-notes.index')"
    >
        <x-slot:actions>
            @if($debitNote->isPosted())
            <a href="{{ route('accounting.debit-notes.print', $debitNote) }}" target="_blank" class="btn btn-secondary">
                <i class="fa-solid fa-print"></i> Cetak Bukti
            </a>
            @endif

            @if($debitNote->isDraft())
            <form method="POST" action="{{ route('accounting.debit-notes.post', $debitNote) }}" style="display:inline;" onsubmit="return confirm('Posting Debet Note ini ke jurnal akuntansi?')">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-check"></i> Posting Sekarang
                </button>
            </form>
            @endif

            @if($debitNote->isPosted())
            <form method="POST" action="{{ route('accounting.debit-notes.cancel', $debitNote) }}" style="display:inline;" onsubmit="return confirm('Batalkan Debet Note ini? Jurnal terkait akan dihapus dan saldo faktur dikembalikan.')">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-danger">
                    <i class="fa-solid fa-ban"></i> Batalkan
                </button>
            </form>
            @endif
        </x-slot:actions>
    </x-detail-header>

    @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:20px; display:flex; align-items:center; gap:10px;">
        <i class="fa-solid fa-circle-check" style="font-size:18px;"></i>
        <div>{{ session('success') }}</div>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger" style="margin-bottom:20px; display:flex; align-items:center; gap:10px;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size:18px;"></i>
        <div>{{ session('error') }}</div>
    </div>
    @endif

    {{-- Ringkasan Informasi --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h3>Informasi Debet Note</h3>
            <span class="badge badge-{{ $debitNote->status === 'posted' ? 'done' : ($debitNote->status === 'cancelled' ? 'danger' : 'secondary') }}">
                {{ ucfirst($debitNote->status) }}
            </span>
        </div>
        <div class="card-body">
            <div class="form-row form-row-2" style="gap:24px;">
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">No. Debet Note</div>
                        <div style="font-family:monospace; font-weight:700; font-size:15px; color:var(--primary);">{{ $debitNote->dn_number }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">No. Draft</div>
                        <div style="font-family:monospace; font-weight:600;">#{{ $debitNote->draft_number ?: $debitNote->id }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Tipe Debet Note</div>
                        <span class="badge badge-{{ $debitNote->dn_type === 'AP' ? 'warning' : 'primary' }}" style="font-size:12px;">
                            {{ $debitNote->dn_type }} ({{ $debitNote->dn_type === 'AP' ? 'Potong Hutang Vendor' : 'Potong Piutang Customer' }})
                        </span>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">{{ $debitNote->dn_type === 'AP' ? 'Supplier' : 'Customer' }}</div>
                        <div style="font-weight:700; font-size:14px; color:var(--text-primary);">{{ $debitNote->partner_name }}</div>
                    </div>
                </div>

                <div style="display:flex; flex-direction:column; gap:10px;">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Tanggal Transaksi</div>
                        <div style="font-weight:600;">{{ $debitNote->dn_date ? $debitNote->dn_date->format('d F Y') : '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Total Potongan (DN)</div>
                        <div style="font-size:18px; font-weight:800; color:var(--primary);">
                            Rp {{ number_format($debitNote->total_amount, 0, ',', '.') }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Pengaturan Pajak</div>
                        <div style="font-weight:600;">{{ $debitNote->tax_type }} ({{ $debitNote->tax_rate }}%) &bull; Rp {{ number_format($debitNote->tax_amount, 0, ',', '.') }}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Keterangan</div>
                        <div style="color:var(--text-primary);">{{ $debitNote->notes ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detail Faktur yang Dipotong --}}
    <div class="card" style="margin-bottom:20px; padding:0; overflow:hidden;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; padding:16px 20px; background:#f8fafc; border-bottom:1px solid #e2e8f0; margin:0;">
            <h3 style="font-size:15px; font-weight:700; margin:0; color:#0e1b35;">Faktur yang Dipotong (Invoices)</h3>
            <span style="font-size:12px; color:var(--text-secondary); font-weight:600;">Total: {{ $debitNote->invoices->count() }} Faktur</span>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-responsive" style="margin-bottom:0; border:none; border-radius:0;">
                <table class="erp-table" style="width:100%; min-width:900px; margin:0; border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="width:50px; text-align:center; white-space:nowrap;">No</th>
                            <th style="min-width:140px; white-space:nowrap;">No. Invoice</th>
                            <th style="min-width:130px; white-space:nowrap;">No. Surat Jalan / Reff</th>
                            <th style="min-width:100px; text-align:center; white-space:nowrap;">Tanggal Inv</th>
                            <th style="min-width:130px; text-align:right; white-space:nowrap;">Sisa Tagihan Awal</th>
                            <th style="min-width:120px; text-align:right; white-space:nowrap;">Dibayar</th>
                            <th style="min-width:140px; text-align:right; font-weight:700; color:var(--primary); white-space:nowrap;">Potongan DN</th>
                            <th style="min-width:130px; text-align:right; white-space:nowrap;">Sisa Tagihan Akhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($debitNote->invoices as $idx => $inv)
                        <tr>
                            <td style="text-align:center; color:var(--text-secondary);">{{ $idx + 1 }}</td>
                            <td style="font-family:monospace; font-weight:700; color:var(--primary);">{{ $inv->invoice_number }}</td>
                            <td style="color:var(--text-secondary);">{{ $inv->delivery_order_number ?: '-' }}</td>
                            <td style="text-align:center;">{{ $inv->invoice_date ? $inv->invoice_date->format('d/m/Y') : '-' }}</td>
                            <td style="text-align:right;">Rp {{ number_format($inv->remaining_amount, 0, ',', '.') }}</td>
                            <td style="text-align:right; color:var(--text-secondary);">Rp {{ number_format($inv->paid_amount, 0, ',', '.') }}</td>
                            <td style="text-align:right; font-weight:700; color:var(--primary); background:#f8fafc;">
                                Rp {{ number_format($inv->dn_amount, 0, ',', '.') }}
                            </td>
                            <td style="text-align:right; font-weight:700;">Rp {{ number_format($inv->balance_amount, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Sub Detail Akun (COA) --}}
    <div class="card" style="margin-bottom:20px; padding:0; overflow:hidden;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; padding:16px 20px; background:#f8fafc; border-bottom:1px solid #e2e8f0; margin:0;">
            <h3 style="font-size:15px; font-weight:700; margin:0; color:#0e1b35;">Alokasi Akun Pembukuan (COA)</h3>
            <span style="font-size:12px; color:var(--text-secondary); font-weight:600;">Total: {{ $debitNote->accounts->count() }} Baris</span>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-responsive" style="margin-bottom:0; border:none; border-radius:0;">
                <table class="erp-table" style="width:100%; min-width:980px; margin:0; border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="width:50px; text-align:center; white-space:nowrap;">No</th>
                            <th style="min-width:130px; white-space:nowrap;">No. Invoice</th>
                            <th style="min-width:200px; white-space:nowrap;">Akun Debet</th>
                            <th style="min-width:200px; white-space:nowrap;">Akun Kredit</th>
                            <th style="min-width:130px; text-align:right; white-space:nowrap;">Outstanding</th>
                            <th style="min-width:140px; text-align:right; font-weight:700; white-space:nowrap;">Nominal DN</th>
                            <th style="min-width:120px; text-align:right; white-space:nowrap;">Pajak</th>
                            <th style="min-width:160px; white-space:nowrap;">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($debitNote->accounts as $idx => $acc)
                        <tr>
                            <td style="text-align:center; color:var(--text-secondary);">{{ $idx + 1 }}</td>
                            <td style="font-family:monospace; font-weight:600;">{{ $acc->invoice_number ?: '-' }}</td>
                            <td style="font-weight:600;">
                                [{{ $acc->debitAccount->code ?? '-' }}] {{ $acc->debitAccount->name ?? '-' }}
                            </td>
                            <td style="font-weight:600;">
                                [{{ $acc->creditAccount->code ?? '-' }}] {{ $acc->creditAccount->name ?? '-' }}
                            </td>
                            <td style="text-align:right; color:var(--text-secondary);">Rp {{ number_format($acc->outstanding_amount, 0, ',', '.') }}</td>
                            <td style="text-align:right; font-weight:700;">Rp {{ number_format($acc->dn_amount, 0, ',', '.') }}</td>
                            <td style="text-align:right;">Rp {{ number_format($acc->tax_amount, 0, ',', '.') }}</td>
                            <td>{{ $acc->notes ?: '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
