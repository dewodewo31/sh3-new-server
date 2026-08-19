<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscribeMembershipRequest;
use App\Http\Resources\MembershipHistoryResource;
use App\Http\Resources\ParticipantResource;
use App\Repositories\MembershipHistoryRepository;
use App\Services\FileService;
use App\Services\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MembershipController extends Controller
{
    public function __construct(
        private MembershipService $membershipService,
        private MembershipHistoryRepository $membershipHistoryRepository,
        private FileService $fileService,
    ) {}

    public function show(): JsonResponse
    {
        $participant = $this->currentParticipant();

        if (! $participant) {
            return response()->json(['message' => 'Profil peserta tidak ditemukan.'], 404);
        }

        return response()->json([
            'data' => new ParticipantResource($participant->load('membershipHistories')),
        ]);
    }

    public function plans(): JsonResponse
    {
        $data = Cache::remember('api:membership:plans', 3600, fn () => $this->membershipService->plans());

        return response()->json(['data' => $data]);
    }

    public function history(): JsonResponse
    {
        $participant = $this->currentParticipant();

        if (! $participant) {
            return response()->json(['data' => []]);
        }

        $histories = $this->membershipHistoryRepository->findByParticipant($participant->id);

        return response()->json([
            'data' => MembershipHistoryResource::collection($histories),
        ]);
    }

    public function subscribe(SubscribeMembershipRequest $request): JsonResponse
    {
        $participant = $this->currentParticipant();

        if (! $participant) {
            return response()->json(['message' => 'Profil peserta tidak ditemukan.'], 404);
        }

        $paymentProof = null;

        if ($request->hasFile('payment_proof')) {
            $paymentProof = $this->fileService->upload($request->file('payment_proof'), 'payments');
        }

        $history = $this->membershipService->requestSubscription(
            $participant,
            $request->string('membership_type')->toString(),
            $request->string('payment_method')->toString(),
            $paymentProof,
            $request->integer('duration_months') ?: null,
        );

        return response()->json([
            'data' => new MembershipHistoryResource($history),
            'message' => 'Permintaan membership berhasil dibuat. Silakan lakukan pembayaran.',
        ], 201);
    }

    public function cancel(Request $request): JsonResponse
    {
        $participant = $this->currentParticipant();

        if (! $participant) {
            return response()->json(['message' => 'Profil peserta tidak ditemukan.'], 404);
        }

        $this->membershipService->cancelMembership($participant);

        return response()->json(['message' => 'Membership dibatalkan.']);
    }

    private function currentParticipant()
    {
        return auth()->user()?->participants()->first();
    }
}
