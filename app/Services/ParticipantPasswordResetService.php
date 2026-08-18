<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ParticipantPasswordResetService
{
    /**
     * Validate that the username belongs to a participant whose hash_id matches.
     * Returns false for any mismatch without revealing which field is wrong.
     */
    public function verify(string $username, string $participantCode): bool
    {
        $user = $this->findParticipantUser($username);

        if (! $user) {
            return false;
        }

        $participant = $user->participants()->first();

        return $participant !== null && $participant->hash_id === $participantCode;
    }

    /**
     * Reset the participant's password when username + hash_id match.
     * Returns false when validation fails (no detail leaked).
     */
    public function reset(string $username, string $participantCode, string $password): bool
    {
        $user = $this->findParticipantUser($username);

        if (! $user) {
            return false;
        }

        $participant = $user->participants()->first();

        if (! $participant || $participant->hash_id !== $participantCode) {
            return false;
        }

        $user->update([
            'password' => Hash::make($password),
        ]);

        // Invalidate existing sessions/tokens.
        $user->tokens()->delete();

        $user->activityLogs()->create([
            'action' => 'Participant Password Reset',
            'details' => [
                'participant_id' => $participant->id,
                'username' => $username,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return true;
    }

    private function findParticipantUser(string $username): ?User
    {
        return User::where('username', $username)
            ->where('role', 'participant')
            ->first();
    }
}
