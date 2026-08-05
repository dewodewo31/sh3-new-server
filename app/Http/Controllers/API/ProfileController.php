<?php

namespace App\Http\Controllers\API;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UploadProfilePhotoRequest;
use App\Http\Resources\ParticipantResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function show(): JsonResponse
    {
        $user = auth()->user();

        if (! $this->currentParticipant($user)) {
            return response()->json(['message' => 'Profil peserta tidak ditemukan.'], 404);
        }

        return response()->json([
            'data' => $this->profilePayload($user),
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth()->user();
        $participant = $this->currentParticipant($user);

        if (! $participant) {
            return response()->json(['message' => 'Profil peserta tidak ditemukan.'], 404);
        }

        $data = $request->validated();

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

        return response()->json([
            'data' => $this->profilePayload($user->fresh()),
            'message' => 'Profil berhasil diupdate',
        ]);
    }

    public function uploadPhoto(UploadProfilePhotoRequest $request): JsonResponse
    {
        $user = auth()->user();

        if (! $this->currentParticipant($user)) {
            return response()->json(['message' => 'Profil peserta tidak ditemukan.'], 404);
        }

        if ($user->avatar) {
            ImageHelper::delete($user->avatar);
        }

        $avatar = ImageHelper::upload($request->file('avatar'), 'avatars');
        $user->update(['avatar' => $avatar]);

        return response()->json([
            'data' => [
                'avatar' => $avatar,
                'url' => ImageHelper::getUrl($avatar),
            ],
            'message' => 'Foto profil berhasil diupload',
        ]);
    }

    private function currentParticipant($user)
    {
        return $user?->participants()->first();
    }

    private function profilePayload($user): array
    {
        $participant = $user->participants()->with('membershipHistories')->first();

        return [
            'user' => new UserResource($user),
            'participant' => $participant ? new ParticipantResource($participant) : null,
        ];
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
