@extends('layouts.app')

@section('title', 'Detail Gallery')
@section('subtitle', 'Gallery item details')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Detail Gallery</h3>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="form-label">Title</label>
                <p class="text-gray-900 dark:text-gray-100 font-medium">{{ $gallery->title }}</p>
            </div>
            <div>
                <label class="form-label">Event</label>
                <p class="text-gray-900 dark:text-gray-100">{{ $gallery->event->title ?? '-' }}</p>
            </div>
            <div>
                <label class="form-label">Album</label>
                <p class="text-gray-900 dark:text-gray-100">{{ $gallery->album->title ?? '-' }}</p>
            </div>
            <div>
                <label class="form-label">Type</label>
                <p class="text-gray-900 dark:text-gray-100">{{ ucfirst($gallery->type) }}</p>
            </div>
            <div>
                <label class="form-label">Source</label>
                <span class="badge {{ $gallery->source === 'local' ? 'badge-success' : 'badge-info' }}">{{ ucfirst($gallery->source) }}</span>
            </div>
            <div>
                <label class="form-label">Featured</label>
                <p class="text-gray-900 dark:text-gray-100">{{ $gallery->is_featured ? 'Yes' : 'No' }}</p>
            </div>
            <div>
                <label class="form-label">Sort Order</label>
                <p class="text-gray-900 dark:text-gray-100">{{ $gallery->sort_order }}</p>
            </div>
            <div>
                <label class="form-label">Created By</label>
                <p class="text-gray-900 dark:text-gray-100">{{ $gallery->createdBy?->name ?? '-' }}</p>
            </div>
            <div class="md:col-span-2">
                <label class="form-label">Description</label>
                <p class="text-gray-900 dark:text-gray-100">{{ $gallery->description ?? '-' }}</p>
            </div>
            <div class="md:col-span-2">
                <label class="form-label">Preview</label>
                <div class="mt-2">
                    @if($gallery->source === 'gdrive')
                        <img src="{{ $gallery->google_drive_url }}" alt="{{ $gallery->title }}" class="max-h-64 rounded-md border border-slate-200">
                    @elseif($gallery->file_path)
                        <img src="{{ \App\Helpers\ImageHelper::getUrl($gallery->file_path) }}" alt="{{ $gallery->title }}" class="max-h-64 rounded-md border border-slate-200">
                    @else
                        <p class="text-gray-500">No preview available</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3 mt-8 pt-6 divider">
            <a href="{{ route('admin.galleries.edit', $gallery->id) }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit
            </a>
            <a href="{{ route('admin.galleries.index') }}" class="btn btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali
            </a>
        </div>
    </div>
</div>
@endsection