@props([
    'name',
    'id' => null,
    'label' => null,
    'value' => null,
    'required' => false,
    'placeholder' => 'dd/mm/yyyy',
    'min' => null,
    'max' => null,
    'hint' => null,
    'disabled' => false,
    'readonly' => false,
    'classGroup' => 'form-group',
])

@php
    $inputId = $id ?? $name;
    // Format nilai default ke format Y-m-d untuk kompatibilitas form & flatpickr
    $rawVal = old($name, $value);
    if ($rawVal instanceof \DateTimeInterface) {
        $rawVal = $rawVal->format('Y-m-d');
    } elseif (is_string($rawVal) && !empty($rawVal)) {
        try {
            $rawVal = \Carbon\Carbon::parse($rawVal)->format('Y-m-d');
        } catch (\Throwable $e) {}
    }
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
        <input 
            type="date" 
            id="{{ $inputId }}" 
            name="{{ $name }}" 
            value="{{ $rawVal }}"
            lang="en-US"
            @if($min) min="{{ $min }}" @endif
            @if($max) max="{{ $max }}" @endif
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            {{ $attributes->merge([
                'class' => 'form-control ' . ($errors->has($name) ? 'is-invalid' : ''),
            ]) }}
        >
    </div>

    @if($hint)
        <span class="form-text" style="font-size:11px; color:var(--text-secondary); margin-top:3px; display:block;">
            {!! $hint !!}
        </span>
    @endif

    @error($name)
        <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
    @enderror
</div>
