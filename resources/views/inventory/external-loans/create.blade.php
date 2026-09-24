@extends('layouts.app')

@section('title', 'Buat Pinjaman Barang')
@section('subtitle', 'Catat pinjaman dari perusahaan / komunitas lain')

@section('breadcrumb')
    @include('includes.breadcrumb', [
        'items' => [
            ['label' => 'Inventaris', 'url' => route('admin.inventory.index')],
            ['label' => 'Pinjaman Barang', 'url' => route('admin.inventory.external-loans.index')],
            ['label' => 'Buat Pinjaman'],
        ],
    ])
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Buat Pinjaman Barang</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.inventory.external-loans.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="form-group">
                    <label class="form-label">Perusahaan / Komunitas *</label>
                    <input type="text" name="external_party" value="{{ old('external_party') }}" required placeholder="Nama pihak yang meminjamkan" class="form-input @error('external_party') error @enderror">
                    @error('external_party') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Kontak</label>
                    <input type="text" name="contact_name" value="{{ old('contact_name') }}" class="form-input @error('contact_name') error @enderror">
                    @error('contact_name') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Kontak (HP / Email)</label>
                    <input type="text" name="contact_info" value="{{ old('contact_info') }}" class="form-input @error('contact_info') error @enderror">
                    @error('contact_info') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Tujuan</label>
                    <input type="text" name="purpose" value="{{ old('purpose') }}" placeholder="Long Run, dokumentasi, dll." class="form-input @error('purpose') error @enderror">
                    @error('purpose') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group md:col-span-2">
                    <label class="form-label">Daftar Barang *</label>
                    <textarea name="items_description" rows="4" required placeholder="Sebutkan barang yang dipinjam, mis. 2 unit tenda dome, 1 pcs sound portable..." class="form-input @error('items_description') error @enderror">{{ old('items_description') }}</textarea>
                    @error('items_description') <p class="form-error">{{ $message }}</p> @enderror
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
                <a href="{{ route('admin.inventory.external-loans.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
