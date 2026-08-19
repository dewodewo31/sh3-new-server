<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    public function index(): JsonResponse|View
    {
        if (request()->wantsJson()) {
            return response()->json([
                'data' => $this->notificationService->getLatest(),
                'unread_count' => $this->notificationService->getUnreadCount(),
            ]);
        }

        $notifications = $this->notificationService->getLatestPaginated();
        $unreadCount = $this->notificationService->getUnreadCount();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'unread_count' => $this->notificationService->getUnreadCount(),
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        if (! $this->notificationService->markAsRead($id)) {
            return response()->json(['message' => 'Notifikasi tidak ditemukan.'], 404);
        }

        return response()->json(['message' => 'Notifikasi ditandai dibaca.']);
    }

    public function markAllAsRead(): JsonResponse
    {
        $this->notificationService->markAllAsRead();

        return response()->json(['message' => 'Semua notifikasi ditandai dibaca.']);
    }
}
