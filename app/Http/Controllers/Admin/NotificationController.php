<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): JsonResponse|View
    {
        $notificationsQuery = auth()->user()->notifications()->latest();

        if (request()->wantsJson()) {
            return response()->json([
                'data' => $notificationsQuery->limit(20)->get()->map(fn ($notification) => $this->format($notification)),
                'unread_count' => auth()->user()->unreadNotifications()->count(),
            ]);
        }

        $notifications = $notificationsQuery->paginate(15)->withQueryString();
        $unreadCount = auth()->user()->unreadNotifications()->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'unread_count' => auth()->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = auth()->user()->notifications()->find($id);

        if (! $notification) {
            return response()->json(['message' => 'Notifikasi tidak ditemukan.'], 404);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Notifikasi ditandai dibaca.']);
    }

    public function markAllAsRead(): JsonResponse
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => 'Semua notifikasi ditandai dibaca.']);
    }

    private function format($notification): array
    {
        $data = $notification->data;

        return [
            'id' => $notification->id,
            'title' => $data['title'] ?? 'Notifikasi',
            'body' => $data['body'] ?? '',
            'icon' => $data['icon'] ?? 'bell',
            'url' => $data['url'] ?? null,
            'read_at' => $notification->read_at,
            'created_at' => $notification->created_at->diffForHumans(),
            'created_at_raw' => $notification->created_at->toISOString(),
        ];
    }
}
