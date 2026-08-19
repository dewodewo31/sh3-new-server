@extends('layouts.app')

@section('title', 'Tambah Album')
@section('subtitle', 'Create a new gallery album')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Tambah Album</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.gallery-albums.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2 form-group">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" value="{{ old('title') }}" required class="form-input @error('title') error @enderror">
                    @error('title') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Event</label>
                    <select name="event_id" class="form-select @error('event_id') error @enderror">
                        <option value="">- Pilih Event -</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}" {{ old('event_id') == $event->id ? 'selected' : '' }}>{{ $event->title }}</option>
                        @endforeach
                    </select>
                    @error('event_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Cover Image</label>
                    <input type="file" name="cover_image" class="form-input @error('cover_image') error @enderror">
                    @error('cover_image') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Google Drive Folder URL</label>
                    <input type="url" name="gdrive_folder_url" value="{{ old('gdrive_folder_url') }}" class="form-input @error('gdrive_folder_url') error @enderror" placeholder="https://drive.google.com/drive/folders/FOLDER_ID">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Opsional. Folder harus disetel 'Anyone with the link can view'.</p>
                    @error('gdrive_folder_url') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2 form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3" class="form-textarea @error('description') error @enderror">{{ old('description') }}</textarea>
                    @error('description') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex items-center gap-3 mt-8 pt-6 divider">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan
                </button>
                <a href="{{ route('admin.gallery-albums.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali
                </a>
            </div>
        </form>
    </div>
</div>
@endsection