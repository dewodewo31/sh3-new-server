@extends('layouts.app')

@section('title', 'Edit User')
@section('subtitle', 'Update user information')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Edit User</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.users.update', $user->id) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="name" class="form-input @error('name') error @enderror" value="{{ old('name', $user->name) }}" required>
                        @error('name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-input @error('email') error @enderror" value="{{ old('email', $user->email) }}" required>
                        @error('email') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password <span class="text-gray-400 font-normal dark:text-slate-500">(kosongkan jika tidak diubah)</span></label>
                        <input type="password" name="password" id="password" class="form-input @error('password') error @enderror">
                        @error('password') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" id="role" class="form-select @error('role') error @enderror" required>
                            @foreach(['admin_full_access','admin_laman','admin_member','admin_bnh','organizer','bendahara','sponsor','merchandise'] as $role)
                                <option value="{{ $role }}" {{ old('role', $user->role) == $role ? 'selected' : '' }}>{{ str_replace('_', ' ', ucwords($role)) }}</option>
                            @endforeach
                        </select>
                        @error('role') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="is_active" id="is_active" class="form-select">
                            <option value="1" {{ old('is_active', $user->is_active) == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('is_active', $user->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Avatar</label>
                    @if($user->avatar)
                    <div class="mb-3">
                        <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="w-16 h-16 rounded-xl object-cover border border-gray-200 dark:border-slate-700">
                    </div>
                    @endif
                    <div class="flex items-center gap-4">
                        <label class="cursor-pointer rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 inline-flex items-center gap-2 transition-colors dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700/40">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                            Pilih File
                            <input type="file" name="avatar" id="avatar" class="hidden">
                        </label>
                        <span class="text-sm text-gray-400 dark:text-slate-500" id="avatar-filename">Tidak ada file dipilih</span>
                    </div>
                    @error('avatar') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex items-center gap-3 mt-8 pt-6 divider">
                <button type="submit" class="btn btn-warning">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                    Update
                </button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/></svg>
                    Kembali
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('avatar')?.addEventListener('change', function() {
    const name = this.files[0]?.name || 'Tidak ada file dipilih';
    document.getElementById('avatar-filename').textContent = name;
});
</script>
@endpush
@endsection
