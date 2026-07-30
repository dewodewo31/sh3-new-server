<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\ParticipantController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\MerchandiseController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\SponsorController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware(['auth'])->group(function () {

    Route::prefix('admin')->name('admin.')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::middleware([RoleMiddleware::class . ':admin_full_access'])->group(function () {
            Route::resource('users', UserController::class);
            Route::put('/users/{id}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        });

        Route::middleware([RoleMiddleware::class . ':admin_full_access,admin_member'])->group(function () {
            Route::resource('participants', ParticipantController::class);
        });

        Route::middleware([RoleMiddleware::class . ':admin_full_access,organizer'])->group(function () {
            Route::resource('events', EventController::class);
            Route::put('/events/{id}/publish', [EventController::class, 'publish'])->name('events.publish');
        });

        Route::middleware([RoleMiddleware::class . ':admin_full_access,admin_laman'])->group(function () {
            Route::resource('categories', CategoryController::class);
            Route::resource('sponsors', SponsorController::class);
            Route::resource('galleries', GalleryController::class);
            Route::resource('organization', OrganizationController::class);
        });

        Route::middleware([RoleMiddleware::class . ':admin_full_access,admin_laman,merchandise'])->group(function () {
            Route::resource('merchandise', MerchandiseController::class);
        });

        Route::middleware([RoleMiddleware::class . ':admin_full_access,bendahara'])->group(function () {
            Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
            Route::get('/payments/{id}', [PaymentController::class, 'show'])->name('payments.show');
            Route::put('/payments/{id}/confirm', [PaymentController::class, 'confirm'])->name('payments.confirm');
            Route::put('/payments/{id}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
        });

        Route::middleware([RoleMiddleware::class . ':admin_full_access,admin_laman'])->group(function () {
            Route::get('/attendance/event/{eventId}', [AttendanceController::class, 'byEvent'])->name('attendance.by-event');
            Route::get('/attendance/report', [AttendanceController::class, 'report'])->name('attendance.report');
        });
    });
});

require __DIR__ . '/auth.php';
