<?php

use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\MembershipController;
use App\Http\Controllers\Admin\MembershipPlanController;
use App\Http\Controllers\Admin\MerchandiseController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\ParticipantController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\SponsorController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/admin/dashboard');
    }

    return redirect('/login');
});

Route::middleware(['auth'])->group(function () {

    Route::prefix('admin')->name('admin.')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
            Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
        });

        Route::middleware([RoleMiddleware::class.':admin_full_access'])->group(function () {
            Route::resource('users', UserController::class);
            Route::put('/users/{id}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');

            Route::prefix('membership-plans')->name('membership-plans.')->group(function () {
                Route::get('/', [MembershipPlanController::class, 'index'])->name('index');
                Route::post('/', [MembershipPlanController::class, 'store'])->name('store');
                Route::put('/{id}', [MembershipPlanController::class, 'update'])->name('update');
                Route::delete('/{id}', [MembershipPlanController::class, 'destroy'])->name('destroy');
            });
        });

        Route::middleware([RoleMiddleware::class.':admin_full_access,admin_member'])->group(function () {
            Route::resource('participants', ParticipantController::class);
        });

        Route::middleware([RoleMiddleware::class.':admin_full_access,admin_member,bendahara'])->group(function () {
            Route::get('/memberships', [MembershipController::class, 'index'])->name('memberships.index');
            Route::get('/memberships/create', [MembershipController::class, 'create'])->name('memberships.create');
            Route::post('/memberships', [MembershipController::class, 'store'])->name('memberships.store');
            Route::post('/memberships/{id}/cancel', [MembershipController::class, 'cancel'])->name('memberships.cancel');
        });

        Route::middleware([RoleMiddleware::class.':admin_full_access,organizer'])->group(function () {
            Route::resource('events', EventController::class);
            Route::put('/events/{id}/publish', [EventController::class, 'publish'])->name('events.publish');
            Route::post('/events/{id}/cancel', [EventController::class, 'cancel'])->name('events.cancel');
        });

        Route::middleware([RoleMiddleware::class.':admin_full_access,admin_laman'])->group(function () {
            Route::resource('categories', CategoryController::class);
            Route::resource('galleries', GalleryController::class);
            Route::resource('organization', OrganizationController::class);
        });

        Route::middleware([RoleMiddleware::class.':admin_full_access,admin_laman,sponsor'])->group(function () {
            Route::resource('sponsors', SponsorController::class);
        });

        Route::middleware([RoleMiddleware::class.':admin_full_access,admin_laman,merchandise'])->group(function () {
            Route::resource('merchandise', MerchandiseController::class);
        });

        Route::middleware([RoleMiddleware::class.':admin_full_access,bendahara'])->group(function () {
            Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
            Route::get('/payments/{id}', [PaymentController::class, 'show'])->name('payments.show');
            Route::put('/payments/{id}/confirm', [PaymentController::class, 'confirm'])->name('payments.confirm');
            Route::put('/payments/{id}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
        });

        Route::middleware([RoleMiddleware::class.':admin_full_access,admin_laman'])->group(function () {
            Route::get('/attendance/event/{eventId}', [AttendanceController::class, 'byEvent'])->name('attendance.by-event');
            Route::get('/attendance/report', [AttendanceController::class, 'report'])->name('attendance.report');
            Route::get('/attendance/scan', [AttendanceController::class, 'scan'])->name('attendance.scan');
            Route::post('/attendance/scan', [AttendanceController::class, 'processScan'])->name('attendance.scan.process');
            Route::post('/attendance/event-participant/{id}/generate-qr', [AttendanceController::class, 'generateQr'])->name('attendance.generate-qr');
        });
    });
});

require __DIR__.'/auth.php';
