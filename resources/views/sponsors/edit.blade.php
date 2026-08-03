@extends('layouts.app')

@section('title', 'Edit Sponsor')
@section('subtitle', 'Update sponsor details')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Edit Sponsor</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.sponsors.update', $sponsor->id) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="{{ old('name', $sponsor->name) }}" required class="form-input @error('name') error @enderror">
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Tier</label>
                    <select name="tier" required class="form-select @error('tier') error @enderror">
                        @foreach(['platinum','gold','silver','bronze','media_partner'] as $tier)
                            <option value="{{ $tier }}" {{ old('tier', $sponsor->tier) == $tier ? 'selected' : '' }}>{{ ucfirst($tier) }}</option>
                        @endforeach
                    </select>
                    @error('tier') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Person</label>
                    <input type="text" name="contact_person" value="{{ old('contact_person', $sponsor->contact_person) }}" class="form-input @error('contact_person') error @enderror">
                    @error('contact_person') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Email</label>
                    <input type="email" name="contact_email" value="{{ old('contact_email', $sponsor->contact_email) }}" class="form-input @error('contact_email') error @enderror">
                    @error('contact_email') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Phone</label>
                    <input type="text" name="contact_phone" value="{{ old('contact_phone', $sponsor->contact_phone) }}" class="form-input @error('contact_phone') error @enderror">
                    @error('contact_phone') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Website</label>
                    <input type="url" name="website" value="{{ old('website', $sponsor->website) }}" class="form-input @error('website') error @enderror">
                    @error('website') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Year</label>
                    <input type="number" name="year" value="{{ old('year', $sponsor->year) }}" class="form-input @error('year') error @enderror">
                    @error('year') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Sponsorship Value</label>
                    <input type="number" name="sponsorship_value" value="{{ old('sponsorship_value', $sponsor->sponsorship_value) }}" step="0.01" class="form-input @error('sponsorship_value') error @enderror">
                    @error('sponsorship_value') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $sponsor->sort_order) }}" class="form-input @error('sort_order') error @enderror">
                    @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Logo</label>
                    <input type="file" name="logo" class="form-input @error('logo') error @enderror">
                    @error('logo') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2 form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="2" class="form-textarea @error('description') error @enderror">{{ old('description', $sponsor->description) }}</textarea>
                    @error('description') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex items-center gap-3 mt-8 pt-6 divider">
                <button type="submit" class="btn btn-warning">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Update
                </button>
                <a href="{{ route('admin.sponsors.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
