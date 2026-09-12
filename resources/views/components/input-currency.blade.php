@props([
    'name',
    'id' => null,
    'label' => null,
    'value' => null,
    'required' => false,
    'placeholder' => '0',
    'hint' => null,
    'disabled' => false,
    'readonly' => false,
    'prefix' => 'Rp',
    'classGroup' => 'form-group',
])

@php
    $inputId = $id ?? $name;
    // Bersihkan nilai: jika bernilai desimal/float string dari DB, bulatkan jadi integer murni
    $cleanedValue = old($name, ($value !== null && $value !== '') ? round((float)$value) : '');
@endphp

<div class="{{ $classGroup }}">
    @if($label)
        <label for="{{ $inputId }}" class="form-label">
            {{ $label }}
            @if($required)
                <span style="color:var(--danger);">*</span>
            @endif
        </label>
    @endif

    <div style="position:relative; display:flex; align-items:center;">
        @if($prefix)
            <span style="position:absolute; left:12px; font-weight:700; color:var(--text-secondary, #64748b); font-size:13px; pointer-events:none; z-index:2;">
                {{ $prefix }}
            </span>
        @endif

        <input 
            type="text" 
            inputmode="numeric" 
            id="{{ $inputId }}" 
            name="{{ $name }}" 
            value="{{ $cleanedValue }}"
            placeholder="{{ $placeholder }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            {{ $attributes->merge([
                'class' => 'form-control format-currency ' . ($errors->has($name) ? 'is-invalid' : ''),
                'style' => ($prefix ? 'padding-left:36px; font-family:monospace; font-weight:600;' : '')
            ]) }}
        >
    </div>

    @if($hint)
        <span class="form-text" style="font-size:11px; color:var(--text-secondary); margin-top:3px; display:block;">
            {!! $hint !!}
        </span>
    @endif

    @error($name)
        <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
            <span>{{ $message }}</span>
        </div>
    @enderror
</div>
