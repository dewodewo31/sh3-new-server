@extends('layouts.app')

@section('title', 'Tambah Item Inventaris')
@section('subtitle', 'Catat aset baru ke inventaris klub')

@section('breadcrumb')
    @include('includes.breadcrumb', [
        'items' => [
            ['label' => 'Inventory', 'url' => route('admin.inventory.index')],
            ['label' => 'Tambah Item'],
        ],
    ])
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Tambah Item Inventaris</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.inventory.store') }}" method="POST">
            @csrf
            @include('inventory._form')
            <div class="flex items-center gap-3 mt-8 pt-6 divider">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan
                </button>
                <a href="{{ route('admin.inventory.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
