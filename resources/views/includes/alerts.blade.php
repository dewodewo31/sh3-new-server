{{-- Reusable flash alerts. Handles success, error, warning, info. --}}
<div class="space-y-4">
    @if (session('success'))
        <div class="alert alert-success animate-slide-up" role="alert">
            <svg class="alert-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="flex-1">
                <p class="alert-title">Success</p>
                <p class="alert-desc">{{ session('success') }}</p>
            </div>
            <button type="button" class="rounded-lg p-1 opacity-60 transition-colors hover:bg-white/50 hover:opacity-100 dark:hover:bg-white/10" onclick="this.closest('.alert').remove()" aria-label="Dismiss alert">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-error animate-slide-up" role="alert">
            <svg class="alert-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <div class="flex-1">
                <p class="alert-title">Error</p>
                <p class="alert-desc">{{ session('error') }}</p>
            </div>
            <button type="button" class="rounded-lg p-1 opacity-60 transition-colors hover:bg-white/50 hover:opacity-100 dark:hover:bg-white/10" onclick="this.closest('.alert').remove()" aria-label="Dismiss alert">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning animate-slide-up" role="alert">
            <svg class="alert-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-1.5 6.75h3m-6.75 0a2.25 2.25 0 01-2.25-2.25V8.25L11.25 2.25H18a2.25 2.25 0 012.25 2.25v12a2.25 2.25 0 01-2.25 2.25H5.25z"/>
            </svg>
            <div class="flex-1">
                <p class="alert-title">Warning</p>
                <p class="alert-desc">{{ session('warning') }}</p>
            </div>
            <button type="button" class="rounded-lg p-1 opacity-60 transition-colors hover:bg-white/50 hover:opacity-100 dark:hover:bg-white/10" onclick="this.closest('.alert').remove()" aria-label="Dismiss alert">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error animate-slide-up" role="alert">
            <svg class="alert-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
            </svg>
            <div class="flex-1">
                <p class="alert-title">Terdapat {{ $errors->count() }} masalah pada formulir</p>
                <ul class="mt-1.5 list-inside list-disc space-y-0.5 opacity-90">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" class="rounded-lg p-1 opacity-60 transition-colors hover:bg-white/50 hover:opacity-100 dark:hover:bg-white/10" onclick="this.closest('.alert').remove()" aria-label="Dismiss alert">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    @endif
</div>
