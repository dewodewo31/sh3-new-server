<footer class="shrink-0 border-t border-slate-200/80 bg-white/60 px-4 py-5 sm:px-6 lg:px-8 dark:border-slate-700/60 dark:bg-slate-900/60">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 text-sm text-slate-400 sm:flex-row dark:text-slate-500">
        <div class="flex flex-col items-center gap-1.5 sm:flex-row sm:gap-3">
            <span class="inline-flex items-center gap-2 font-medium text-slate-600 dark:text-slate-400">
                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-gradient-to-br from-blue-500 to-indigo-600 text-[10px] font-bold text-white">SH</span>
                {{ config('app.name') }} <span class="font-normal">Admin</span>
            </span>
            <span class="hidden h-3 w-px bg-slate-200 sm:block dark:bg-slate-700"></span>
            <span>&copy; {{ date('Y') }} All rights reserved.</span>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2">
            <a href="{{ route('admin.dashboard') }}" class="transition-colors duration-150 hover:text-slate-600 dark:hover:text-slate-300">Dashboard</a>
            <a href="{{ route('admin.events.index') }}" class="transition-colors duration-150 hover:text-slate-600 dark:hover:text-slate-300">Events</a>
            <a href="{{ route('admin.payments.index') }}" class="transition-colors duration-150 hover:text-slate-600 dark:hover:text-slate-300">Payments</a>
            <span class="inline-flex items-center gap-1.5">
                <span class="badge-dot bg-emerald-500"></span>
                All systems operational
            </span>
        </div>
    </div>
</footer>
