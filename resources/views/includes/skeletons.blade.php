{{-- Loading skeleton components.

    Table skeleton:
    @include('includes.skeletons', ['variant' => 'table', 'rows' => 5])

    Card grid skeleton:
    @include('includes.skeletons', ['variant' => 'cards', 'count' => 4])

    Inline skeleton:
    @include('includes.skeletons', ['variant' => 'text'])
--}}
@if (($variant ?? 'text') === 'table')
    <div class="space-y-3 p-6" aria-hidden="true">
        <div class="flex items-center justify-between">
            <div class="skeleton skeleton-title"></div>
            <div class="skeleton skeleton-button"></div>
        </div>
        <div class="space-y-2">
            @for ($i = 0; $i < ($rows ?? 5); $i++)
                <div class="flex items-center gap-4">
                    <div class="skeleton skeleton-avatar"></div>
                    <div class="flex-1 space-y-2">
                        <div class="skeleton skeleton-text w-2/5"></div>
                        <div class="skeleton skeleton-text w-1/4"></div>
                    </div>
                </div>
            @endfor
        </div>
    </div>
@elseif (($variant ?? 'text') === 'cards')
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4" aria-hidden="true">
        @for ($i = 0; $i < ($count ?? 4); $i++)
            <div class="skeleton skeleton-card"></div>
        @endfor
    </div>
@elseif (($variant ?? 'text') === 'form')
    <div class="space-y-5 p-6" aria-hidden="true">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            @for ($i = 0; $i < 4; $i++)
                <div class="space-y-2">
                    <div class="skeleton skeleton-text w-1/3"></div>
                    <div class="skeleton h-11 w-full rounded-xl"></div>
                </div>
            @endfor
        </div>
        <div class="skeleton h-11 w-36 rounded-xl"></div>
    </div>
@else
    <div class="space-y-2" aria-hidden="true">
        <div class="skeleton skeleton-title"></div>
        <div class="skeleton skeleton-text w-3/4"></div>
        <div class="skeleton skeleton-text w-1/2"></div>
    </div>
@endif
