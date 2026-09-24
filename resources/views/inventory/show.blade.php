@extends('layouts.app')

@section('title', 'Detail Item Inventaris')
@section('subtitle', $item->name)

@section('breadcrumb')
    @include('includes.breadcrumb', [
        'items' => [
            ['label' => 'Inventory', 'url' => route('admin.inventory.index')],
            ['label' => $item->asset_code],
        ],
    ])
@endsection

@section('actions')
    @if (in_array(auth()->user()->role, config('sh3.inventory_manage_roles'), true))
        <a href="{{ route('admin.inventory.edit', $item->id) }}" class="btn btn-warning btn-sm">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
            Edit
        </a>
    @endif
@endsection

@section('content')
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- Detail --}}
    <div class="card lg:col-span-2">
        <div class="card-header">
            <h3 class="card-header-title">Detail Item</h3>
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
        </div>
        <div class="card-body">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Asset Code</dt>
                    <dd class="mt-0.5 font-mono text-sm font-medium text-slate-900 dark:text-slate-100">{{ $item->asset_code }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Nama</dt>
                    <dd class="mt-0.5 text-sm font-medium text-slate-900 dark:text-slate-100">{{ $item->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Kategori</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $item->category?->name ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Serial Number</dt>
                    <dd class="mt-0.5 font-mono text-sm text-slate-700 dark:text-slate-300">{{ $item->serial_number ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Brand</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $item->brand ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Model</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $item->model ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Kondisi</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ ucfirst($item->condition) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Lokasi</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $item->location ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Tanggal Pembelian</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $item->purchase_date?->format('d/m/Y') ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Harga Pembelian</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $item->purchase_price !== null ? 'Rp '.number_format($item->purchase_price, 0, ',', '.') : '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Garansi Berakhir</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $item->warranty_expiry?->format('d/m/Y') ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Terakhir Diubah</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $item->updated_at->format('d/m/Y H:i') }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Deskripsi</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $item->description ?: '-' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Catatan</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $item->notes ?: '-' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Foto --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-header-title">Foto ({{ $item->photos->count() }})</h3>
        </div>
        <div class="card-body space-y-6">
            @if (in_array(auth()->user()->role, config('sh3.inventory_manage_roles'), true))
                <form action="{{ route('admin.inventory.photos.store', $item->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Upload Foto</label>
                        <input type="file" name="photo" accept="image/*" required class="form-input @error('photo') error @enderror">
                        @error('photo') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Konteks</label>
                        <select name="context" class="form-select @error('context') error @enderror">
                            @foreach (\App\Models\InventoryPhoto::CONTEXTS as $ctx)
                                <option value="{{ $ctx }}" {{ old('context') === $ctx ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $ctx)) }}
                                </option>
                            @endforeach
                        </select>
                        @error('context') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Caption</label>
                        <input type="text" name="caption" value="{{ old('caption') }}" class="form-input @error('caption') error @enderror">
                        @error('caption') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-full">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                        Upload Foto
                    </button>
                </form>
                <div class="divider"></div>
            @endif

            @if ($item->photos->isEmpty())
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.41a2.25 2.25 0 013.182 0l2.909 2.91m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                    </div>
                    <p class="empty-state-title">Belum ada foto</p>
                    <p class="empty-state-text">Upload foto kondisi item.</p>
                </div>
            @else
                <div class="grid grid-cols-2 gap-3">
                    @foreach ($item->photos as $photo)
                        <div class="group relative overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700/80">
                            <img src="{{ asset('storage/'.$photo->file_path) }}" alt="{{ $photo->caption ?: $item->name }}" class="h-28 w-full object-cover" loading="lazy">
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-2">
                                <p class="truncate text-xs text-white">{{ ucwords(str_replace('_', ' ', $photo->context)) }}</p>
                            </div>
                            @if (in_array(auth()->user()->role, config('sh3.inventory_manage_roles'), true))
                                <form action="{{ route('admin.inventory.photos.destroy', $photo->id) }}" method="POST" class="absolute right-1 top-1">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="icon-btn bg-white/90 text-red-600 hover:bg-red-50 dark:bg-slate-900/90" title="Hapus foto" aria-label="Hapus foto" onclick="return confirm('Hapus foto ini?')">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Peminjaman --}}
<div class="card mt-6">
    <div class="card-header">
        <h3 class="card-header-title">Peminjaman ({{ $item->loans->count() }})</h3>
        <a href="{{ route('admin.inventory.loans.index') }}" class="btn btn-secondary btn-sm">Semua Peminjaman</a>
    </div>
    <div class="table-wrap w-full max-w-full overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700/80">
        <table class="min-w-full whitespace-nowrap">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Peminjam</th>
                    <th>Tujuan</th>
                    <th>Status</th>
                    <th>Tanggal Pinjam</th>
                    <th>Perkiraan Kembali</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($item->loans as $loan)
                    <tr>
                        <td class="text-slate-400 dark:text-slate-500">{{ $loan->id }}</td>
                        <td class="font-medium text-slate-900 dark:text-slate-100">{{ $loan->borrower_name }}</td>
                        <td>{{ $loan->purpose ?: '-' }}</td>
                        <td>
                            @php
                                $loanBadge = match ($loan->status) {
                                    'draft' => 'badge-secondary',
                                    'approved' => 'badge-blue',
                                    'borrowed' => 'badge-info',
                                    'returned' => 'badge-success',
                                    'cancelled' => 'badge-danger',
                                    default => 'badge-secondary',
                                };
                            @endphp
                            <span class="badge {{ $loanBadge }}">{{ ucwords($loan->status) }}</span>
                        </td>
                        <td>{{ $loan->borrow_date?->format('d/m/Y') ?: '-' }}</td>
                        <td>{{ $loan->expected_return_date?->format('d/m/Y') ?: '-' }}</td>
                        <td>
                            <div class="flex items-center justify-end">
                                <a href="{{ route('admin.inventory.loans.show', $loan->id) }}" class="icon-btn" title="Detail" aria-label="Lihat detail peminjaman">
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
                        <td colspan="7">
                            <div class="empty-state">
                                <p class="empty-state-title">Belum ada peminjaman</p>
                                <p class="empty-state-text">Item ini belum pernah dipinjam.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
