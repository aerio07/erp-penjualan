@props([
    'name',
    'id' => null,
    'label' => null,
    'value' => null,
    'type' => 'decimal', // 'decimal' atau 'integer'
    'suffix' => null,
    'prefix' => null,
    'required' => false,
    'placeholder' => '0',
    'hint' => null,
    'disabled' => false,
    'readonly' => false,
    'classGroup' => 'form-group',
])

@php
    $inputId = $id ?? $name;
    $rawVal = old($name, $value);

    // Format nilai awal: jika decimal tampilkan dengan koma jika ada desimal
    if ($rawVal !== null && $rawVal !== '') {
        if ($type === 'integer') {
            $cleanedValue = round((float)$rawVal);
        } else {
            $floatVal = (float)$rawVal;
            // Jika integer bulat (misal 30.00), tampilkan 30, jika desimal (misal 1.25), tampilkan 1,25
            $cleanedValue = str_replace('.', ',', (string)$floatVal);
        }
    } else {
        $cleanedValue = '';
    }

    $maskClass = $type === 'integer' ? 'format-integer' : 'format-decimal';
    $inputMode = $type === 'integer' ? 'numeric' : 'decimal';
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
            inputmode="{{ $inputMode }}" 
            id="{{ $inputId }}" 
            name="{{ $name }}" 
            value="{{ $cleanedValue }}"
            placeholder="{{ $placeholder }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            {{ $attributes->merge([
                'class' => "form-control {$maskClass} " . ($errors->has($name) ? 'is-invalid' : ''),
                'style' => ($prefix ? 'padding-left:36px; ' : '') . ($suffix ? 'padding-right:48px; ' : '') . 'font-family:monospace; font-weight:600;'
            ]) }}
        >

        @if($suffix)
            <span style="position:absolute; right:12px; font-weight:600; color:var(--text-secondary, #64748b); font-size:12px; pointer-events:none; z-index:2;">
                {{ $suffix }}
            </span>
        @endif
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
