@extends('layouts.app')

@section('title', 'Tambah Anggota Organisasi')
@section('subtitle', 'Add new organization member')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Tambah Anggota Organisasi</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.organization.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="form-input @error('name') error @enderror">
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Position</label>
                    <input type="text" name="position" value="{{ old('position') }}" required class="form-input @error('position') error @enderror">
                    @error('position') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-input @error('sort_order') error @enderror">
                    @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Period Start</label>
                    <input type="date" name="period_start" value="{{ old('period_start') }}" class="form-input @error('period_start') error @enderror">
                    @error('period_start') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Period End</label>
                    <input type="date" name="period_end" value="{{ old('period_end') }}" class="form-input @error('period_end') error @enderror">
                    @error('period_end') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Avatar</label>
                    <input type="file" name="avatar" class="form-input @error('avatar') error @enderror">
                    @error('avatar') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2 form-group">
                    <label class="form-label">Role Description</label>
                    <textarea name="role_description" rows="2" class="form-textarea @error('role_description') error @enderror">{{ old('role_description') }}</textarea>
                    @error('role_description') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex items-center gap-3 mt-8 pt-6 divider">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan
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
