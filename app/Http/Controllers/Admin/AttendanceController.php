<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\AttendanceRepository;
use App\Repositories\EventParticipantRepository;
use App\Repositories\EventRepository;
use App\Services\AttendanceService;
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
        return view('attendance.scan');
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
            'qr_code' => ['required', 'string'],
        ]);

        $qrData = trim($request->qr_code);
        $decoded = $this->qrCodeService->decode($qrData);

        if (! $decoded) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code tidak valid. Pastikan format kode benar (SH3-...).',
            ], 422);
        }

        $registration = $this->eventParticipantRepository->findByEventAndParticipant(
            $decoded['event_id'],
            $decoded['participant_id'],
        );

        if (! $registration) {
            return response()->json([
                'success' => false,
                'message' => 'Peserta tidak terdaftar di event ini.',
            ], 422);
        }

        if (! $registration->qr_code || $registration->qr_code !== $qrData) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code tidak dikenali. Silakan gunakan QR terbaru milik peserta.',
            ], 422);
        }

        try {
            $this->attendanceService->checkIn(
                $registration->event,
                $registration->participant,
                ['method' => 'qr_code'],
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
            'data' => [
                'participant_name' => $registration->participant->name,
                'event_title' => $registration->event->title,
                'check_in_time' => now()->format('d/m/Y H:i:s'),
                'already_checked_in' => false,
            ],
        ]);
    }
}
