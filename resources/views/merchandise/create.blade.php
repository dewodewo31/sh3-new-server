@extends('layouts.app')

@section('title', 'Tambah Merchandise')
@section('subtitle', 'Add new merchandise item')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Tambah Merchandise</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.merchandise.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="form-input @error('name') error @enderror">
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Price (Rp)</label>
                    <input type="number" name="price" value="{{ old('price') }}" step="0.01" required class="form-input @error('price') error @enderror">
                    @error('price') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Stock</label>
                    <input type="number" name="stock" value="{{ old('stock', 0) }}" required class="form-input @error('stock') error @enderror">
                    @error('stock') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select @error('status') error @enderror">
                        <option value="available" {{ old('status') == 'available' ? 'selected' : '' }}>Available</option>
                        <option value="sold_out" {{ old('status') == 'sold_out' ? 'selected' : '' }}>Sold Out</option>
                        <option value="discontinued" {{ old('status') == 'discontinued' ? 'selected' : '' }}>Discontinued</option>
                    </select>
                    @error('status') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Size Options (JSON)</label>
                    <input type="text" name="size_options" value="{{ old('size_options') }}" placeholder='["S","M","L","XL"]' class="form-input @error('size_options') error @enderror">
                    @error('size_options') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Image</label>
                    <input type="file" name="image" class="form-input @error('image') error @enderror">
                    @error('image') <p class="form-error">{{ $message }}</p> @enderror
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
                <a href="{{ route('admin.merchandise.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
