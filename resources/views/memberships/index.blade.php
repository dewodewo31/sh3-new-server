@extends('layouts.app')

@section('title', 'Memberships')
@section('subtitle', 'Kelola membership peserta klub')

@section('content')
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
    <div class="stat-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Membership Aktif</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ $stats['active'] }}</p>
            </div>
            <div class="stat-icon bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Menunggu Pembayaran</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ $stats['pending'] }}</p>
            </div>
            <div class="stat-icon bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Akan Kadaluarsa</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ $stats['expiring_soon'] }}</p>
            </div>
            <div class="stat-icon bg-orange-50 text-orange-600 dark:bg-orange-500/10 dark:text-orange-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Pendapatan</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Rp {{ number_format($stats['revenue'], 0, ',', '.') }}</p>
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
            <h3 class="card-header-title">Riwayat Membership</h3>
            <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">Total {{ $stats['total'] }} transaksi membership</p>
        </div>
        <div class="flex items-center gap-2">
            @if(auth()->user()->role === 'admin_full_access')
                <a href="{{ route('admin.membership-plans.index') }}" class="btn btn-secondary btn-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                    Kelola Plan
                </a>
            @endif
            <a href="{{ route('admin.memberships.create') }}" class="btn btn-primary btn-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Beri Membership
            </a>
        </div>
    </div>
    <div class="table-wrap w-full max-w-full overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700/80">
        <table class="min-w-full whitespace-nowrap">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Peserta</th>
                    <th>Tipe</th>
                    <th>Periode</th>
                    <th>Harga</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($histories as $h)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <p class="font-medium text-gray-900 dark:text-slate-100">{{ $h->participant->name ?? '-' }}</p>
                        <small class="text-gray-400 dark:text-slate-500">{{ $h->participant->email ?? '-' }}</small>
                    </td>
                    <td><span class="badge badge-blue">{{ $h->planLabel() }}</span></td>
                    <td>
                        <p class="text-sm text-gray-900 dark:text-slate-100">{{ $h->start_date->format('d/m/Y') }} - {{ $h->end_date->format('d/m/Y') }}</p>
                        @if($h->status === 'active')
                            <small class="text-gray-400 dark:text-slate-500">Sisa {{ now()->diffInDays($h->end_date, false) }} hari</small>
                        @endif
                    </td>
                    <td>Rp {{ number_format($h->price, 0, ',', '.') }}</td>
                    <td>
                        @php
                            $statusClasses = ['active' => 'badge-success', 'pending' => 'badge-warning', 'expired' => 'badge-secondary', 'cancelled' => 'badge-danger'];
                        @endphp
                        <span class="badge {{ $statusClasses[$h->status] ?? 'badge-secondary' }}">{{ ucfirst($h->status) }}</span>
                    </td>
                    <td>
                        @if(in_array($h->status, ['active', 'pending']))
                            <form action="{{ route('admin.memberships.cancel', $h->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Batalkan membership ini?')">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Batalkan
                                </button>
                            </form>
                        @else
                            <span class="text-xs text-gray-400 dark:text-slate-500">-</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                            <p class="empty-state-title">Belum ada membership</p>
                            <p class="empty-state-text">Berikan membership kepada peserta untuk memulai.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($histories->hasPages())
        <div class="pagination">
            <div class="pagination-info">
                Showing {{ $histories->firstItem() }} to {{ $histories->lastItem() }} of {{ $histories->total() }} memberships
            </div>
            <div class="pagination-links">{{ $histories->links() }}</div>
        </div>
    @endif
</div>
@endsection
