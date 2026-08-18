@extends('layouts.app')

@section('title', 'Tambah Akun Guest Sponsor')
@section('subtitle', 'Buat akun login dan QR Code untuk sponsorship event')

@section('content')
<div class="mx-auto w-full max-w-3xl">
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-header-title">Tambah Akun Guest Sponsor</h3>
                <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">Username, password, dan QR Code dibuat otomatis bila tidak diisi</p>
            </div>
        </div>

        <form action="{{ route('admin.guest-sponsors.store') }}" method="POST" class="p-5 sm:p-6">
            @csrf
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div class="form-group">
                    <label class="form-label">Sponsor <span class="text-red-600">*</span></label>
                    <select name="sponsor_id" id="sponsor_id" class="form-select">
                        <option value="">Pilih Sponsor</option>
                        @foreach($sponsors as $sponsor)
                            <option value="{{ $sponsor['sponsor_id'] }}" {{ old('sponsor_id') == $sponsor['sponsor_id'] ? 'selected' : '' }}>{{ $sponsor['sponsor_name'] }}</option>
                        @endforeach
                    </select>
                    @error('sponsor_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Event <span class="text-red-600">*</span></label>
                    <select name="event_id" id="event_id" class="form-select">
                        <option value="">Pilih Event</option>
                        @foreach($quotas as $quota)
                            <option value="{{ $quota['event_id'] }}" data-sponsor="{{ $quota['sponsor_id'] }}" {{ old('event_id') == $quota['event_id'] && old('sponsor_id') == $quota['sponsor_id'] ? 'selected' : '' }}>{{ $quota['event_title'] }}</option>
                        @endforeach
                    </select>
                    @error('event_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-input" placeholder="Nama perwakilan sponsor">
                </div>
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" value="{{ old('username') }}" class="form-input" placeholder="Otomatis bila kosong">
                    @error('username') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-input" placeholder="Opsional">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="text" name="password" value="{{ old('password') }}" class="form-input" placeholder="Otomatis bila kosong">
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Berlaku Dari</label>
                    <input type="date" name="valid_from" value="{{ old('valid_from', now()->toDateString()) }}" class="form-input">
                    @error('valid_from') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Berlaku Sampai</label>
                    <input type="date" name="valid_until" value="{{ old('valid_until') }}" class="form-input">
                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Kosongkan untuk otomatis mengikuti tanggal selesai event</p>
                    @error('valid_until') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button type="submit" class="btn btn-primary">Buat Akun</button>
                <a href="{{ route('admin.guest-sponsors.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var sponsorSelect = document.getElementById('sponsor_id');
        var eventSelect = document.getElementById('event_id');

        if (!sponsorSelect || !eventSelect) return;

        var options = Array.prototype.slice.call(eventSelect.options).filter(function (o) {
            return o.value !== '';
        });

        function filterEvents() {
            var sponsorId = sponsorSelect.value;
            var kept = options.filter(function (o) {
                return o.getAttribute('data-sponsor') === sponsorId;
            });
            var selected = eventSelect.value;

            eventSelect.innerHTML = '';
            var placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Pilih Event';
            eventSelect.appendChild(placeholder);

            kept.forEach(function (o) {
                eventSelect.appendChild(o);
            });

            if (selected && kept.some(function (o) { return o.value === selected; })) {
                eventSelect.value = selected;
            }
        }

        sponsorSelect.addEventListener('change', filterEvents);
    })();
</script>
@endpush
@endsection