@extends('layouts.app')

@section('title', 'Attendance')
@section('subtitle', $event->title)

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Attendance: {{ $event->title }}</h3>
    </div>
    <div class="table-wrap w-full max-w-full overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700/80">
        <table class="min-w-full whitespace-nowrap">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Participant</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Status</th>
                    <th>Method</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $attendance)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="font-medium text-gray-900 dark:text-slate-100">{{ $attendance->eventParticipant->participant->name ?? '-' }}</td>
                    <td>{{ $attendance->check_in_time?->format('d/m/Y H:i:s') ?? '-' }}</td>
                    <td>{{ $attendance->check_out_time?->format('d/m/Y H:i:s') ?? '-' }}</td>
                    <td>
                        @php
                            $statusClasses = ['present' => 'badge-success', 'late' => 'badge-warning', 'left_early' => 'badge-info', 'absent' => 'badge-danger'];
                        @endphp
                        <span class="badge {{ $statusClasses[$attendance->status] ?? 'badge-secondary' }}">{{ ucwords(str_replace('_', ' ', $attendance->status)) }}</span>
                    </td>
                    <td>{{ str_replace('_', ' ', ucwords($attendance->check_in_method)) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <p class="empty-state-title">Belum ada data attendance</p>
                            <p class="empty-state-text">Data attendance akan muncul ketika peserta melakukan check-in.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6">
    <a href="{{ route('admin.attendance.report') }}" class="btn btn-secondary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Kembali
    </a>
</div>
@endsection
