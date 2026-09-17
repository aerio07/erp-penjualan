@props([
    'value' => null,
    'time' => false,
    'placeholder' => '-',
])

@php
    $formatted = $time ? format_datetime($value, 'd/m/Y H:i') : format_date($value, 'd/m/Y');
@endphp

<span>{{ $formatted ?: $placeholder }}</span>
