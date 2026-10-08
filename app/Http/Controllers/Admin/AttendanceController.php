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

    /**
     * Manual attendance-invalidation lifecycle (audit finding H2 / V11).
     * Authorized admin only.
     */
    public function invalidate(Request $request, int $id)
    {
        $result = $this->attendanceService->invalidateAttendance(
            $id,
            auth()->id(),
            $request->input('reason', 'Manual invalidasi oleh admin.')
        );

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return back()->with('success', $result['message']);
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
            'action' => ['nullable', 'in:check_in,check_out'],
        ]);

        $qrData = trim($request->qr_code);
        $action = $request->input('action', 'check_in');
        $isCheckOut = $action === 'check_out';

        try {
            $data = $isCheckOut
                ? $this->attendanceService->scanAndCheckOutAdmin((int) $request->event_id, $qrData)
                : $this->attendanceService->scanAndCheckInAdmin((int) $request->event_id, $qrData);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Terjadi kesalahan.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $isCheckOut ? 'Check-out berhasil!' : 'Check-in berhasil!',
            'data' => $data,
        ]);
    }
}
