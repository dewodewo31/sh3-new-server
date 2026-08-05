@php
    $sidebarService = app(\App\Services\SidebarService::class);
    $menus = $sidebarService->getMenus();
@endphp

<nav class="custom-scroll flex-1 space-y-6 overflow-y-auto px-3 py-5" aria-label="Navigasi utama">
    @foreach ($menus as $menuGroup)
        <div class="space-y-1">
            <div class="sidebar-section-label">{{ $menuGroup['section'] }}</div>

            @foreach ($menuGroup['items'] as $item)
                <a href="{{ route($item['route']) }}"
                   class="sidebar-link {{ $sidebarService->isActive($item['active']) ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
                    {!! $item['icon'] !!}
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    @endforeach
</nav>