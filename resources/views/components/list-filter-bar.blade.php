@props([
    'action' => request()->url(),
    'placeholder' => null,
    'searchPlaceholder' => null,
    'showDateFilter' => false,
    'dateFromParam' => 'date_from',
    'dateToParam' => 'date_to',
    'showSearch' => true,
    'showPerPage' => true,
])

@php
    $finalPlaceholder = $placeholder ?? $searchPlaceholder ?? 'Cari kata kunci...';
    $hasActiveFilters = request()->hasAny([
        'q', 'search', 'status', 'fulfillment_status', 'supplier_id', 'customer_id', 
        'warehouse_id', 'from_warehouse_id', 'to_warehouse_id', 'product_id', 'type', 
        'method', 'qc_status', 'condition_status', 'resolution_type', 'approvable_type', 
        'category', 'category_id', 'is_active', 'sales_person_id', 'tax_type', 'is_invoiced',
        'payment_status', 'billing_status', 'acquisition_type',
        $dateFromParam, $dateToParam
    ]);
@endphp

<div class="card erp-filter-card list-filter-card">
    <form method="GET" action="{{ $action }}" class="erp-filter-bar list-filter-form">
        {{-- Row 1: Search and Filter Dropdowns --}}
        <div class="list-filter-inputs-row">
            <!-- Search Input -->
            @if($showSearch)
            <div class="list-filter-search">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" name="q" value="{{ request('q', request('search', '')) }}" 
                       placeholder="{{ $finalPlaceholder }}" 
                       class="form-control filter-input-search">
            </div>
            @endif

            <!-- Custom Slot (Dropdowns, Selects, etc.) -->
            @if(trim($slot))
                {{ $slot }}
            @endif
        </div>

        {{-- Row 2: Secondary Controls (Date Range, Action Buttons, Per-page) --}}
        <div class="list-filter-controls-row">
            {{-- Left: Date Range & Action Buttons --}}
            <div class="list-filter-controls-left">
                <!-- Date Range Filter -->
                @if($showDateFilter)
                <div class="list-filter-dates">
                    <span class="date-label"><i class="fa-regular fa-calendar" style="margin-right: 4px;"></i> Periode:</span>
                    <div class="date-input-wrap">
                        <input type="date" name="{{ $dateFromParam }}" value="{{ request($dateFromParam) }}" 
                                class="form-control date-input" title="Dari Tanggal">
                    </div>
                    <span class="date-sep">s/d</span>
                    <div class="date-input-wrap">
                        <input type="date" name="{{ $dateToParam }}" value="{{ request($dateToParam) }}" 
                                class="form-control date-input" title="Sampai Tanggal">
                    </div>
                </div>
                @endif

                <!-- Submit Filter Button & Reset Button -->
                <div class="list-filter-actions">
                    <button type="submit" class="btn btn-primary filter-submit-btn">
                        <i class="fa-solid fa-filter"></i> <span>Filter</span>
                    </button>

                    @if($hasActiveFilters)
                    <a href="{{ $action }}" class="btn btn-secondary filter-reset-btn" title="Hapus Semua Filter">
                        <i class="fa-solid fa-rotate-left"></i> <span>Reset</span>
                    </a>
                    <span class="filter-active-badge">
                        <i class="fa-solid fa-circle-info"></i> Aktif
                    </span>
                    @endif
                </div>
            </div>

            {{-- Right: Per Page Selector & Hidden Sorts --}}
            @if($showPerPage)
            <div class="list-filter-perpage">
                @if(request('sort_by'))
                    <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                @endif
                @if(request('sort_dir'))
                    <input type="hidden" name="sort_dir" value="{{ request('sort_dir') }}">
                @endif

                <span class="perpage-label">Tampil:</span>
                <select name="per_page" onchange="this.form.submit()" class="form-control perpage-select">
                    <option value="10" {{ request('per_page', 20) == 10 ? 'selected' : '' }}>10</option>
                    <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20</option>
                    <option value="50" {{ request('per_page', 20) == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page', 20) == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>
            @endif
        </div>
    </form>
</div>

<style>
/* ==========================================================================
   RESPONSIVE FILTER BAR COMPONENT STYLES
   ========================================================================== */
.list-filter-card {
    margin-bottom: 16px !important;
    padding: 14px 18px !important;
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 10px !important;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04) !important;
    box-sizing: border-box !important;
    width: 100% !important;
    max-width: 100% !important;
}

.list-filter-form {
    display: flex !important;
    flex-direction: column !important;
    gap: 12px !important;
    width: 100% !important;
    box-sizing: border-box !important;
}

/* Row 1: Search & Filter Dropdowns */
.list-filter-inputs-row {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 10px !important;
    align-items: center !important;
    width: 100% !important;
    box-sizing: border-box !important;
}

.list-filter-search {
    position: relative !important;
    flex: 1 1 240px !important;
    min-width: 200px !important;
    box-sizing: border-box !important;
}

.list-filter-search .search-icon {
    position: absolute !important;
    left: 12px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    color: #94a3b8 !important;
    font-size: 13px !important;
    pointer-events: none !important;
    z-index: 2 !important;
}

.list-filter-search .filter-input-search {
    padding-left: 36px !important;
    height: 38px !important;
    font-size: 13px !important;
    border-radius: 8px !important;
    width: 100% !important;
    margin-bottom: 0 !important;
    background: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    box-sizing: border-box !important;
}

/* All Dropdowns in Row 1: Flex equally to fill available space nicely */
.list-filter-inputs-row select {
    height: 38px !important;
    font-size: 13px !important;
    border-radius: 8px !important;
    background-color: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    padding: 0 10px !important;
    margin-bottom: 0 !important;
    box-sizing: border-box !important;
    flex: 1 1 150px !important;
    min-width: 140px !important;
    cursor: pointer !important;
}

/* Row 2: Secondary Controls (Date, Buttons, Per Page) */
.list-filter-controls-row {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 10px !important;
    align-items: center !important;
    justify-content: space-between !important;
    width: 100% !important;
    box-sizing: border-box !important;
    padding-top: 4px !important;
}

.list-filter-controls-left {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 10px !important;
    align-items: center !important;
    flex: 1 1 auto !important;
    min-width: 0 !important;
}

/* Date Range Pill */
.list-filter-dates {
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    flex: 0 1 auto !important;
    min-width: 0 !important;
    box-sizing: border-box !important;
    background: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    padding: 3px 8px !important;
    border-radius: 8px !important;
}

.list-filter-dates .date-label {
    font-size: 12px !important;
    font-weight: 600 !important;
    color: #475569 !important;
    white-space: nowrap !important;
    display: inline-flex !important;
    align-items: center !important;
}

.list-filter-dates .date-input-wrap {
    flex: 0 1 auto !important;
    min-width: 0 !important;
}

.list-filter-dates .date-input {
    height: 32px !important;
    font-size: 12.5px !important;
    width: 130px !important;
    border-radius: 6px !important;
    margin-bottom: 0 !important;
    background: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    box-sizing: border-box !important;
    padding: 0 8px !important;
}

.list-filter-dates .date-sep {
    color: #94a3b8 !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    flex-shrink: 0 !important;
}

/* Action Buttons */
.list-filter-actions {
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
    flex: 0 0 auto !important;
}

.list-filter-actions .filter-submit-btn {
    height: 38px !important;
    padding: 0 16px !important;
    font-size: 13px !important;
    border-radius: 8px !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    white-space: nowrap !important;
}

.list-filter-actions .filter-reset-btn {
    height: 38px !important;
    padding: 0 12px !important;
    font-size: 13px !important;
    border-radius: 8px !important;
    color: #dc2626 !important;
    border-color: #fecaca !important;
    background: #fef2f2 !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    white-space: nowrap !important;
}

.list-filter-actions .filter-active-badge {
    font-size: 11.5px !important;
    font-weight: 600 !important;
    color: #1e40af !important;
    background: #eff6ff !important;
    border: 1px solid #bfdbfe !important;
    padding: 3px 8px !important;
    border-radius: 6px !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 4px !important;
    white-space: nowrap !important;
}

/* Per Page Selector */
.list-filter-perpage {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    flex: 0 0 auto !important;
    margin-left: auto !important;
}

.list-filter-perpage .perpage-label {
    font-size: 12px !important;
    font-weight: 600 !important;
    color: #64748b !important;
    white-space: nowrap !important;
    margin-bottom: 0 !important;
    text-transform: none !important;
    letter-spacing: normal !important;
}

.list-filter-perpage .perpage-select {
    height: 38px !important;
    width: 75px !important;
    min-width: 70px !important;
    font-size: 13px !important;
    border-radius: 8px !important;
    padding: 0 8px !important;
    margin-bottom: 0 !important;
    background: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    box-sizing: border-box !important;
}

/* Mobile Screens (<= 768px) */
@media (max-width: 768px) {
    .list-filter-card {
        padding: 12px 14px !important;
    }
    .list-filter-inputs-row {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 10px !important;
    }
    .list-filter-search {
        width: 100% !important;
        min-width: 100% !important;
    }
    .list-filter-inputs-row select {
        width: 100% !important;
        min-width: 100% !important;
    }
    .list-filter-controls-row {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 12px !important;
    }
    .list-filter-controls-left {
        flex-direction: column !important;
        align-items: stretch !important;
        width: 100% !important;
        gap: 10px !important;
    }
    .list-filter-dates {
        width: 100% !important;
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
        padding: 6px 8px !important;
    }
    .list-filter-dates .date-input-wrap {
        flex: 1 1 50% !important;
        min-width: 0 !important;
    }
    .list-filter-dates .date-input {
        width: 100% !important;
        min-width: 0 !important;
    }
    .list-filter-actions {
        width: 100% !important;
        display: flex !important;
        gap: 8px !important;
    }
    .list-filter-actions .filter-submit-btn {
        flex: 1 1 auto !important;
        justify-content: center !important;
    }
    .list-filter-actions .filter-reset-btn {
        flex: 0 0 auto !important;
        justify-content: center !important;
    }
    .list-filter-perpage {
        width: 100% !important;
        margin-left: 0 !important;
        padding-top: 10px !important;
        border-top: 1px dashed #e2e8f0 !important;
        justify-content: space-between !important;
    }
    .list-filter-perpage .perpage-select {
        flex: 0 0 85px !important;
    }
}
</style>

