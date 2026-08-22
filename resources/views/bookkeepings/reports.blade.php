@extends('layouts.app')

@section('title', 'Laporan Pembukuan')
@section('subtitle', 'Ringkasan pemasukan, pengeluaran, dan saldo')

@section('content')
<div class="flex flex-wrap items-center gap-2 mb-5">
    <a href="{{ route('admin.bookkeepings.reports') }}" class="btn btn-primary btn-sm">Dashboard</a>
    <a href="{{ route('admin.bookkeepings.receivables') }}" class="btn btn-secondary btn-sm">Piutang</a>
    <a href="{{ route('admin.bookkeepings.payables') }}" class="btn btn-secondary btn-sm">Hutang</a>
    <a href="{{ route('admin.bookkeepings.cash-flow') }}" class="btn btn-secondary btn-sm">Arus Kas</a>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h3 class="card-header-title">Ekspor Budget vs Actual</h3>
        <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">Pilih event, lalu unduh dalam format yang diinginkan.</p>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.bookkeepings.export-budget-vs-actual') }}" class="flex flex-wrap items-end gap-3">
            <div class="form-group mb-0">
                <label class="form-label">Event</label>
                <select name="event_id" class="form-select" required>
                    <option value="">Pilih Event</option>
                    @foreach($events as $event)
                        <option value="{{ $event->id }}">{{ $event->title }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" name="format" value="csv" class="btn btn-secondary btn-sm">CSV</button>
            <button type="submit" name="format" value="xlsx" class="btn btn-secondary btn-sm">XLSX</button>
            <button type="submit" name="format" value="pdf" class="btn btn-secondary btn-sm">PDF</button>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
    <div class="stat-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Pemasukan</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">Rp {{ number_format($periodTotals['income'], 0, ',', '.') }}</p>
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
                <p class="mt-2 text-3xl font-bold tracking-tight text-red-600 dark:text-red-400">Rp {{ number_format($periodTotals['expense'], 0, ',', '.') }}</p>
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
                <p class="mt-2 text-3xl font-bold tracking-tight {{ $periodTotals['balance'] >= 0 ? 'text-slate-900 dark:text-slate-100' : 'text-red-600 dark:text-red-400' }}">Rp {{ number_format($periodTotals['balance'], 0, ',', '.') }}</p>
            </div>
            <div class="stat-icon bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mt-6">
    <div class="card">
        <div class="card-header">
            <h3 class="card-header-title">Per Akun Keuangan</h3>
        </div>
        <div class="table-wrap w-full max-w-full overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700/80">
            <table class="min-w-full whitespace-nowrap">
                <thead>
                    <tr>
                        <th>Akun ID</th>
                        <th>Pemasukan</th>
                        <th>Pengeluaran</th>
                        <th>Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($perAccountTotals as $row)
                    <tr>
                        <td class="font-medium text-gray-900 dark:text-gray-100">{{ $row['account_id'] }}</td>
                        <td class="text-emerald-600 dark:text-emerald-400">Rp {{ number_format($row['income'], 0, ',', '.') }}</td>
                        <td class="text-red-600 dark:text-red-400">Rp {{ number_format($row['expense'], 0, ',', '.') }}</td>
                        <td class="font-medium {{ $row['balance'] >= 0 ? 'text-slate-900 dark:text-slate-100' : 'text-red-600 dark:text-red-400' }}">Rp {{ number_format($row['balance'], 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-slate-400 py-6">Belum ada data per akun.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-header-title">Transaksi Terbaru</h3>
        </div>
        <div class="table-wrap w-full max-w-full overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700/80">
            <table class="min-w-full whitespace-nowrap">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                        <th>Tipe</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent as $entry)
                    <tr>
                        <td class="font-medium text-gray-900 dark:text-gray-100">{{ $entry->transaction_date->format('d/m/Y') }}</td>
                        <td>{{ $entry->description }}</td>
                        <td>
                            @if($entry->type === 'income')
                                <span class="badge badge-success">Pemasukan</span>
                            @else
                                <span class="badge badge-danger">Pengeluaran</span>
                            @endif
                        </td>
                        <td>
                            @if($entry->type === 'income')
                                <span class="font-medium text-emerald-600 dark:text-emerald-400">+ Rp {{ number_format($entry->amount, 0, ',', '.') }}</span>
                            @else
                                <span class="font-medium text-red-600 dark:text-red-400">− Rp {{ number_format($entry->amount, 0, ',', '.') }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-slate-400 py-6">Belum ada transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-6">
    <div class="card-header">
        <h3 class="card-header-title">Per Bulan</h3>
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
                @foreach($perMonthTotals as $row)
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
