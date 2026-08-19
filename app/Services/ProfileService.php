<?php

namespace App\Services;

use App\Helpers\ImageHelper;
use App\Http\Resources\ParticipantResource;
use App\Http\Resources\UserResource;
use App\Models\User;

class ProfileService
{
    public function getCurrentParticipant(User $User)
    {
        return $User?->participants()->with('membershipPlan')->first();
    }

    public function getProfilePayload(User $user): array
    {
        $participant = $this->getCurrentParticipant($user);

        if ($participant) {
            $participant->load('membershipHistories');
        }

        return [
            'user' => new UserResource($user),
            'participant' => $participant ? new ParticipantResource($participant) : null,
        ];
    }

    public function update(User $user, array $data): ?User
    {
        $participant = $this->getCurrentParticipant($user);

        if (! $participant) {
            return null;
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $participantData = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        foreach ($this->participantFields() as $field) {
            if (array_key_exists($field, $data)) {
                $participantData[$field] = $data[$field];
            }
        }

        $participant->update($participantData);

        return $user->fresh();
    }

    public function uploadPhoto(User $user, $file): string
    {
        if ($user->avatar) {
            ImageHelper::delete($user->avatar);
        }

        $avatar = ImageHelper::upload($file, 'avatars');
        $user->update(['avatar' => $avatar]);

        return $avatar;
    }

    private function participantFields(): array
    {
        return [
            'phone',
            'gender',
            'date_of_birth',
            'address',
            'emergency_contact',
            'emergency_phone',
            'medical_conditions',
            'blood_type',
            'jersey_size',
        ];
    }
}
