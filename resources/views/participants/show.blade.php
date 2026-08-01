@extends('layouts.app')

@section('title', 'Detail Peserta')
@section('subtitle', $participant->name)

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="card">
        <div class="card-header">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                <h3 class="card-header-title">Data Peserta</h3>
            </div>
        </div>
        <div class="card-body">
            <dl class="divide-y divide-gray-100 dark:divide-slate-700/60">
                @if($participant->user?->avatar)
                <div class="flex justify-between py-3 items-center">
                    <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Foto Profil</dt>
                    <dd>
                        <img src="{{ asset('storage/'.$participant->user->avatar) }}" alt="{{ $participant->name }}" class="w-16 h-16 rounded-full object-cover border border-gray-200 dark:border-slate-700">
                    </dd>
                </div>
                @endif
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Nama</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-slate-100">{{ $participant->name }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Email</dt>
                    <dd class="text-sm text-gray-900 dark:text-slate-100">{{ $participant->email }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Phone</dt>
                    <dd class="text-sm text-gray-900 dark:text-slate-100">{{ $participant->phone ?? '-' }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Gender</dt>
                    <dd class="text-sm text-gray-900 dark:text-slate-100">{{ $participant->gender === 'male' ? 'Laki-laki' : ($participant->gender === 'female' ? 'Perempuan' : '-') }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Tgl Lahir</dt>
                    <dd class="text-sm text-gray-900 dark:text-slate-100">{{ $participant->date_of_birth?->format('d/m/Y') ?? '-' }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Blood Type</dt>
                    <dd class="text-sm text-gray-900 dark:text-slate-100">{{ $participant->blood_type ?? '-' }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Jersey Size</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-slate-100">{{ $participant->jersey_size ?? '-' }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Emergency Contact</dt>
                    <dd class="text-sm text-gray-900 dark:text-slate-100">{{ $participant->emergency_contact ?? '-' }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Emergency Phone</dt>
                    <dd class="text-sm text-gray-900 dark:text-slate-100">{{ $participant->emergency_phone ?? '-' }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Medical Conditions</dt>
                    <dd class="text-sm text-gray-900 dark:text-slate-100">{{ $participant->medical_conditions ?? '-' }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Membership</dt>
                    <dd>
                        @if($participant->membership_type !== 'none')
                            <span class="badge badge-success">{{ $participant->membershipTypeLabel() }}</span>
                        @else
                            <span class="badge badge-secondary">None</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Member s/d</dt>
                    <dd class="text-sm text-gray-900 dark:text-slate-100">{{ $participant->membership_end_date?->format('d/m/Y') ?? '-' }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Total Events</dt>
                    <dd class="text-sm font-semibold text-gray-900 dark:text-slate-100">{{ $participant->total_events_participated }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card">
            <div class="card-header">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z"/></svg>
                    <h3 class="card-header-title">QR Attendance</h3>
                    <a href="{{ route('admin.attendance.scan') }}" class="btn btn-primary btn-xs ml-auto">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Scan
                    </a>
                </div>
            </div>
            <div class="p-0">
                @if($participant->eventParticipants->count())
                    <div class="grid grid-cols-1 gap-px bg-gray-100 sm:grid-cols-2 dark:bg-slate-700/60">
                        @foreach($participant->eventParticipants as $ep)
                        <div class="flex flex-col items-center gap-3 bg-white px-4 py-5 text-center dark:bg-slate-800">
                            @if($ep->qr_code)
                                <div class="rounded-xl border border-gray-200 bg-white p-2 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                    {!! QrCode::format('svg')->size(96)->margin(1)->generate($ep->qr_code) !!}
                                </div>
                            @else
                                <div class="flex h-24 w-24 items-center justify-center rounded-xl border border-dashed border-gray-300 text-gray-300 dark:border-slate-600 dark:text-slate-300">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5z"/></svg>
                                </div>
                            @endif
                            <div>
                                <p class="text-sm font-medium text-gray-900 truncate max-w-[180px] dark:text-slate-100">{{ $ep->event->title ?? 'Event #'.$ep->event_id }}</p>
                                @if($ep->qr_code)
                                    <code class="mt-1 block truncate max-w-[180px] rounded bg-gray-50 px-1.5 py-0.5 font-mono text-[10px] text-gray-500 dark:bg-slate-800 dark:text-slate-400">{{ $ep->qr_code }}</code>
                                @else
                                    <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">QR belum tersedia</p>
                                @endif
                            </div>
                            @if($ep->qr_code)
                                <form action="{{ route('admin.attendance.generate-qr', $ep->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-xs" title="Buat ulang QR" aria-label="Buat ulang QR">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                        Buat Ulang
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('admin.attendance.generate-qr', $ep->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-xs">Generate QR</button>
                                </form>
                            @endif
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state py-8">
                        <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5z"/></svg>
                        <p class="empty-state-title">Belum ada QR</p>
                        <p class="empty-state-text">QR akan muncul saat peserta mendaftar di sebuah event.</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    <h3 class="card-header-title">Riwayat Membership</h3>
                </div>
            </div>
            <div class="p-0">
                @if($participant->membershipHistories->count())
                    <div class="divide-y divide-gray-100 dark:divide-slate-700/60">
                        @foreach($participant->membershipHistories as $h)
                        <div class="flex items-center justify-between px-6 py-3.5">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-slate-100">{{ $h->planLabel() }}</p>
                                <p class="text-xs text-gray-400 dark:text-slate-500">{{ $h->start_date->format('d/m/Y') }} - {{ $h->end_date->format('d/m/Y') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900 dark:text-slate-100">Rp {{ number_format($h->price, 0, ',', '.') }}</p>
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
                    <svg class="w-5 h-5 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                    <h3 class="card-header-title">Event Terdaftar</h3>
                </div>
            </div>
            <div class="p-0">
                @if($participant->eventParticipants->count())
                    <div class="divide-y divide-gray-100 dark:divide-slate-700/60">
                        @foreach($participant->eventParticipants as $ep)
                        <div class="flex items-center justify-between px-6 py-3.5">
                            <span class="text-sm text-gray-900 dark:text-slate-100">{{ $ep->event->title ?? 'Event #'.$ep->event_id }}</span>
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
