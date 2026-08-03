@extends('layouts.app')

@section('title', 'Detail User')
@section('subtitle', $user->name)

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="card">
        <div class="card-header">
            <div class="flex items-center gap-3">
                @if($user->avatar)
                    <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="h-10 w-10 rounded-xl object-cover border border-gray-200 dark:border-slate-700">
                @else
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-sm font-semibold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                @endif
                <h3 class="card-header-title">Detail User</h3>
            </div>
        </div>
        <div class="card-body">
            <dl class="divide-y divide-gray-100 dark:divide-slate-700/60">
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Name</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-slate-100">{{ $user->name }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Email</dt>
                    <dd class="text-sm text-gray-900 dark:text-slate-100">{{ $user->email }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Role</dt>
                    <dd><span class="badge badge-indigo">{{ str_replace('_', ' ', $user->role) }}</span></dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Status</dt>
                    <dd>
                        @if($user->is_active)
                            <span class="badge badge-success"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span> Active</span>
                        @else
                            <span class="badge badge-danger"><span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span> Inactive</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Last Login</dt>
                    <dd class="text-sm text-gray-900 dark:text-slate-100">{{ $user->last_login ? $user->last_login->format('d/m/Y H:i:s') : '-' }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Created</dt>
                    <dd class="text-sm text-gray-900 dark:text-slate-100">{{ $user->created_at->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>
        </div>
        <div class="card-footer">
            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-warning btn-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                Edit
            </a>
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/></svg>
                Kembali
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <h3 class="card-header-title">Activity Log</h3>
            </div>
        </div>
        <div class="p-0">
            @if($user->activityLogs->count())
                <div class="divide-y divide-gray-100 dark:divide-slate-700/60">
                    @foreach($user->activityLogs as $log)
                    <div class="flex items-center justify-between px-6 py-3.5">
                        <div>
                            <p class="text-sm text-gray-900 dark:text-slate-100">{{ $log->action }}</p>
                            <p class="text-xs text-gray-400 dark:text-slate-500 font-mono">{{ $log->ip_address }}</p>
                        </div>
                        <span class="text-xs text-gray-400 dark:text-slate-500">{{ $log->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state py-8">
                    <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    <p class="empty-state-title">Belum ada aktivitas</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
