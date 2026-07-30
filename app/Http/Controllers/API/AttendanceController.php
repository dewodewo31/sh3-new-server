<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Repositories\EventRepository;
use App\Repositories\ParticipantRepository;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
        private EventRepository $eventRepository,
        private ParticipantRepository $participantRepository,
    ) {}

    public function checkIn(Request $request): JsonResponse
    {
        $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'participant_id' => ['required', 'exists:participants,id'],
            'method' => ['nullable', 'in:qr_code,manual,self_scan'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $event = $this->eventRepository->findById($request->event_id);
        $participant = $this->participantRepository->findById($request->participant_id);

        $this->attendanceService->checkIn($event, $participant, $request->all());

        return response()->json(['message' => 'Check-in berhasil']);
    }

    public function checkOut(Request $request): JsonResponse
    {
        $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'participant_id' => ['required', 'exists:participants,id'],
        ]);

        $event = $this->eventRepository->findById($request->event_id);
        $participant = $this->participantRepository->findById($request->participant_id);

        $this->attendanceService->checkOut($event, $participant);

        return response()->json(['message' => 'Check-out berhasil']);
    }

    public function byEvent(int $eventId): JsonResponse
    {
        $event = $this->eventRepository->findById($eventId, ['eventParticipants.attendance']);

        return response()->json(['data' => $event->eventParticipants]);
    }

    public function scan(Request $request): JsonResponse
    {
        $request->validate([
            'qr_code' => ['required', 'string'],
        ]);

        $decoded = $this->attendanceService->scanQRCode($request->qr_code);

        return response()->json(['data' => $decoded]);
    }
}
