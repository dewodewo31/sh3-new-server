@extends('layouts.app')

@section('title', 'Tambah Sponsor')
@section('subtitle', 'Add a new sponsor')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Tambah Sponsor</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.sponsors.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="form-input @error('name') error @enderror">
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Tier</label>
                    <select name="tier" required class="form-select @error('tier') error @enderror">
                        @foreach(['platinum','gold','silver','bronze','media_partner'] as $tier)
                            <option value="{{ $tier }}" {{ old('tier') == $tier ? 'selected' : '' }}>{{ ucfirst($tier) }}</option>
                        @endforeach
                    </select>
                    @error('tier') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Person</label>
                    <input type="text" name="contact_person" value="{{ old('contact_person') }}" class="form-input @error('contact_person') error @enderror">
                    @error('contact_person') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Email</label>
                    <input type="email" name="contact_email" value="{{ old('contact_email') }}" class="form-input @error('contact_email') error @enderror">
                    @error('contact_email') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Phone</label>
                    <input type="text" name="contact_phone" value="{{ old('contact_phone') }}" class="form-input @error('contact_phone') error @enderror">
                    @error('contact_phone') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Website</label>
                    <input type="url" name="website" value="{{ old('website') }}" class="form-input @error('website') error @enderror">
                    @error('website') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Year</label>
                    <input type="number" name="year" value="{{ old('year', date('Y')) }}" class="form-input @error('year') error @enderror">
                    @error('year') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Sponsorship Value</label>
                    <input type="number" name="sponsorship_value" value="{{ old('sponsorship_value') }}" step="0.01" class="form-input @error('sponsorship_value') error @enderror">
                    @error('sponsorship_value') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-input @error('sort_order') error @enderror">
                    @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Logo</label>
                    <input type="file" name="logo" class="form-input @error('logo') error @enderror">
                    @error('logo') <p class="form-error">{{ $message }}</p> @enderror
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
                <a href="{{ route('admin.sponsors.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
