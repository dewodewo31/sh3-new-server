@extends('layouts.app')

@section('title', 'Edit Akun Keuangan')
@section('subtitle', 'Perbarui akun keuangan')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Edit Akun Keuangan</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.financial-accounts.update', $financialAccount->id) }}" method="POST">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="{{ old('name', $financialAccount->name) }}" required class="form-input @error('name') error @enderror">
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Tipe</label>
                    <select name="type" required class="form-select @error('type') error @enderror">
                        <option value="kas" {{ old('type', $financialAccount->type) == 'kas' ? 'selected' : '' }}>Kas</option>
                        <option value="bank" {{ old('type', $financialAccount->type) == 'bank' ? 'selected' : '' }}>Bank</option>
                        <option value="piutang" {{ old('type', $financialAccount->type) == 'piutang' ? 'selected' : '' }}>Piutang</option>
                        <option value="hutang" {{ old('type', $financialAccount->type) == 'hutang' ? 'selected' : '' }}>Hutang</option>
                        <option value="pendapatan" {{ old('type', $financialAccount->type) == 'pendapatan' ? 'selected' : '' }}>Pendapatan</option>
                        <option value="beban" {{ old('type', $financialAccount->type) == 'beban' ? 'selected' : '' }}>Beban</option>
                    </select>
                    @error('type') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2 form-group">
                    <label class="form-label">Aktif</label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $financialAccount->is_active) ? 'checked' : '' }} class="form-checkbox">
                        <span class="text-sm text-slate-600 dark:text-slate-300">Akun aktif</span>
                    </label>
                    @error('is_active') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex items-center gap-3 mt-8 pt-6 divider">
                <button type="submit" class="btn btn-warning">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Update
                </button>
                <a href="{{ route('admin.financial-accounts.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
