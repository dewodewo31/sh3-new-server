<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\User;
use App\Notifications\AdminNotification;

class NotificationService
{
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
