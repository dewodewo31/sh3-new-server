@extends('layouts.app')

@section('title', 'Pinjaman Barang')
@section('subtitle', 'Barang yang dipinjam dari perusahaan / komunitas lain')

@section('breadcrumb')
    @include('includes.breadcrumb', [
        'items' => [
            ['label' => 'Inventaris', 'url' => route('admin.inventory.index')],
            ['label' => 'Pinjaman Barang'],
        ],
    ])
@endsection

@section('actions')
    @if (in_array(auth()->user()->role, config('sh3.inventory_manage_roles'), true))
        <a href="{{ route('admin.inventory.external-loans.create') }}" class="btn btn-primary btn-sm">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Buat Pinjaman
        </a>
    @endif
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <div class="flex w-full flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <form action="{{ route('admin.inventory.external-loans.index') }}" method="GET" class="relative sm:w-72" role="search">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari pihak, barang, tujuan..." class="search-input" aria-label="Cari pinjaman barang">
                </form>

                <form action="{{ route('admin.inventory.external-loans.index') }}" method="GET" class="flex flex-wrap items-center gap-2">
                    @if (!empty($filters['search']))
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                    @endif
                    <div class="relative">
                        <select name="status" onchange="this.form.submit()" class="form-select py-2 text-sm" aria-label="Filter status">
                            <option value="">Semua Status</option>
                            @foreach (\App\Models\InventoryExternalLoan::STATUSES as $status)
                                <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $status)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <label class="filter-chip cursor-pointer {{ !empty($filters['overdue']) ? 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400' : '' }}">
                        <input type="checkbox" name="overdue" value="1" class="hidden" onchange="this.form.submit()" {{ !empty($filters['overdue']) ? 'checked' : '' }}>
                        Terlambat
                    </label>
                    @if (!empty($filters['search']) || !empty($filters['status']) || !empty($filters['overdue']))
                        <a href="{{ route('admin.inventory.external-loans.index') }}" class="filter-chip" aria-label="Reset filter">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Reset
                        </a>
                    @endif
                    <noscript><button type="submit" class="btn btn-secondary btn-sm">Terapkan</button></noscript>
                </form>
            </div>

            <span class="badge badge-secondary shrink-0">{{ $loans->total() }} pinjaman</span>
        </div>
    </div>

    <div class="table-wrap w-full max-w-full overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700/80">
        <table class="min-w-full whitespace-nowrap">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Pihak Peminjam</th>
                    <th>Barang</th>
                    <th>Tujuan</th>
                    <th>Status</th>
                    <th>Tanggal Pinjam</th>
                    <th>Perkiraan Kembali</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($loans as $loan)
                    <tr>
                        <td class="text-slate-400 dark:text-slate-500">{{ $loans->firstItem() + $loop->index }}</td>
                        <td>
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ $loan->external_party }}</span>
                            @if ($loan->contact_name)
                                <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $loan->contact_name }}</span>
                            @endif
                        </td>
                        <td class="max-w-xs truncate">{{ $loan->items_description }}</td>
                        <td>{{ $loan->purpose ?: '-' }}</td>
                        <td>
                            @php
                                $statusBadge = match ($loan->status) {
                                    'draft' => 'badge-secondary',
                                    'approved' => 'badge-blue',
                                    'borrowed' => 'badge-info',
                                    'returned' => 'badge-success',
                                    'cancelled' => 'badge-danger',
                                    default => 'badge-secondary',
                                };
                            @endphp
                            <span class="badge {{ $statusBadge }}">{{ ucwords($loan->status) }}</span>
                            @if ($loan->isOverdue())
                                <span class="badge badge-danger">Terlambat</span>
                            @endif
                        </td>
                        <td>{{ $loan->borrow_date?->format('d/m/Y') ?: '-' }}</td>
                        <td>{{ $loan->expected_return_date?->format('d/m/Y') ?: '-' }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.inventory.external-loans.show', $loan->id) }}" class="icon-btn" title="Detail" aria-label="Lihat detail pinjaman">
                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                                    </svg>
                                </div>
                                @if (!empty($filters['search']) || !empty($filters['status']) || !empty($filters['overdue']))
                                    <p class="empty-state-title">Tidak ada hasil pencarian</p>
                                    <p class="empty-state-text">Tidak ditemukan pinjaman yang cocok dengan filter Anda.</p>
                                    <a href="{{ route('admin.inventory.external-loans.index') }}" class="btn btn-secondary btn-sm mt-4">Reset Filter</a>
                                @else
                                    <p class="empty-state-title">Belum ada pinjaman barang</p>
                                    <p class="empty-state-text">Catat pinjaman barang dari perusahaan atau komunitas lain.</p>
                                    @if (in_array(auth()->user()->role, config('sh3.inventory_manage_roles'), true))
                                        <a href="{{ route('admin.inventory.external-loans.create') }}" class="btn btn-primary btn-sm mt-4">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                            Buat Pinjaman
                                        </a>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('includes.pagination', ['items' => $loans, 'label' => 'pinjaman barang'])
</div>
@endsection
