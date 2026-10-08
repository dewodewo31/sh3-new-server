<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ParticipantPasswordResetService
{
    /**
     * Validate that the username belongs to a participant whose permanent
     * member hash or NM non-member code matches.
     * Returns false for any mismatch without revealing which field is wrong.
     */
    public function verify(string $username, string $participantCode): bool
    {
        $user = $this->findParticipantUser($username);

        if (! $user) {
            return false;
        }

        $participant = $user->participants()->first();

        return $participant !== null && $this->codeMatches($participant, $participantCode);
    }

    /**
     * Reset the participant's password when username + participant code match.
     * Returns false when validation fails (no detail leaked).
     */
    public function reset(string $username, string $participantCode, string $password): bool
    {
        $user = $this->findParticipantUser($username);

        if (! $user) {
            return false;
        }

        $participant = $user->participants()->first();

        if (! $participant || ! $this->codeMatches($participant, $participantCode)) {
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

    private function codeMatches(Participant $participant, string $participantCode): bool
    {
        return $participant->hash_id === $participantCode
            || $participant->non_member_code === $participantCode;
    }
}
