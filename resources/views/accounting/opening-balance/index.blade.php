@extends('layouts.app')
@section('title', 'Saldo Awal Neraca')
@section('page-title', 'Saldo Awal Neraca')

@section('content')
<div class="animate-in flex flex-col gap-6" x-data="openingBalanceForm()">
    {{-- Header Page --}}
    <div class="page-header flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Saldo Awal Neraca</h1>
            <p class="text-xs text-slate-500">Konfigurasi cut-off saldo akun Aset, Kewajiban, dan Ekuitas saat mulai pembukuan ERP</p>
        </div>
        <div>
            @if($existingEntry)
                <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Jurnal Terposting: <strong class="font-mono text-emerald-900">#{{ $existingEntry->entry_number }}</strong> ({{ $existingEntry->entry_date->format('m/d/Y') }})</span>
                </div>
            @else
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-medium">
                    <i class="fa-solid fa-clock text-amber-500"></i>
                    <span>Belum ada jurnal saldo awal</span>
                </div>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm flex items-center gap-3 shadow-xs">
        <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
            <i class="fa-solid fa-circle-check text-base"></i>
        </div>
        <div>
            <div class="font-bold">Berhasil Disimpan!</div>
            <div class="text-xs text-emerald-700 mt-0.5">{{ session('success') }}</div>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-sm flex items-center gap-3 shadow-xs">
        <div class="w-8 h-8 rounded-lg bg-rose-100 flex items-center justify-center text-rose-600 shrink-0">
            <i class="fa-solid fa-triangle-exclamation text-base"></i>
        </div>
        <div>
            <div class="font-bold">Periksa Kembali Saldo Anda</div>
            <div class="text-xs text-rose-700 mt-0.5">{{ session('error') }}</div>
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('accounting.opening-balance.store') }}" @submit="validateForm($event)">
        @csrf

        {{-- ======================================================== --}}
        {{-- TOP SUMMARY & ACTION CONTROL CARD (In-Flow, Zero Overlap) --}}
        {{-- ======================================================== --}}
        <div class="card p-5 border-2 rounded-2xl shadow-sm transition-all"
             :class="isBalanced ? 'border-emerald-300 bg-gradient-to-r from-emerald-50/50 via-white to-emerald-50/30' : 'border-slate-200 bg-white'">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                
                {{-- Metric Chips --}}
                <div class="flex flex-wrap items-center gap-3 sm:gap-4">
                    {{-- Total Debit --}}
                    <div class="flex items-center gap-3 bg-blue-50 border border-blue-200/80 rounded-xl px-4 py-2.5">
                        <div class="w-9 h-9 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-xs">
                            <i class="fa-solid fa-arrow-down"></i>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase tracking-wider font-extrabold text-blue-700">Total Debit</div>
                            <div class="text-base sm:text-lg font-mono font-black text-blue-950" x-text="formatRupiah(totalDebit)"></div>
                        </div>
                    </div>

                    {{-- Total Kredit --}}
                    <div class="flex items-center gap-3 bg-purple-50 border border-purple-200/80 rounded-xl px-4 py-2.5">
                        <div class="w-9 h-9 rounded-lg bg-purple-600 text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-xs">
                            <i class="fa-solid fa-arrow-up"></i>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase tracking-wider font-extrabold text-purple-700">Total Kredit</div>
                            <div class="text-base sm:text-lg font-mono font-black text-purple-950" x-text="formatRupiah(totalCredit)"></div>
                        </div>
                    </div>

                    {{-- Dynamic Balance Pill --}}
                    <div>
                        <template x-if="isBalanced">
                            <div class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-emerald-100 border border-emerald-300 text-emerald-950 shadow-xs">
                                <span class="relative flex h-3 w-3">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-600"></span>
                                </span>
                                <div>
                                    <div class="text-xs font-black tracking-wide uppercase text-emerald-800">SEIMBANG (BALANCED)</div>
                                    <div class="text-[11px] font-semibold text-emerald-700">Total Debit = Kredit</div>
                                </div>
                            </div>
                        </template>

                        <template x-if="!isBalanced">
                            <div class="flex items-center gap-2.5 px-4 py-2 rounded-xl bg-rose-50 border border-rose-300 text-rose-950 shadow-xs">
                                <div class="w-8 h-8 rounded-lg bg-rose-600 text-white flex items-center justify-center text-xs shrink-0">
                                    <i class="fa-solid fa-scale-unbalanced"></i>
                                </div>
                                <div>
                                    <div class="text-[11px] font-black tracking-wide uppercase text-rose-800">BELUM SEIMBANG</div>
                                    <div class="text-xs font-mono font-extrabold text-rose-700">
                                        Selisih: <span x-text="formatRupiah(diff)"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Action Buttons (Top) --}}
                <div class="flex flex-wrap items-center gap-2.5">
                    {{-- Auto-Balance Button (Secondary Style) --}}
                    <button type="button"
                            @click="autoBalanceToModal()"
                            class="btn btn-secondary text-amber-700 hover:text-amber-800 border-amber-300 hover:border-amber-400 bg-amber-50 hover:bg-amber-100"
                            title="Otomatis isi selisih ke akun modal agar neraca seimbang">
                        <i class="fa-solid fa-wand-magic-sparkles text-amber-600"></i>
                        <span>Auto-Balance ke Modal</span>
                    </button>

                    {{-- Simpan & Posting Button (Standard Primary Style) --}}
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Simpan & Posting Saldo Awal</span>
                    </button>
                </div>

            </div>
        </div>

        {{-- Parameter Header Card --}}
        <div class="card p-5 border border-slate-200 rounded-2xl shadow-xs bg-white">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-start">
                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        <i class="fa-regular fa-calendar text-blue-600 mr-1"></i> Tanggal Cut-Off Pembukuan <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="cut_off_date" x-model="cutOffDate" class="form-control text-sm font-semibold" style="height: 40px !important; border-radius: 8px;" required>
                    <p class="text-[11px] text-slate-400 mt-1.5 leading-normal">Tanggal penetapan posisi saldo awal neraca.</p>
                </div>

                <div class="md:col-span-5">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        <i class="fa-regular fa-pen-to-square text-blue-600 mr-1"></i> Keterangan Dokumen Jurnal
                    </label>
                    <input type="text" name="notes" x-model="notes" placeholder="Contoh: Saldo Awal Neraca Awal Tahun 2026" class="form-control text-sm" style="height: 40px !important; border-radius: 8px;">
                    <p class="text-[11px] text-slate-400 mt-1.5 leading-normal">Akan tercatat sebagai deskripsi pada bukti jurnal pembuka.</p>
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        <i class="fa-solid fa-magnifying-glass text-blue-600 mr-1"></i> Cari Akun Cepat
                    </label>
                    <div style="position: relative; width: 100%;">
                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; pointer-events: none; z-index: 2;"></i>
                        <input type="text" x-model="searchQuery" placeholder="Ketik kode / nama akun..." class="form-control" style="padding-left: 38px !important; height: 40px !important; font-size: 13px; border-radius: 8px;">
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1.5 leading-normal">Filter instan baris akun pada tabel.</p>
                </div>
            </div>

            {{-- Quick Filter Category Pills --}}
            <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider mr-1">Filter Tipe:</span>
                    <button type="button" @click="selectedCategory = 'all'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                            :class="selectedCategory === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                        Semua Akun ({{ $accounts->count() }})
                    </button>
                    <button type="button" @click="selectedCategory = 'asset'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                            :class="selectedCategory === 'asset' ? 'bg-blue-600 text-white shadow-xs' : 'bg-blue-50 text-blue-700 hover:bg-blue-100'">
                        1. Aset ({{ $accounts->where('type', 'asset')->count() }})
                    </button>
                    <button type="button" @click="selectedCategory = 'liability'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                            :class="selectedCategory === 'liability' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 text-amber-700 hover:bg-amber-100'">
                        2. Kewajiban ({{ $accounts->where('type', 'liability')->count() }})
                    </button>
                    <button type="button" @click="selectedCategory = 'equity'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                            :class="selectedCategory === 'equity' ? 'bg-purple-600 text-white shadow-xs' : 'bg-purple-50 text-purple-700 hover:bg-purple-100'">
                        3. Ekuitas ({{ $accounts->where('type', 'equity')->count() }})
                    </button>
                </div>

                <div class="text-xs text-slate-500">
                    Saldo normal: <span class="text-blue-600 font-bold">Debit</span> (Aset) &bull; <span class="text-purple-600 font-bold">Kredit</span> (Kewajiban & Ekuitas).
                </div>
            </div>
        </div>

        {{-- Tabel Input Akun --}}
        <div class="card overflow-hidden border border-slate-200 rounded-2xl shadow-xs bg-white">
            <div class="table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th style="width: 130px;" class="py-3 px-4 text-xs font-bold text-slate-700 uppercase tracking-wider">Kode</th>
                            <th class="py-3 px-4 text-xs font-bold text-slate-700 uppercase tracking-wider">Nama Akun & Kategori</th>
                            <th style="width: 140px;" class="py-3 px-4 text-xs font-bold text-slate-700 uppercase tracking-wider">Saldo Normal</th>
                            <th style="width: 240px; text-align:right;" class="py-3 px-4 text-xs font-bold text-blue-700 uppercase tracking-wider">
                                Debit (Rp)
                            </th>
                            <th style="width: 240px; text-align:right;" class="py-3 px-4 text-xs font-bold text-purple-700 uppercase tracking-wider">
                                Kredit (Rp)
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $currentGroup = '';
                        @endphp

                        @foreach($accounts as $acc)
                            @php
                                $groupTitle = match($acc->type) {
                                    'asset' => 'KELOMPOK 1: ASET (ASSETS)',
                                    'liability' => 'KELOMPOK 2: KEWAJIBAN (LIABILITIES)',
                                    'equity' => 'KELOMPOK 3: EKUITAS & MODAL (EQUITY)',
                                    default => strtoupper($acc->type)
                                };

                                $lineDebit = old("lines.{$loop->index}.debit", $existingLines[$acc->id]['debit'] ?? 0);
                                $lineCredit = old("lines.{$loop->index}.credit", $existingLines[$acc->id]['credit'] ?? 0);
                                $isOwnerCapital = ($acc->code === '3-1100');
                            @endphp

                            @if($currentGroup !== $groupTitle)
                                @php $currentGroup = $groupTitle; @endphp
                                <tr class="bg-slate-100/80 border-t-2 border-slate-200"
                                    x-show="(selectedCategory === 'all' || selectedCategory === '{{ $acc->type }}') && !searchQuery">
                                    <td colspan="5" class="py-2.5 px-4 font-extrabold text-xs tracking-wider text-slate-800 uppercase">
                                        <div class="flex items-center justify-between">
                                            <span>
                                                @if($acc->type === 'asset')
                                                    <i class="fa-solid fa-building-columns text-blue-600 mr-1.5"></i>
                                                @elseif($acc->type === 'liability')
                                                    <i class="fa-solid fa-hand-holding-dollar text-amber-600 mr-1.5"></i>
                                                @else
                                                    <i class="fa-solid fa-piggy-bank text-purple-600 mr-1.5"></i>
                                                @endif
                                                {{ $groupTitle }}
                                            </span>
                                            <span class="text-[11px] font-normal text-slate-500 lowercase">
                                                normal balance: {{ $acc->normal_balance }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @endif

                            <tr id="account-row-{{ $acc->code }}"
                                class="transition-colors hover:bg-slate-50/70 border-b border-slate-100"
                                x-show="(selectedCategory === 'all' || selectedCategory === '{{ $acc->type }}') && ('{{ strtolower($acc->code . ' ' . $acc->name) }}'.includes(searchQuery.toLowerCase()))">
                                <td class="py-3 px-4 font-mono font-bold text-slate-800 text-xs">
                                    {{ $acc->code }}
                                    <input type="hidden" name="lines[{{ $loop->index }}][account_id]" value="{{ $acc->id }}">
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-slate-900 text-sm flex items-center gap-2">
                                        {{ $acc->name }}
                                        @if($isOwnerCapital)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                <i class="fa-solid fa-star text-[9px] mr-1 text-emerald-600"></i> Akun Penyeimbang
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        {{ ucfirst($acc->type) }} &bull; Saldo normal: <strong class="uppercase text-slate-600">{{ $acc->normal_balance }}</strong>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold {{ $acc->normal_balance === 'debit' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-purple-50 text-purple-700 border border-purple-200' }}">
                                        {{ strtoupper($acc->normal_balance) }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-right">
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 text-xs font-bold text-slate-400 select-none">Rp</span>
                                        <input type="number"
                                               name="lines[{{ $loop->index }}][debit]"
                                               value="{{ $lineDebit }}"
                                               step="1"
                                               min="0"
                                               @input="calculateTotals()"
                                               placeholder="0"
                                               class="form-control form-control-sm text-right font-mono text-sm font-semibold pl-8 rounded-lg h-9 border-slate-300 input-debit {{ $isOwnerCapital ? 'input-modal-debit' : '' }}">
                                    </div>
                                </td>
                                <td class="py-2.5 px-4 text-right">
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 text-xs font-bold text-slate-400 select-none">Rp</span>
                                        <input type="number"
                                               name="lines[{{ $loop->index }}][credit]"
                                               value="{{ $lineCredit }}"
                                               step="1"
                                               min="0"
                                               @input="calculateTotals()"
                                               placeholder="0"
                                               class="form-control form-control-sm text-right font-mono text-sm font-semibold pl-8 rounded-lg h-9 border-slate-300 input-credit {{ $isOwnerCapital ? 'input-modal-credit' : '' }}">
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Bottom Footer Card (In-flow, directly under the table) --}}
            <div class="p-5 bg-slate-50 border-t border-slate-200 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-4">
                    <div>
                        <span class="text-xs text-slate-500 font-bold uppercase">Total Debit:</span>
                        <span class="text-base font-mono font-black text-blue-900 ml-1.5" x-text="formatRupiah(totalDebit)"></span>
                    </div>
                    <div class="text-slate-300 font-light text-xl">|</div>
                    <div>
                        <span class="text-xs text-slate-500 font-bold uppercase">Total Kredit:</span>
                        <span class="text-base font-mono font-black text-purple-900 ml-1.5" x-text="formatRupiah(totalCredit)"></span>
                    </div>
                    <div class="text-slate-300 font-light text-xl">|</div>
                    <div>
                        <template x-if="isBalanced">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-100 text-emerald-800 text-xs font-extrabold border border-emerald-300">
                                <i class="fa-solid fa-circle-check text-emerald-600"></i> SEIMBANG
                            </span>
                        </template>
                        <template x-if="!isBalanced">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-rose-100 text-rose-800 text-xs font-extrabold border border-rose-300">
                                <i class="fa-solid fa-triangle-exclamation text-rose-600"></i> SELISIH: <span x-text="formatRupiah(diff)"></span>
                            </span>
                        </template>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <button type="button"
                            @click="autoBalanceToModal()"
                            class="btn btn-secondary text-amber-700 hover:text-amber-800 border-amber-300 hover:border-amber-400 bg-amber-50 hover:bg-amber-100">
                        <i class="fa-solid fa-wand-magic-sparkles text-amber-600"></i>
                        <span>Auto-Balance ke Modal</span>
                    </button>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Simpan & Posting Saldo Awal</span>
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script>
function openingBalanceForm() {
    return {
        cutOffDate: '{{ $cutOffDate }}',
        notes: @json($notes ?? ''),
        searchQuery: '',
        selectedCategory: 'all',
        totalDebit: 0,
        totalCredit: 0,

        get diff() {
            return Math.abs(this.totalDebit - this.totalCredit);
        },

        get isBalanced() {
            return this.diff < 0.01 && (this.totalDebit > 0 || this.totalCredit > 0);
        },

        formatRupiah(num) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(num || 0);
        },

        calculateTotals() {
            let debits = 0;
            let credits = 0;
            document.querySelectorAll('.input-debit').forEach(input => {
                debits += parseFloat(input.value) || 0;
            });
            document.querySelectorAll('.input-credit').forEach(input => {
                credits += parseFloat(input.value) || 0;
            });
            this.totalDebit = Math.round(debits * 100) / 100;
            this.totalCredit = Math.round(credits * 100) / 100;
        },

        autoBalanceToModal() {
            let modalCreditInput = document.querySelector('.input-modal-credit');
            let modalDebitInput = document.querySelector('.input-modal-debit');
            if (!modalCreditInput || !modalDebitInput) return;

            modalCreditInput.value = 0;
            modalDebitInput.value = 0;
            this.calculateTotals();

            if (this.totalDebit > this.totalCredit) {
                let needed = this.totalDebit - this.totalCredit;
                modalCreditInput.value = needed;
                modalDebitInput.value = 0;
            } else if (this.totalCredit > this.totalDebit) {
                let needed = this.totalCredit - this.totalDebit;
                modalDebitInput.value = needed;
                modalCreditInput.value = 0;
            }
            this.calculateTotals();

            let modalRow = document.getElementById('account-row-3-1100');
            if (modalRow) {
                modalRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                modalRow.classList.add('bg-emerald-50', 'ring-2', 'ring-emerald-400');
                setTimeout(() => {
                    modalRow.classList.remove('bg-emerald-50', 'ring-2', 'ring-emerald-400');
                }, 2500);
            }
        },

        validateForm(e) {
            if (this.totalDebit <= 0 && this.totalCredit <= 0) {
                alert('Saldo awal belum diisi atau total debit dan kredit masih 0. Masukkan saldo debit atau kredit pada akun terkait.');
                e.preventDefault();
                return false;
            }
            if (!this.isBalanced) {
                alert('Saldo awal tidak balance atau total debit dan kredit belum sama. Selisih: ' + this.formatRupiah(this.diff) + '. Pastikan total debit sama dengan kredit.');
                e.preventDefault();
                return false;
            }
            return true;
        },

        init() {
            this.calculateTotals();
        }
    };
}
</script>
@endpush
