@extends('layouts.app')

@php
    $initialForm = [
        'key' => old('key', ''),
        'name' => old('name', ''),
        'description' => old('description', ''),
        'price' => old('price') !== null ? (int) old('price') : '',
        'duration' => old('duration', 12),
        'duration_unit' => old('duration_unit', 'months'),
        'sort_order' => old('sort_order', $nextSortOrder),
        'is_active' => old('is_active') !== null ? (bool) old('is_active') : true,
    ];

    $emptyForm = $initialForm;
    $emptyForm['key'] = '';
    $emptyForm['name'] = '';
    $emptyForm['description'] = '';
    $emptyForm['price'] = '';
    $emptyForm['duration'] = 12;
    $emptyForm['duration_unit'] = 'months';
    $emptyForm['sort_order'] = $nextSortOrder;
    $emptyForm['is_active'] = true;

    $formConfig = [
        'storeUrl' => route('admin.membership-plans.store'),
        'updateUrlTemplate' => route('admin.membership-plans.update', ['id' => '__ID__']),
        'destroyUrlTemplate' => route('admin.membership-plans.destroy', ['id' => '__ID__']),
        'initialMode' => old('editing_id') ? 'edit' : 'create',
        'initialEditingId' => old('editing_id') ? (int) old('editing_id') : null,
        'initialForm' => $initialForm,
        'emptyForm' => $emptyForm,
    ];
@endphp

@section('breadcrumb')
    @include('includes.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Memberships', 'url' => route('admin.memberships.index')],
        ['label' => 'Kelola Paket Membership'],
    ]])
@endsection

@section('title', 'Kelola Paket Membership')
@section('subtitle', 'Kelola nama, harga, durasi, dan status paket membership')

@section('content')
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3"
     x-data="planForm({{ Js::from($formConfig) }})">

    {{-- ============ Kolom Kiri: Form Tambah / Edit Plan ============ --}}
    <div id="plan-form-card" class="card h-fit min-w-0 lg:col-span-1">
        <div class="card-header">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                </span>
                <div>
                    <h3 class="card-header-title" x-text="isEdit ? 'Edit Plan' : 'Tambah Plan'"></h3>
                    <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500" x-text="isEdit ? 'Perbarui paket membership yang dipilih' : 'Buat paket membership baru'"></p>
                </div>
            </div>
        </div>

        <div class="card-body">
            {{-- Banner mode edit --}}
            <template x-if="isEdit">
                <div class="mb-5 flex items-center gap-3 rounded-xl bg-blue-50 px-4 py-3 text-sm text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold">Mode Edit</p>
                        <p class="truncate text-xs opacity-80" x-text="'Mengedit plan: ' + form.name"></p>
                    </div>
                    <button type="button" class="shrink-0 rounded-lg p-1 transition-colors hover:bg-blue-100 dark:hover:bg-blue-500/20" @click="resetForm" aria-label="Batal edit">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </template>

            <form method="POST" :action="actionUrl" @submit="saving = true">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                <input type="hidden" name="editing_id" :value="editingId ?? ''">
                <input type="hidden" name="price" :value="form.price">

                <div class="space-y-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="form-group">
                            <label for="plan-key" class="form-label">Key (identifier)</label>
                            <div class="relative">
                                <input type="text" id="plan-key" name="key" x-model="form.key" @input="onKeyInput"
                                    class="form-input !pr-20 @error('key') error @enderror"
                                    placeholder="otomatis dari nama"
                                    aria-label="Key plan"
                                    required pattern="[a-z0-9_]+">
                                <button type="button" @click="generateKey"
                                    class="absolute inset-y-1 right-1 rounded-lg px-2.5 text-xs font-semibold text-blue-600 transition-colors hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-500/10"
                                    aria-label="Generate key dari nama">Generate</button>
                            </div>
                            @error('key') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="form-hint">Auto-generate dari nama. Contoh: <code>tahunan</code></p>
                        </div>

                        <div class="form-group">
                            <label for="plan-name" class="form-label">Nama Plan</label>
                            <input type="text" id="plan-name" name="name" x-model="form.name" @input="onNameInput"
                                class="form-input @error('name') error @enderror"
                                placeholder="cth: Tahunan"
                                aria-label="Nama plan"
                                required>
                            @error('name') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="plan-price" class="form-label">Harga (Rupiah)</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-sm font-medium text-slate-400">Rp</span>
                            <input type="text" id="plan-price" inputmode="numeric" :value="priceDisplay" @input="onPriceInput"
                                class="form-input !pl-10"
                                placeholder="cth: 400.000"
                                aria-label="Harga plan dalam Rupiah"
                                required>
                        </div>
                        @error('price') <p class="form-error">{{ $message }}</p> @enderror
                        <p class="form-hint">Format Rupiah otomatis dengan pemisah ribuan, contoh: 400.000</p>
                    </div>

                    <div class="form-group">
                        <label for="plan-duration" class="form-label">Durasi</label>
                        <div class="flex items-stretch overflow-hidden rounded-xl border border-slate-300 shadow-sm transition-all duration-200 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 dark:border-slate-600">
                            <input type="number" id="plan-duration" name="duration" x-model.number="form.duration"
                                min="1" max="365" required
                                class="w-full min-w-0 rounded-none border-0 bg-transparent px-4 py-2.5 text-sm text-slate-900 shadow-none outline-none placeholder:text-slate-400 focus:ring-0 dark:text-slate-100 dark:placeholder:text-slate-500"
                                placeholder="12"
                                aria-label="Durasi plan">
                            <div class="relative flex items-center border-l border-slate-200 bg-slate-50 dark:border-slate-600 dark:bg-slate-700/50">
                                <select name="duration_unit" x-model="form.duration_unit"
                                    class="appearance-none border-0 bg-transparent py-2.5 pl-3 pr-8 text-sm font-medium text-slate-700 outline-none focus:ring-0 dark:text-slate-200"
                                    aria-label="Satuan durasi">
                                    <option value="months">Bulan</option>
                                    <option value="days">Hari</option>
                                </select>
                                <svg class="pointer-events-none absolute right-3 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                            </div>
                        </div>
                        @error('duration') <p class="form-error">{{ $message }}</p> @enderror
                        @error('duration_unit') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="plan-description" class="form-label">Deskripsi (opsional)</label>
                        <input type="text" id="plan-description" name="description" x-model="form.description"
                            class="form-input @error('description') error @enderror"
                            placeholder="cth: Membership 12 bulan"
                            aria-label="Deskripsi plan">
                        @error('description') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="form-group">
                            <label for="plan-sort" class="form-label">Urutan</label>
                            <div class="flex items-stretch">
                                <input type="number" id="plan-sort" name="sort_order" x-model.number="form.sort_order"
                                    min="0"
                                    class="form-input w-full !pr-10"
                                    aria-label="Urutan plan">
                                <div class="ml-2 flex flex-col">
                                    <button type="button" @click="adjustSort(1)"
                                        class="flex h-1/2 items-center justify-center rounded-t-lg border border-slate-300 bg-white px-2.5 text-slate-500 transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:hover:bg-slate-700"
                                        aria-label="Naikkan urutan">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5"/></svg>
                                    </button>
                                    <button type="button" @click="adjustSort(-1)"
                                        class="flex h-1/2 items-center justify-center rounded-b-lg border-x border-b border-slate-300 bg-white px-2.5 text-slate-500 transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:hover:bg-slate-700"
                                        aria-label="Turunkan urutan">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                                    </button>
                                </div>
                            </div>
                            <p class="form-hint">Angka kecil menentukan posisi di daftar.</p>
                        </div>

                        <div class="form-group">
                            <span class="form-label">Status</span>
                            <label class="flex cursor-pointer items-center pt-1">
                                <span class="toggle">
                                    <input type="checkbox" name="is_active" value="1" class="peer sr-only" x-model="form.is_active" aria-label="Status aktif plan">
                                    <span class="toggle-track"></span>
                                    <span class="toggle-thumb"></span>
                                </span>
                                <span class="toggle-label" x-text="form.is_active ? 'Aktif' : 'Tidak Aktif'"></span>
                            </label>
                            <p class="form-hint">Plan nonaktif tidak tampil pada daftar API.</p>
                        </div>
                    </div>

                    <div class="space-y-3 border-t border-slate-100 pt-5 dark:border-slate-700/60">
                        <template x-if="!isEdit">
                            <button type="submit" class="btn btn-primary w-full" :disabled="saving">
                                <svg x-show="!saving" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                <svg x-show="saving" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span x-text="saving ? 'Menyimpan...' : 'Simpan Plan'"></span>
                            </button>
                        </template>

                        <template x-if="isEdit">
                            <div class="grid grid-cols-2 gap-3">
                                <button type="submit" class="btn btn-primary" :disabled="saving">
                                    <svg x-show="!saving" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    <svg x-show="saving" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <span x-text="saving ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                                </button>
                                <button type="button" @click="destroyPlan({ id: editingId, name: form.name })" class="btn btn-danger" :disabled="saving">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    Hapus
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ============ Kolom Kanan: Daftar Plan ============ --}}
    <div class="card min-w-0 lg:col-span-2">
        <div class="card-header">
            <div>
                <h3 class="card-header-title">Daftar Plan</h3>
                <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">
                    Total <span class="font-medium text-slate-600 dark:text-slate-300">{{ $plans->total() }}</span> plan membership
                </p>
            </div>
        </div>

        <div class="border-b border-slate-100 px-5 py-4 sm:px-6 dark:border-slate-700/60">
            <form method="GET" action="{{ route('admin.membership-plans.index') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative flex-1 sm:max-w-xs">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    <input type="search" name="search" value="{{ request('search') }}" class="search-input" placeholder="Cari nama atau key..." aria-label="Cari plan">
                </div>
                <select name="status" class="form-select h-9 w-full !py-1.5 text-sm sm:w-44" aria-label="Filter status plan">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                </select>
                <div class="flex items-center gap-2">
                    <button type="submit" class="btn btn-secondary btn-sm">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/></svg>
                        Terapkan
                    </button>
                    @if(request('search') || request('status'))
                        <a href="{{ route('admin.membership-plans.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-wrap w-full max-w-full overflow-x-auto !rounded-none !border-0">
            <table class="min-w-full whitespace-nowrap">
                <thead>
                    <tr>
                        <th class="w-14">No</th>
                        <th>Nama Plan</th>
                        <th>Harga</th>
                        <th>Durasi</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $plan)
                    <tr>
                        <td class="!py-3.5 text-slate-400 dark:text-slate-500">{{ $plans->firstItem() + $loop->index }}</td>
                        <td class="!py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-slate-900 dark:text-white">{{ $plan->name }}</p>
                                    <p class="truncate text-xs text-slate-400 dark:text-slate-500">
                                        <code class="font-mono">{{ $plan->key }}</code>
                                        @if($plan->description) · {{ $plan->description }} @endif
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="!py-3.5 font-medium text-slate-900 dark:text-slate-100">{{ $plan->priceLabel() }}</td>
                        <td class="!py-3.5 text-slate-600 dark:text-slate-300">
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                {{ $plan->durationLabel() }}
                            </span>
                        </td>
                        <td class="!py-3.5">
                            @if($plan->is_active)
                                <span class="badge badge-success"><span class="badge-dot bg-emerald-500"></span>Aktif</span>
                            @else
                                <span class="badge badge-secondary"><span class="badge-dot bg-slate-400"></span>Tidak Aktif</span>
                            @endif
                        </td>
                        <td class="!py-3.5">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" class="icon-btn" @click="startEdit({{ Js::from($plan->only(['id','key','name','description','price','duration','duration_unit','sort_order','is_active'])) }})" aria-label="Edit plan {{ $plan->name }}" title="Edit">
                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                                </button>
                                <button type="button" class="icon-btn text-red-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10" @click="destroyPlan({{ Js::from($plan->only(['id','name'])) }})" aria-label="Hapus plan {{ $plan->name }}" title="Hapus">
                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
                                <p class="empty-state-title">{{ request('search') || request('status') ? 'Plan tidak ditemukan' : 'Belum ada plan' }}</p>
                                <p class="empty-state-text">{{ request('search') || request('status') ? 'Coba ubah kata kunci atau filter status.' : 'Tambahkan plan membership pertama melalui formulir di samping.' }}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('includes.pagination', ['items' => $plans, 'label' => 'plan'])
    </div>

    {{-- Form hapus tersembunyi (di dalam scope x-data), action diset langsung sebelum submit --}}
    <form x-ref="deleteForm" method="POST" class="hidden" aria-hidden="true">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('planForm', (config) => ({
            storeUrl: config.storeUrl,
            updateUrlTemplate: config.updateUrlTemplate,
            destroyUrlTemplate: config.destroyUrlTemplate,

            mode: config.initialMode,
            editingId: config.initialEditingId,
            deleteId: null,
            saving: false,
            keyAuto: config.initialMode === 'create',

            form: { ...config.initialForm },

            get isEdit() {
                return this.mode === 'edit';
            },

            get actionUrl() {
                return this.isEdit
                    ? this.updateUrlTemplate.replace('__ID__', this.editingId)
                    : this.storeUrl;
            },

            get deleteUrl() {
                return this.destroyUrlTemplate.replace('__ID__', this.deleteId);
            },

            get priceDisplay() {
                const p = this.form.price;
                if (p === '' || p === null || p === undefined) return '';
                const n = Number(p) || 0;
                return n.toLocaleString('id-ID');
            },

            slugify(text) {
                return String(text).toLowerCase().trim()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9\s_-]/g, '')
                    .replace(/[\s-]+/g, '_')
                    .replace(/^_+|_+$/g, '');
            },

            onNameInput(event) {
                this.form.name = event.target.value;
                if (this.keyAuto) {
                    this.form.key = this.slugify(this.form.name);
                }
            },

            onKeyInput(event) {
                this.form.key = event.target.value;
                this.keyAuto = false;
            },

            generateKey() {
                this.form.key = this.slugify(this.form.name);
                this.keyAuto = true;
            },

            onPriceInput(event) {
                const raw = String(event.target.value).replace(/[^\d]/g, '');
                this.form.price = raw === '' ? '' : parseInt(raw, 10);
            },

            adjustSort(step) {
                this.form.sort_order = Math.max(0, (Number(this.form.sort_order) || 0) + step);
            },

            startEdit(plan) {
                this.mode = 'edit';
                this.editingId = plan.id;
                this.keyAuto = false;
                this.saving = false;
                this.form = {
                    key: plan.key,
                    name: plan.name,
                    description: plan.description || '',
                    price: plan.price,
                    duration: plan.duration,
                    duration_unit: plan.duration_unit,
                    sort_order: plan.sort_order ?? 0,
                    is_active: plan.is_active,
                };
                this.$nextTick(() => {
                    document.getElementById('plan-form-card')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                });
            },

            resetForm() {
                this.mode = 'create';
                this.editingId = null;
                this.deleteId = null;
                this.saving = false;
                this.keyAuto = true;
                this.form = { ...config.emptyForm };
            },

            destroyPlan(plan) {
                this.deleteId = plan.id;
                if (window.confirm('Hapus plan "' + plan.name + '"?')) {
                    const form = this.$refs.deleteForm;
                    form.action = this.deleteUrl;
                    form.submit();
                } else {
                    this.deleteId = null;
                }
            },
        }));
    });
</script>

@if (session('success') || session('error'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            var success = @js(session('success'));
            var error = @js(session('error'));

            var removeInlineAlert = function (type, message) {
                document.querySelectorAll('.alert-' + type + ' .alert-desc').forEach(function (el) {
                    if (el.textContent.trim() === message) {
                        var alertEl = el.closest('.alert');
                        if (alertEl) alertEl.remove();
                    }
                });
            };

            if (success && window.showToast) {
                window.showToast('success', 'Berhasil', success);
                removeInlineAlert('success', success);
            }
            if (error && window.showToast) {
                window.showToast('error', 'Gagal', error);
                removeInlineAlert('error', error);
            }
        }, 300);
    });
</script>
@endif
@endpush
