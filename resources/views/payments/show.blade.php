@extends('layouts.app')

@section('title', 'Detail Pembayaran')
@section('subtitle', 'Invoice #' . $payment->invoice_number)

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Detail Pembayaran</h3>
        <span class="badge {{ $payment->status === 'confirmed' ? 'badge-success' : ($payment->status === 'pending' ? 'badge-warning' : ($payment->status === 'rejected' ? 'badge-danger' : 'badge-info')) }}">
            {{ ucfirst($payment->status) }}
        </span>
    </div>
    <div class="card-body">
        <dl class="divide-y divide-gray-100 dark:divide-slate-700/60">
            <div class="flex justify-between py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Invoice</dt>
                <dd class="text-sm font-medium text-gray-900 dark:text-slate-100">{{ $payment->invoice_number }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Participant</dt>
                <dd class="text-sm text-gray-900 dark:text-slate-100">{{ $payment->participant->name ?? '-' }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Tipe</dt>
                <dd class="text-sm text-gray-900 dark:text-slate-100">{{ ucwords(str_replace('_', ' ', $payment->payment_type)) }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Amount</dt>
                <dd class="text-sm font-semibold text-gray-900 dark:text-slate-100">Rp {{ number_format($payment->amount, 0, ',', '.') }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Method</dt>
                <dd class="text-sm font-medium text-gray-900 dark:text-slate-100">{{ strtoupper($payment->payment_method) }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Confirmed By</dt>
                <dd class="text-sm text-gray-900 dark:text-slate-100">{{ $payment->confirmedBy->name ?? '-' }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Paid At</dt>
                <dd class="text-sm text-gray-900 dark:text-slate-100">{{ $payment->paid_at?->format('d/m/Y H:i') ?? '-' }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Created</dt>
                <dd class="text-sm text-gray-900 dark:text-slate-100">{{ $payment->created_at->format('d/m/Y H:i') }}</dd>
            </div>
        </dl>

        @if($payment->payment_proof)
        <hr class="my-6 border-gray-100 dark:border-slate-700/60">
        <div>
            <span class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-slate-500">Payment Proof</span>
            <div class="mt-3">
                <img src="{{ asset('storage/' . $payment->payment_proof) }}" class="rounded-xl border border-gray-200 max-h-80 shadow-sm dark:border-slate-700">
            </div>
        </div>
        @endif
    </div>
</div>

<div class="mt-6">
    <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Kembali
    </a>
</div>
@endsection
