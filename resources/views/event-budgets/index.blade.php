@extends('layouts.app')

@section('title', 'Anggaran Event')
@section('subtitle', 'Kelola anggaran per event dan aktivitas')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Daftar Anggaran Event</h3>
        <a href="{{ route('admin.event-budgets.create') }}" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Anggaran
        </a>
    </div>
    <div class="table-wrap w-full max-w-full overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700/80">
        <table class="min-w-full whitespace-nowrap">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Event</th>
                    <th>Aktivitas</th>
                    <x-th-sort column="amount">Jumlah</x-th-sort>
                    <th>Catatan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($eventBudgets as $budget)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="font-medium text-gray-900 dark:text-gray-100">{{ $budget->event->title ?? '-' }}</td>
                    <td>{{ $budget->activity->name ?? '-' }}</td>
                    <td class="font-medium text-slate-900 dark:text-slate-100">Rp {{ number_format($budget->amount, 0, ',', '.') }}</td>
                    <td class="max-w-xs truncate">{{ $budget->notes ?? '-' }}</td>
                    <td>
                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('admin.event-budgets.edit', $budget->id) }}" class="btn btn-warning btn-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit
                            </a>
                            <form action="{{ route('admin.event-budgets.destroy', $budget->id) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Hapus anggaran ini?')">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
                            <p class="empty-state-title">Belum ada anggaran event</p>
                            <p class="empty-state-text">Buat anggaran pertama untuk memulai.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
