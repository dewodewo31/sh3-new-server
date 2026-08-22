<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\BookkeepingController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventBudgetController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\FinancialAccountController;
use App\Http\Controllers\Admin\GalleryAlbumController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\GuestSponsorController;
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

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware(RoleMiddleware::class.':admin_full_access,admin_laman,admin_member,admin_bnh,organizer,bendahara,merchandise,gallery');

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

        Route::middleware([RoleMiddleware::class.':admin_full_access,admin_laman,gallery'])->group(function () {
            Route::resource('galleries', GalleryController::class);
            Route::post('/gallery-albums/sync', [GalleryAlbumController::class, 'syncNow'])->name('gallery-albums.sync');
            Route::resource('gallery-albums', GalleryAlbumController::class);
        });

        Route::middleware([RoleMiddleware::class.':admin_full_access,admin_laman'])->group(function () {
            Route::resource('categories', CategoryController::class);
            Route::resource('organization', OrganizationController::class);
        });

        Route::middleware([RoleMiddleware::class.':admin_full_access,admin_laman'])->group(function () {
            Route::resource('sponsors', SponsorController::class);
        });

        Route::middleware([RoleMiddleware::class.':admin_full_access'])->group(function () {
            Route::get('/guest-sponsors', [GuestSponsorController::class, 'index'])->name('guest-sponsors.index');
            Route::get('/guest-sponsors/create', [GuestSponsorController::class, 'create'])->name('guest-sponsors.create');
            Route::post('/guest-sponsors', [GuestSponsorController::class, 'store'])->name('guest-sponsors.store');
            Route::get('/guest-sponsors/{id}', [GuestSponsorController::class, 'show'])->whereNumber('id')->name('guest-sponsors.show');
            Route::put('/guest-sponsors/{id}', [GuestSponsorController::class, 'update'])->whereNumber('id')->name('guest-sponsors.update');
            Route::delete('/guest-sponsors/{id}', [GuestSponsorController::class, 'destroy'])->whereNumber('id')->name('guest-sponsors.destroy');
            Route::post('/guest-sponsors/{id}/toggle-active', [GuestSponsorController::class, 'toggleActive'])->whereNumber('id')->name('guest-sponsors.toggle-active');
            Route::post('/guest-sponsors/quota', [GuestSponsorController::class, 'setQuota'])->name('guest-sponsors.quota');
        });

        Route::middleware([RoleMiddleware::class.':admin_full_access,admin_laman,merchandise'])->group(function () {
            Route::resource('merchandise', MerchandiseController::class);
        });

        Route::middleware([RoleMiddleware::class.':admin_full_access,bendahara'])->group(function () {
            Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
            Route::get('/payments/{id}', [PaymentController::class, 'show'])->name('payments.show');
            Route::put('/payments/{id}/confirm', [PaymentController::class, 'confirm'])->name('payments.confirm');
            Route::put('/payments/{id}/reject', [PaymentController::class, 'reject'])->name('payments.reject');

            Route::get('/bookkeepings/reports', [BookkeepingController::class, 'reports'])->name('bookkeepings.reports');
            Route::get('/bookkeepings/receivables', [BookkeepingController::class, 'receivables'])->name('bookkeepings.receivables');
            Route::get('/bookkeepings/payables', [BookkeepingController::class, 'payables'])->name('bookkeepings.payables');
            Route::get('/bookkeepings/cash-flow', [BookkeepingController::class, 'cashFlow'])->name('bookkeepings.cash-flow');

            Route::get('/bookkeepings/export', [BookkeepingController::class, 'export'])->name('bookkeepings.export');
            Route::get('/bookkeepings/export-budget-vs-actual', [BookkeepingController::class, 'exportBudgetVsActual'])->name('bookkeepings.export-budget-vs-actual');
            Route::get('/bookkeepings/export-cash-flow', [BookkeepingController::class, 'exportCashFlow'])->name('bookkeepings.export-cash-flow');
            Route::get('/bookkeepings/export-receivables', [BookkeepingController::class, 'exportReceivables'])->name('bookkeepings.export-receivables');
            Route::get('/bookkeepings/export-payable', [BookkeepingController::class, 'exportPayable'])->name('bookkeepings.export-payable');

            Route::put('bookkeepings/{id}/submit', [BookkeepingController::class, 'submit'])->name('bookkeepings.submit');

            Route::resource('bookkeepings', BookkeepingController::class);
            Route::resource('financial-accounts', FinancialAccountController::class);
            Route::resource('activities', ActivityController::class);
            Route::resource('event-budgets', EventBudgetController::class);

            Route::middleware([RoleMiddleware::class.':'.implode(',', config('sh3.financial_approver_roles'))])->group(function () {
                Route::put('bookkeepings/{id}/approve', [BookkeepingController::class, 'approve'])->name('bookkeepings.approve');
                Route::put('bookkeepings/{id}/mark-paid', [BookkeepingController::class, 'markPaid'])->name('bookkeepings.mark-paid');
                Route::put('bookkeepings/{id}/cancel', [BookkeepingController::class, 'cancel'])->name('bookkeepings.cancel');
            });
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
