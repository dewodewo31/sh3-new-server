@extends('layouts.app')

@section('title', 'Payments')
@section('subtitle', 'Manage and verify participant payments')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Daftar Pembayaran</h3>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Invoice</th>
                    <th>Participant</th>
                    <th>Tipe</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="font-medium text-gray-900">{{ $payment->invoice_number }}</td>
                    <td>{{ $payment->participant->name ?? '-' }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $payment->payment_type)) }}</td>
                    <td>Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                    <td><span class="font-medium text-gray-900">{{ strtoupper($payment->payment_method) }}</span></td>
                    <td>
                        @php
                            $statusClasses = ['confirmed' => 'badge-success', 'pending' => 'badge-warning', 'rejected' => 'badge-danger', 'refunded' => 'badge-info'];
                        @endphp
                        <span class="badge {{ $statusClasses[$payment->status] ?? 'badge-secondary' }}">{{ ucfirst($payment->status) }}</span>
                    </td>
                    <td>{{ $payment->created_at->format('d/m/Y') }}</td>
                    <td>
                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('admin.payments.show', $payment->id) }}" class="btn btn-info btn-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Detail
                            </a>
                            @if($payment->status === 'pending')
                                <form action="{{ route('admin.payments.confirm', $payment->id) }}" method="POST" class="inline">
                                    @csrf @method('PUT')
                                    <button type="submit" class="btn btn-success btn-xs" onclick="return confirm('Konfirmasi pembayaran ini?')">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Confirm
                                    </button>
                                </form>
                                <form action="{{ route('admin.payments.reject', $payment->id) }}" method="POST" class="inline">
                                    @csrf @method('PUT')
                                    <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Tolak pembayaran ini?')">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Reject
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9">
                        <div class="empty-state">
                            <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
                            <p class="empty-state-title">Belum ada pembayaran</p>
                            <p class="empty-state-text">Pembayaran akan muncul ketika peserta melakukan registrasi.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($payments->hasPages())
        <div class="pagination">
            <div class="pagination-info">
                Showing {{ $payments->firstItem() }} to {{ $payments->lastItem() }} of {{ $payments->total() }} payments
            </div>
            <div class="pagination-links">{{ $payments->links() }}</div>
        </div>
    @endif
</div>
@endsection
