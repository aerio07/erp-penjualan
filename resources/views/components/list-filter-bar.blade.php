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

<div class="card" style="margin-bottom: 20px; padding: 16px 20px; background: #ffffff; border: 1px solid var(--border); border-radius: 8px;">
    <form method="GET" action="{{ $action }}" class="erp-filter-bar list-filter-form" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; width: 100%;">
        {{-- Left: Filter inputs and controls --}}
        <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; flex: 1 1 auto; min-width: 0;">
            
            <!-- Search Input -->
            <div style="position: relative; flex: 1 1 220px; min-width: 180px; max-width: 320px;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); font-size: 13px; pointer-events: none; z-index: 2;"></i>
                <input type="text" name="q" value="{{ request('q', request('search', '')) }}" placeholder="{{ $finalPlaceholder }}" 
                       class="form-control" 
                       style="padding-left: 36px; height: 38px; font-size: 13px; border-radius: 6px; width: 100%; margin-bottom: 0;">
            </div>

            <!-- Custom Slot (Dropdowns, Selects, etc.) -->
            <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
                {{ $slot }}
            </div>

            <!-- Date Range Filter -->
            @if($showDateFilter)
            <div style="display: inline-flex; align-items: center; gap: 6px; flex: 0 0 auto;">
                <input type="date" name="{{ $dateFromParam }}" value="{{ request($dateFromParam) }}" 
                       class="form-control" style="height: 38px; font-size: 13px; width: 135px; border-radius: 6px; margin-bottom: 0;" title="Dari Tanggal">
                <span style="color: var(--text-secondary); font-size: 13px;">-</span>
                <input type="date" name="{{ $dateToParam }}" value="{{ request($dateToParam) }}" 
                       class="form-control" style="height: 38px; font-size: 13px; width: 135px; border-radius: 6px; margin-bottom: 0;" title="Sampai Tanggal">
            </div>
            @endif

            <!-- Submit Filter Button & Reset Button -->
            <div style="display: inline-flex; align-items: center; gap: 8px; flex: 0 0 auto;">
                <button type="submit" class="btn btn-primary" style="height: 38px; padding: 0 16px; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; border-radius: 6px;">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>

                @if(request()->hasAny(['q', 'search', 'sort_by', 'sort_dir', 'per_page', 'date_from', 'date_to', 'status', 'fulfillment_status', 'supplier_id', 'customer_id', 'warehouse_id', 'from_warehouse_id', 'to_warehouse_id', 'product_id', 'type', 'method', 'qc_status', 'condition_status', 'resolution_type', 'approvable_type', 'category', 'category_id', 'is_active', 'sales_person_id', 'tax_type']))
                <a href="{{ $action }}" class="btn btn-secondary" style="height: 38px; padding: 0 14px; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; border-radius: 6px;" title="Reset Semua Filter">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </a>
                @endif
            </div>
        </div>

        {{-- Right: Per Page Selector & Hidden Sorts --}}
        <div style="display: flex; align-items: center; gap: 8px; flex: 0 0 auto; margin-left: auto;">
            @if(request('sort_by'))
                <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
            @endif
            @if(request('sort_dir'))
                <input type="hidden" name="sort_dir" value="{{ request('sort_dir') }}">
            @endif

            <span style="font-size: 12px; font-weight: 600; color: var(--text-secondary); white-space: nowrap;">Tampil:</span>
            <select name="per_page" onchange="this.form.submit()" class="form-control" style="height: 38px; width: 75px; font-size: 13px; border-radius: 6px; padding: 0 8px; margin-bottom: 0;">
                <option value="10" {{ request('per_page', 20) == 10 ? 'selected' : '' }}>10</option>
                <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20</option>
                <option value="50" {{ request('per_page', 20) == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ request('per_page', 20) == 100 ? 'selected' : '' }}>100</option>
            </select>
        </div>
    </form>
</div>

