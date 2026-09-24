@extends('layouts.app')
@section('title', 'Detail Cash Bank Receipt - ' . $cashBankReceipt->receipt_number)
@section('page-title', 'Detail Cash Bank Receipt')

@section('content')
<div class="animate-in">
    <x-detail-header
        :title="$cashBankReceipt->receipt_number"
        :subtitle="'Cash Bank Receipt • ' . $cashBankReceipt->company_name"
        :status="$cashBankReceipt->status"
        :back-url="route('accounting.cash-bank-receipts.index')"
    >
        <x-slot:actions>
            @if($cashBankReceipt->isDraft())
            <a href="{{ route('accounting.cash-bank-receipts.edit', $cashBankReceipt) }}" class="btn btn-warning">
                <i class="fa-solid fa-pen-to-square"></i> Edit
            </a>
            <form method="POST" action="{{ route('accounting.cash-bank-receipts.post', $cashBankReceipt) }}" style="display:inline;" onsubmit="return confirm('Posting dokumen ini?')">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-success"><i class="fa-solid fa-check"></i> Posting</button>
            </form>
            <form method="POST" action="{{ route('accounting.cash-bank-receipts.destroy', $cashBankReceipt) }}" style="display:inline;" onsubmit="return confirm('Hapus dokumen ini?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Hapus</button>
            </form>
            @endif
        </x-slot:actions>
    </x-detail-header>

    @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:20px;"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
    @endif

    <div class="grid grid-3" style="margin-bottom:20px;">
        <div class="card" style="grid-column:span 2;">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h3>Informasi Penerimaan</h3>
                <span class="badge badge-{{ $cashBankReceipt->status === 'posted' ? 'done' : 'secondary' }}">{{ ucfirst($cashBankReceipt->status) }}</span>
            </div>
            <div class="card-body">
                <div class="form-row form-row-2" style="gap:20px;">
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Company</div>
                            <div style="font-weight:600;">{{ $cashBankReceipt->company_name }}</div>
                        </div>
                        <div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">No. Receipt</div>
                            <div style="font-weight:700; font-family:monospace; color:var(--primary);">{{ $cashBankReceipt->receipt_number }}</div>
                        </div>
                        <div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">No. Kas/Bank/Giro</div>
                            <div style="font-family:monospace;">{{ $cashBankReceipt->voucher_number ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Type</div>
                            <span class="badge badge-{{ $cashBankReceipt->type === 'KAS' ? 'confirmed' : ($cashBankReceipt->type === 'BANK' ? 'primary' : 'warning') }}">{{ $cashBankReceipt->type }}</span>
                        </div>
                        <div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Notes</div>
                            <div>{{ $cashBankReceipt->notes ?? '-' }}</div>
                        </div>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Tanggal</div>
                            <div style="font-weight:600;">{{ $cashBankReceipt->transaction_date ? $cashBankReceipt->transaction_date->format('d/m/Y') : '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Group</div>
                            <div>{{ $cashBankReceipt->group ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Account</div>
                            <div style="font-weight:600;">[{{ $cashBankReceipt->account->code ?? '' }}] {{ $cashBankReceipt->account->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">No. Reference</div>
                            <div>{{ $cashBankReceipt->reference_number ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Dibuat Oleh</div>
                            <div>{{ $cashBankReceipt->creator->name ?? '-' }}</div>
                        </div>
                    </div>
                </div>
                @if($cashBankReceipt->document_file)
                <div style="margin-top:16px; padding:10px 14px; background:#eff6ff; border-radius:8px; font-size:13px;">
                    <i class="fa-solid fa-file" style="color:var(--primary);"></i>
                    <a href="{{ Storage::url($cashBankReceipt->document_file) }}" target="_blank" style="color:var(--primary); font-weight:600; text-decoration:none;">Lihat Dokumen Upload</a>
                </div>
                @endif
                @if($cashBankReceipt->isPosted() && $cashBankReceipt->poster)
                <div style="margin-top:16px; padding:10px 14px; background:#ecfdf5; border-radius:8px; font-size:13px; color:#065f46; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-circle-check" style="color:#059669;"></i>
                    <span>Diposting oleh <strong>{{ $cashBankReceipt->poster->name }}</strong> pada {{ $cashBankReceipt->posted_at?->format('d/m/Y H:i') }}</span>
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Ringkasan</h3></div>
            <div class="card-body">
                <div style="display:flex; flex-direction:column; gap:16px;">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary);">Total Penerimaan</div>
                        <div style="font-size:26px; font-weight:800; color:var(--success);">
                            Rp {{ number_format($cashBankReceipt->amount, 0, ',', '.') }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary);">Jumlah Item</div>
                        <div style="font-size:18px; font-weight:700;">{{ $cashBankReceipt->items->count() }} baris</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Items Table --}}
    <div class="card">
        <div class="card-header"><h3>Detail Penerimaan</h3></div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th style="width:50px;">No</th>
                        <th>Kode Akun</th>
                        <th>Deskripsi</th>
                        <th style="text-align:right;">Jumlah</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cashBankReceipt->items as $i => $item)
                    <tr>
                        <td style="text-align:center; color:var(--text-secondary);">{{ $i + 1 }}</td>
                        <td>
                            @if($item->account)
                            <div style="font-weight:600; font-family:monospace;">{{ $item->account->code }}</div>
                            <div style="font-size:12px; color:var(--text-secondary);">{{ $item->account->name }}</div>
                            @else
                            <span style="color:var(--text-secondary);">-</span>
                            @endif
                        </td>
                        <td>{{ $item->description ?? '-' }}</td>
                        <td style="text-align:right; font-weight:700; color:var(--success);">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                        <td style="color:var(--text-secondary);">{{ $item->notes ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align:center; padding:32px; color:var(--text-secondary);">Tidak ada detail item</td>
                    </tr>
                    @endforelse
                </tbody>
                @if($cashBankReceipt->items->count() > 0)
                <tfoot>
                    <tr style="font-weight:800; background:#f0fdf4;">
                        <td colspan="3" style="text-align:right; padding:10px 14px;">TOTAL</td>
                        <td style="text-align:right; color:var(--success); font-size:15px;">Rp {{ number_format($cashBankReceipt->items->sum('amount'), 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
