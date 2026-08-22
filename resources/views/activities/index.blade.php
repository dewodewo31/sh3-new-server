@extends('layouts.app')

@section('title', 'Aktivitas')
@section('subtitle', 'Kelola aktivitas / pusat biaya')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Daftar Aktivitas</h3>
        <a href="{{ route('admin.activities.create') }}" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Aktivitas
        </a>
    </div>
    <div class="table-wrap w-full max-w-full overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700/80">
        <table class="min-w-full whitespace-nowrap">
            <thead>
                <tr>
                    <th>#</th>
                    <x-th-sort column="name">Name</x-th-sort>
                    <x-th-sort column="sort_order">Order</x-th-sort>
                    <x-th-sort column="is_active">Active</x-th-sort>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $activity)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="font-medium text-gray-900 dark:text-gray-100">{{ $activity->name }}</td>
                    <td>{{ $activity->sort_order }}</td>
                    <td>
                        @if($activity->is_active)
                            <span class="badge badge-success">Yes</span>
                        @else
                            <span class="badge badge-danger">No</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('admin.activities.edit', $activity->id) }}" class="btn btn-warning btn-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit
                            </a>
                            <form action="{{ route('admin.activities.destroy', $activity->id) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Hapus aktivitas ini?')">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg>
                            <p class="empty-state-title">Belum ada aktivitas</p>
                            <p class="empty-state-text">Buat aktivitas pertama untuk memulai.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
