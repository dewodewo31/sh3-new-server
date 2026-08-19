<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCheckInRequest;
use App\Http\Requests\AttendanceCheckOutRequest;
use App\Http\Requests\AttendanceScanRequest;
use App\Models\Event;
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

    public function checkIn(AttendanceCheckInRequest $request): JsonResponse
    {
        $event = $this->eventRepository->findById($request->event_id);
        $participant = $this->participantRepository->findById($request->participant_id);

        $this->attendanceService->checkIn(
            $event,
            $participant,
            $request->validated(),
            $request->user()?->id,
            $request->ip(),
        );

        return response()->json(['message' => 'Check-in berhasil']);
    }

    public function checkOut(AttendanceCheckOutRequest $request): JsonResponse
    {
        $event = $this->eventRepository->findById($request->event_id);
        $participant = $this->participantRepository->findById($request->participant_id);

        $this->attendanceService->checkOut(
            $event,
            $participant,
            $request->user()?->id,
            $request->ip(),
        );

        return response()->json(['message' => 'Check-out berhasil']);
    }

    public function byEvent(int $eventId): JsonResponse
    {
        $event = $this->eventRepository->findById($eventId, ['eventParticipants.attendance']);

        return response()->json(['data' => $event->eventParticipants]);
    }

    public function scan(AttendanceScanRequest $request): JsonResponse
    {
        $decoded = $this->attendanceService->scanQRCode(
            $request->qr_code,
            $request->filled('event_id') ? (int) $request->input('event_id') : null,
        );

        return response()->json(['data' => $decoded]);
    }

    public function report(Request $request): JsonResponse
    {
        $report = $this->attendanceService->report([
            'event_id' => $request->query('event_id'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ]);

        return response()->json(['data' => $report]);
    }

    public function syncUp(Request $request)
    {
        try {
            $result = $this->attendanceService->syncUpOffline(
                $request->input('attendances', []),
                $request->input('ots_registrations', [])
            );

            return response()->json([
                'success' => true,
                'message' => 'Event attendance synced successfully',
                'synced_attendance_count' => $result['att_count'],
                'synced_ots_count' => $result['ots_count'],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync data.',
                'error_detail' => $e->getMessage(),
                'error_line' => $e->getLine(),
                'error_file' => basename($e->getFile()),
            ], 500);
        }
    }

    public function syncDown(Request $request): JsonResponse
    {
        $data = $this->attendanceService->syncDown([
            'event_id' => $request->query('event_id'),
            'since' => $request->query('since'),
        ]);

        return response()->json(['data' => $data]);
    }
}
