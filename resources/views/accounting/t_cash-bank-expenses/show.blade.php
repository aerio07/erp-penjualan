@extends('layouts.app')
@section('title', 'Detail Cash Bank Expense - ' . $cashBankExpense->expense_number)
@section('page-title', 'Detail Cash Bank Expense')

@section('content')
<div class="animate-in">
    <x-detail-header
        :title="$cashBankExpense->expense_number"
        :subtitle="'Cash Bank Expense • ' . $cashBankExpense->company_name"
        :status="$cashBankExpense->status"
        :back-url="route('accounting.cash-bank-expenses.index')"
    >
        <x-slot:actions>
            @if($cashBankExpense->isDraft())
            <a href="{{ route('accounting.cash-bank-expenses.edit', $cashBankExpense) }}" class="btn btn-secondary">
                <i class="fa-solid fa-pen-to-square"></i> Edit
            </a>
            <form method="POST" action="{{ route('accounting.cash-bank-expenses.post', $cashBankExpense) }}" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin memposting Cash Bank Expense {{ $cashBankExpense->expense_number }}?')">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Posting Sekarang</button>
            </form>
            <form method="POST" action="{{ route('accounting.cash-bank-expenses.destroy', $cashBankExpense) }}" style="display:inline;" onsubmit="return confirm('Hapus dokumen draft ini?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Hapus</button>
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

    @if($cashBankExpense->isDraft())
    <x-draft-banner 
        title="Pengeluaran Masih Berstatus DRAFT"
        message="Dokumen ini belum diposting. Periksa kembali rekening kas/bank, akun lawan, dan nominal sebelum memposting."
        :action-url="route('accounting.cash-bank-expenses.post', $cashBankExpense)"
        action-method="PATCH"
        action-label="Posting Sekarang"
        confirm-text="Apakah Anda yakin ingin memposting Cash Bank Expense {{ $cashBankExpense->expense_number }}?"
        :edit-url="route('accounting.cash-bank-expenses.edit', $cashBankExpense)"
        :delete-url="route('accounting.cash-bank-expenses.destroy', $cashBankExpense)"
        delete-confirm="Hapus dokumen draft ini?"
    />
    @endif

    <div class="grid grid-3" style="margin-bottom:20px;">
        <div class="card" style="grid-column:span 2;">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h3>Informasi Pengeluaran</h3>
                <span class="badge badge-{{ $cashBankExpense->status === 'posted' ? 'done' : 'secondary' }}">{{ ucfirst($cashBankExpense->status) }}</span>
            </div>
            <div class="card-body">
                <div class="form-row form-row-2" style="gap:20px;">
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Company</div>
                            <div style="font-weight:600;">{{ $cashBankExpense->company_name }}</div>
                        </div>
                        <div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">No. Expense</div>
                            <div style="font-weight:700; font-family:monospace; color:var(--primary);">{{ $cashBankExpense->expense_number }}</div>
                        </div>
                        <div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">No. Kas/Bank/Giro</div>
                            <div style="font-family:monospace;">{{ $cashBankExpense->voucher_number ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Type</div>
                            <span class="badge badge-{{ $cashBankExpense->type === 'KAS' ? 'confirmed' : ($cashBankExpense->type === 'BANK' ? 'primary' : 'warning') }}">{{ $cashBankExpense->type }}</span>
                        </div>
                        <div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Notes</div>
                            <div>{{ $cashBankExpense->notes ?? '-' }}</div>
                        </div>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Tanggal</div>
                            <div style="font-weight:600;">{{ $cashBankExpense->transaction_date ? $cashBankExpense->transaction_date->format('d/m/Y') : '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Group</div>
                            <div>{{ $cashBankExpense->group ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Account</div>
                            <div style="font-weight:600;">[{{ $cashBankExpense->account->code ?? '' }}] {{ $cashBankExpense->account->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">No. Reference</div>
                            <div>{{ $cashBankExpense->reference_number ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:2px;">Dibuat Oleh</div>
                            <div>{{ $cashBankExpense->creator->name ?? '-' }}</div>
                        </div>
                    </div>
                </div>
                @if($cashBankExpense->document_file)
                <div style="margin-top:16px; padding:10px 14px; background:#eff6ff; border-radius:8px; font-size:13px;">
                    <i class="fa-solid fa-file" style="color:var(--primary);"></i>
                    <a href="{{ Storage::url($cashBankExpense->document_file) }}" target="_blank" style="color:var(--primary); font-weight:600; text-decoration:none;"> Lihat Dokumen Upload</a>
                </div>
                @endif
                @if($cashBankExpense->isPosted() && $cashBankExpense->poster)
                <div style="margin-top:16px; padding:10px 14px; background:#ecfdf5; border-radius:8px; font-size:13px; color:#065f46; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-circle-check" style="color:#059669;"></i>
                    <span>Diposting oleh <strong>{{ $cashBankExpense->poster->name }}</strong> pada {{ $cashBankExpense->posted_at?->format('d/m/Y H:i') }}</span>
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Ringkasan</h3></div>
            <div class="card-body">
                <div style="display:flex; flex-direction:column; gap:16px;">
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary);">Total Pengeluaran</div>
                        <div style="font-size:26px; font-weight:800; color:var(--danger);">
                            Rp {{ number_format($cashBankExpense->amount, 0, ',', '.') }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-secondary);">Jumlah Item</div>
                        <div style="font-size:18px; font-weight:700;">{{ $cashBankExpense->items->count() }} baris</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Items Table --}}
    <div class="card">
        <div class="card-header"><h3>Detail Pengeluaran</h3></div>
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
                    @forelse($cashBankExpense->items as $i => $item)
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
                        <td style="text-align:right; font-weight:700; color:var(--danger);">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                        <td style="color:var(--text-secondary);">{{ $item->notes ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align:center; padding:32px; color:var(--text-secondary);">Tidak ada detail item</td>
                    </tr>
                    @endforelse
                </tbody>
                @if($cashBankExpense->items->count() > 0)
                <tfoot>
                    <tr style="font-weight:800; background:#fef2f2;">
                        <td colspan="3" style="text-align:right; padding:10px 14px;">TOTAL</td>
                        <td style="text-align:right; color:var(--danger); font-size:15px;">Rp {{ number_format($cashBankExpense->items->sum('amount'), 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
