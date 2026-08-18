@extends('layouts.app')

@section('title', 'Guest Sponsor')
@section('subtitle', 'Kelola akun guest sponsor dan kuota sponsorship')

@section('content')
<div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
    <div class="stat-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Akun</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ $totalAccounts }}</p>
            </div>
            <div class="stat-icon bg-slate-50 text-slate-600 dark:bg-slate-500/10 dark:text-slate-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Akun Aktif</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">{{ $activeAccounts }}</p>
            </div>
            <div class="stat-icon bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Kedaluwarsa</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-red-600 dark:text-red-400">{{ $expiredAccounts }}</p>
            </div>
            <div class="stat-icon bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            </div>
        </div>
    </div>
</div>

<div class="card mt-6">
    <div class="card-header">
        <div>
            <h3 class="card-header-title">Kuota Akun per Sponsor / Event</h3>
            <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">Tentukan jumlah maksimum akun guest sponsor untuk setiap sponsor pada event terkait</p>
        </div>
    </div>

    <form action="{{ route('admin.guest-sponsors.quota') }}" method="POST" class="border-b border-slate-100 px-4 py-4 dark:border-slate-700/60">
        @csrf
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="form-group mb-0">
                <label class="form-label">Sponsor</label>
                <select name="sponsor_id" class="form-select">
                    <option value="">Pilih Sponsor</option>
                    @foreach($sponsors as $sponsor)
                        <option value="{{ $sponsor->id }}" {{ old('sponsor_id') == $sponsor->id ? 'selected' : '' }}>{{ $sponsor->name }}</option>
                    @endforeach
                </select>
                @error('sponsor_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Event</label>
                <select name="event_id" class="form-select">
                    <option value="">Pilih Event</option>
                    @foreach($events as $event)
                        <option value="{{ $event->id }}" {{ old('event_id') == $event->id ? 'selected' : '' }}>{{ $event->title }}</option>
                    @endforeach
                </select>
                @error('event_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Maksimum Akun</label>
                <input type="number" name="max_guest_accounts" value="{{ old('max_guest_accounts') }}" min="0" max="1000" class="form-input" placeholder="0">
                @error('max_guest_accounts') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn btn-primary btn-sm w-full">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Set Kuota
                </button>
            </div>
        </div>
    </form>

    @if(count($quotas) > 0)
    <div class="table-wrap w-full max-w-full overflow-x-auto">
        <table class="min-w-full whitespace-nowrap">
            <thead>
                <tr>
                    <th>Sponsor</th>
                    <th>Event</th>
                    <th>Maksimum</th>
                    <th>Terpakai</th>
                    <th>Sisa</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quotas as $quota)
                <tr>
                    <td>{{ $quota['sponsor_name'] }}</td>
                    <td>{{ $quota['event_title'] }}</td>
                    <td class="font-medium text-slate-900 dark:text-slate-100">{{ $quota['max'] }}</td>
                    <td>{{ $quota['used'] }}</td>
                    <td>
                        @if($quota['remaining'] > 0)
                            <span class="badge badge-success">{{ $quota['remaining'] }}</span>
                        @else
                            <span class="badge badge-danger">0</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

<div class="card mt-6">
    <div class="card-header">
        <div>
            <h3 class="card-header-title">Daftar Akun Guest Sponsor</h3>
            <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">Akun login (username/password) dan QR Code untuk attendance event terkait</p>
        </div>
        <a href="{{ route('admin.guest-sponsors.create') }}" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Tambah Akun
        </a>
    </div>

    <div class="table-wrap w-full max-w-full overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700/80">
        <table class="min-w-full whitespace-nowrap">
            <thead>
                <tr>
                    <th>#</th>
                    <x-th-sort column="id">Username</x-th-sort>
                    <th>Sponsor</th>
                    <th>Event</th>
                    <th>QR Code</th>
                    <x-th-sort column="valid_until">Masa Berlaku</x-th-sort>
                    <x-th-sort column="is_active">Status</x-th-sort>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                <tr>
                    <td>{{ $entries->firstItem() + $loop->index }}</td>
                    <td class="font-medium text-gray-900 dark:text-slate-100">{{ $entry->user->username }}</td>
                    <td>{{ $entry->sponsor->name ?? '-' }}</td>
                    <td>{{ $entry->event->title ?? '-' }}</td>
                    <td><code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-700 dark:bg-slate-700 dark:text-slate-200">{{ $entry->qr_code }}</code></td>
                    <td>{{ $entry->valid_until ? $entry->valid_until->format('d/m/Y') : '-' }}</td>
                    <td>
                        @if(!$entry->is_active)
                            <span class="badge badge-secondary">Nonaktif</span>
                        @elseif($entry->isExpired())
                            <span class="badge badge-danger">Kedaluwarsa</span>
                        @else
                            <span class="badge badge-success">Aktif</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('admin.guest-sponsors.show', $entry->id) }}" class="btn btn-info btn-xs">Detail</a>
                            <form action="{{ route('admin.guest-sponsors.toggle-active', $entry->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="btn {{ $entry->is_active ? 'btn-warning' : 'btn-success' }} btn-xs" onclick="return confirm('{{ $entry->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun ini?')">
                                    {{ $entry->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                            <form action="{{ route('admin.guest-sponsors.destroy', $entry->id) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Hapus akun guest sponsor ini? Data attendance turut terhapus.')">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                            <p class="empty-state-title">Belum ada akun guest sponsor</p>
                            <p class="empty-state-text">Atur kuota terlebih dahulu, lalu buat akun guest sponsor.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($entries->hasPages())
        <div class="pagination">
            <div class="pagination-info">
                Showing {{ $entries->firstItem() }} to {{ $entries->lastItem() }} of {{ $entries->total() }} records
            </div>
            <div class="pagination-links">{{ $entries->links() }}</div>
        </div>
    @endif
</div>
@endsection