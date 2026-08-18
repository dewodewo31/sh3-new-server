@extends('layouts.app')

@section('title', 'Detail Pembukuan')
@section('subtitle', 'Detail catatan pemasukan atau pengeluaran')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Detail Pembukuan</h3>
        <span class="badge {{ $entry->type === 'income' ? 'badge-success' : 'badge-danger' }}">
            {{ $entry->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
        </span>
    </div>
    <div class="card-body">
        <dl class="divide-y divide-gray-100 dark:divide-slate-700/60">
            <div class="flex justify-between py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Tanggal</dt>
                <dd class="text-sm font-medium text-gray-900 dark:text-slate-100">{{ $entry->transaction_date->format('d/m/Y') }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Kategori</dt>
                <dd class="text-sm text-gray-900 dark:text-slate-100">
                    @if($entry->category === 'sponsor')
                        Sponsor - {{ $entry->sponsor->name ?? '-' }}
                    @elseif($entry->category === 'event_income')
                        Pendapatan Event - {{ $entry->event->title ?? '-' }}
                    @else
                        Lainnya
                    @endif
                </dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Keterangan</dt>
                <dd class="text-sm text-gray-900 dark:text-slate-100">{{ $entry->description }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Jumlah</dt>
                <dd class="text-sm font-semibold {{ $entry->type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $entry->type === 'income' ? '+' : '-' }} Rp {{ number_format($entry->amount, 0, ',', '.') }}
                </dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Dibuat oleh</dt>
                <dd class="text-sm text-gray-900 dark:text-slate-100">{{ $entry->createdBy->name ?? '-' }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Dibuat pada</dt>
                <dd class="text-sm text-gray-900 dark:text-slate-100">{{ $entry->created_at->format('d/m/Y H:i') }}</dd>
            </div>
        </dl>

        @if($entry->receipt)
        <hr class="my-6 border-gray-100 dark:border-slate-700/60">
        <div>
            <span class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-slate-500">Bukti Nota</span>
            <div class="mt-3">
                <img src="{{ asset('storage/' . $entry->receipt) }}" class="rounded-xl border border-gray-200 max-h-80 shadow-sm dark:border-slate-700">
            </div>
        </div>
        @endif
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <a href="{{ route('admin.bookkeepings.edit', $entry->id) }}" class="btn btn-warning">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        Edit
    </a>
    <a href="{{ route('admin.bookkeepings.index') }}" class="btn btn-secondary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Kembali
    </a>
</div>
@endsection