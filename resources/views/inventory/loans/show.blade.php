@extends('layouts.app')

@section('title', 'Detail Peminjaman')
@section('subtitle', $loan->borrower_name)

@section('breadcrumb')
    @include('includes.breadcrumb', [
        'items' => [
            ['label' => 'Inventory', 'url' => route('admin.inventory.index')],
            ['label' => 'Peminjaman', 'url' => route('admin.inventory.loans.index')],
            ['label' => '#'.$loan->id],
        ],
    ])
@endsection

@php
    $isManager = in_array(auth()->user()->role, config('sh3.inventory_manage_roles'), true);
@endphp

@section('content')
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- Detail --}}
    <div class="card lg:col-span-2">
        <div class="card-header">
            <h3 class="card-header-title">Detail Peminjaman</h3>
            <div class="flex items-center gap-2">
                @if ($loan->isOverdue())
                    <span class="badge badge-danger">Terlambat</span>
                @endif
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
            </div>
        </div>
        <div class="card-body">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Item</dt>
                    <dd class="mt-0.5 text-sm font-medium text-slate-900 dark:text-slate-100">
                        <a href="{{ route('admin.inventory.show', $loan->inventory_item_id) }}" class="text-primary hover:underline">{{ $loan->item?->asset_code }} — {{ $loan->item?->name }}</a>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Peminjam</dt>
                    <dd class="mt-0.5 text-sm font-medium text-slate-900 dark:text-slate-100">{{ $loan->borrower_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Tujuan</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $loan->purpose ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Event</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $loan->event?->name ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Tanggal Pinjam</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $loan->borrow_date?->format('d/m/Y') ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Perkiraan Kembali</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $loan->expected_return_date?->format('d/m/Y') ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Kondisi Sebelum</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $loan->condition_before ? ucfirst($loan->condition_before) : '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Kondisi Setelah</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $loan->condition_after ? ucfirst($loan->condition_after) : '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Tanggal Kembali Aktual</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $loan->actual_return_date?->format('d/m/Y') ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Diterima Oleh</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $loan->returnedReceivedBy?->name ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Dibuat Oleh</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $loan->createdBy?->name ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Disetujui</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $loan->approvedBy?->name ?: '-' }} {{ $loan->approved_at?->format('(d/m/Y H:i)') }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Catatan</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $loan->notes ?: '-' }}</dd>
                </div>
            </dl>

            @if ($isManager && ($loan->canCancel() || $loan->canReturn()))
                <div class="mt-6 flex flex-wrap gap-2 pt-4 divider">
                    @if ($loan->canCancel())
                        <form action="{{ route('admin.inventory.loans.cancel', $loan->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Batalkan peminjaman ini?')">Batalkan</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Aksi & Dokumen --}}
    <div class="space-y-6">
        @if ($isManager && $loan->canHandover())
            <div class="card">
                <div class="card-header">
                    <h3 class="card-header-title">Handover</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.inventory.loans.handover', $loan->id) }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Dari Lokasi *</label>
                            <input type="text" name="from_location" value="{{ old('from_location', 'Gudang') }}" required class="form-input @error('from_location') error @enderror">
                            @error('from_location') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Diterima Oleh (Nama) *</label>
                            <input type="text" name="to_name" value="{{ old('to_name', $loan->borrower_name) }}" required class="form-input @error('to_name') error @enderror">
                            @error('to_name') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kondisi Saat Handover *</label>
                            <select name="condition_at_handover" required class="form-select @error('condition_at_handover') error @enderror">
                                @foreach (\App\Models\InventoryItem::CONDITIONS as $cond)
                                    <option value="{{ $cond }}" {{ old('condition_at_handover') === $cond ? 'selected' : '' }}>{{ ucfirst($cond) }}</option>
                                @endforeach
                            </select>
                            @error('condition_at_handover') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Catatan</label>
                            <input type="text" name="notes" value="{{ old('notes') }}" class="form-input @error('notes') error @enderror">
                            @error('notes') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-full">Catat Handover</button>
                    </form>
                </div>
            </div>
        @endif

        @if ($isManager && $loan->canReturn())
            <div class="card">
                <div class="card-header">
                    <h3 class="card-header-title">Pengembalian</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.inventory.loans.return', $loan->id) }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Kondisi Saat Kembali *</label>
                            <select name="condition_after" required class="form-select @error('condition_after') error @enderror">
                                @foreach (\App\Models\InventoryItem::CONDITIONS as $cond)
                                    <option value="{{ $cond }}" {{ old('condition_after') === $cond ? 'selected' : '' }}>{{ ucfirst($cond) }}</option>
                                @endforeach
                            </select>
                            @error('condition_after') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="btn btn-success btn-sm w-full" onclick="return confirm('Konfirmasi pengembalian item ini?')">Catat Pengembalian</button>
                    </form>
                </div>
            </div>
        @endif

        @if ($isManager)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-header-title">Dokumen</h3>
                </div>
                <div class="card-body space-y-4">
                    <form action="{{ route('admin.inventory.loans.documents', $loan->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">File *</label>
                            <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required class="form-input @error('file') error @enderror">
                            @error('file') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jenis *</label>
                            <select name="type" required class="form-select @error('type') error @enderror">
                                @foreach (\App\Models\InventoryDocument::TYPES as $type)
                                    <option value="{{ $type }}" {{ old('type') === $type ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                                @endforeach
                            </select>
                            @error('type') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-full">Unggah Dokumen</button>
                    </form>

                    <div class="divider"></div>

                    @if ($loan->documents->isEmpty())
                        <p class="text-sm text-slate-500 dark:text-slate-400">Belum ada dokumen.</p>
                    @else
                        <ul class="space-y-2">
                            @foreach ($loan->documents as $document)
                                <li class="flex items-center justify-between gap-2 rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700/80">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ $document->original_name }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ ucwords(str_replace('_', ' ', $document->type)) }}</p>
                                    </div>
                                    <a href="{{ route('admin.inventory.documents.download', $document->id) }}" class="icon-btn shrink-0" title="Download" aria-label="Download dokumen">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Riwayat Handover --}}
<div class="card mt-6">
    <div class="card-header">
        <h3 class="card-header-title">Riwayat Handover ({{ $loan->handovers->count() }})</h3>
    </div>
    <div class="table-wrap w-full max-w-full overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700/80">
        <table class="min-w-full whitespace-nowrap">
            <thead>
                <tr>
                    <th>Urutan</th>
                    <th>Dari</th>
                    <th>Kepada</th>
                    <th>Pengirim</th>
                    <th>Penerima</th>
                    <th>Kondisi</th>
                    <th>Waktu</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($loan->handovers->sortBy('sequence') as $handover)
                    <tr>
                        <td class="font-mono text-sm">#{{ $handover->sequence }}</td>
                        <td>{{ $handover->from_location }}</td>
                        <td>{{ $handover->to_name ?: '-' }}</td>
                        <td>{{ $handover->sender?->name ?: '-' }}</td>
                        <td>{{ $handover->receiver_name ?: ($handover->receiver?->name ?: '-') }}</td>
                        <td>{{ ucfirst($handover->condition_at_handover) }}</td>
                        <td>{{ $handover->handed_over_at?->format('d/m/Y H:i') ?: '-' }}</td>
                        <td>{{ $handover->notes ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <p class="empty-state-title">Belum ada handover</p>
                                <p class="empty-state-text">Item masih disimpan; catat handover pertama saat item diambil.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
