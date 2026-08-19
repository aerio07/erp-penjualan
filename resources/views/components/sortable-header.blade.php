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
@endphp

<th style="text-align:{{ $align }}; user-select:none;">
    <a href="{{ $sortUrl }}" style="text-decoration:none; color:inherit; display:inline-flex; align-items:center; gap:6px; font-weight:600; cursor:pointer;" title="Urutkan berdasarkan {{ $title }}">
        <span>{{ $title }}</span>
        @if($isSorted)
            @if($currentDir === 'asc')
                <i class="fa-solid fa-sort-up" style="color:var(--primary, #3b82f6); font-size:12px;"></i>
            @else
                <i class="fa-solid fa-sort-down" style="color:var(--primary, #3b82f6); font-size:12px;"></i>
            @endif
        @else
            <i class="fa-solid fa-sort" style="color:var(--text-secondary, #9ca3af); font-size:11px; opacity:0.4;"></i>
        @endif
    </a>
</th>
