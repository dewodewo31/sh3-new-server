@extends('layouts.app')

@section('title', 'Edit Event')
@section('subtitle', 'Update event details')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Edit Event</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.events.update', $event->id) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-input @error('title') error @enderror" value="{{ old('title', $event->title) }}" required>
                    @error('title') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select @error('category_id') error @enderror" required>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $event->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Start Date</label>
                    <input type="datetime-local" name="start_date" class="form-input @error('start_date') error @enderror" value="{{ old('start_date', $event->start_date->format('Y-m-d\TH:i')) }}" required>
                    @error('start_date') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">End Date</label>
                    <input type="datetime-local" name="end_date" class="form-input @error('end_date') error @enderror" value="{{ old('end_date', $event->end_date->format('Y-m-d\TH:i')) }}" required>
                    @error('end_date') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Registration Start</label>
                    <input type="datetime-local" name="registration_start_date" class="form-input" value="{{ old('registration_start_date', $event->registration_start_date->format('Y-m-d\TH:i')) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Registration End</label>
                    <input type="datetime-local" name="registration_end_date" class="form-input" value="{{ old('registration_end_date', $event->registration_end_date->format('Y-m-d\TH:i')) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-input" value="{{ old('location', $event->location) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Quota</label>
                    <input type="number" name="quota" class="form-input" value="{{ old('quota', $event->quota) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Price (Rp)</label>
                    <input type="number" name="price" class="form-input" value="{{ old('price', $event->price) }}" step="0.01">
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
                <div class="md:col-span-2 form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-textarea" rows="2">{{ old('address', $event->address) }}</textarea>
                </div>
                <div class="md:col-span-2 form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" rows="3">{{ old('description', $event->description) }}</textarea>
                </div>
            </div>
            <div class="flex items-center gap-3 mt-8 pt-6 divider">
                <button type="submit" class="btn btn-warning">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Update
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
