@extends('layouts.app')

@section('title', 'Edit Anggota Organisasi')
@section('subtitle', 'Update organization member')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Edit Anggota Organisasi</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.organization.update', $member->id) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="{{ old('name', $member->name) }}" required class="form-input @error('name') error @enderror">
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Position</label>
                    <input type="text" name="position" value="{{ old('position', $member->position) }}" required class="form-input @error('position') error @enderror">
                    @error('position') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $member->sort_order) }}" class="form-input @error('sort_order') error @enderror">
                    @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Period Start</label>
                    <input type="date" name="period_start" value="{{ old('period_start', $member->period_start?->format('Y-m-d')) }}" class="form-input @error('period_start') error @enderror">
                    @error('period_start') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Period End</label>
                    <input type="date" name="period_end" value="{{ old('period_end', $member->period_end?->format('Y-m-d')) }}" class="form-input @error('period_end') error @enderror">
                    @error('period_end') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Avatar</label>
                    <input type="file" name="avatar" class="form-input @error('avatar') error @enderror">
                    @error('avatar') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2 form-group">
                    <label class="form-label">Role Description</label>
                    <textarea name="role_description" rows="2" class="form-textarea @error('role_description') error @enderror">{{ old('role_description', $member->role_description) }}</textarea>
                    @error('role_description') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex items-center gap-3 mt-8 pt-6 divider">
                <button type="submit" class="btn btn-warning">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Update
                </button>
                <a href="{{ route('admin.organization.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
