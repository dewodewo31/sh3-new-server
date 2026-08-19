<?php

namespace App\Http\Controllers\API;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UploadProfilePhotoRequest;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function __construct(
        private ProfileService $profileService,
    ) {}

    public function show(): JsonResponse
    {
        $user = auth()->user();
        $data = $this->profileService->getProfilePayload($user);

        if (! $data['participant']) {
            return response()->json(['message' => 'Profil peserta tidak ditemukan.'], 404);
        }

        return response()->json(['data' => $data]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth()->user();

        if (! $this->profileService->getCurrentParticipant($user)) {
            return response()->json(['message' => 'Profil peserta tidak ditemukan.'], 404);
        }

        $result = $this->profileService->update($user, $request->validated());

        return response()->json([
            'data' => $this->profileService->getProfilePayload($result),
            'message' => 'Profil berhasil diupdate',
        ]);
    }

    public function uploadPhoto(UploadProfilePhotoRequest $request): JsonResponse
    {
        $user = auth()->user();

        if (! $this->profileService->getCurrentParticipant($user)) {
            return response()->json(['message' => 'Profil peserta tidak ditemukan.'], 404);
        }

        $avatar = $this->profileService->uploadPhoto($user, $request->file('avatar'));

        return response()->json([
            'data' => [
                'avatar' => $avatar,
                'url' => ImageHelper::getUrl($avatar),
            ],
            'message' => 'Foto profil berhasil diupload',
        ]);
    }
}
