@extends('layouts.app')

@section('title', 'Notifikasi')
@section('subtitle', 'Semua notifikasi Anda')

@section('breadcrumb')
    @include('includes.breadcrumb', ['items' => [['label' => 'Notifikasi']]])
@endsection

@section('actions')
    @if($unreadCount > 0)
        <form action="{{ route('admin.notifications.read-all') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Tandai semua dibaca
            </button>
        </form>
    @endif
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-header-title">Semua Notifikasi</h3>
            <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">{{ $unreadCount > 0 ? $unreadCount . ' belum dibaca' : 'Semua sudah dibaca' }}</p>
        </div>
        <span class="badge badge-secondary">{{ $notifications->total() }} total</span>
    </div>

    <div class="p-0">
        @forelse($notifications as $notification)
            @php
                $data = $notification->data;
                $url = $data['url'] ?? null;
            @endphp
            <a href="{{ $url ?? '#' }}"
               class="flex items-start gap-4 border-b border-slate-100 dark:border-slate-700/60 px-4 py-4 transition-colors duration-150 hover:bg-slate-50 dark:hover:bg-slate-700/40 sm:px-6 {{ is_null($notification->read_at) ? 'bg-blue-50/40 dark:bg-blue-500/10' : '' }}"
               onclick="markNotificationRead(@js($notification->id), @js($url ?? ''))">
                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ is_null($notification->read_at) ? 'bg-blue-100 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400' : 'bg-slate-100 dark:bg-slate-700/60 text-slate-500 dark:text-slate-400' }}">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-0.5">
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $data['title'] ?? 'Notifikasi' }}</p>
                        <span class="text-xs text-slate-400 dark:text-slate-500">{{ $notification->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ $data['body'] ?? '' }}</p>
                </div>
                <span class="mt-1.5 shrink-0">
                    @if(is_null($notification->read_at))
                        <span class="badge-dot bg-blue-500 ring-2 ring-white dark:ring-slate-800"></span>
                    @endif
                </span>
            </a>
        @empty
            <div class="empty-state py-12">
                <div class="empty-state-icon">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                </div>
                <p class="empty-state-title">Belum ada notifikasi</p>
                <p class="empty-state-text">Notifikasi akan muncul di sini ketika ada aktivitas baru.</p>
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div class="pagination">
            <div class="pagination-info">
                Showing {{ $notifications->firstItem() }} to {{ $notifications->lastItem() }} of {{ $notifications->total() }} notifications
            </div>
            <div class="pagination-links">{{ $notifications->links() }}</div>
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    window.markNotificationRead = function (id, url) {
        window.axios.post('/admin/notifications/' + id + '/read').catch(function (error) {
            console.error('Gagal menandai notifikasi:', error);
        });

        if (url && url !== '#') {
            window.location.href = url;
        }
    };
</script>
@endsection
