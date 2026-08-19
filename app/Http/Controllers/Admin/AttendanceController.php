<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\AttendanceRepository;
use App\Repositories\EventParticipantRepository;
use App\Repositories\EventRepository;
use App\Repositories\GuestSponsorRepository;
use App\Services\AttendanceService;
use App\Services\GuestSponsorService;
use App\Services\QRCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function __construct(
        private EventRepository $eventRepository,
        private AttendanceRepository $attendanceRepository,
        private EventParticipantRepository $eventParticipantRepository,
        private AttendanceService $attendanceService,
        private GuestSponsorService $guestSponsorService,
        private GuestSponsorRepository $guestSponsorRepository,
        private QRCodeService $qrCodeService,
    ) {}

    public function byEvent(int $eventId)
    {
        $event = $this->eventRepository->findById($eventId);
        $attendances = $this->attendanceRepository->findByEvent($eventId);

        return view('attendance.index', compact('event', 'attendances'));
    }

    public function report()
    {
        $events = $this->eventRepository->all();

        return view('attendance.report', compact('events'));
    }

    public function scan()
    {
        $events = $this->eventRepository->findScannable();

        return view('attendance.scan', compact('events'));
    }

    public function generateQr(int $id)
    {
        $registration = $this->eventParticipantRepository->findById($id);

        if (! $registration) {
            return back()->with('error', 'Registrasi tidak ditemukan.');
        }

        $this->qrCodeService->generate($registration);

        return back()->with('success', 'QR Code berhasil dibuat untuk '.$registration->participant->name);
    }

    public function processScan(Request $request): JsonResponse
    {
        $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'qr_code' => ['required', 'string'],
        ]);

        $qrData = trim($request->qr_code);

        if ($this->qrCodeService->isGuestSponsorCode($qrData)) {
            return $this->processGuestSponsorScan($request, $qrData);
        }

        try {
            $data = $this->attendanceService->scanAndCheckInAdmin(
                (int) $request->event_id,
                $qrData,
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Terjadi kesalahan.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Check-in berhasil!',
            'data' => $data,
        ]);
    }

    public function processGuestSponsorScan(Request $request, string $qrData): JsonResponse
    {
        $guestSponsor = $this->guestSponsorRepository->findByQr($qrData);

        if (! $guestSponsor) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code guest sponsor tidak dikenal.',
            ], 422);
        }

        if ($guestSponsor->event_id !== (int) $request->event_id) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code ini tidak untuk event tersebut.',
            ], 422);
        }

        $event = $this->eventRepository->findById($request->event_id);

        try {
            $this->guestSponsorService->checkIn(
                $guestSponsor,
                $event,
                ['method' => 'qr_code'],
                auth()->id(),
                $request->ip(),
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Terjadi kesalahan.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Check-in guest sponsor berhasil!',
            'data' => [
                'guest_sponsor' => true,
                'sponsor_name' => $guestSponsor->sponsor?->name,
                'event_title' => $event->title,
                'check_in_time' => now()->format('d/m/Y H:i:s'),
                'already_checked_in' => false,
            ],
        ]);
    }
}
