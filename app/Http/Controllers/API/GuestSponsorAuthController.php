<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Repositories\GuestSponsorRepository;
use App\Services\AuthService;
use App\Services\GuestSponsorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestSponsorAuthController extends Controller
{
    public function __construct(
        private GuestSponsorService $guestSponsorService,
        private GuestSponsorRepository $guestSponsorRepository,
        private AuthService $authService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->guestSponsorService->authenticate(
            $request->validated()['username'],
            $request->validated()['password'],
        );

        $token = $this->authService->generateToken($user);

        $guestSponsor = $this->guestSponsorRepository->findByUser($user->id);

        return response()->json([
            'message' => 'Login berhasil',
            'token' => $token,
            'guest_sponsor' => [
                'id' => $guestSponsor->id,
                'name' => $user->name,
                'username' => $user->username,
                'sponsor_name' => $guestSponsor->sponsor?->name,
                'event_id' => $guestSponsor->event_id,
                'event_title' => $guestSponsor->event?->title,
                'qr_code' => $guestSponsor->qr_code,
                'valid_until' => $guestSponsor->valid_until?->toDateString(),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $guestSponsor = $this->guestSponsorRepository->findByUser($user->id);

        if (! $guestSponsor) {
            return response()->json(['message' => 'Akun guest sponsor tidak ditemukan.'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $guestSponsor->id,
                'name' => $user->name,
                'username' => $user->username,
                'sponsor_name' => $guestSponsor->sponsor?->name,
                'event_id' => $guestSponsor->event_id,
                'event_title' => $guestSponsor->event?->title,
                'qr_code' => $guestSponsor->qr_code,
                'valid_from' => $guestSponsor->valid_from?->toDateString(),
                'valid_until' => $guestSponsor->valid_until?->toDateString(),
                'is_active' => (bool) $guestSponsor->is_active,
                'usable' => $this->guestSponsorService->isUsableForEvent($guestSponsor, $guestSponsor->event),
            ],
        ]);
    }
}
