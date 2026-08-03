<?php

use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\GalleryController;
use App\Http\Controllers\API\MembershipController;
use App\Http\Controllers\API\MerchandiseController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\OrganizationController;
use App\Http\Controllers\API\ParticipantController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\SponsorController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    Route::post('/auth/refresh', [AuthController::class, 'refresh'])->middleware('auth:sanctum');
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

    Route::get('/events/upcoming', [EventController::class, 'upcoming']);
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{id}', [EventController::class, 'show']);
    Route::get('/events/{id}/participants', [EventController::class, 'participants']);
    Route::get('/galleries', [GalleryController::class, 'index']);

    Route::get('/sponsors', [SponsorController::class, 'index']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/organization/stats', [OrganizationController::class, 'stats']);
    Route::get('/organization/tree', [OrganizationController::class, 'tree']);
    Route::get('/organization/years', [OrganizationController::class, 'years']);
    Route::get('/organization', [OrganizationController::class, 'index']);
    Route::get('/organization/{id}', [OrganizationController::class, 'show'])->whereNumber('id');
    Route::get('/merchandise', [MerchandiseController::class, 'index']);
    Route::get('/merchandise/{id}', [MerchandiseController::class, 'show'])->whereNumber('id');

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/events/{eventId}/register', [EventController::class, 'register']);
        Route::get('/my-events', [EventController::class, 'myEvents']);

        Route::middleware('role:admin_full_access,organizer')->group(function () {
            Route::post('/events', [EventController::class, 'store']);
            Route::put('/events/{id}', [EventController::class, 'update']);
            Route::delete('/events/{id}', [EventController::class, 'destroy']);
            Route::get('/events/{id}/qr', [EventController::class, 'qrCodes']);
        });

        Route::get('/participants', [ParticipantController::class, 'index']);
        Route::get('/participants/{id}', [ParticipantController::class, 'show']);
        Route::put('/participants/{id}', [ParticipantController::class, 'update']);
        Route::get('/participants/{id}/events', [ParticipantController::class, 'events']);
        Route::get('/participants/{id}/attendance', [ParticipantController::class, 'attendance']);

        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto']);

        Route::post('/payments/create', [PaymentController::class, 'store']);
        Route::get('/payments/{id}', [PaymentController::class, 'show']);
        Route::get('/payments/history', [PaymentController::class, 'history']);

        Route::middleware('role:admin_full_access,bendahara')->group(function () {
            Route::post('/payments/confirm/{id}', [PaymentController::class, 'confirm'])->whereNumber('id');
        });

        Route::get('/membership', [MembershipController::class, 'show']);
        Route::get('/membership/history', [MembershipController::class, 'history']);
        Route::get('/membership/plans', [MembershipController::class, 'plans']);
        Route::post('/membership/subscribe', [MembershipController::class, 'subscribe']);
        Route::post('/membership/cancel', [MembershipController::class, 'cancel']);

        Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);
        Route::post('/attendance/scan', [AttendanceController::class, 'scan']);
        Route::post('/attendance/sync-up', [AttendanceController::class, 'syncUp']);
        Route::get('/attendance/report', [AttendanceController::class, 'report']);
        Route::get('/attendance/sync-down', [AttendanceController::class, 'syncDown']);
        Route::get('/attendance/{eventId}', [AttendanceController::class, 'byEvent']);

        Route::post('/merchandise/order', [MerchandiseController::class, 'order']);
        Route::get('/merchandise/orders', [MerchandiseController::class, 'orders']);
        Route::get('/merchandise/orders/{id}', [MerchandiseController::class, 'orderDetail'])->whereNumber('id');
        Route::post('/merchandise/orders/{id}/cancel', [MerchandiseController::class, 'cancelOrder'])->whereNumber('id');
        Route::post('/merchandise/orders/{id}/payment', [MerchandiseController::class, 'uploadPayment'])->whereNumber('id');

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    });
});
