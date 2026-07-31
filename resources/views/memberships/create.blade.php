@extends('layouts.app')

@section('title', 'Beri Membership')
@section('subtitle', 'Grant a membership to a participant')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Beri Membership</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.memberships.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Peserta</label>
                    <select name="participant_id" id="participant_id" class="form-select @error('participant_id') error @enderror" required>
                        <option value="">-- Pilih Peserta --</option>
                        @foreach($participants as $p)
                            <option value="{{ $p->id }}" {{ old('participant_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->email }}) - {{ $p->membership_type !== 'none' ? $p->membershipTypeLabel() : 'Non Member' }}
                            </option>
                        @endforeach
                    </select>
                    @error('participant_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Tipe Membership</label>
                    <select name="membership_type" id="membership_type" class="form-select @error('membership_type') error @enderror" required>
                        <option value="">-- Pilih Tipe --</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan['type'] }}" {{ old('membership_type') == $plan['type'] ? 'selected' : '' }}>
                                {{ $plan['name'] }} - Rp {{ number_format($plan['price'], 0, ',', '.') }} ({{ $plan['duration'] }})
                            </option>
                        @endforeach
                    </select>
                    @error('membership_type') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Durasi (bulan, opsional)</label>
                    <input type="number" name="duration_months" id="duration_months" class="form-input @error('duration_months') error @enderror" value="{{ old('duration_months') }}" min="1" max="12" placeholder="Default sesuai tipe">
                    @error('duration_months') <p class="form-error">{{ $message }}</p> @enderror
                    <p class="form-hint">Kosongkan untuk memakai durasi default plan.</p>
                </div>
            </div>

            <div class="flex items-center gap-3 mt-8 pt-6 divider">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    Berikan Membership
                </button>
                <a href="{{ route('admin.memberships.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/></svg>
                    Kembali
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
