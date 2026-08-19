@php
    $sidebarService = app(\App\Services\SidebarService::class);
    $menus = $sidebarService->getMenus();
@endphp

<nav class="custom-scroll flex-1 space-y-1 overflow-y-auto px-3 py-5" aria-label="Navigasi utama"
     x-data="{
         openSections: JSON.parse(localStorage.getItem('sh3-sidebar-sections') || '[]'),
         init() {
             if (this.openSections.length === 0) {
                 this.openSections = [@js(collect($menus)->pluck('section')->values()->all())];
             }
         },
         toggle(section) {
             if (this.openSections.includes(section)) {
                 this.openSections = this.openSections.filter(s => s !== section);
             } else {
                 this.openSections.push(section);
             }
             localStorage.setItem('sh3-sidebar-sections', JSON.stringify(this.openSections));
         },
         isOpen(section) {
             return this.openSections.includes(section);
         }
     }"
     x-init="init()">
    @foreach ($menus as $menuGroup)
        <div class="space-y-1">
            <button type="button"
                    @click="toggle('{{ $menuGroup['section'] }}')"
                    class="sidebar-section-label flex w-full items-center justify-between cursor-pointer select-none rounded-lg px-3 py-1.5 transition-colors hover:bg-slate-100 dark:hover:bg-slate-800">
                {{ $menuGroup['section'] }}
                <svg class="h-3.5 w-3.5 shrink-0 text-slate-400 transition-transform duration-200 dark:text-slate-500"
                     :class="isOpen('{{ $menuGroup['section'] }}') ? 'rotate-0' : '-rotate-90'"
                     fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                </svg>
            </button>

            <div x-show="isOpen('{{ $menuGroup['section'] }}')"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="space-y-1">
                @foreach ($menuGroup['items'] as $item)
                    <a href="{{ route($item['route']) }}"
                       class="sidebar-link {{ $sidebarService->isActive($item['active']) ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
                        {!! $item['icon'] !!}
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach
</nav>
