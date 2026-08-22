@extends('layouts.app')

@section('title', 'Edit Anggaran Event')
@section('subtitle', 'Perbarui anggaran per event dan aktivitas')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Edit Anggaran Event</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.event-budgets.update', $eventBudget->id) }}" method="POST">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Event</label>
                    <select name="event_id" required class="form-select @error('event_id') error @enderror">
                        <option value="">Pilih Event</option>
                        @foreach($events as $id => $name)
                            <option value="{{ $id }}" {{ old('event_id', $eventBudget->event_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('event_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Aktivitas</label>
                    <select name="activity_id" required class="form-select @error('activity_id') error @enderror">
                        <option value="">Pilih Aktivitas</option>
                        @foreach($activities as $id => $name)
                            <option value="{{ $id }}" {{ old('activity_id', $eventBudget->activity_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('activity_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Jumlah</label>
                    <input type="number" name="amount" value="{{ old('amount', $eventBudget->amount) }}" step="0.01" min="0" required class="form-input @error('amount') error @enderror">
                    @error('amount') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2 form-group">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" rows="2" class="form-textarea @error('notes') error @enderror">{{ old('notes', $eventBudget->notes) }}</textarea>
                    @error('notes') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex items-center gap-3 mt-8 pt-6 divider">
                <button type="submit" class="btn btn-warning">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Update
                </button>
                <a href="{{ route('admin.event-budgets.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
