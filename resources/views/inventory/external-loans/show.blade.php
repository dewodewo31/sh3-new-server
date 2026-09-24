@extends('layouts.app')

@section('title', 'Detail Pinjaman Barang')
@section('subtitle', $loan->external_party)

@section('breadcrumb')
    @include('includes.breadcrumb', [
        'items' => [
            ['label' => 'Inventaris', 'url' => route('admin.inventory.index')],
            ['label' => 'Pinjaman Barang', 'url' => route('admin.inventory.external-loans.index')],
            ['label' => '#'.$loan->id],
        ],
    ])
@endsection

@php
    $isManager = in_array(auth()->user()->role, config('sh3.inventory_manage_roles'), true);
@endphp

@section('content')
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="card lg:col-span-2">
        <div class="card-header">
            <h3 class="card-header-title">Detail Pinjaman Barang</h3>
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
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Perusahaan / Komunitas</dt>
                    <dd class="mt-0.5 text-sm font-medium text-slate-900 dark:text-slate-100">{{ $loan->external_party }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Kontak</dt>
                    <dd class="mt-0.5 text-sm font-medium text-slate-900 dark:text-slate-100">
                        {{ $loan->contact_name ?: '-' }}
                        @if ($loan->contact_info)
                            <span class="block text-xs font-normal text-slate-500 dark:text-slate-400">{{ $loan->contact_info }}</span>
                        @endif
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Barang</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ $loan->items_description }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Tujuan</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $loan->purpose ?: '-' }}</dd>
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
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Kondisi Saat Terima</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $loan->condition_before ? ucfirst($loan->condition_before) : '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Kondisi Saat Kembali</dt>
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

            @if ($isManager && $loan->canCancel())
                <div class="mt-6 flex flex-wrap gap-2 pt-4 divider">
                    <form action="{{ route('admin.inventory.external-loans.cancel', $loan->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Batalkan pinjaman ini?')">Batalkan</button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        @if ($isManager && $loan->canHandover())
            <div class="card">
                <div class="card-header">
                    <h3 class="card-header-title">Handover (Terima Barang)</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.inventory.external-loans.handover', $loan->id) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Dari Lokasi *</label>
                            <input type="text" name="from_location" value="{{ old('from_location', $loan->external_party) }}" required class="form-input @error('from_location') error @enderror">
                            @error('from_location') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Diterima Oleh (Nama) *</label>
                            <input type="text" name="to_name" value="{{ old('to_name', auth()->user()->name) }}" required class="form-input @error('to_name') error @enderror">
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
                    <form action="{{ route('admin.inventory.external-loans.return', $loan->id) }}" method="POST">
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
                        <button type="submit" class="btn btn-success btn-sm w-full" onclick="return confirm('Konfirmasi pengembalian barang ini?')">Catat Pengembalian</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>

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
                                <p class="empty-state-text">Catat handover pertama saat barang diterima.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
