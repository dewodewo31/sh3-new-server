@extends('layouts.app')

@section('title', 'Laporan Hutang')
@section('subtitle', 'Komitmen, dibayar, dan sisa hutang')

@section('content')
<div class="flex flex-wrap items-center gap-2 mb-5">
    <a href="{{ route('admin.bookkeepings.reports') }}" class="btn btn-secondary btn-sm">Dashboard</a>
    <a href="{{ route('admin.bookkeepings.receivables') }}" class="btn btn-secondary btn-sm">Piutang</a>
    <a href="{{ route('admin.bookkeepings.payables') }}" class="btn btn-primary btn-sm">Hutang</a>
    <a href="{{ route('admin.bookkeepings.cash-flow') }}" class="btn btn-secondary btn-sm">Arus Kas</a>
</div>

@php
    $filterKeys = ['type', 'category', 'sponsor_id', 'event_id', 'status', 'financial_account_id', 'activity_id', 'date_from', 'date_to'];
    $pcsv = route('admin.bookkeepings.export-payable', array_merge(request()->only($filterKeys), ['format' => 'csv']));
    $pxlsx = route('admin.bookkeepings.export-payable', array_merge(request()->only($filterKeys), ['format' => 'xlsx']));
    $ppdf = route('admin.bookkeepings.export-payable', array_merge(request()->only($filterKeys), ['format' => 'pdf']));
@endphp
<div class="flex flex-wrap items-center gap-2 mb-5">
    <span class="text-sm text-slate-500 dark:text-slate-400">Ekspor:</span>
    <a href="{{ $pcsv }}" class="btn btn-secondary btn-sm">CSV</a>
    <a href="{{ $pxlsx }}" class="btn btn-secondary btn-sm">XLSX</a>
    <a href="{{ $ppdf }}" class="btn btn-secondary btn-sm">PDF</a>
</div>

<div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
    <div class="stat-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Komitmen</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Rp {{ number_format($committed, 0, ',', '.') }}</p>
            </div>
            <div class="stat-icon bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h12M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605"/></svg>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Dibayar</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">Rp {{ number_format($paid, 0, ',', '.') }}</p>
            </div>
            <div class="stat-icon bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Sisa Hutang</p>
                <p class="mt-2 text-3xl font-bold tracking-tight {{ $outstanding >= 0 ? 'text-slate-900 dark:text-slate-100' : 'text-red-600 dark:text-red-400' }}">Rp {{ number_format($outstanding, 0, ',', '.') }}</p>
            </div>
            <div class="stat-icon bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>
</div>
@endsection
