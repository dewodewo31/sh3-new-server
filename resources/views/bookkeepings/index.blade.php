@extends('layouts.app')

@section('title', 'Pembukuan')
@section('subtitle', 'Kelola pemasukan dan pengeluaran kas')

@section('content')
<div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
    <div class="stat-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Pemasukan</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">Rp {{ number_format($totals['income'], 0, ',', '.') }}</p>
            </div>
            <div class="stat-icon bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.306a11.95 11.95 0 015.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/></svg>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Pengeluaran</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-red-600 dark:text-red-400">Rp {{ number_format($totals['expense'], 0, ',', '.') }}</p>
            </div>
            <div class="stat-icon bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75v6.75m0 0-3-3m3 3 3-3m-8.25 6a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z"/></svg>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Saldo</p>
                <p class="mt-2 text-3xl font-bold tracking-tight {{ $totals['balance'] >= 0 ? 'text-slate-900 dark:text-slate-100' : 'text-red-600 dark:text-red-400' }}">Rp {{ number_format($totals['balance'], 0, ',', '.') }}</p>
            </div>
            <div class="stat-icon bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
            </div>
        </div>
    </div>
</div>

<div class="card mt-6">
    <div class="card-header">
        <div>
            <h3 class="card-header-title">Daftar Pembukuan</h3>
            <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">Catatan pemasukan dan pengeluaran kas</p>
        </div>
        <a href="{{ route('admin.bookkeepings.create') }}" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Tambah Pembukuan
        </a>
    </div>

    <form action="{{ route('admin.bookkeepings.index') }}" method="GET" class="border-b border-slate-100 px-4 py-4 dark:border-slate-700/60">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="form-group mb-0">
                <label class="form-label">Tipe</label>
                <select name="type" class="form-select">
                    <option value="">Semua Tipe</option>
                    <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>Pemasukan</option>
                    <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Pengeluaran</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Kategori</label>
                <select name="category" class="form-select">
                    <option value="">Semua Kategori</option>
                    <option value="sponsor" {{ request('category') == 'sponsor' ? 'selected' : '' }}>Sponsor</option>
                    <option value="event_income" {{ request('category') == 'event_income' ? 'selected' : '' }}>Pendapatan Event</option>
                    <option value="other" {{ request('category') == 'other' ? 'selected' : '' }}>Lainnya</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Sponsor</label>
                <select name="sponsor_id" class="form-select">
                    <option value="">Semua Sponsor</option>
                    @foreach($sponsors as $sponsor)
                        <option value="{{ $sponsor->id }}" {{ request('sponsor_id') == $sponsor->id ? 'selected' : '' }}>{{ $sponsor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Event</label>
                <select name="event_id" class="form-select">
                    <option value="">Semua Event</option>
                    @foreach($events as $event)
                        <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>{{ $event->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-4 flex items-center gap-3">
            <button type="submit" class="btn btn-primary btn-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/></svg>
                Filter
            </button>
            <a href="{{ route('admin.bookkeepings.index') }}" class="btn btn-secondary btn-sm">Reset</a>
        </div>
    </form>

    <div class="table-wrap w-full max-w-full overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700/80">
        <table class="min-w-full whitespace-nowrap">
            <thead>
                <tr>
                    <th>#</th>
                    <x-th-sort column="transaction_date">Tanggal</x-th-sort>
                    <th>Keterangan</th>
                    <x-th-sort column="type">Tipe</x-th-sort>
                    <x-th-sort column="category">Kategori</x-th-sort>
                    <x-th-sort column="amount">Jumlah</x-th-sort>
                    <th>Bukti</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="font-medium text-gray-900 dark:text-slate-100">{{ $entry->transaction_date->format('d/m/Y') }}</td>
                    <td>{{ $entry->description }}</td>
                    <td>
                        @if($entry->type === 'income')
                            <span class="badge badge-success">Pemasukan</span>
                        @else
                            <span class="badge badge-danger">Pengeluaran</span>
                        @endif
                    </td>
                    <td>
                        @if($entry->category === 'sponsor')
                            {{ $entry->sponsor->name ?? '-' }}
                        @elseif($entry->category === 'event_income')
                            {{ $entry->event->title ?? '-' }}
                        @else
                            Lainnya
                        @endif
                    </td>
                    <td>
                        @if($entry->type === 'income')
                            <span class="font-medium text-emerald-600 dark:text-emerald-400">+ Rp {{ number_format($entry->amount, 0, ',', '.') }}</span>
                        @else
                            <span class="font-medium text-red-600 dark:text-red-400">− Rp {{ number_format($entry->amount, 0, ',', '.') }}</span>
                        @endif
                    </td>
                    <td>
                        @if($entry->receipt)
                            <a href="{{ route('admin.bookkeepings.show', $entry->id) }}" class="btn btn-info btn-xs">Lihat</a>
                        @else
                            <span class="text-xs text-gray-400 dark:text-slate-500">-</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('admin.bookkeepings.show', $entry->id) }}" class="btn btn-info btn-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Detail
                            </a>
                            <a href="{{ route('admin.bookkeepings.edit', $entry->id) }}" class="btn btn-warning btn-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit
                            </a>
                            <form action="{{ route('admin.bookkeepings.destroy', $entry->id) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Hapus pembukuan ini?')">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="empty-state-title">Belum ada pembukuan</p>
                            <p class="empty-state-text">Tambah catatan pemasukan atau pengeluaran untuk memulai.</p>
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