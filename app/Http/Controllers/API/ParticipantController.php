<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParticipantRequest;
use App\Repositories\ParticipantRepository;
use App\Repositories\EventParticipantRepository;
use Illuminate\Http\JsonResponse;

class ParticipantController extends Controller
{
    public function __construct(
        private ParticipantRepository $participantRepository,
        private EventParticipantRepository $eventParticipantRepository,
    ) {}

    public function index(): JsonResponse
    {
        $participants = $this->participantRepository->paginate(['user']);

        return response()->json($participants);
    }

    public function show(int $id): JsonResponse
    {
        $participant = $this->participantRepository->findById($id, ['user', 'membershipHistories']);

        return response()->json(['data' => $participant]);
    }

    public function update(int $id, ParticipantRequest $request): JsonResponse
    {
        $participant = $this->participantRepository->findById($id);
        $this->participantRepository->update($participant, $request->validated());

        return response()->json(['data' => $participant->fresh(), 'message' => 'Data berhasil diupdate']);
    }

    public function events(int $id): JsonResponse
    {
        $registrations = $this->eventParticipantRepository->findEventsByParticipant($id);

        return response()->json(['data' => $registrations]);
    }

    public function attendance(int $id): JsonResponse
    {
        $participant = $this->participantRepository->findById($id, ['eventParticipants.attendance']);

        return response()->json(['data' => $participant->eventParticipants]);
    }
}
