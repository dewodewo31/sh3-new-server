@extends('layouts.app')

@section('title', 'Laporan Attendance')
@section('subtitle', 'View attendance by event')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($events as $event)
    <div class="card hover-lift">
        <div class="card-body">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <h3 class="text-base font-semibold text-gray-900 truncate">{{ $event->title }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ $event->start_date->format('d/m/Y') }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 shrink-0 ml-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
            </div>
            <div class="mt-5">
                <a href="{{ route('admin.attendance.by-event', $event->id) }}" class="btn btn-info btn-sm w-full justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Lihat Attendance
                </a>
            </div>
        </div>
    </div>
    @empty
    <div class="lg:col-span-3">
        <div class="card">
            <div class="empty-state">
                <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <p class="empty-state-title">Belum ada event</p>
                <p class="empty-state-text">Buat event terlebih dahulu untuk melihat attendance.</p>
            </div>
        </div>
    </div>
    @endforelse
</div>
@endsection
