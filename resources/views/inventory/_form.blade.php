@php
    $item = $item ?? null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="form-group">
        <label class="form-label">Asset Code</label>
        @if ($item)
            <input type="text" value="{{ $item->asset_code }}" class="form-input font-mono bg-slate-50 dark:bg-slate-800" readonly>
        @else
            <input type="text" value="Otomatis (AST-0001)" class="form-input font-mono bg-slate-50 dark:bg-slate-800" readonly disabled>
        @endif
        <p class="text-xs text-slate-500 dark:text-slate-400">Kode dibuat otomatis saat disimpan.</p>
    </div>

    <div class="form-group">
        <label class="form-label">Nama Item *</label>
        <input type="text" name="name" value="{{ old('name', $item->name ?? '') }}" required class="form-input @error('name') error @enderror">
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label class="form-label">Kategori</label>
        <select name="category_id" class="form-select @error('category_id') error @enderror">
            <option value="">— Tanpa kategori —</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" {{ old('category_id', $item->category_id ?? '') == $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        @error('category_id') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label class="form-label">Serial Number</label>
        <input type="text" name="serial_number" value="{{ old('serial_number', $item->serial_number ?? '') }}" class="form-input font-mono @error('serial_number') error @enderror">
        @error('serial_number') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label class="form-label">Brand</label>
        <input type="text" name="brand" value="{{ old('brand', $item->brand ?? '') }}" class="form-input @error('brand') error @enderror">
        @error('brand') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label class="form-label">Model</label>
        <input type="text" name="model" value="{{ old('model', $item->model ?? '') }}" class="form-input @error('model') error @enderror">
        @error('model') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label class="form-label">Tanggal Pembelian</label>
        <input type="date" name="purchase_date" value="{{ old('purchase_date', isset($item) && $item->purchase_date ? $item->purchase_date->format('Y-m-d') : '') }}" class="form-input @error('purchase_date') error @enderror">
        @error('purchase_date') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label class="form-label">Harga Pembelian (Rp)</label>
        <input type="number" name="purchase_price" value="{{ old('purchase_price', $item->purchase_price ?? '') }}" step="0.01" min="0" class="form-input @error('purchase_price') error @enderror">
        @error('purchase_price') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label class="form-label">Garansi Berakhir</label>
        <input type="date" name="warranty_expiry" value="{{ old('warranty_expiry', isset($item) && $item->warranty_expiry ? $item->warranty_expiry->format('Y-m-d') : '') }}" class="form-input @error('warranty_expiry') error @enderror">
        @error('warranty_expiry') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label class="form-label">Lokasi</label>
        <input type="text" name="location" value="{{ old('location', $item->location ?? '') }}" placeholder="Gudang / Ruangan" class="form-input @error('location') error @enderror">
        @error('location') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label class="form-label">Status</label>
        <select name="status" class="form-select @error('status') error @enderror">
            @foreach (\App\Models\InventoryItem::STATUSES as $status)
                <option value="{{ $status }}" {{ old('status', $item->status ?? 'available') === $status ? 'selected' : '' }}>
                    {{ ucwords(str_replace('_', ' ', $status)) }}
                </option>
            @endforeach
        </select>
        @error('status') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div class="form-group">
        <label class="form-label">Kondisi</label>
        <select name="condition" class="form-select @error('condition') error @enderror">
            @foreach (\App\Models\InventoryItem::CONDITIONS as $cond)
                <option value="{{ $cond }}" {{ old('condition', $item->condition ?? 'good') === $cond ? 'selected' : '' }}>
                    {{ ucfirst($cond) }}
                </option>
            @endforeach
        </select>
        @error('condition') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div class="md:col-span-2 form-group">
        <label class="form-label">Deskripsi</label>
        <textarea name="description" rows="2" class="form-textarea @error('description') error @enderror">{{ old('description', $item->description ?? '') }}</textarea>
        @error('description') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div class="md:col-span-2 form-group">
        <label class="form-label">Catatan</label>
        <textarea name="notes" rows="2" class="form-textarea @error('notes') error @enderror">{{ old('notes', $item->notes ?? '') }}</textarea>
        @error('notes') <p class="form-error">{{ $message }}</p> @enderror
    </div>
</div>
