<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Password-reset emails link here, then redirect to the SPA reset page.
    Route::get('reset-password/{token}', function (string $token, Request $request) {
        $base = config('app.frontend_url');
        $email = $request->query('email', '');

        return redirect($base.'/reset-password?token='.$token.'&email='.urlencode($email));
    })->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
