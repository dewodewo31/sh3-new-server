<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <div class="flex min-h-screen" x-data="{ sidebarOpen: false }">
        @auth
            @include('includes.sidebar')
        @endauth

        <div class="flex flex-1 flex-col transition-all duration-200">
            @auth
                @include('includes.navbar')
            @endauth

            <main class="flex-1 p-6 lg:p-8">
                @if (session('success'))
                    <div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3.5 text-sm text-emerald-800 shadow-sm" role="alert">
                        <svg class="h-5 w-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ session('success') }}</span>
                        <button type="button" class="ml-auto rounded-lg p-1 text-emerald-500 transition-colors hover:bg-emerald-100" onclick="this.parentElement.remove()">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-3.5 text-sm text-red-800 shadow-sm" role="alert">
                        <svg class="h-5 w-5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                        <span>{{ session('error') }}</span>
                        <button type="button" class="ml-auto rounded-lg p-1 text-red-500 transition-colors hover:bg-red-100" onclick="this.parentElement.remove()">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @endif

                <div class="page-header animate-fade-in">
                    <h1>@yield('title', 'Dashboard')</h1>
                    <p>@yield('subtitle', '&nbsp;')</p>
                </div>

                <div class="animate-slide-up" style="animation-delay: 50ms">
                    @yield('content')
                </div>
            </main>

            @auth
                @include('includes.footer')
            @endauth
        </div>
    </div>

    @stack('scripts')
</body>
</html>
