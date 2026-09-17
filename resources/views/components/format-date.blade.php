@props([
    'value' => null,
    'time' => false,
    'placeholder' => '-',
])

@php
    $formatted = $time ? format_datetime($value, 'm/d/Y H:i') : format_date($value, 'm/d/Y');
@endphp

<span>{{ $formatted ?: $placeholder }}</span>
