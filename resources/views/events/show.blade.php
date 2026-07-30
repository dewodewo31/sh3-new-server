@extends('layouts.app')

@section('title', 'Detail Event')
@section('subtitle', $event->title)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-header-title">Detail Event</h3>
                <span class="badge {{ $event->status === 'publish' ? 'badge-success' : ($event->status === 'draft' ? 'badge-secondary' : ($event->status === 'ongoing' ? 'badge-info' : ($event->status === 'completed' ? 'badge-indigo' : 'badge-danger'))) }}">
                    {{ ucfirst($event->status) }}
                </span>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <span class="text-xs font-medium uppercase tracking-wider text-gray-400">Category</span>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $event->category->name ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-medium uppercase tracking-wider text-gray-400">Start</span>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $event->start_date->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-medium uppercase tracking-wider text-gray-400">End</span>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $event->end_date->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-medium uppercase tracking-wider text-gray-400">Registration</span>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $event->registration_start_date->format('d/m/Y') }} - {{ $event->registration_end_date->format('d/m/Y') }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-medium uppercase tracking-wider text-gray-400">Location</span>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $event->location ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-medium uppercase tracking-wider text-gray-400">Quota</span>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $event->quota ?? 'Unlimited' }} <span class="text-gray-500">({{ $event->remainingQuota() }} tersisa)</span></p>
                    </div>
                    <div>
                        <span class="text-xs font-medium uppercase tracking-wider text-gray-400">Price</span>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $event->price ? 'Rp '.number_format($event->price, 0, ',', '.') : 'Gratis' }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-medium uppercase tracking-wider text-gray-400">Free for members</span>
                        <p class="mt-1">
                            @if($event->is_free_for_members)
                                <span class="badge badge-success">Ya</span>
                            @else
                                <span class="badge badge-secondary">Tidak</span>
                            @endif
                        </p>
                    </div>
                </div>
                <hr class="my-6 border-gray-100">
                <div>
                    <span class="text-xs font-medium uppercase tracking-wider text-gray-400">Description</span>
                    <p class="mt-2 text-sm text-gray-700 leading-relaxed">{{ $event->description ?? 'Tidak ada deskripsi.' }}</p>
                </div>
                <hr class="my-6 border-gray-100">
                <div>
                    <span class="text-xs font-medium uppercase tracking-wider text-gray-400">Address</span>
                    <p class="mt-2 text-sm text-gray-700">{{ $event->address ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-header-title">Peserta Terdaftar</h3>
                <span class="badge badge-indigo">{{ $event->eventParticipants->count() }}</span>
            </div>
            <div class="p-0">
                @if($event->eventParticipants->count())
                    <div class="divide-y divide-gray-100">
                        @foreach($event->eventParticipants as $ep)
                        <div class="flex items-center justify-between px-6 py-3.5">
                            <span class="text-sm text-gray-900">{{ $ep->participant->name ?? '#' . $ep->participant_id }}</span>
                            <span class="badge {{ $ep->payment_status === 'confirmed' ? 'badge-success' : 'badge-warning' }}">{{ $ep->payment_status }}</span>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state py-8">
                        <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                        <p class="empty-state-title">Belum ada peserta</p>
                    </div>
                @endif
            </div>
        </div>

        @if($event->schedules->count())
        <div class="card">
            <div class="card-header">
                <h3 class="card-header-title">Schedule</h3>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($event->schedules as $s)
                <div class="flex items-center gap-4 px-6 py-3.5">
                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-indigo-50 text-xs font-semibold text-indigo-700">{{ $s->start_time->format('H:i') }}</span>
                    <span class="text-sm text-gray-900">{{ $s->title }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <a href="{{ route('admin.events.edit', $event->id) }}" class="btn btn-warning">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        Edit
    </a>
    <a href="{{ route('admin.events.index') }}" class="btn btn-secondary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Kembali
    </a>
</div>
@endsection
