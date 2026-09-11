@props([
    'cancelUrl' => null,
    'cancelRoute' => null,
    'backUrl' => null,
    'cancelLabel' => null,
    'showCancel' => false,
    'submitLabel' => 'Simpan',
    'submitIcon' => 'fa-solid fa-floppy-disk',
    'submitAttrs' => '',
])

@php
    $resolvedCancelUrl = $cancelRoute ?? $cancelUrl ?? $backUrl;
    $label = $cancelLabel ?? ($backUrl && !$cancelUrl && !$cancelRoute ? 'Kembali' : 'Batal');
    $icon = str_contains(strtolower($label), 'kembali') ? 'fa-solid fa-arrow-left' : 'fa-solid fa-xmark';
@endphp

<div {{ $attributes->merge(['class' => 'form-actions flex flex-wrap items-center justify-end gap-3 pt-5 mt-6 border-t border-slate-200']) }}>
    {{-- Slot untuk tombol aksi tambahan di sebelah kiri (misal Reset, Draft, dll) --}}
    @if(isset($slot) && !empty(trim($slot)))
        <div class="flex items-center gap-2 mr-auto">
            {{ $slot }}
        </div>
    @endif

    {{-- Tombol Batal / Cancel di samping simpan telah dihapus (cukup pertahankan tombol Kembali di header atas) --}}
    @if($showCancel)
        @if($resolvedCancelUrl)
            <a href="{{ $resolvedCancelUrl }}" class="btn btn-secondary inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-lg text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition-colors shadow-xs">
                <i class="{{ $icon }}"></i>
                <span>{{ $label }}</span>
            </a>
        @else
            <button type="button" onclick="window.history.back()" class="btn btn-secondary inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-lg text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition-colors shadow-xs">
                <i class="{{ $icon }}"></i>
                <span>{{ $label }}</span>
            </button>
        @endif
    @endif

    {{-- Tombol Simpan / Aksi Utama: SELALU di PALING KANAN --}}
    @if(isset($submitButton))
        {{ $submitButton }}
    @else
        <button type="submit" {!! $submitAttrs !!} class="btn btn-primary inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold rounded-lg text-white shadow-xs">
            @if($submitIcon)
                <i class="{{ $submitIcon }}"></i>
            @endif
            <span>{!! $submitLabel !!}</span>
        </button>
    @endif
</div>
