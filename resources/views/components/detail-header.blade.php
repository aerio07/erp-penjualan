@props([
    'title',
    'subtitle' => null,
    'status' => null,
    'statusLabel' => null,
    'backUrl' => null,
    'badgeText' => null,
    'badgeClass' => null,
])

<div class="page-header" style="margin-bottom: 16px; padding-bottom: 14px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; gap: 16px; width: 100%;">
    <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
        @if(isset($prefix))
            <div class="shrink-0">
                {{ $prefix }}
            </div>
        @endif

        <div style="min-width: 0;">
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <h1 style="font-size: 1.35rem; font-weight: 700; color: #0e1b35; margin: 0; line-height: 1.25; font-family: 'Public Sans', sans-serif;">{{ $title }}</h1>
                @if($status)
                    <x-status-badge :status="$status" :label="$statusLabel" />
                @elseif($badgeText)
                    <span class="badge {{ $badgeClass ?? 'badge-confirmed' }}" style="font-size: 11px; padding: 3px 8px;">
                        {{ $badgeText }}
                    </span>
                @elseif(isset($badge))
                    {{ $badge }}
                @endif
            </div>
            @if($subtitle)
                <p style="font-size: 0.8125rem; color: #64748b; margin: 3px 0 0 0; line-height: 1.4; font-family: 'Inter', sans-serif;">{!! $subtitle !!}</p>
            @endif
        </div>
    </div>

    @if(isset($actions) || isset($action) || (isset($slot) && !empty(trim($slot))) || $backUrl)
        <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px; flex-shrink: 0; margin-left: auto;">
            @if($backUrl)
                <a href="{{ $backUrl }}" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 600; padding: 7px 16px; border-radius: 8px; font-size: 13px; text-decoration: none; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; box-shadow: 0 1px 2px rgba(0,0,0,0.04); transition: all 0.15s ease;" title="Kembali ke halaman sebelumnya">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Kembali</span>
                </a>
            @endif
            {{ $actions ?? $action ?? $slot ?? '' }}
        </div>
    @endif
</div>
