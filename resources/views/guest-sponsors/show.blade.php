@extends('layouts.app')

@section('title', 'Detail Guest Sponsor')
@section('subtitle', $guestSponsor->user->username)

@section('content')
<div class="mx-auto w-full max-w-3xl">
    @if(session('new_username'))
        <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/30 dark:bg-emerald-500/10">
            <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">Akun baru berhasil dibuat. Simpan kredensial berikut — hanya ditampilkan sekali.</p>
            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="rounded-xl bg-white/70 p-3 dark:bg-slate-800/60">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Username</p>
                    <p class="mt-0.5 font-mono text-sm font-semibold text-slate-900 dark:text-slate-100">{{ session('new_username') }}</p>
                </div>
                <div class="rounded-xl bg-white/70 p-3 dark:bg-slate-800/60">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Password</p>
                    <p class="mt-0.5 font-mono text-sm font-semibold text-slate-900 dark:text-slate-100">{{ session('new_password') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('new_password') && !session('new_username'))
        <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/30 dark:bg-amber-500/10">
            <p class="text-sm font-semibold text-amber-700 dark:text-amber-300">Password baru: <span class="font-mono">{{ session('new_password') }}</span></p>
        </div>
    @endif

    <div class="card">
        <div class="card-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="card-header-title">{{ $guestSponsor->user->name ?? $guestSponsor->user->username }}</h3>
                <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">{{ $guestSponsor->sponsor->name ?? '-' }} &middot; {{ $guestSponsor->event->title ?? '-' }}</p>
            </div>
            <div class="flex items-center gap-1.5">
                <form action="{{ route('admin.guest-sponsors.toggle-active', $guestSponsor->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn {{ $guestSponsor->is_active ? 'btn-warning' : 'btn-success' }} btn-sm">
                        {{ $guestSponsor->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </form>
                <form action="{{ route('admin.guest-sponsors.destroy', $guestSponsor->id) }}" method="POST" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Hapus akun guest sponsor ini? Data attendance turut terhapus.')">Hapus</button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 p-5 md:grid-cols-2">
            <div>
                <dl class="space-y-3">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">Username</dt>
                        <dd class="font-mono text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $guestSponsor->user->username }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">Sponsor</dt>
                        <dd class="text-sm text-slate-900 dark:text-slate-100">{{ $guestSponsor->sponsor->name ?? '-' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">Event</dt>
                        <dd class="text-right text-sm text-slate-900 dark:text-slate-100">{{ $guestSponsor->event->title ?? '-' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">Berlaku Dari</dt>
                        <dd class="text-sm text-slate-900 dark:text-slate-100">{{ $guestSponsor->valid_from ? $guestSponsor->valid_from->format('d/m/Y') : '-' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">Berlaku Sampai</dt>
                        <dd class="text-sm text-slate-900 dark:text-slate-100">{{ $guestSponsor->valid_until ? $guestSponsor->valid_until->format('d/m/Y') : '-' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">Status</dt>
                        <dd>
                            @if(!$guestSponsor->is_active)
                                <span class="badge badge-secondary">Nonaktif</span>
                            @elseif($guestSponsor->isExpired())
                                <span class="badge badge-danger">Kedaluwarsa</span>
                            @else
                                <span class="badge badge-success">Aktif</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">Dibuat Oleh</dt>
                        <dd class="text-sm text-slate-900 dark:text-slate-100">{{ $guestSponsor->createdBy->name ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="flex flex-col items-center justify-center rounded-2xl border border-slate-100 bg-slate-50/50 p-5 dark:border-slate-700/60 dark:bg-slate-800/30">
                <div class="rounded-xl bg-white p-3 shadow-sm dark:bg-slate-700">
                    {!! QrCode::format('svg')->size(140)->margin(1)->generate($guestSponsor->qr_code) !!}
                </div>
                <p class="mt-3 font-mono text-xs text-slate-500 dark:text-slate-400">{{ $guestSponsor->qr_code }}</p>
                <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">QR untuk attendance oleh petugas event</p>
            </div>
        </div>
    </div>

    <div class="card mt-6">
        <div class="card-header">
            <h3 class="card-header-title">Edit Akun</h3>
        </div>
        <form action="{{ route('admin.guest-sponsors.update', $guestSponsor->id) }}" method="POST" class="p-5 sm:p-6">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div class="form-group">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" value="{{ old('name', $guestSponsor->user->name) }}" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Password Baru (opsional)</label>
                    <input type="text" name="password" value="{{ old('password') }}" class="form-input" placeholder="Kosongkan bila tidak diganti">
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Berlaku Dari</label>
                    <input type="date" name="valid_from" value="{{ old('valid_from', $guestSponsor->valid_from?->toDateString()) }}" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Berlaku Sampai</label>
                    <input type="date" name="valid_until" value="{{ old('valid_until', $guestSponsor->valid_until?->toDateString()) }}" class="form-input">
                </div>
                <div class="form-group md:col-span-2">
                    <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <input type="checkbox" name="is_active" value="1" {{ $guestSponsor->is_active ? 'checked' : '' }} class="form-checkbox rounded border-slate-300">
                        Akun aktif (dapat login dan check-in)
                    </label>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-3">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="{{ route('admin.guest-sponsors.index') }}" class="btn btn-secondary">Kembali</a>
            </div>
        </form>
    </div>
</div>
@endsection