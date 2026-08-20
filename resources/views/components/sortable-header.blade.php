@props([
    'column',
    'title',
    'align' => 'left',
])

@php
    $currentSort = request('sort_by');
    $currentDir = strtolower(request('sort_dir', 'desc'));
    $isSorted = ($currentSort === $column);
    $nextDir = ($isSorted && $currentDir === 'asc') ? 'desc' : 'asc';
    
    $queryParams = array_merge(request()->query(), [
        'sort_by' => $column,
        'sort_dir' => $nextDir,
    ]);
    $sortUrl = request()->url() . '?' . http_build_query($queryParams);

    $alignClass = match($align) {
        'right' => 'text-right justify-end',
        'center' => 'text-center justify-center',
        default => 'text-left justify-start'
    };
@endphp

<th class="py-3 px-4 font-label-xs text-label-xs text-on-surface uppercase tracking-wider whitespace-nowrap {{ match($align) { 'right' => 'text-right', 'center' => 'text-center', default => 'text-left' } }}">
    <a href="{{ $sortUrl }}" class="inline-flex items-center gap-1 hover:text-primary transition-colors select-none font-bold {{ $alignClass }}" title="Urutkan berdasarkan {{ $title }}">
        <span>{{ $title }}</span>
        @if($isSorted)
            @if($currentDir === 'asc')
                <span class="material-symbols-outlined text-primary text-[16px]">arrow_upward</span>
            @else
                <span class="material-symbols-outlined text-primary text-[16px]">arrow_downward</span>
            @endif
        @else
            <span class="material-symbols-outlined text-on-surface-variant/40 text-[16px]">unfold_more</span>
        @endif
    </a>
</th>
