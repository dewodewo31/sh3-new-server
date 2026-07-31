@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Overview aktivitas dan performa klub Anda')

@section('breadcrumb')
    @include('includes.breadcrumb', ['items' => [['label' => 'Dashboard']]])
@endsection

@section('actions')
    <button type="button" class="btn btn-secondary btn-sm" @click="showToast('info', 'Info', 'Laporan sedang disiapkan')">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
        </svg>
        Export
    </button>
    <a href="{{ route('admin.events.create') }}" class="btn btn-primary btn-sm">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Buat Event
    </a>
@endsection

@section('content')
{{-- Loading skeleton demo (tampil saat halaman dimuat via fetch/HTMX) --}}
<div class="hidden" x-data="{ loading: false }">
    <div x-show="loading">
        @include('includes.skeletons', ['variant' => 'cards', 'count' => 4])
    </div>
</div>

{{-- Statistik --}}
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
    <div class="stat-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Events</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ $stats['total_events'] }}</p>
            </div>
            <div class="stat-icon bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-700/60">
            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.306a11.95 11.95 0 015.814-5.518l2.74-1.22m0 0l-5.94-2.281m5.94 2.28l-2.28 5.941"/></svg>
                {{ $stats['upcoming_events'] }} upcoming
            </span>
            <a href="{{ route('admin.events.index') }}" class="link inline-flex items-center gap-1 text-xs font-medium">
                Lihat semua
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>
    </div>

    <div class="stat-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Participants</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ $stats['total_participants'] }}</p>
            </div>
            <div class="stat-icon bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-700/60">
            <span class="text-xs text-slate-400 dark:text-slate-500">Total terdaftar</span>
            <a href="{{ route('admin.participants.index') }}" class="link inline-flex items-center gap-1 text-xs font-medium">
                Kelola
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>
    </div>

    <div class="stat-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Payments</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ $stats['total_payments'] }}</p>
            </div>
            <div class="stat-icon bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-700/60">
            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-600 dark:text-amber-400">
                <span class="badge-dot bg-amber-500"></span>
                {{ $stats['pending_payments'] }} pending
            </span>
            <a href="{{ route('admin.payments.index') }}" class="link inline-flex items-center gap-1 text-xs font-medium">
                Lihat
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>
    </div>

    <div class="stat-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Users</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ $stats['total_users'] }}</p>
            </div>
            <div class="stat-icon bg-cyan-50 text-cyan-600 dark:bg-cyan-500/10 dark:text-cyan-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-700/60">
            <span class="text-xs text-slate-400 dark:text-slate-500">Seluruh sistem</span>
            <a href="{{ route('admin.users.index') }}" class="link inline-flex items-center gap-1 text-xs font-medium">
                Kelola
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- Recent Activity --}}
    <div class="card lg:col-span-2">
        <div class="card-header">
            <div>
                <h3 class="card-header-title">Aktivitas Terbaru</h3>
                <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">Pembaruan terbaru di seluruh sistem</p>
            </div>
            <span class="badge badge-secondary">Live</span>
        </div>
        <div class="card-body">
            <ol class="relative space-y-6 border-l border-slate-200 pl-6 dark:border-slate-700">
                @forelse ($recentActivity as $activity)
                    @php
                        $activityStyles = [
                            'payment_confirmed' => ['bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400', 'M4.5 12.75l6 6 9-13.5'],
                            'payment' => ['bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400', 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z'],
                            'event' => ['bg-sky-100 text-sky-600 dark:bg-sky-500/20 dark:text-sky-400', 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25'],
                            'participant' => ['bg-indigo-100 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400', 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z'],
                            'membership' => ['bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400', 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                            'attendance' => ['bg-green-100 text-green-600 dark:bg-green-500/20 dark:text-green-400', 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ];
                        $style = $activityStyles[$activity['type']] ?? $activityStyles['payment'];
                    @endphp
                    <li class="relative">
                        <span class="absolute -left-[31px] flex h-5 w-5 items-center justify-center rounded-full ring-4 ring-white dark:ring-slate-800 {{ $style[0] }}">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $style[1] }}"/></svg>
                        </span>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm text-slate-700 dark:text-slate-300">{!! $activity['title'] !!}</p>
                            <span class="text-xs text-slate-400 dark:text-slate-500">{{ $activity['time']->diffForHumans() }}</span>
                        </div>
                        <p class="mt-0.5 truncate text-xs text-slate-400 dark:text-slate-500">{{ $activity['description'] }}</p>
                    </li>
                @empty
                    <li class="text-sm text-slate-400 dark:text-slate-500">Belum ada aktivitas terbaru.</li>
                @endforelse
            </ol>
        </div>
    </div>

    {{-- Upcoming Events --}}
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-header-title">Upcoming Events</h3>
                <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">Event yang akan datang</p>
            </div>
            <span class="badge badge-blue">{{ $stats['upcoming_events'] }} event</span>
        </div>
        <div class="card-body">
            @if ($stats['upcoming_events'] > 0)
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Ada <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $stats['upcoming_events'] }}</span> event terjadwal.
                </p>
                <div class="mt-4 space-y-3">
                    @forelse ($upcomingEvents as $event)
                        <a href="{{ route('admin.events.show', $event->id) }}" class="flex items-center gap-3 rounded-xl border border-slate-100 p-3 transition-colors duration-150 hover:border-slate-200 hover:bg-slate-50/60 dark:border-slate-700/60 dark:hover:border-slate-600 dark:hover:bg-slate-700/30">
                            <div class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                                <span class="text-[10px] font-semibold uppercase leading-none">{{ $event->start_date->format('M') }}</span>
                                <span class="text-base font-bold leading-tight">{{ $event->start_date->format('d') }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ $event->title }}</p>
                                <p class="truncate text-xs text-slate-400 dark:text-slate-500">{{ $event->location ?? 'Lokasi belum diatur' }}</p>
                            </div>
                            <svg class="h-4 w-4 shrink-0 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                        </a>
                    @empty
                        <p class="text-sm text-slate-400 dark:text-slate-500">Tidak ada event yang akan datang.</p>
                    @endforelse
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                        </svg>
                    </div>
                    <p class="empty-state-title">Belum ada event</p>
                    <p class="empty-state-text">Jadwalkan event pertama Anda untuk memulai.</p>
                    <a href="{{ route('admin.events.create') }}" class="btn btn-primary btn-sm mt-4">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Buat Event
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
