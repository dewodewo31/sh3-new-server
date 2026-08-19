<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\User;
use App\Notifications\AdminNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NotificationService
{
    public function getLatest(?int $limit = 20): \Illuminate\Support\Collection
    {
        return auth()->user()
            ->notifications()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($notification) => $this->format($notification));
    }

    public function getLatestPaginated(int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return auth()->user()
            ->notifications()
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getUnreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    public function getById(string $id)
    {
        return auth()->user()->notifications()->find($id);
    }

    public function markAsRead(string $id): bool
    {
        $notification = $this->getById($id);
        if (! $notification) {
            return false;
        }
        $notification->markAsRead();

        return true;
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
    }

    public function format($notification): array
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

    public function notifyAdmins(string $title, string $body, string $icon = 'bell', ?string $url = null): void
    {
        $users = User::where('is_active', true)
            ->whereIn('role', [
                'admin_full_access',
                'admin_laman',
                'admin_member',
                'admin_bnh',
                'organizer',
                'bendahara',
                'sponsor',
                'merchandise',
            ])
            ->get();

        foreach ($users as $user) {
            $user->notify(new AdminNotification($title, $body, $icon, $url));
        }
    }

    public function notifyRoles(array $roles, string $title, string $body, string $icon = 'bell', ?string $url = null): void
    {
        $users = User::where('is_active', true)
            ->whereIn('role', $roles)
            ->get();

        foreach ($users as $user) {
            $user->notify(new AdminNotification($title, $body, $icon, $url));
        }
    }

    public function notifyUser(User $user, string $title, string $body, string $icon = 'bell', ?string $url = null): void
    {
        $user->notify(new AdminNotification($title, $body, $icon, $url));
    }

    public function notifyParticipant(Participant $participant, string $title, string $body, string $icon = 'bell', ?string $url = null): void
    {
        $user = $participant->user;

        if (! $user || ! $user->is_active) {
            return;
        }

        $this->notifyUser($user, $title, $body, $icon, $url);
    }
}
