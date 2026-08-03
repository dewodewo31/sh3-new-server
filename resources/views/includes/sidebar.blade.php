@php
    $user = auth()->user();
    $roleLabel = str_replace('_', ' ', $user->role);
@endphp

{{-- Desktop sidebar --}}
<aside class="hidden w-64 shrink-0 flex-col border-r border-slate-200/80 bg-white lg:sticky lg:top-0 lg:flex lg:h-screen dark:border-slate-700/60 dark:bg-slate-900">
    {{-- Brand --}}
    <a href="{{ route('admin.dashboard') }}" class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-200/80 px-5 dark:border-slate-700/60">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-bold shadow-lg shadow-indigo-500/20">
            <span class="text-white">SH</span>
        </span>
        <span class="text-base font-semibold tracking-tight text-slate-900 dark:text-white">
            SH3 <span class="font-normal text-slate-400 dark:text-slate-500">Admin</span>
        </span>
        <span class="ml-auto hidden rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-400 xl:inline dark:bg-slate-800 dark:text-slate-500">v1.0</span>
    </a>

    {{-- Navigation (shared with mobile) --}}
    @include('includes.sidebar-nav')

    {{-- User --}}
    <div class="shrink-0 border-t border-slate-200/80 p-4 dark:border-slate-700/60">
        <div class="flex items-center gap-3 rounded-xl bg-slate-100/70 p-2.5 dark:bg-slate-800/60">
            <div class="avatar h-9 w-9 shrink-0">{{ substr($user->name, 0, 2) }}</div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ $user->name }}</p>
                <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $roleLabel }}</p>
            </div>
            <span class="badge-dot bg-emerald-500 ring-2 ring-white dark:ring-slate-800" title="Online"></span>
        </div>
    </div>
</aside>

{{-- Mobile sidebar --}}
<div x-show="sidebarOpen" x-cloak class="sidebar-mobile lg:hidden" role="dialog" aria-modal="true" aria-label="Navigasi">
    <div class="sidebar-mobile-backdrop" @click="sidebarOpen = false"></div>
    <div class="sidebar-mobile-panel">
        {{-- Mobile header --}}
        <div class="flex h-16 shrink-0 items-center justify-between border-b border-slate-200/80 px-4 dark:border-slate-700/60">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3" @click="sidebarOpen = false">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-bold shadow-lg shadow-indigo-500/20">
                    <span class="text-white">SH</span>
                </span>
                <span class="text-base font-semibold tracking-tight text-slate-900 dark:text-white">
                    SH3 <span class="font-normal text-slate-400 dark:text-slate-500">Admin</span>
                </span>
            </a>
            <button type="button" class="sidebar-mobile-close" @click="sidebarOpen = false" aria-label="Tutup navigasi">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Navigation (shared with desktop) --}}
        @include('includes.sidebar-nav')

        {{-- Mobile user --}}
        <div class="shrink-0 border-t border-slate-200/80 p-4 dark:border-slate-700/60">
            <div class="flex items-center gap-3">
                <div class="avatar h-9 w-9 shrink-0">{{ substr($user->name, 0, 2) }}</div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ $user->name }}</p>
                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $roleLabel }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="icon-btn" aria-label="Keluar" title="Keluar">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
