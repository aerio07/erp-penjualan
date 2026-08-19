@props([
    'action' => request()->url(),
    'placeholder' => 'Cari kata kunci...',
    'showDateFilter' => false,
    'dateFromParam' => 'date_from',
    'dateToParam' => 'date_to',
])

<form method="GET" action="{{ $action }}" class="filter-bar-container" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between; margin-bottom:16px; background:var(--card-bg, #fff); padding:16px; border-radius:10px; border:1px solid var(--border, #e5e7eb);">
    <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; flex:1; min-width:280px;">
        {{-- Search Keyword Input --}}
        <div style="position:relative; min-width:220px; flex:1;">
            <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary, #9ca3af); font-size:14px;"></i>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ $placeholder }}" class="form-control" style="padding-left:36px; height:38px; font-size:14px; width:100%; border-radius:6px; border:1px solid var(--border, #d1d5db);">
        </div>

        {{-- Custom Filter Slots --}}
        {{ $slot }}

        {{-- Date Range Filters if Enabled --}}
        @if($showDateFilter)
        <div style="display:flex; align-items:center; gap:6px;">
            <input type="date" name="{{ $dateFromParam }}" value="{{ request($dateFromParam) }}" class="form-control" style="height:38px; font-size:13px; border-radius:6px;" title="Dari Tanggal">
            <span style="color:var(--text-secondary, #6b7280); font-size:12px;">s/d</span>
            <input type="date" name="{{ $dateToParam }}" value="{{ request($dateToParam) }}" class="form-control" style="height:38px; font-size:13px; border-radius:6px;" title="Sampai Tanggal">
        </div>
        @endif

        {{-- Submit Filter Button --}}
        <button type="submit" class="btn btn-primary" style="height:38px; padding:0 16px; font-size:13px; display:inline-flex; align-items:center; gap:6px;">
            <i class="fa-solid fa-filter"></i> Filter
        </button>

        {{-- Reset Filter Link --}}
        @if(request()->hasAny(['q', 'sort_by', 'sort_dir', 'per_page', 'date_from', 'date_to', 'status', 'supplier_id', 'customer_id', 'warehouse_id', 'from_warehouse_id', 'to_warehouse_id', 'product_id', 'type', 'method', 'qc_status', 'condition_status', 'resolution_type', 'approvable_type', 'category']))
        <a href="{{ $action }}" class="btn btn-secondary" style="height:38px; padding:0 14px; font-size:13px; display:inline-flex; align-items:center; gap:6px; background:var(--bg-hover, #f3f4f6); color:var(--text-main, #374151); border:1px solid var(--border, #d1d5db); text-decoration:none; border-radius:6px;" title="Reset Filter">
            <i class="fa-solid fa-rotate-right"></i> Reset
        </a>
        @endif
    </div>

    {{-- Per Page Select & Preserve Sort --}}
    <div style="display:flex; align-items:center; gap:8px;">
        @if(request('sort_by'))
            <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
        @endif
        @if(request('sort_dir'))
            <input type="hidden" name="sort_dir" value="{{ request('sort_dir') }}">
        @endif

        <span style="font-size:13px; color:var(--text-secondary, #6b7280); font-weight:500;">Tampil:</span>
        <select name="per_page" onchange="this.form.submit()" class="form-control" style="height:38px; width:75px; font-size:13px; padding:0 8px; border-radius:6px;">
            <option value="10" {{ request('per_page', 20) == 10 ? 'selected' : '' }}>10</option>
            <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20</option>
            <option value="50" {{ request('per_page', 20) == 50 ? 'selected' : '' }}>50</option>
            <option value="100" {{ request('per_page', 20) == 100 ? 'selected' : '' }}>100</option>
        </select>
    </div>
</form>
