@extends('layouts.app')

@section('title', 'Tambah Event')
@section('subtitle', 'Create a new running event')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Tambah Event</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.events.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-input @error('title') error @enderror" value="{{ old('title') }}" required>
                    @error('title') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select @error('category_id') error @enderror" required>
                        <option value="">-- Pilih --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Start Date</label>
                    <input type="datetime-local" name="start_date" class="form-input @error('start_date') error @enderror" value="{{ old('start_date') }}" required>
                    @error('start_date') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">End Date</label>
                    <input type="datetime-local" name="end_date" class="form-input @error('end_date') error @enderror" value="{{ old('end_date') }}" required>
                    @error('end_date') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Registration Start</label>
                    <input type="datetime-local" name="registration_start_date" class="form-input @error('registration_start_date') error @enderror" value="{{ old('registration_start_date') }}" required>
                    @error('registration_start_date') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Registration End</label>
                    <input type="datetime-local" name="registration_end_date" class="form-input @error('registration_end_date') error @enderror" value="{{ old('registration_end_date') }}" required>
                    @error('registration_end_date') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-input @error('location') error @enderror" value="{{ old('location') }}">
                    @error('location') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Quota</label>
                    <input type="number" name="quota" class="form-input @error('quota') error @enderror" value="{{ old('quota') }}">
                    @error('quota') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Price (Rp)</label>
                    <input type="number" name="price" class="form-input @error('price') error @enderror" value="{{ old('price') }}" step="0.01">
                    @error('price') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Image</label>
                    <input type="file" name="image" class="form-input @error('image') error @enderror">
                    @error('image') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Banner</label>
                    <input type="file" name="banner" class="form-input @error('banner') error @enderror">
                    @error('banner') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group flex items-end pb-2.5">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_free_for_members" value="1" {{ old('is_free_for_members', true) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm font-medium text-gray-700">Free for members</span>
                    </label>
                </div>
                <div class="md:col-span-2 form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-textarea @error('address') error @enderror" rows="2">{{ old('address') }}</textarea>
                    @error('address') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2 form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea @error('description') error @enderror" rows="3">{{ old('description') }}</textarea>
                    @error('description') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex items-center gap-3 mt-8 pt-6 divider">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan
                </button>
                <a href="{{ route('admin.events.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
