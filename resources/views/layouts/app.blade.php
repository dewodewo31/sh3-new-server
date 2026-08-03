<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function () {
            var stored = localStorage.getItem('sh3-theme');
            var dark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (dark) document.documentElement.classList.add('dark');
        })();
    </script>
    @stack('head')
</head>
<body class="overflow-x-clip bg-slate-50 font-sans antialiased dark:bg-slate-900">
    <div class="flex min-h-screen w-full overflow-x-clip" x-data="{ sidebarOpen: false, toastVisible: false }">
        @auth
            @include('includes.sidebar')
        @endauth

        <div class="flex min-w-0 flex-1 flex-col transition-all duration-200">
            @auth
                @include('includes.navbar')
            @endauth

            <main class="w-full min-w-0 flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div class="mx-auto w-full min-w-0 max-w-7xl">
                    {{-- Breadcrumb --}}
                    @hasSection('breadcrumb')
                        <div class="animate-fade-in">
                            @yield('breadcrumb')
                        </div>
                    @endif

                    {{-- Page header --}}
                    <div class="page-header animate-fade-in @hasSection('breadcrumb') mt-4 @endif">
                        <div>
                            <h1>@yield('title', 'Dashboard')</h1>
                            <p>@yield('subtitle', '&nbsp;')</p>
                        </div>
                        @hasSection('actions')
                            <div class="flex flex-wrap items-center gap-2.5">
                                @yield('actions')
                            </div>
                        @endif
                    </div>

                    {{-- Flash alerts --}}
                    @include('includes.alerts')

                    {{-- Page content --}}
                    <div class="animate-slide-up" style="animation-delay: 50ms">
                        @yield('content')
                    </div>
                </div>
            </main>

            @auth
                @include('includes.footer')
            @endauth
        </div>
    </div>

    {{-- Toast placeholder --}}
    @include('includes.toast')

    @stack('scripts')
</body>
</html>
