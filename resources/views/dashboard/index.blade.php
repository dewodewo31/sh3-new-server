@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
    <div class="stat-card bg-gradient-to-br from-indigo-500 to-indigo-600 text-white shadow-indigo-200">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-indigo-100">Total Events</p>
                <p class="mt-1.5 text-3xl font-bold tracking-tight">{{ $stats['total_events'] }}</p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 backdrop-blur-sm">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
            </div>
        </div>
        <div class="mt-4 flex items-center gap-1 text-sm text-indigo-200">
            <span>{{ $stats['upcoming_events'] }} upcoming</span>
            <span class="text-indigo-300">-</span>
            <a href="{{ route('admin.events.index') }}" class="font-medium text-white hover:text-indigo-100 transition-colors">View all</a>
        </div>
    </div>

    <div class="stat-card bg-gradient-to-br from-emerald-500 to-emerald-600 text-white shadow-emerald-200">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-100">Participants</p>
                <p class="mt-1.5 text-3xl font-bold tracking-tight">{{ $stats['total_participants'] }}</p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 backdrop-blur-sm">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
            </div>
        </div>
        <div class="mt-4">
            <a href="{{ route('admin.participants.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-white hover:text-emerald-100 transition-colors">
                View all participants
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>
    </div>

    <div class="stat-card bg-gradient-to-br from-amber-500 to-amber-600 text-white shadow-amber-200">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-amber-100">Users</p>
                <p class="mt-1.5 text-3xl font-bold tracking-tight">{{ $stats['total_users'] }}</p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 backdrop-blur-sm">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
            </div>
        </div>
        <div class="mt-4">
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-white hover:text-amber-100 transition-colors">
                View all users
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>
    </div>

    <div class="stat-card bg-gradient-to-br from-rose-500 to-rose-600 text-white shadow-rose-200">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-rose-100">Pending Payments</p>
                <p class="mt-1.5 text-3xl font-bold tracking-tight">{{ $stats['pending_payments'] }}</p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 backdrop-blur-sm">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
            </div>
        </div>
        <div class="mt-4">
            <a href="{{ route('admin.payments.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-white hover:text-rose-100 transition-colors">
                View payments
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="card lg:col-span-2">
        <div class="card-header">
            <h3 class="card-header-title">Recent Activity</h3>
        </div>
        <div class="card-body">
            <p class="text-sm text-gray-500">Your dashboard overview is ready. Monitor events, participants, and payments from here.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-header-title">Upcoming Events</h3>
            <span class="badge badge-indigo">{{ $stats['upcoming_events'] }} events</span>
        </div>
        <div class="card-body">
            @if($stats['upcoming_events'] > 0)
                <p class="text-sm text-gray-600">There are <span class="font-semibold text-gray-900">{{ $stats['upcoming_events'] }}</span> upcoming events scheduled.</p>
            @else
                <div class="empty-state">
                    <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                    <p class="empty-state-title">No upcoming events</p>
                    <p class="empty-state-text">Schedule your first event to get started.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
