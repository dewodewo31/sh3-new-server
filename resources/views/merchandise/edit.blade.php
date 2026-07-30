@extends('layouts.app')

@section('title', 'Edit Merchandise')
@section('subtitle', 'Update merchandise item')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Edit Merchandise</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.merchandise.update', $item->id) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="{{ old('name', $item->name) }}" required class="form-input @error('name') error @enderror">
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Price (Rp)</label>
                    <input type="number" name="price" value="{{ old('price', $item->price) }}" step="0.01" required class="form-input @error('price') error @enderror">
                    @error('price') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Stock</label>
                    <input type="number" name="stock" value="{{ old('stock', $item->stock) }}" required class="form-input @error('stock') error @enderror">
                    @error('stock') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select @error('status') error @enderror">
                        <option value="available" {{ old('status', $item->status) == 'available' ? 'selected' : '' }}>Available</option>
                        <option value="sold_out" {{ old('status', $item->status) == 'sold_out' ? 'selected' : '' }}>Sold Out</option>
                        <option value="discontinued" {{ old('status', $item->status) == 'discontinued' ? 'selected' : '' }}>Discontinued</option>
                    </select>
                    @error('status') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Size Options (JSON)</label>
                    <input type="text" name="size_options" value="{{ old('size_options', $item->size_options) }}" placeholder='["S","M","L","XL"]' class="form-input @error('size_options') error @enderror">
                    @error('size_options') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Image</label>
                    <input type="file" name="image" class="form-input @error('image') error @enderror">
                    @error('image') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2 form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="2" class="form-textarea @error('description') error @enderror">{{ old('description', $item->description) }}</textarea>
                    @error('description') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex items-center gap-3 mt-8 pt-6 divider">
                <button type="submit" class="btn btn-warning">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Update
                </button>
                <a href="{{ route('admin.merchandise.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
