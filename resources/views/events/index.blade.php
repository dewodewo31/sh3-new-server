@extends('layouts.app')

@section('title', 'Events')
@section('subtitle', 'Kelola seluruh event lari klub Anda')

@section('breadcrumb')
    @include('includes.breadcrumb', ['items' => [['label' => 'Events']]])
@endsection

@section('actions')
    <a href="{{ route('admin.events.create') }}" class="btn btn-primary btn-sm">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Tambah Event
    </a>
@endsection

@section('content')
<div class="card">
    {{-- Toolbar: search + filter --}}
    <div class="card-header">
        <div class="flex w-full flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <form action="{{ route('admin.events.index') }}" method="GET" class="relative sm:w-72" role="search">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari event..." class="search-input" aria-label="Cari event">
                </form>

                <form action="{{ route('admin.events.index') }}" method="GET" class="flex flex-wrap items-center gap-2">
                    @if (!empty($filters['search']))
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                    @endif
                    <div class="relative">
                        <select name="category_id" onchange="this.form.submit()" class="form-select py-2 text-sm" aria-label="Filter kategori">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ ($filters['category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="relative">
                        <select name="status" onchange="this.form.submit()" class="form-select py-2 text-sm" aria-label="Filter status">
                            <option value="">Semua Status</option>
                            @foreach (['draft', 'publish', 'ongoing', 'completed', 'cancelled'] as $status)
                                <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>
                                    {{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if (!empty($filters['category_id']) || !empty($filters['status']))
                        <a href="{{ route('admin.events.index') }}" class="filter-chip" aria-label="Reset filter">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Reset
                        </a>
                    @endif
                    <noscript><button type="submit" class="btn btn-secondary btn-sm">Terapkan</button></noscript>
                </form>
            </div>

            <span class="badge badge-secondary shrink-0">{{ $events->total() }} event</span>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-wrap w-full max-w-full overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700/80">
        <table class="min-w-full whitespace-nowrap">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Tanggal</th>
                    <th>Kuota</th>
                    <th>Harga</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($events as $event)
                    <tr>
                        <td class="text-slate-400 dark:text-slate-500">{{ $events->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-slate-900 dark:text-slate-100">{{ $event->title }}</p>
                                    @if ($event->location)
                                        <p class="truncate text-xs text-slate-400 dark:text-slate-500">{{ $event->location }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if ($event->category)
                                <span class="badge badge-blue">{{ $event->category->name }}</span>
                            @else
                                <span class="text-slate-400 dark:text-slate-500">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="text-slate-700 dark:text-slate-300">{{ $event->start_date->format('d/m/Y') }}</span>
                            <span class="block text-xs text-slate-400 dark:text-slate-500">{{ $event->start_date->format('H:i') }}</span>
                        </td>
                        <td>
                            @if ($event->quota)
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-700 dark:text-slate-300">{{ $event->remainingQuota() }}/{{ $event->quota }}</span>
                                    <span class="hidden w-12 overflow-hidden rounded-full bg-slate-100 sm:block dark:bg-slate-700/60">
                                        <span class="block h-1.5 rounded-full bg-blue-500" style="width: {{ min(100, ($event->quota - $event->remainingQuota()) * 100 / $event->quota) }}%"></span>
                                    </span>
                                </div>
                            @else
                                <span class="badge badge-secondary">Unlimited</span>
                            @endif
                        </td>
                        <td>
                            @if ($event->price)
                                <span class="font-medium text-slate-900 dark:text-slate-100">Rp {{ number_format($event->price, 0, ',', '.') }}</span>
                            @else
                                <span class="badge badge-success">Gratis</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $statusBadge = match ($event->status) {
                                    'publish' => 'badge-success',
                                    'draft' => 'badge-secondary',
                                    'ongoing' => 'badge-info',
                                    'completed' => 'badge-indigo',
                                    'cancelled' => 'badge-danger',
                                    default => 'badge-secondary',
                                };
                            @endphp
                            <span class="badge {{ $statusBadge }}">
                                <span class="badge-dot {{ $event->status === 'publish' ? 'bg-emerald-500' : ($event->status === 'draft' ? 'bg-slate-400' : ($event->status === 'ongoing' ? 'bg-sky-500' : ($event->status === 'completed' ? 'bg-indigo-500' : 'bg-red-500'))) }}"></span>
                                {{ ucfirst($event->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.events.show', $event->id) }}" class="icon-btn" title="Detail" aria-label="Lihat detail event">
                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('admin.events.edit', $event->id) }}" class="icon-btn" title="Edit" aria-label="Edit event">
                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/>
                                    </svg>
                                </a>
                                @if ($event->status === 'draft')
                                    <form action="{{ route('admin.events.publish', $event->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="icon-btn text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 dark:text-emerald-400 dark:hover:bg-emerald-500/10" title="Publikasikan" aria-label="Publikasikan event">
                                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L9.25 15.25L18 6.75"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                                <button type="button" class="icon-btn text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-500/10" title="Hapus" aria-label="Hapus event"
                                        @click="$dispatch('open-delete-modal', { id: {{ $event->id }}, title: @js($event->title) })">
                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                                    </svg>
                                </div>
                                @if (!empty($filters['search']) || !empty($filters['category_id']) || !empty($filters['status']))
                                    <p class="empty-state-title">Tidak ada hasil pencarian</p>
                                    <p class="empty-state-text">Tidak ditemukan event yang cocok dengan filter Anda. Coba ubah kata kunci atau reset filter.</p>
                                    <a href="{{ route('admin.events.index') }}" class="btn btn-secondary btn-sm mt-4">Reset Filter</a>
                                @else
                                    <p class="empty-state-title">Belum ada event</p>
                                    <p class="empty-state-text">Buat event pertama Anda untuk mulai menjangkau peserta.</p>
                                    <a href="{{ route('admin.events.create') }}" class="btn btn-primary btn-sm mt-4">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                        Tambah Event
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('includes.pagination', ['items' => $events, 'label' => 'event'])
</div>

{{-- Modal hapus event --}}
<div x-data="{ open: false, id: null, title: '' }"
     @open-delete-modal.window="open = true; id = $event.detail.id; title = $event.detail.title"
     x-cloak>
    <div x-show="open" class="modal-overlay" x-transition.opacity.duration.200ms>
        <div class="modal-panel" @click.outside="open = false" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
            <div class="modal-header">
                <h3 id="delete-modal-title" class="text-sm font-semibold text-slate-900 dark:text-slate-100">Hapus Event</h3>
                <button type="button" class="icon-btn" @click="open = false" aria-label="Tutup">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-slate-700 dark:text-slate-300">
                            Apakah Anda yakin ingin menghapus event <span class="font-semibold text-slate-900 dark:text-slate-100" x-text="title"></span>?
                            Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="open = false">Batal</button>
                <form :action="`{{ route('admin.events.destroy', 'PLACEHOLDER') }}`.replace('PLACEHOLDER', id)" method="POST" @submit="open = false; $nextTick(() => showToast('success', 'Berhasil', 'Event dihapus'))">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                        </svg>
                        Hapus Event
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
