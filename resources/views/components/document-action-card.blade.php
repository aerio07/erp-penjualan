@props([
    'title' => null,
    'message' => null,
    'icon' => null,
    'iconBg' => '#f1f5f9',
    'iconColor' => '#475569',
    'status' => null,
])

@if($title || $message)
<div {{ $attributes->merge(['class' => 'card document-action-card']) }} style="margin-top: 24px; padding: 16px 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: 16px; min-width: 260px; flex: 1;">
        @if($icon)
        <div style="width: 42px; height: 42px; border-radius: 10px; background: {{ $iconBg }}; color: {{ $iconColor }}; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;">
            <i class="{{ $icon }}"></i>
        </div>
        @endif
        <div style="min-width: 0;">
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <h4 style="margin: 0; font-size: 14.5px; font-weight: 700; color: #1e293b; font-family: 'Public Sans', sans-serif;">{{ $title }}</h4>
                @if($status)
                    <x-status-badge :status="$status" />
                @endif
            </div>
            @if($message)
                <p style="margin: 3px 0 0 0; font-size: 13px; color: #64748b; line-height: 1.4;">{{ $message }}</p>
            @endif
        </div>
    </div>

    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; flex-shrink: 0; margin-left: auto;">
        {{ $slot }}
    </div>
</div>
@else
<div {{ $attributes->merge(['class' => 'document-action-bar']) }} style="margin-top: 24px; margin-bottom: 24px; display: flex; align-items: center; justify-content: flex-end; gap: 12px; flex-wrap: wrap;">
    {{ $slot }}
</div>
@endif
