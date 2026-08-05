@extends('layouts.app')

@section('title', 'Participants')
@section('subtitle', 'Manage club participants and members')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Daftar Peserta</h3>
        <a href="{{ route('admin.participants.create') }}" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Tambah Peserta
        </a>
    </div>
    <div class="table-wrap w-full max-w-full overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700/80">
        <table class="min-w-full whitespace-nowrap">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Membership</th>
                    <th>Status</th>
                    <th>Events</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($participants as $p)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="font-medium text-gray-900 dark:text-slate-100">{{ $p->name }}</td>
                    <td>{{ $p->email }}</td>
                    <td>{{ $p->phone ?? '-' }}</td>
                    <td>
                        @if($p->membership_type !== 'none')
                            <span class="badge badge-success">{{ $p->membershipTypeLabel() }}</span>
                            <small class="block text-gray-400 mt-0.5 dark:text-slate-500">s/d {{ $p->membership_end_date?->format('d/m/Y') }}</small>
                        @else
                            <span class="badge badge-secondary">None</span>
                        @endif
                    </td>
                    <td>
                        @if($p->is_active)
                            <span class="badge badge-success">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                                Active
                            </span>
                        @else
                            <span class="badge badge-danger">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span>
                                Inactive
                            </span>
                        @endif
                    </td>
                    <td>{{ $p->total_events_participated }}</td>
                    <td>
                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('admin.participants.show', $p->id) }}" class="btn btn-info btn-xs">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                Detail
                            </a>
                            <a href="{{ route('admin.participants.edit', $p->id) }}" class="btn btn-warning btn-xs">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                Edit
                            </a>
                            <form action="{{ route('admin.participants.destroy', $p->id) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Hapus peserta ini?')">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
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
                            <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                            <p class="empty-state-title">Belum ada peserta</p>
                            <p class="empty-state-text">Tambah peserta pertama untuk memulai.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($participants->hasPages())
        <div class="pagination">
            <div class="pagination-info">
                Showing {{ $participants->firstItem() }} to {{ $participants->lastItem() }} of {{ $participants->total() }} participants
            </div>
            <div class="pagination-links">
                {{ $participants->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
