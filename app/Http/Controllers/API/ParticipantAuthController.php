<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParticipantResetPasswordRequest;
use App\Http\Requests\ParticipantVerifyResetRequest;
use App\Services\ParticipantPasswordResetService;
use Illuminate\Http\JsonResponse;

class ParticipantAuthController extends Controller
{
    public function __construct(
        private ParticipantPasswordResetService $service
    ) {}

    public function verifyReset(ParticipantVerifyResetRequest $request): JsonResponse
    {
        $valid = $this->service->verify(
            $request->input('username'),
            $request->input('hash_id'),
        );

        if (! $valid) {
            return response()->json([
                'success' => false,
                'message' => 'Data participant tidak valid.',
            ]);
        }

        return response()->json([
            'success' => true,
            'can_reset' => true,
        ]);
    }

    public function resetPassword(ParticipantResetPasswordRequest $request): JsonResponse
    {
        $done = $this->service->reset(
            $request->input('username'),
            $request->input('hash_id'),
            $request->input('password'),
        );

        if (! $done) {
            return response()->json([
                'success' => false,
                'message' => 'Data participant tidak valid.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui.',
        ]);
    }
}
