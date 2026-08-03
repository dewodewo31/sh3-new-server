@extends('layouts.app')

@section('title', 'Edit Event')
@section('subtitle', 'Perbarui detail event yang sudah ada')

@section('breadcrumb')
    @include('includes.breadcrumb', ['items' => [
        ['label' => 'Events', 'url' => route('admin.events.index')],
        ['label' => 'Edit Event'],
    ]])
@endsection

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="card">
        <div class="card-header">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/>
                    </svg>
                </div>
                <div>
                    <h3 class="card-header-title">Edit Event</h3>
                    <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">Perbarui informasi event</p>
                </div>
            </div>
        </div>

        <div class="card-body">
            <form action="{{ route('admin.events.update', $event->id) }}" method="POST" enctype="multipart/form-data" novalidate>
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    {{-- Status --}}
                    <div class="form-group md:col-span-2">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            @foreach (['draft', 'publish', 'ongoing', 'completed', 'cancelled'] as $status)
                                <option value="{{ $status }}" {{ old('status', $event->status) === $status ? 'selected' : '' }}>
                                    {{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Title --}}
                    <div class="form-group md:col-span-2">
                        <label for="title" class="form-label">Judul Event <span class="text-red-500">*</span></label>
                        <input type="text" id="title" name="title" class="form-input @error('title') error @enderror" value="{{ old('title', $event->title) }}" required>
                        @error('title')
                            <p class="form-error" role="alert">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Category --}}
                    <div class="form-group">
                        <label for="category_id" class="form-label">Kategori <span class="text-red-500">*</span></label>
                        <select id="category_id" name="category_id" class="form-select @error('category_id') error @enderror" required>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $event->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="form-error" role="alert">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Location --}}
                    <div class="form-group">
                        <label for="location" class="form-label">Lokasi</label>
                        <input type="text" id="location" name="location" class="form-input @error('location') error @enderror" value="{{ old('location', $event->location) }}" placeholder="cth: Lapangan Monas">
                        @error('location')
                            <p class="form-error" role="alert">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Start Date --}}
                    <div class="form-group">
                        <label for="start_date" class="form-label">Tanggal Mulai <span class="text-red-500">*</span></label>
                        <input type="datetime-local" id="start_date" name="start_date" class="form-input @error('start_date') error @enderror" value="{{ old('start_date', $event->start_date->format('Y-m-d\TH:i')) }}" required>
                        @error('start_date')
                            <p class="form-error" role="alert">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- End Date --}}
                    <div class="form-group">
                        <label for="end_date" class="form-label">Tanggal Selesai <span class="text-red-500">*</span></label>
                        <input type="datetime-local" id="end_date" name="end_date" class="form-input @error('end_date') error @enderror" value="{{ old('end_date', $event->end_date->format('Y-m-d\TH:i')) }}" required>
                        @error('end_date')
                            <p class="form-error" role="alert">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Registration Start --}}
                    <div class="form-group">
                        <label for="registration_start_date" class="form-label">Pendaftaran Dibuka <span class="text-red-500">*</span></label>
                        <input type="datetime-local" id="registration_start_date" name="registration_start_date" class="form-input @error('registration_start_date') error @enderror" value="{{ old('registration_start_date', $event->registration_start_date?->format('Y-m-d\TH:i')) }}" required>
                        @error('registration_start_date')
                            <p class="form-error" role="alert">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Registration End --}}
                    <div class="form-group">
                        <label for="registration_end_date" class="form-label">Pendaftaran Ditutup <span class="text-red-500">*</span></label>
                        <input type="datetime-local" id="registration_end_date" name="registration_end_date" class="form-input @error('registration_end_date') error @enderror" value="{{ old('registration_end_date', $event->registration_end_date?->format('Y-m-d\TH:i')) }}" required>
                        @error('registration_end_date')
                            <p class="form-error" role="alert">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Quota --}}
                    <div class="form-group">
                        <label for="quota" class="form-label">Kuota Peserta</label>
                        <input type="number" id="quota" name="quota" min="1" class="form-input @error('quota') error @enderror" value="{{ old('quota', $event->quota) }}" placeholder="cth: 500">
                        <p class="form-hint">Kosongkan untuk kuota tanpa batas.</p>
                        @error('quota')
                            <p class="form-error" role="alert">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Price --}}
                    <div class="form-group">
                        <label for="price" class="form-label">Harga Tiket (Rp)</label>
                        <input type="number" id="price" name="price" min="0" step="0.01" class="form-input @error('price') error @enderror" value="{{ old('price', $event->price) }}" placeholder="cth: 150000">
                        <p class="form-hint">Kosongkan atau isi 0 untuk event gratis.</p>
                        @error('price')
                            <p class="form-error" role="alert">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Free for members --}}
                    <div class="form-group md:col-span-2">
                        <label class="form-check-group cursor-pointer">
                            <input type="checkbox" name="is_free_for_members" value="1" {{ old('is_free_for_members', $event->is_free_for_members) ? 'checked' : '' }} class="form-check">
                            <span>
                                <span class="block font-medium text-slate-800 dark:text-slate-200">Gratis untuk anggota</span>
                                <span class="block text-xs text-slate-400 dark:text-slate-500">Peserta dengan keanggotaan aktif tidak dikenakan biaya.</span>
                            </span>
                        </label>
                    </div>

                    {{-- Image --}}
                    <div class="form-group">
                        <label for="image" class="form-label">Gambar</label>
                        <input type="file" id="image" name="image" accept="image/*" class="form-input file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-500/10 dark:file:text-blue-400 dark:hover:file:bg-blue-500/20 @error('image') error @enderror">
                        <p class="form-hint">Maksimal 2MB, format JPG/PNG.</p>
                        @error('image')
                            <p class="form-error" role="alert">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Banner --}}
                    <div class="form-group">
                        <label for="banner" class="form-label">Banner</label>
                        <input type="file" id="banner" name="banner" accept="image/*" class="form-input file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-500/10 dark:file:text-blue-400 dark:hover:file:bg-blue-500/20 @error('banner') error @enderror">
                        <p class="form-hint">Maksimal 2MB, format JPG/PNG.</p>
                        @error('banner')
                            <p class="form-error" role="alert">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Address --}}
                    <div class="form-group md:col-span-2">
                        <label for="address" class="form-label">Alamat Lengkap</label>
                        <textarea id="address" name="address" rows="2" class="form-textarea @error('address') error @enderror" placeholder="Alamat detail lokasi event">{{ old('address', $event->address) }}</textarea>
                        @error('address')
                            <p class="form-error" role="alert">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div class="form-group md:col-span-2">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea id="description" name="description" rows="4" class="form-textarea @error('description') error @enderror" placeholder="Jelaskan rute, benefit, dan informasi penting lainnya">{{ old('description', $event->description) }}</textarea>
                        @error('description')
                            <p class="form-error" role="alert">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div class="mt-8 flex flex-col-reverse items-stretch gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700/60">
                    <div class="flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:items-center">
                        <a href="{{ route('admin.events.show', $event->id) }}" class="btn btn-secondary">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Detail
                        </a>
                        <a href="{{ route('admin.events.index') }}" class="btn btn-ghost">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                            </svg>
                            Kembali
                        </a>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Update Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
