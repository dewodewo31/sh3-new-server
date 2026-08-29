<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\PointService;
use Illuminate\Http\JsonResponse;

class PointController extends Controller
{
    public function __construct(
        private PointService $pointService,
    ) {}

    /**
     * GET /api/v1/points/balance — current flat-point balance.
     */
    public function balance(): JsonResponse
    {
        $participant = $this->currentParticipant();

        if (! $participant) {
            return response()->json(['message' => 'Profil peserta tidak ditemukan.'], 404);
        }

        return response()->json([
            'data' => [
                'participant_id' => $participant->id,
                'balance' => $participant->point_balance,
                'ledger_balance' => $this->pointService->balanceFromLedger($participant->id),
            ],
        ]);
    }

    /**
     * GET /api/v1/points/history — paginated ledger of the participant's points.
     */
    public function history(): JsonResponse
    {
        $participant = $this->currentParticipant();

        if (! $participant) {
            return response()->json(['message' => 'Profil peserta tidak ditemukan.'], 404);
        }

        return response()->json([
            'data' => $this->pointService->historyForParticipant($participant->id),
        ]);
    }

    private function currentParticipant()
    {
        return auth()->user()?->participants()->first();
    }
}
