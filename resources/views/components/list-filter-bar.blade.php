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

@php
    $hasActiveFilters = request()->hasAny([
        'q', 'search', 'status', 'fulfillment_status', 'supplier_id', 'customer_id', 
        'warehouse_id', 'from_warehouse_id', 'to_warehouse_id', 'product_id', 'type', 
        'method', 'qc_status', 'condition_status', 'resolution_type', 'approvable_type', 
        'category', 'category_id', 'is_active', 'sales_person_id', 'tax_type', 'is_invoiced',
        $dateFromParam, $dateToParam
    ]);
@endphp

<div class="card erp-filter-card" style="margin-bottom: 16px; padding: 12px 18px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);">
    <form method="GET" action="{{ $action }}" class="erp-filter-bar list-filter-form" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; width: 100%;">
        {{-- Left: Filter inputs and controls --}}
        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; flex: 1 1 auto; min-width: 0;">
            
            <!-- Search Input -->
            <div style="position: relative; flex: 1 1 220px; min-width: 180px; max-width: 300px;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; pointer-events: none; z-index: 2;"></i>
                <input type="text" name="q" value="{{ request('q', request('search', '')) }}" placeholder="{{ $finalPlaceholder }}" 
                       class="form-control" 
                       style="padding-left: 36px; height: 38px; font-size: 13px; border-radius: 8px; width: 100%; margin-bottom: 0; background: #ffffff; border: 1px solid #cbd5e1;">
            </div>

            <!-- Custom Slot (Dropdowns, Selects, etc.) -->
            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                {{ $slot }}
            </div>

            <!-- Date Range Filter -->
            @if($showDateFilter)
            <div style="display: inline-flex; align-items: center; gap: 6px; flex: 0 0 auto;">
                <input type="date" name="{{ $dateFromParam }}" value="{{ request($dateFromParam) }}" 
                       class="form-control" style="height: 38px; font-size: 13px; width: 135px; border-radius: 8px; margin-bottom: 0; background: #ffffff; border: 1px solid #cbd5e1;" title="Dari Tanggal">
                <span style="color: #94a3b8; font-size: 12px; font-weight: 500;">s/d</span>
                <input type="date" name="{{ $dateToParam }}" value="{{ request($dateToParam) }}" 
                       class="form-control" style="height: 38px; font-size: 13px; width: 135px; border-radius: 8px; margin-bottom: 0; background: #ffffff; border: 1px solid #cbd5e1;" title="Sampai Tanggal">
            </div>
            @endif

            <!-- Submit Filter Button & Reset Button -->
            <div style="display: inline-flex; align-items: center; gap: 8px; flex: 0 0 auto;">
                <button type="submit" class="btn btn-primary" style="height: 38px; padding: 0 16px; font-size: 13px; border-radius: 8px;">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>

                @if($hasActiveFilters)
                <a href="{{ $action }}" class="btn btn-secondary" style="height: 38px; padding: 0 12px; font-size: 13px; border-radius: 8px; color: #dc2626; border-color: #fecaca; background: #fef2f2;" title="Hapus Semua Filter">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </a>
                <span style="font-size: 11.5px; font-weight: 600; color: #1e40af; background: #eff6ff; border: 1px solid #bfdbfe; padding: 3px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;">
                    <i class="fa-solid fa-circle-info" style="font-size: 11px;"></i> Filter Aktif
                </span>
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

            <span style="font-size: 12px; font-weight: 600; color: #64748b; white-space: nowrap;">Tampil:</span>
            <select name="per_page" onchange="this.form.submit()" class="form-control" style="height: 38px; width: 75px; font-size: 13px; border-radius: 8px; padding: 0 8px; margin-bottom: 0; background: #ffffff; border: 1px solid #cbd5e1;">
                <option value="10" {{ request('per_page', 20) == 10 ? 'selected' : '' }}>10</option>
                <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20</option>
                <option value="50" {{ request('per_page', 20) == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ request('per_page', 20) == 100 ? 'selected' : '' }}>100</option>
            </select>
        </div>
    </form>
</div>

