@extends('layouts.app')

@section('title', 'Albums')
@section('subtitle', 'Manage gallery albums')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Daftar Album</h3>
        <div class="flex items-center gap-2">
            <form action="{{ route('admin.gallery-albums.sync') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Sync Drive
                </button>
            </form>
            <a href="{{ route('admin.gallery-albums.create') }}" class="btn btn-primary btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Album
            </a>
        </div>
    </div>
    <div class="table-wrap w-full max-w-full overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700/80">
        <table class="min-w-full whitespace-nowrap">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cover</th>
                    <x-th-sort column="title">Title</x-th-sort>
                    <th>Event</th>
                    <th>Galleries</th>
                    <th>Drive</th>
                    <th>Sync</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($albums as $album)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        @if($album->cover_image)
                            <img src="{{ \App\Helpers\ImageHelper::getUrl($album->cover_image) }}" alt="{{ $album->title }}" class="h-10 w-10 rounded-md border border-slate-200 object-cover">
                        @else
                            <span class="badge badge-secondary">-</span>
                        @endif
                    </td>
                    <td class="font-medium text-gray-900 dark:text-gray-100">{{ $album->title }}</td>
                    <td>{{ $album->event->title ?? '-' }}</td>
                    <td><span class="badge badge-info">{{ $album->galleries_count }}</span></td>
                    <td>
                        @if($album->gdrive_folder_url)
                            <a href="{{ $album->gdrive_folder_url }}" target="_blank" rel="noopener" class="badge badge-info">Drive</a>
                        @else
                            <span class="badge badge-secondary">-</span>
                        @endif
                    </td>
                    <td>
                        @if($album->gdrive_sync_error)
                            <span class="badge badge-danger" title="{{ $album->gdrive_sync_error }}">Error</span>
                        @elseif($album->last_synced_at)
                            <span class="badge badge-success">{{ $album->last_synced_at->diffForHumans() }}</span>
                        @else
                            <span class="badge badge-secondary">-</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('admin.gallery-albums.edit', $album->id) }}" class="btn btn-warning btn-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit
                            </a>
                            <form action="{{ route('admin.gallery-albums.destroy', $album->id) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Hapus album ini? Semua gallery di dalamnya akan menyesuaikan.')">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.41a2.25 2.25 0 013.182 0l2.909 2.91m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                            <p class="empty-state-title">Belum ada album</p>
                            <p class="empty-state-text">Buat album pertama untuk memulai.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($albums->hasPages())
        <div class="pagination">
            <div class="pagination-info">
                Showing {{ $albums->firstItem() }} to {{ $albums->lastItem() }} of {{ $albums->total() }} items
            </div>
            <div class="pagination-links">{{ $albums->links() }}</div>
        </div>
    @endif
</div>
@endsection