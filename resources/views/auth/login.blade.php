<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
    <script>
        (function () {
            var stored = localStorage.getItem('sh3-theme');
            var dark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (dark) document.documentElement.classList.add('dark');
        })();
    </script>
</head>
<body class="bg-slate-50 font-sans antialiased dark:bg-slate-900">
    <div class="grid min-h-screen lg:grid-cols-2">
        {{-- Brand panel (desktop) --}}
        <div class="relative hidden overflow-hidden bg-slate-900 lg:flex lg:flex-col lg:justify-between">
            <div class="pointer-events-none absolute -left-24 -top-24 h-96 w-96 rounded-full bg-blue-500/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-32 -right-24 h-96 w-96 rounded-full bg-indigo-500/20 blur-3xl"></div>

            <div class="relative flex h-16 items-center gap-3 px-8">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-bold shadow-lg shadow-indigo-500/30">
                    <span class="text-white">SH</span>
                </div>
                <span class="text-lg font-semibold tracking-tight text-white">SH3 <span class="font-normal text-slate-400">Admin</span></span>
            </div>

            <div class="relative px-8">
                <h1 class="text-balance text-4xl font-bold leading-tight tracking-tight text-white">
                    Kelola seluruh event lari klub Anda dalam satu tempat.
                </h1>
                <p class="mt-4 max-w-md text-slate-400">
                    Dashboard yang bersih dan intuitif untuk mengelola event, peserta, pembayaran, dan konten klub.
                </p>

                <div class="mt-10 space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-blue-400">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                        </span>
                        <span class="text-sm text-slate-300">Manajemen event real-time</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-blue-400">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
                        </span>
                        <span class="text-sm text-slate-300">Pelacakan pembayaran otomatis</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-blue-400">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <span class="text-sm text-slate-300">Laporan kehadiran yang akurat</span>
                    </div>
                </div>
            </div>

            <p class="relative px-8 pb-8 text-xs text-slate-500">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>

        {{-- Form panel --}}
        <div class="flex flex-col items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
            <div class="w-full max-w-sm animate-slide-up">
                {{-- Mobile brand --}}
                <div class="mb-8 text-center lg:hidden">
                    <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-lg shadow-indigo-500/25">
                        <span class="text-xl font-bold text-white">SH</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ config('app.name') }}</h1>
                    <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">Masuk ke akun Anda</p>
                </div>

                <div class="hidden lg:block">
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Selamat datang kembali</h2>
                    <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">Masuk untuk melanjutkan ke dashboard.</p>
                </div>

                <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_4px_12px_rgba(15,23,42,0.06)] sm:p-8 dark:border-slate-700/80 dark:bg-slate-800">
                    @if ($errors->any())
                        <div class="alert alert-error mb-6 animate-slide-up" role="alert">
                            <svg class="alert-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                            </svg>
                            <div>
                                <p class="alert-title">Gagal masuk</p>
                                <p class="alert-desc">{{ $errors->first() }}</p>
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('login') }}" method="POST" class="space-y-5" novalidate>
                        @csrf

                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-affix">
                                <svg class="input-affix-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                                </svg>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                                       autocomplete="email" class="form-input @error('email') error @enderror"
                                       placeholder="name@example.com">
                            </div>
                            @error('email')
                                <p class="form-error" role="alert">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-affix">
                                <svg class="input-affix-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                                </svg>
                                <input type="password" name="password" id="password" required
                                       autocomplete="current-password" class="form-input @error('password') error @enderror"
                                       placeholder="Masukkan password">
                            </div>
                            @error('password')
                                <p class="form-error" role="alert">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="form-check-group cursor-pointer">
                                <input type="checkbox" name="remember" class="form-check">
                                <span class="text-sm">Ingat saya</span>
                            </label>
                            <a href="#" class="link text-sm font-medium">Lupa password?</a>
                        </div>

                        <button type="submit" class="btn btn-primary w-full py-2.5">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                            </svg>
                            Masuk
                        </button>
                    </form>
                </div>

                <p class="mt-6 text-center text-xs text-slate-400 lg:hidden">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
