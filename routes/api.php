<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\ParticipantController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\MerchandiseController;
use App\Http\Controllers\API\SponsorController;
use App\Http\Controllers\API\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

    Route::get('/events/upcoming', [EventController::class, 'upcoming']);
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{id}', [EventController::class, 'show']);

    Route::get('/sponsors', [SponsorController::class, 'index']);
    Route::get('/organization', [OrganizationController::class, 'index']);
    Route::get('/merchandise', [MerchandiseController::class, 'index']);
    Route::get('/merchandise/{id}', [MerchandiseController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/events/{eventId}/register', [EventController::class, 'register']);

        Route::get('/participants', [ParticipantController::class, 'index']);
        Route::get('/participants/{id}', [ParticipantController::class, 'show']);
        Route::put('/participants/{id}', [ParticipantController::class, 'update']);
        Route::get('/participants/{id}/events', [ParticipantController::class, 'events']);
        Route::get('/participants/{id}/attendance', [ParticipantController::class, 'attendance']);

        Route::post('/payments/create', [PaymentController::class, 'store']);
        Route::get('/payments/{id}', [PaymentController::class, 'show']);
        Route::get('/payments/history', [PaymentController::class, 'history']);

        Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);
        Route::get('/attendance/{eventId}', [AttendanceController::class, 'byEvent']);
        Route::post('/attendance/scan', [AttendanceController::class, 'scan']);

        Route::post('/merchandise/order', [MerchandiseController::class, 'order']);
    });
});
