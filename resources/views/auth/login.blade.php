<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen bg-gray-50 font-sans antialiased">
    <div class="flex flex-1 flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-sm animate-slide-up">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 shadow-lg shadow-indigo-500/25">
                    <span class="text-xl font-bold text-white">SH</span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ config('app.name') }}</h1>
                <p class="mt-1.5 text-sm text-gray-500">Sign in to your account</p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <form action="{{ route('login') }}" method="POST">
                    @csrf

                    <div class="space-y-4">
                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                                   class="form-input @error('email') error @enderror"
                                   placeholder="name@example.com">
                            @error('email')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Password</label>
                            <input type="password" name="password" id="password" required
                                   class="form-input @error('password') error @enderror"
                                   placeholder="Enter your password">
                            @error('password')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                                <input type="checkbox" name="remember" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span>Remember me</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit"
                                class="btn btn-primary w-full py-2.5">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                            Sign in
                        </button>
                    </div>
                </form>
            </div>

            <p class="mt-6 text-center text-xs text-gray-400">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
