@extends('layouts.app')

@section('title', 'Buat Peminjaman Inventaris')
@section('subtitle', 'Catat peminjaman aset klub')

@section('breadcrumb')
    @include('includes.breadcrumb', [
        'items' => [
            ['label' => 'Inventory', 'url' => route('admin.inventory.index')],
            ['label' => 'Peminjaman', 'url' => route('admin.inventory.loans.index')],
            ['label' => 'Buat Peminjaman'],
        ],
    ])
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Buat Peminjaman</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.inventory.loans.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="form-group md:col-span-2">
                    <label class="form-label">Item Inventaris *</label>
                    <select name="inventory_item_id" required class="form-select @error('inventory_item_id') error @enderror">
                        <option value="">— Pilih item (tersedia) —</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}" {{ old('inventory_item_id') == $item->id ? 'selected' : '' }}>
                                {{ $item->asset_code }} — {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('inventory_item_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Peminjam *</label>
                    <input type="text" name="borrower_name" value="{{ old('borrower_name') }}" required class="form-input @error('borrower_name') error @enderror">
                    @error('borrower_name') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Tujuan</label>
                    <input type="text" name="purpose" value="{{ old('purpose') }}" placeholder="Long Run, dokumentasi, dll." class="form-input @error('purpose') error @enderror">
                    @error('purpose') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Tanggal Pinjam</label>
                    <input type="date" name="borrow_date" value="{{ old('borrow_date') }}" class="form-input @error('borrow_date') error @enderror">
                    @error('borrow_date') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Perkiraan Tanggal Kembali</label>
                    <input type="date" name="expected_return_date" value="{{ old('expected_return_date') }}" class="form-input @error('expected_return_date') error @enderror">
                    @error('expected_return_date') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group md:col-span-2">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" rows="3" class="form-input @error('notes') error @enderror">{{ old('notes') }}</textarea>
                    @error('notes') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center gap-3 mt-8 pt-6 divider">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan
                </button>
                <a href="{{ route('admin.inventory.loans.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
