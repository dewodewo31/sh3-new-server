{{-- Modern pagination.
    Usage:
    @include('includes.pagination', ['items' => $events, 'label' => 'events'])
--}}
@if (isset($items) && $items->hasPages())
    <div class="pagination">
        <p class="pagination-info">
            Menampilkan <span class="font-medium text-slate-700 dark:text-slate-200">{{ $items->firstItem() }}</span>
            - <span class="font-medium text-slate-700 dark:text-slate-200">{{ $items->lastItem() }}</span>
            dari <span class="font-medium text-slate-700 dark:text-slate-200">{{ $items->total() }}</span>
            {{ $label ?? 'data' }}
        </p>
        <div class="pagination-links">
            {{ $items->appends(request()->query())->links() }}
        </div>
    </div>
@endif
