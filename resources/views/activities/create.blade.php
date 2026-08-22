@extends('layouts.app')

@section('title', 'Tambah Aktivitas')
@section('subtitle', 'Buat aktivitas baru')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Tambah Aktivitas</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.activities.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="form-input @error('name') error @enderror">
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-input @error('sort_order') error @enderror">
                    @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2 form-group">
                    <label class="form-label">Aktif</label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="form-checkbox">
                        <span class="text-sm text-slate-600 dark:text-slate-300">Aktivitas aktif</span>
                    </label>
                    @error('is_active') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex items-center gap-3 mt-8 pt-6 divider">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan
                </button>
                <a href="{{ route('admin.activities.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
