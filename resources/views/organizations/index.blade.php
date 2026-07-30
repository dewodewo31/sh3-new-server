@extends('layouts.app')

@section('title', 'Organization')
@section('subtitle', 'Manage organizational structure')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Struktur Organisasi</h3>
        <a href="{{ route('admin.organization.create') }}" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Anggota
        </a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Order</th>
                    <th>Active</th>
                    <th>Period</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="font-medium text-gray-900">{{ $member->name }}</td>
                    <td>{{ $member->position }}</td>
                    <td>{{ $member->sort_order }}</td>
                    <td>
                        @if($member->is_active)
                            <span class="badge badge-success">Yes</span>
                        @else
                            <span class="badge badge-danger">No</span>
                        @endif
                    </td>
                    <td>{{ $member->period_start?->format('Y') ?? '-' }} - {{ $member->period_end?->format('Y') ?? '-' }}</td>
                    <td>
                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('admin.organization.edit', $member->id) }}" class="btn btn-warning btn-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit
                            </a>
                            <form action="{{ route('admin.organization.destroy', $member->id) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Hapus anggota ini?')">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>
                            <p class="empty-state-title">Belum ada anggota</p>
                            <p class="empty-state-text">Tambah anggota organisasi pertama untuk memulai.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
