@extends('layouts.app')

@section('title', 'Inventory')
@section('subtitle', 'Kelola aset inventaris klub')

@section('breadcrumb')
    @include('includes.breadcrumb', ['items' => [['label' => 'Inventory']]])
@endsection

@section('actions')
    <a href="{{ route('admin.inventory.loans.index') }}" class="btn btn-secondary btn-sm">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
        </svg>
        Peminjaman
    </a>
    <a href="{{ route('admin.inventory.external-loans.index') }}" class="btn btn-secondary btn-sm">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
        </svg>
        Pinjaman barang
    </a>
    @if (in_array(auth()->user()->role, config('sh3.inventory_manage_roles'), true))
        <a href="{{ route('admin.inventory.create') }}" class="btn btn-primary btn-sm">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Tambah Item
        </a>
    @endif
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <div class="flex w-full flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <form action="{{ route('admin.inventory.index') }}" method="GET" class="relative sm:w-72" role="search">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari aset, nama, serial..." class="search-input" aria-label="Cari item inventaris">
                </form>

                <form action="{{ route('admin.inventory.index') }}" method="GET" class="flex flex-wrap items-center gap-2">
                    @if (!empty($filters['search']))
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                    @endif
                    <div class="relative">
                        <select name="status" onchange="this.form.submit()" class="form-select py-2 text-sm" aria-label="Filter status">
                            <option value="">Semua Status</option>
                            @foreach (\App\Models\InventoryItem::STATUSES as $status)
                                <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $status)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
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
                        <select name="condition" onchange="this.form.submit()" class="form-select py-2 text-sm" aria-label="Filter kondisi">
                            <option value="">Semua Kondisi</option>
                            @foreach (\App\Models\InventoryItem::CONDITIONS as $cond)
                                <option value="{{ $cond }}" {{ ($filters['condition'] ?? '') === $cond ? 'selected' : '' }}>
                                    {{ ucfirst($cond) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if (!empty($filters['search']) || !empty($filters['status']) || !empty($filters['category_id']) || !empty($filters['condition']))
                        <a href="{{ route('admin.inventory.index') }}" class="filter-chip" aria-label="Reset filter">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Reset
                        </a>
                    @endif
                    <noscript><button type="submit" class="btn btn-secondary btn-sm">Terapkan</button></noscript>
                </form>
            </div>

            <span class="badge badge-secondary shrink-0">{{ $items->total() }} item</span>
        </div>
    </div>

    <div class="table-wrap w-full max-w-full overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700/80">
        <table class="min-w-full whitespace-nowrap">
            <thead>
                <tr>
                    <th>#</th>
                    <x-th-sort column="asset_code">Asset Code</x-th-sort>
                    <x-th-sort column="name">Nama</x-th-sort>
                    <th>Kategori</th>
                    <x-th-sort column="status">Status</x-th-sort>
                    <x-th-sort column="condition">Kondisi</x-th-sort>
                    <x-th-sort column="location">Lokasi</x-th-sort>
                    <x-th-sort column="created_at">Terakhir Diubah</x-th-sort>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td class="text-slate-400 dark:text-slate-500">{{ $items->firstItem() + $loop->index }}</td>
                        <td class="font-mono text-sm font-medium text-slate-900 dark:text-slate-100">{{ $item->asset_code }}</td>
                        <td class="font-medium text-slate-900 dark:text-slate-100">{{ $item->name }}</td>
                        <td>
                            @if ($item->category)
                                <span class="badge badge-blue">{{ $item->category->name }}</span>
                            @else
                                <span class="text-slate-400 dark:text-slate-500">-</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $statusBadge = match ($item->status) {
                                    'available' => 'badge-success',
                                    'borrowed' => 'badge-info',
                                    'in_maintenance' => 'badge-warning',
                                    'lost' => 'badge-danger',
                                    'retired' => 'badge-secondary',
                                    default => 'badge-secondary',
                                };
                            @endphp
                            <span class="badge {{ $statusBadge }}">{{ ucwords(str_replace('_', ' ', $item->status)) }}</span>
                        </td>
                        <td>
                            @php
                                $condBadge = match ($item->condition) {
                                    'excellent' => 'badge-success',
                                    'good' => 'badge-info',
                                    'fair' => 'badge-warning',
                                    'damaged' => 'badge-danger',
                                    'critical' => 'badge-danger',
                                    default => 'badge-secondary',
                                };
                            @endphp
                            <span class="badge {{ $condBadge }}">{{ ucfirst($item->condition) }}</span>
                        </td>
                        <td>{{ $item->location ?: '-' }}</td>
                        <td>{{ $item->updated_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.inventory.show', $item->id) }}" class="icon-btn" title="Detail" aria-label="Lihat detail item">
                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </a>
                                @if (in_array(auth()->user()->role, config('sh3.inventory_manage_roles'), true))
                                    <a href="{{ route('admin.inventory.edit', $item->id) }}" class="icon-btn" title="Edit" aria-label="Edit item">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.inventory.destroy', $item->id) }}" method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="icon-btn text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-500/10" title="Hapus" aria-label="Hapus item" onclick="return confirm('Hapus item inventaris ini beserta foto-fotonya?')">
                                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5V18a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18V7.5m18 0A2.25 2.25 0 0018.75 5.25H5.25A2.25 2.25 0 003 7.5m18 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 9.671A2.25 2.25 0 012.25 7.757V7.5"/>
                                    </svg>
                                </div>
                                @if (!empty($filters['search']) || !empty($filters['status']) || !empty($filters['category_id']) || !empty($filters['condition']))
                                    <p class="empty-state-title">Tidak ada hasil pencarian</p>
                                    <p class="empty-state-text">Tidak ditemukan item yang cocok dengan filter Anda. Coba ubah kata kunci atau reset filter.</p>
                                    <a href="{{ route('admin.inventory.index') }}" class="btn btn-secondary btn-sm mt-4">Reset Filter</a>
                                @else
                                    <p class="empty-state-title">Belum ada item inventaris</p>
                                    <p class="empty-state-text">Tambah item pertama untuk mulai mencatat aset klub.</p>
                                    @if (in_array(auth()->user()->role, config('sh3.inventory_manage_roles'), true))
                                        <a href="{{ route('admin.inventory.create') }}" class="btn btn-primary btn-sm mt-4">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                            Tambah Item
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

    @include('includes.pagination', ['items' => $items, 'label' => 'item'])
</div>
@endsection
