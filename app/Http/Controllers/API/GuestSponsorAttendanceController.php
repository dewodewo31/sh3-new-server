<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceScanRequest;
use App\Http\Requests\GuestSponsorCheckInRequest;
use App\Repositories\EventRepository;
use App\Repositories\GuestSponsorRepository;
use App\Services\GuestSponsorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestSponsorAttendanceController extends Controller
{
    public function __construct(
        private GuestSponsorService $guestSponsorService,
        private GuestSponsorRepository $guestSponsorRepository,
        private EventRepository $eventRepository,
    ) {}

    public function checkIn(GuestSponsorCheckInRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'guest_sponsor') {
            return response()->json(['message' => 'Hanya akun guest sponsor yang dapat check-in.'], 403);
        }

        $guestSponsor = $this->guestSponsorRepository->findByUser($user->id);
        $event = $this->eventRepository->findById($request->validated()['event_id']);

        $this->guestSponsorService->checkIn(
            $guestSponsor,
            $event,
            $request->validated(),
            $user->id,
            $request->ip(),
        );

        return response()->json(['message' => 'Check-in berhasil']);
    }

    public function checkOut(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'guest_sponsor') {
            return response()->json(['message' => 'Hanya akun guest sponsor yang dapat check-out.'], 403);
        }

        $request->validate(['event_id' => ['required', 'integer', 'exists:events,id']]);

        $guestSponsor = $this->guestSponsorRepository->findByUser($user->id);
        $event = $this->eventRepository->findById($request->input('event_id'));

        $this->guestSponsorService->checkOut($guestSponsor, $event, $user->id, $request->ip());

        return response()->json(['message' => 'Check-out berhasil']);
    }

    public function scan(AttendanceScanRequest $request): JsonResponse
    {
        $result = $this->guestSponsorService->scan(
            $request->validated()['qr_code'],
            $request->filled('event_id') ? (int) $request->input('event_id') : null,
        );

        return response()->json(['data' => $result]);
    }

    public function my(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'guest_sponsor') {
            return response()->json(['message' => 'Hanya akun guest sponsor yang dapat mengakses.'], 403);
        }

        $guestSponsor = $this->guestSponsorRepository->findByUser($user->id);

        $history = $this->guestSponsorService->history($guestSponsor)->map(fn ($att) => [
            'event_id' => $att->event_id,
            'event_title' => $att->event?->title,
            'check_in_time' => $att->check_in_time?->toISOString(),
            'check_out_time' => $att->check_out_time?->toISOString(),
            'status' => $att->status,
        ])->values();

        return response()->json(['data' => $history]);
    }
}
