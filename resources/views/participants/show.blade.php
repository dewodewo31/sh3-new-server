@extends('layouts.app')

@section('title', 'Detail Peserta')
@section('subtitle', $participant->name)

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="card">
        <div class="card-header">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                <h3 class="card-header-title">Data Peserta</h3>
            </div>
        </div>
        <div class="card-body">
            <dl class="divide-y divide-gray-100">
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500">Nama</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $participant->name }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                    <dd class="text-sm text-gray-900">{{ $participant->email }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500">Phone</dt>
                    <dd class="text-sm text-gray-900">{{ $participant->phone ?? '-' }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500">Gender</dt>
                    <dd class="text-sm text-gray-900">{{ $participant->gender === 'male' ? 'Laki-laki' : ($participant->gender === 'female' ? 'Perempuan' : '-') }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500">Tgl Lahir</dt>
                    <dd class="text-sm text-gray-900">{{ $participant->date_of_birth?->format('d/m/Y') ?? '-' }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500">Blood Type</dt>
                    <dd class="text-sm text-gray-900">{{ $participant->blood_type ?? '-' }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500">Jersey Size</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $participant->jersey_size ?? '-' }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500">Membership</dt>
                    <dd>
                        @if($participant->membership_type !== 'none')
                            <span class="badge badge-success">{{ str_replace('_', ' ', ucwords($participant->membership_type)) }}</span>
                        @else
                            <span class="badge badge-secondary">None</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500">Member s/d</dt>
                    <dd class="text-sm text-gray-900">{{ $participant->membership_end_date?->format('d/m/Y') ?? '-' }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500">Total Events</dt>
                    <dd class="text-sm font-semibold text-gray-900">{{ $participant->total_events_participated }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card">
            <div class="card-header">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    <h3 class="card-header-title">Riwayat Membership</h3>
                </div>
            </div>
            <div class="p-0">
                @if($participant->membershipHistories->count())
                    <div class="divide-y divide-gray-100">
                        @foreach($participant->membershipHistories as $h)
                        <div class="flex items-center justify-between px-6 py-3.5">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ ucwords(str_replace('_', ' ', $h->membership_type)) }}</p>
                                <p class="text-xs text-gray-400">{{ $h->start_date->format('d/m/Y') }} - {{ $h->end_date->format('d/m/Y') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900">Rp {{ number_format($h->price, 0, ',', '.') }}</p>
                                <span class="badge {{ $h->status === 'active' ? 'badge-success' : 'badge-secondary' }}">{{ $h->status }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state py-8">
                        <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        <p class="empty-state-title">Belum ada riwayat membership</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                    <h3 class="card-header-title">Event Terdaftar</h3>
                </div>
            </div>
            <div class="p-0">
                @if($participant->eventParticipants->count())
                    <div class="divide-y divide-gray-100">
                        @foreach($participant->eventParticipants as $ep)
                        <div class="flex items-center justify-between px-6 py-3.5">
                            <span class="text-sm text-gray-900">{{ $ep->event->title ?? 'Event #'.$ep->event_id }}</span>
                            <span class="badge {{ $ep->payment_status === 'confirmed' ? 'badge-success' : 'badge-warning' }}">{{ $ep->payment_status }}</span>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state py-8">
                        <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                        <p class="empty-state-title">Belum terdaftar di event apapun</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="flex items-center gap-3 mt-6">
    <a href="{{ route('admin.participants.edit', $participant->id) }}" class="btn btn-warning">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
        Edit
    </a>
    <a href="{{ route('admin.participants.index') }}" class="btn btn-secondary">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/></svg>
        Kembali
    </a>
</div>
@endsection
