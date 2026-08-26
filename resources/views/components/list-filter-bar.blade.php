@props([
    'action' => request()->url(),
    'placeholder' => null,
    'searchPlaceholder' => null,
    'showDateFilter' => false,
    'dateFromParam' => 'date_from',
    'dateToParam' => 'date_to',
])

@php
    $finalPlaceholder = $placeholder ?? $searchPlaceholder ?? 'Cari kata kunci...';
@endphp

<form method="GET" action="{{ $action }}" class="bg-paper rounded-t-lg shadow-sm p-unit-md flex flex-col md:flex-row gap-unit-md items-start md:items-center justify-between border-b border-border-light mb-unit-md">
    <div class="flex flex-wrap gap-unit-md items-center w-full md:w-auto flex-1">
        
        <!-- Search Input -->
        <div class="relative w-full md:w-64 flex items-center">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px] pointer-events-none select-none z-10">search</span>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ $finalPlaceholder }}" 
                   class="search-input w-full h-[38px] rounded bg-surface border border-border-medium text-body-sm font-body-base text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all !pl-10 pr-3"
                   style="padding-left: 2.5rem !important;">
        </div>

        <!-- Slot dropdowns custom -->
        {{ $slot }}

        <!-- Date Range Filter -->
        @if($showDateFilter)
        <div class="flex items-center gap-2">
            <input type="date" name="{{ $dateFromParam }}" value="{{ request($dateFromParam) }}" 
                   class="h-[38px] px-3 rounded bg-surface border border-border-medium text-body-sm font-body-base text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" title="Dari Tanggal">
            <span class="text-on-surface-variant text-xs">-</span>
            <input type="date" name="{{ $dateToParam }}" value="{{ request($dateToParam) }}" 
                   class="h-[38px] px-3 rounded bg-surface border border-border-medium text-body-sm font-body-base text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" title="Sampai Tanggal">
        </div>
        @endif

        <!-- Submit Filter Button -->
        <button type="submit" class="h-[38px] px-4 rounded bg-primary text-on-primary font-body-medium hover:bg-primary-container transition-colors inline-flex items-center gap-1.5 text-sm shadow-sm">
            <span class="material-symbols-outlined text-[18px]">filter_list</span> Filter
        </button>

        <!-- Reset Button -->
        @if(request()->hasAny(['q', 'sort_by', 'sort_dir', 'per_page', 'date_from', 'date_to', 'status', 'supplier_id', 'customer_id', 'warehouse_id', 'from_warehouse_id', 'to_warehouse_id', 'product_id', 'type', 'method', 'qc_status', 'condition_status', 'resolution_type', 'approvable_type', 'category']))
        <a href="{{ $action }}" class="h-[38px] px-3 rounded bg-surface border border-border-medium text-on-surface hover:bg-surface-variant transition-colors inline-flex items-center gap-1.5 text-sm font-body-medium">
            <span class="material-symbols-outlined text-[18px]">refresh</span> Reset
        </a>
        @endif
    </div>

    <!-- Per Page Selector & Hidden Sort -->
    <div class="flex items-center gap-2 mt-2 md:mt-0">
        @if(request('sort_by'))
            <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
        @endif
        @if(request('sort_dir'))
            <input type="hidden" name="sort_dir" value="{{ request('sort_dir') }}">
        @endif

        <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Tampil:</span>
        <select name="per_page" onchange="this.form.submit()" class="h-[38px] px-2 rounded bg-surface border border-border-medium text-body-sm font-body-base text-on-surface focus:outline-none">
            <option value="10" {{ request('per_page', 20) == 10 ? 'selected' : '' }}>10</option>
            <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20</option>
            <option value="50" {{ request('per_page', 20) == 50 ? 'selected' : '' }}>50</option>
            <option value="100" {{ request('per_page', 20) == 100 ? 'selected' : '' }}>100</option>
        </select>
    </div>
</form>
