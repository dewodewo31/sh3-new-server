@extends('layouts.app')

@section('title', 'Tambah Gallery')
@section('subtitle', 'Add new photo or video to gallery')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Tambah Gallery</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.galleries.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" value="{{ old('title') }}" required class="form-input @error('title') error @enderror">
                    @error('title') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Event</label>
                    <select name="event_id" class="form-select @error('event_id') error @enderror">
                        <option value="">-- Tanpa Event --</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}" {{ old('event_id') == $event->id ? 'selected' : '' }}>{{ $event->title }}</option>
                        @endforeach
                    </select>
                    @error('event_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Album</label>
                    <select name="gallery_album_id" class="form-select @error('gallery_album_id') error @enderror">
                        <option value="">-- Tanpa Album --</option>
                        @foreach($albums as $album)
                            <option value="{{ $album->id }}" {{ old('gallery_album_id') == $album->id ? 'selected' : '' }}>{{ $album->title }}</option>
                        @endforeach
                    </select>
                    @error('gallery_album_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select @error('type') error @enderror">
                        <option value="image" {{ old('type') == 'image' ? 'selected' : '' }}>Image</option>
                        <option value="video" {{ old('type') == 'video' ? 'selected' : '' }}>Video</option>
                    </select>
                    @error('type') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Source</label>
                    <select name="source" id="gallery-source" class="form-select @error('source') error @enderror" onchange="toggleSourceFields()">
                        <option value="local" {{ old('source', 'local') == 'local' ? 'selected' : '' }}>Local Upload</option>
                        <option value="gdrive" {{ old('source') == 'gdrive' ? 'selected' : '' }}>Google Drive Link</option>
                    </select>
                    @error('source') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group" id="field-file">
                    <label class="form-label">File (Max 10MB)</label>
                    <input type="file" name="file" class="form-input @error('file') error @enderror">
                    @error('file') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group" id="field-gdrive-url" style="display:none;">
                    <label class="form-label">Google Drive URL</label>
                    <input type="url" name="google_drive_url" value="{{ old('google_drive_url') }}" class="form-input @error('google_drive_url') error @enderror" placeholder="https://drive.google.com/file/d/FILE_ID/view?usp=sharing">
                    @error('google_drive_url') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-input @error('sort_order') error @enderror">
                    @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <div class="flex items-center h-full pt-6">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_featured" value="1" class="sr-only peer" {{ old('is_featured') ? 'checked' : '' }}>
                            <div class="w-9 h-5 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-checked:bg-indigo-600 peer-focus:ring-2 peer-focus:ring-indigo-200 transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white dark:bg-slate-800 rounded-full shadow peer-checked:translate-x-4 transition-transform"></div>
                            <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">Featured</span>
                        </label>
                    </div>
                </div>
                <div class="md:col-span-2 form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="2" class="form-textarea @error('description') error @enderror">{{ old('description') }}</textarea>
                    @error('description') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex items-center gap-3 mt-8 pt-6 divider">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan
                </button>
                <a href="{{ route('admin.galleries.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSourceFields() {
    const source = document.getElementById('gallery-source').value;
    const fileField = document.getElementById('field-file');
    const gdriveField = document.getElementById('field-gdrive-url');
    const fileInput = document.querySelector('input[name="file"]');
    const gdriveInput = document.querySelector('input[name="google_drive_url"]');

    if (source === 'local') {
        fileField.style.display = '';
        gdriveField.style.display = 'none';
        fileInput.setAttribute('required', 'required');
        gdriveInput.removeAttribute('required');
    } else {
        fileField.style.display = 'none';
        gdriveField.style.display = '';
        fileInput.removeAttribute('required');
        gdriveInput.setAttribute('required', 'required');
    }
}
</script>
@endsection