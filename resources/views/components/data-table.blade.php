@props([
    'head' => null,
    'paginator' => null,
    'showFooter' => true,
    'tableClass' => 'erp-table',
    'responsiveClass' => '',
])

<div class="card data-table-card" style="padding: 0; overflow: hidden; margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
    <div class="table-responsive {{ $responsiveClass }}" style="border: none; border-radius: 0; box-shadow: none; margin-bottom: 0;">
        <table class="{{ $tableClass }}" style="margin-bottom: 0;">
            @if(isset($head) && !empty(trim($head)))
                <thead>
                    {{ $head }}
                </thead>
            @endif
            <tbody>
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @if($showFooter && $paginator && ($paginator->hasPages() || (method_exists($paginator, 'total') && $paginator->total() > 0)))
    <div class="table-footer" style="padding: 14px 20px; border-top: 1px solid #e2e8f0; background: #ffffff; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div class="text-secondary text-sm" style="font-size: 13px; color: var(--text-secondary);">
            Menampilkan <strong>{{ $paginator->firstItem() ?? 0 }}</strong>–<strong>{{ $paginator->lastItem() ?? 0 }}</strong>
            dari <strong>{{ $paginator->total() }}</strong> data
        </div>
        <div>
            {{ $paginator->links() }}
        </div>
    </div>
    @endif
</div>
