@extends('layouts.app')

@section('title', 'Laporan Arus Kas')
@section('subtitle', 'Saldo awal, pemasukan, pengeluaran, dan saldo akhir per tahun')

@section('content')
<div class="flex flex-wrap items-center gap-2 mb-5">
    <a href="{{ route('admin.bookkeepings.reports') }}" class="btn btn-secondary btn-sm">Dashboard</a>
    <a href="{{ route('admin.bookkeepings.receivables') }}" class="btn btn-secondary btn-sm">Piutang</a>
    <a href="{{ route('admin.bookkeepings.payables') }}" class="btn btn-secondary btn-sm">Hutang</a>
    <a href="{{ route('admin.bookkeepings.cash-flow') }}" class="btn btn-primary btn-sm">Arus Kas</a>
</div>

@php
    $filterKeys = ['type', 'category', 'sponsor_id', 'event_id', 'status', 'financial_account_id', 'activity_id', 'date_from', 'date_to'];
    $ccsv = route('admin.bookkeepings.export-cash-flow', array_merge(request()->only($filterKeys), ['year' => $year, 'format' => 'csv']));
    $cxlsx = route('admin.bookkeepings.export-cash-flow', array_merge(request()->only($filterKeys), ['year' => $year, 'format' => 'xlsx']));
    $cpdf = route('admin.bookkeepings.export-cash-flow', array_merge(request()->only($filterKeys), ['year' => $year, 'format' => 'pdf']));
@endphp
<div class="flex flex-wrap items-center gap-2 mb-5">
    <span class="text-sm text-slate-500 dark:text-slate-400">Ekspor:</span>
    <a href="{{ $ccsv }}" class="btn btn-secondary btn-sm">CSV</a>
    <a href="{{ $cxlsx }}" class="btn btn-secondary btn-sm">XLSX</a>
    <a href="{{ $cpdf }}" class="btn btn-secondary btn-sm">PDF</a>
</div>

<form action="{{ route('admin.bookkeepings.cash-flow') }}" method="GET" class="card mb-6">
    <div class="card-body flex items-end gap-4">
        <div class="form-group mb-0">
            <label class="form-label">Tahun</label>
            <select name="year" class="form-select" onchange="this.form.submit()">
                @for($y = now()->year - 4; $y <= now()->year; $y++)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>
    </div>
</form>

<div class="grid grid-cols-1 gap-5 sm:grid-cols-4">
    <div class="stat-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Saldo Awal</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Rp {{ number_format($opening, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Pemasukan</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">Rp {{ number_format($income, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Pengeluaran</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-red-600 dark:text-red-400">Rp {{ number_format($expense, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Saldo Akhir</p>
                <p class="mt-2 text-2xl font-bold tracking-tight {{ $closing >= 0 ? 'text-slate-900 dark:text-slate-100' : 'text-red-600 dark:text-red-400' }}">Rp {{ number_format($closing, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="card mt-6">
    <div class="card-header">
        <h3 class="card-header-title">Arus Kas Bulanan — {{ $year }}</h3>
    </div>
    <div class="table-wrap w-full max-w-full overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700/80">
        <table class="min-w-full whitespace-nowrap">
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th>Pemasukan</th>
                    <th>Pengeluaran</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($months as $row)
                <tr>
                    <td class="font-medium text-gray-900 dark:text-gray-100">{{ $row['month'] }}</td>
                    <td class="text-emerald-600 dark:text-emerald-400">Rp {{ number_format($row['income'], 0, ',', '.') }}</td>
                    <td class="text-red-600 dark:text-red-400">Rp {{ number_format($row['expense'], 0, ',', '.') }}</td>
                    <td class="font-medium {{ $row['balance'] >= 0 ? 'text-slate-900 dark:text-slate-100' : 'text-red-600 dark:text-red-400' }}">Rp {{ number_format($row['balance'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
