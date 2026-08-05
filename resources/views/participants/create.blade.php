@extends('layouts.app')

@section('title', 'Tambah Peserta')
@section('subtitle', 'Register a new participant')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Tambah Peserta</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.participants.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" id="name" class="form-input @error('name') error @enderror" value="{{ old('name') }}" required>
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-input @error('email') error @enderror" value="{{ old('email') }}" required>
                    @error('email') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" id="phone" class="form-input @error('phone') error @enderror" value="{{ old('phone') }}">
                    @error('phone') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Gender</label>
                    <select name="gender" id="gender" class="form-select">
                        <option value="">-- Pilih --</option>
                        <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tanggal Lahir</label>
                    <input type="date" name="date_of_birth" id="date_of_birth" class="form-input @error('date_of_birth') error @enderror" value="{{ old('date_of_birth') }}">
                    @error('date_of_birth') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Jersey Size</label>
                    <select name="jersey_size" id="jersey_size" class="form-select">
                        <option value="">-- Pilih --</option>
                        @foreach(['XS','S','M','L','XL','XXL'] as $size)
                            <option value="{{ $size }}" {{ old('jersey_size') == $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Blood Type</label>
                    <input type="text" name="blood_type" id="blood_type" class="form-input @error('blood_type') error @enderror" value="{{ old('blood_type') }}" placeholder="A/B/AB/O">
                    @error('blood_type') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Emergency Contact</label>
                    <input type="text" name="emergency_contact" id="emergency_contact" class="form-input @error('emergency_contact') error @enderror" value="{{ old('emergency_contact') }}">
                    @error('emergency_contact') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Emergency Phone</label>
                    <input type="text" name="emergency_phone" id="emergency_phone" class="form-input @error('emergency_phone') error @enderror" value="{{ old('emergency_phone') }}">
                    @error('emergency_phone') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2 form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" id="address" rows="2" class="form-textarea @error('address') error @enderror">{{ old('address') }}</textarea>
                    @error('address') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2 form-group">
                    <label class="form-label">Medical Conditions</label>
                    <textarea name="medical_conditions" id="medical_conditions" rows="2" class="form-textarea @error('medical_conditions') error @enderror">{{ old('medical_conditions') }}</textarea>
                    @error('medical_conditions') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex items-center gap-3 mt-8 pt-6 divider">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    Simpan
                </button>
                <a href="{{ route('admin.participants.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/></svg>
                    Kembali
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
