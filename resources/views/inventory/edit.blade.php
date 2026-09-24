@extends('layouts.app')

@section('title', 'Edit Item Inventaris')
@section('subtitle', 'Perbarui data item inventaris')

@section('breadcrumb')
    @include('includes.breadcrumb', [
        'items' => [
            ['label' => 'Inventory', 'url' => route('admin.inventory.index')],
            ['label' => 'Edit Item'],
        ],
    ])
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">Edit Item Inventaris</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.inventory.update', $item->id) }}" method="POST">
            @csrf @method('PUT')
            @include('inventory._form', ['item' => $item])
            <div class="flex items-center gap-3 mt-8 pt-6 divider">
                <button type="submit" class="btn btn-warning">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Update
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
