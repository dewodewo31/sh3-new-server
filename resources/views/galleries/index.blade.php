@extends('layouts.app')

@section('title', 'Galleries')
@section('subtitle', 'Manage event photos and videos')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Daftar Gallery</h3>
        <a href="{{ route('admin.galleries.create') }}" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Gallery
        </a>
    </div>
    <div class="table-wrap w-full max-w-full overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700/80">
        <table class="min-w-full whitespace-nowrap">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Event</th>
                    <th>Featured</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($galleries as $gallery)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="font-medium text-gray-900 dark:text-gray-100">{{ $gallery->title }}</td>
                    <td>
                        @php
                            $typeClasses = ['image' => 'badge-success', 'video' => 'badge-info'];
                        @endphp
                        <span class="badge {{ $typeClasses[$gallery->type] ?? 'badge-secondary' }}">{{ ucfirst($gallery->type) }}</span>
                    </td>
                    <td>{{ $gallery->event->title ?? '-' }}</td>
                    <td>
                        @if($gallery->is_featured)
                            <span class="badge badge-success">Yes</span>
                        @else
                            <span class="badge badge-secondary">No</span>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('admin.galleries.destroy', $gallery->id) }}" method="POST" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Hapus gallery ini?')">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Hapus
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.41a2.25 2.25 0 013.182 0l2.909 2.91m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                            <p class="empty-state-title">Belum ada gallery</p>
                            <p class="empty-state-text">Tambah gallery pertama untuk memulai.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($galleries->hasPages())
        <div class="pagination">
            <div class="pagination-info">
                Showing {{ $galleries->firstItem() }} to {{ $galleries->lastItem() }} of {{ $galleries->total() }} items
            </div>
            <div class="pagination-links">{{ $galleries->links() }}</div>
        </div>
    @endif
</div>
@endsection
