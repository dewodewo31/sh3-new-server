@extends('layouts.app')

@section('title', 'Events')
@section('subtitle', 'Manage running club events')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Daftar Event</h3>
        <a href="{{ route('admin.events.create') }}" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Event
        </a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Tanggal</th>
                    <th>Kuota</th>
                    <th>Harga</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($events as $event)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="font-medium text-gray-900">{{ $event->title }}</td>
                    <td>{{ $event->category->name ?? '-' }}</td>
                    <td>{{ $event->start_date->format('d/m/Y') }}</td>
                    <td>{{ $event->quota ?? 'Unlimited' }}</td>
                    <td>@if($event->price) Rp {{ number_format($event->price, 0, ',', '.') }} @else <span class="badge badge-success">Gratis</span> @endif</td>
                    <td>
                        <span class="badge {{ $event->status === 'publish' ? 'badge-success' : ($event->status === 'draft' ? 'badge-secondary' : ($event->status === 'ongoing' ? 'badge-info' : ($event->status === 'completed' ? 'badge-indigo' : 'badge-danger'))) }}">
                            {{ ucfirst($event->status) }}
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('admin.events.show', $event->id) }}" class="btn btn-info btn-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Detail
                            </a>
                            <a href="{{ route('admin.events.edit', $event->id) }}" class="btn btn-warning btn-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit
                            </a>
                            @if($event->status === 'draft')
                                <form action="{{ route('admin.events.publish', $event->id) }}" method="POST" class="inline">
                                    @csrf @method('PUT')
                                    <button type="submit" class="btn btn-success btn-xs" onclick="return confirm('Publikasi event ini?')">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Publish
                                    </button>
                                </form>
                            @endif
                            <form action="{{ route('admin.events.destroy', $event->id) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Hapus event ini?')">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                            <p class="empty-state-title">Belum ada event</p>
                            <p class="empty-state-text">Buat event pertama Anda untuk memulai.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($events->hasPages())
        <div class="pagination">
            <div class="pagination-info">
                Showing {{ $events->firstItem() }} to {{ $events->lastItem() }} of {{ $events->total() }} events
            </div>
            <div class="pagination-links">
                {{ $events->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
