<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCheckInRequest;
use App\Http\Requests\AttendanceCheckOutRequest;
use App\Http\Requests\AttendanceScanRequest;
use App\Http\Requests\AttendanceSyncUpRequest;
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
        $decoded = $this->attendanceService->scanQRCode($request->qr_code);

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
    $attendances = $request->input('attendances', []);
    $otsRegistrations = $request->input('ots_registrations', []);

    try {
        $result = \Illuminate\Support\Facades\DB::transaction(function () use ($attendances, $otsRegistrations) {
            $savedAttCount = 0;
            $savedOtsCount = 0;

            // ============================================================
            // 1. UPDATE DATA ATTENDANCE REGULER (tetap sama)
            // ============================================================
            foreach ($attendances as $att) {
                $eventParticipant = \App\Models\EventParticipant::where('qr_code', $att['qr_code'])
                                                                ->where('event_id', $att['event_id'])
                                                                ->first();

                if (!$eventParticipant && isset($att['hash_id'])) {
                    $eventParticipant = \App\Models\EventParticipant::where('event_id', $att['event_id'])
                        ->whereHas('participant', function($q) use ($att) {
                            $q->where('hash_id', $att['hash_id']);
                        })
                        ->first();
                }

                if (!$eventParticipant) {
                    \Log::warning("Reguler tidak ditemukan", [
                        'qr_code' => $att['qr_code'] ?? null,
                        'event_id' => $att['event_id'],
                        'hash_id' => $att['hash_id'] ?? null,
                    ]);
                    continue;
                }

                $checkIn = \Carbon\Carbon::parse($att['check_in_time'])->format('Y-m-d H:i:s');
                $checkOut = isset($att['check_out_time']) && $att['check_out_time'] != null
                            ? \Carbon\Carbon::parse($att['check_out_time'])->format('Y-m-d H:i:s')
                            : null;

                $eventParticipant->update([
                    'check_in_at'  => $checkIn,
                    'check_out_at' => $checkOut,
                    'is_attended'  => true,
                ]);

                $attendanceRecord = \App\Models\Attendance::where('event_participant_id', $eventParticipant->id)->first();

                if ($attendanceRecord) {
                    $existingCheckOut = $attendanceRecord->check_out_time;
                    if ($existingCheckOut) {
                        $attendanceRecord->update([
                            'check_in_time' => $checkIn,
                            'status'        => 'present',
                        ]);
                    } else {
                        $attendanceRecord->update([
                            'check_in_time'  => $checkIn,
                            'check_out_time' => $checkOut,
                            'status'         => 'present',
                        ]);
                    }
                } else {
                    \App\Models\Attendance::create([
                        'event_participant_id' => $eventParticipant->id,
                        'event_id'             => $att['event_id'],
                        'participant_id'       => $eventParticipant->participant_id,
                        'check_in_time'        => $checkIn,
                        'check_out_time'       => $checkOut,
                        'status'               => 'present',
                    ]);
                }

                $savedAttCount++;
            }

            // ============================================================
            // 2. INSERT DATA OTS + BUAT PAYMENT
            // ============================================================
            foreach ($otsRegistrations as $ots) {
                $isManual = stripos($ots['hash_id'], 'manual') !== false;

                // 🔥 A. Tentukan atau buat participant
                if ($isManual) {
                    // OTS manual → pakai aggregator
                    $member = \App\Models\Participant::firstOrCreate(
                        ['name' => 'Manual OTS NON MEMBER'],
                        [
                            'hash_id'   => 'MANUAL_OTS_AGGREGATOR',
                            'is_active' => true,
                            'email'     => 'manual.ots@sh3.com',
                            'phone'     => '0000000000',
                        ]
                    );
                } else {
                    // OTS member → cari berdasarkan hash_id
                    $member = \App\Models\Participant::firstOrCreate(
                        ['hash_id' => $ots['hash_id']],
                        ['name' => $ots['member_name'] ?? 'Peserta OTS']
                    );
                }

                $participantId = $member->id;
                $qrCodeForEvent = 'OTS-' . $ots['hash_id'] . 'EV' . $ots['event_id'];

                // 🔥 B. Ambil harga event
                $event = \App\Models\Event::find($ots['event_id']);
                $eventPrice = $event ? $event->price : 0;
                $registrationType = $eventPrice > 0 ? 'paid' : 'free';

                // 🔥 C. Format waktu
                $checkInOts = \Carbon\Carbon::parse($ots['check_in_time'])->format('Y-m-d H:i:s');
                $checkOutOts = isset($ots['check_out_time']) && $ots['check_out_time'] != null
                               ? \Carbon\Carbon::parse($ots['check_out_time'])->format('Y-m-d H:i:s')
                               : null;

                // 🔥 D. Buat atau dapatkan EventParticipant
                $eventParticipant = \App\Models\EventParticipant::firstOrCreate(
                    [
                        'event_id'       => $ots['event_id'],
                        'participant_id' => $participantId,
                    ],
                    [
                        'qr_code'          => $qrCodeForEvent,
                        'registration_type' => $registrationType,
                        'amount'           => $eventPrice,
                        'payment_status'   => 'confirmed', // langsung confirmed
                        'is_attended'      => true,
                        'check_in_at'      => $checkInOts,
                        'check_out_at'     => $checkOutOts,
                    ]
                );

                // 🔥 E. Buat atau update PAYMENT untuk OTS ini
                $payment = \App\Models\Payment::updateOrCreate(
                    [
                        'paymentable_type' => 'App\Models\EventParticipant',
                        'paymentable_id'   => $eventParticipant->id,
                    ],
                    [
                        'participant_id' => $participantId,
                        'invoice_number' => 'INV-OTS-' . strtoupper(\Illuminate\Support\Str::random(8)),
                        'payment_type'   => 'event_registration',
                        'amount'         => $eventPrice,
                        'payment_method' => 'cash', // bisa diambil dari request jika ada
                        'payment_proof'  => null,
                        'status'         => 'confirmed',
                        'confirmed_by'   => null, // atau isi dengan user admin jika ada
                        'paid_at'        => now(),
                    ]
                );

                // 🔥 F. Buat atau update Attendance
                $attendance = \App\Models\Attendance::where('event_participant_id', $eventParticipant->id)->first();

                if ($attendance) {
                    $existingCheckOut = $attendance->check_out_time;
                    if ($existingCheckOut) {
                        $attendance->update([
                            'check_in_time' => $checkInOts,
                            'status'        => 'present',
                        ]);
                    } else {
                        $attendance->update([
                            'check_in_time'  => $checkInOts,
                            'check_out_time' => $checkOutOts,
                            'status'         => 'present',
                        ]);
                    }
                } else {
                    \App\Models\Attendance::create([
                        'event_participant_id' => $eventParticipant->id,
                        'event_id'             => $ots['event_id'],
                        'participant_id'       => $participantId,
                        'check_in_time'        => $checkInOts,
                        'check_out_time'       => $checkOutOts,
                        'status'               => 'present',
                    ]);
                }

                $savedOtsCount++;
            }

            return [
                'att_count' => $savedAttCount,
                'ots_count' => $savedOtsCount,
            ];
        });

        return response()->json([
            'success'                 => true,
            'message'                 => 'Event attendance synced successfully',
            'synced_attendance_count' => $result['att_count'],
            'synced_ots_count'        => $result['ots_count'],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success'      => false,
            'message'      => 'Failed to sync data.',
            'error_detail' => $e->getMessage(),
            'error_line'   => $e->getLine(),
            'error_file'   => basename($e->getFile()),
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
