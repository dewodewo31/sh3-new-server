@extends('layouts.app')

@section('title', 'Tambah Pembukuan')
@section('subtitle', 'Catat pemasukan atau pengeluaran baru')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Tambah Pembukuan</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.bookkeepings.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="transaction_date" value="{{ old('transaction_date') }}" required class="form-input @error('transaction_date') error @enderror">
                    @error('transaction_date') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Tipe</label>
                    <select name="type" required class="form-select @error('type') error @enderror">
                        <option value="income" {{ old('type') == 'income' ? 'selected' : '' }}>Pemasukan</option>
                        <option value="expense" {{ old('type') == 'expense' ? 'selected' : '' }}>Pengeluaran</option>
                    </select>
                    @error('type') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Kategori</label>
                    <select name="category" id="category" required class="form-select @error('category') error @enderror">
                        <option value="sponsor" {{ old('category') == 'sponsor' ? 'selected' : '' }}>Sponsor</option>
                        <option value="event_income" {{ old('category') == 'event_income' ? 'selected' : '' }}>Pendapatan Event</option>
                        <option value="other" {{ old('category') == 'other' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                    @error('category') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Jumlah</label>
                    <input type="number" name="amount" value="{{ old('amount') }}" step="0.01" min="0" required class="form-input @error('amount') error @enderror">
                    @error('amount') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group" id="sponsor-group" style="{{ old('category') == 'sponsor' ? '' : 'display:none' }}">
                    <label class="form-label">Sponsor</label>
                    <select name="sponsor_id" class="form-select @error('sponsor_id') error @enderror">
                        <option value="">Pilih Sponsor</option>
                        @foreach($sponsors as $sponsor)
                            <option value="{{ $sponsor->id }}" {{ old('sponsor_id') == $sponsor->id ? 'selected' : '' }}>{{ $sponsor->name }}</option>
                        @endforeach
                    </select>
                    @error('sponsor_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group" id="event-group" style="{{ old('category') == 'event_income' ? '' : 'display:none' }}">
                    <label class="form-label">Event</label>
                    <select name="event_id" class="form-select @error('event_id') error @enderror">
                        <option value="">Pilih Event</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}" {{ old('event_id') == $event->id ? 'selected' : '' }}>{{ $event->title }}</option>
                        @endforeach
                    </select>
                    @error('event_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2 form-group">
                    <label class="form-label">Keterangan</label>
                    <textarea name="description" rows="2" required class="form-textarea @error('description') error @enderror">{{ old('description') }}</textarea>
                    @error('description') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Bukti Nota</label>
                    <input type="file" name="receipt" accept="image/*" class="form-input @error('receipt') error @enderror">
                    @error('receipt') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex items-center gap-3 mt-8 pt-6 divider">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan
                </button>
                <a href="{{ route('admin.bookkeepings.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        var category = document.getElementById('category');
        var sponsorGroup = document.getElementById('sponsor-group');
        var eventGroup = document.getElementById('event-group');

        function toggleGroups() {
            if (!category) return;
            var value = category.value;
            sponsorGroup.style.display = value === 'sponsor' ? '' : 'none';
            eventGroup.style.display = value === 'event_income' ? '' : 'none';
        }

        if (category) {
            category.addEventListener('change', toggleGroups);
        }
    })();
</script>
@endsection