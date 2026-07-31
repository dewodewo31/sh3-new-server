<header class="glass sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 border-b border-slate-200/80 px-4 shadow-sm shadow-slate-900/5 sm:px-6 lg:px-8 dark:border-slate-700/60">
    {{-- Mobile sidebar toggle --}}
    <button type="button" class="icon-btn lg:hidden" @click="sidebarOpen = !sidebarOpen" aria-label="Buka menu navigasi">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
        </svg>
    </button>

    {{-- Mobile brand --}}
    <a href="{{ route('admin.dashboard') }}" class="flex shrink-0 items-center gap-2.5 lg:hidden" aria-label="SH3 Admin">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 text-xs font-bold shadow shadow-indigo-500/20">
            <span class="text-white">SH</span>
        </span>
        <span class="truncate text-sm font-semibold tracking-tight text-slate-900 dark:text-white">SH3</span>
    </a>

    {{-- Global search --}}
    <form action="{{ route('admin.events.index') }}" method="GET" class="relative hidden max-w-md flex-1 sm:block" role="search" aria-label="Pencarian global">
        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
        </svg>
        <input type="text" name="search" id="global-search" value="{{ request('search') }}" placeholder="Cari event..." class="search-input" aria-label="Cari event">
        <kbd class="pointer-events-none absolute right-3 top-1/2 hidden -translate-y-1/2 rounded-md border border-slate-200 bg-white px-1.5 py-0.5 font-sans text-[10px] font-medium text-slate-400 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-500 lg:inline-block">/</kbd>
    </form>

    <div class="flex-1 sm:flex-none"></div>

    {{-- Right actions --}}
    <div class="flex items-center gap-1.5 sm:gap-2">
        {{-- Theme toggle --}}
        <div x-data="themeToggle">
            <button type="button" class="icon-btn" @click="toggle()" :aria-label="dark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'">
                <svg x-show="!dark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/>
                </svg>
                <svg x-show="dark" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                </svg>
            </button>
        </div>

        {{-- Notifications --}}
        <div class="relative" x-data="notificationBell(@js(auth()->id()))">
            <button type="button" class="icon-btn relative" @click="toggle()" @click.outside="open = false" :class="{ 'bg-slate-100 text-slate-900 dark:bg-slate-700/60 dark:text-white': open }" aria-label="Notifikasi">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                </svg>
                <template x-if="unreadCount > 0">
                    <span class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white dark:ring-slate-800" x-text="unreadCount"></span>
                </template>
            </button>

            <div x-show="open" x-cloak class="dropdown w-80 max-w-[calc(100vw-2rem)]">
                <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700/60">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Notifikasi</p>
                        <div class="flex items-center gap-2">
                            <button x-show="unreadCount > 0" @click="markAllAsRead()" class="text-xs font-medium text-blue-600 hover:text-blue-700">Tandai semua dibaca</button>
                            <span x-show="unreadCount > 0" class="badge badge-danger" x-text="unreadCount + ' baru'"></span>
                        </div>
                    </div>
                </div>
                <div class="max-h-80 overflow-y-auto custom-scroll">
                    <template x-if="notifications.length === 0">
                        <div class="px-4 py-8 text-center">
                            <p class="text-sm text-slate-400 dark:text-slate-500">Belum ada notifikasi</p>
                        </div>
                    </template>
                    <template x-for="notification in notifications" :key="notification.id">
                        <a :href="notification.url || '#'" @click="markAsRead(notification)" :class="{ 'bg-blue-50/60 dark:bg-blue-500/10': !notification.read_at }" class="dropdown-item">
                            <span :class="'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ' + iconMeta(notification.icon).cls">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="iconMeta(notification.icon).d"/>
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium text-slate-900 dark:text-slate-100" x-text="notification.title"></span>
                                <span class="block truncate text-xs text-slate-500 dark:text-slate-400" x-text="notification.body"></span>
                                <span class="block truncate text-xs text-slate-400 dark:text-slate-500" x-text="notification.created_at"></span>
                            </span>
                        </a>
                    </template>
                </div>
                <div class="border-t border-slate-100 p-2 dark:border-slate-700/60">
                    <a href="{{ route('admin.notifications.index') }}" class="dropdown-item justify-center gap-1 text-center text-xs font-medium text-blue-600 hover:text-blue-700">
                        Lihat semua notifikasi
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </a>
                </div>
            </div>
        </div>

        {{-- User dropdown --}}
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" @click.outside="open = false" :class="{ 'bg-slate-100 dark:bg-slate-700/60': open }"
                    class="flex items-center gap-2.5 rounded-xl px-2 py-1.5 transition-colors duration-150 hover:bg-slate-100 dark:hover:bg-slate-700/60" aria-haspopup="menu">
                <span class="hidden text-right sm:block">
                    <span class="block text-sm font-medium leading-tight text-slate-900 dark:text-slate-100">{{ auth()->user()->name }}</span>
                    <span class="block text-xs leading-tight text-slate-400 dark:text-slate-500">{{ str_replace('_', ' ', auth()->user()->role) }}</span>
                </span>
                <span class="avatar avatar-ring h-9 w-9 text-sm">{{ substr(auth()->user()->name, 0, 1) }}</span>
                <svg class="hidden h-4 w-4 text-slate-400 dark:text-slate-500 sm:block" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                </svg>
            </button>

            <div x-show="open" x-cloak class="dropdown" role="menu">
                <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700/60">
                    <div class="flex items-center gap-3">
                        <div class="avatar avatar-ring h-9 w-9 shrink-0 text-sm">{{ substr(auth()->user()->name, 0, 2) }}</div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                    <span class="mt-2 inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        {{ str_replace('_', ' ', auth()->user()->role) }}
                    </span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-500/10 dark:hover:text-red-300" role="menuitem">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
