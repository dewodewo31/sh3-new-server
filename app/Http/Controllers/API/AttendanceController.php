<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCheckInRequest;
use App\Http\Requests\AttendanceCheckOutRequest;
use App\Http\Requests\AttendanceScanRequest;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Participant;
use App\Models\Payment;
use App\Repositories\EventRepository;
use App\Repositories\ParticipantRepository;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $attendances = $request->input('attendances', []);
        $otsRegistrations = $request->input('ots_registrations', []);

        try {
            $result = DB::transaction(function () use ($attendances, $otsRegistrations) {
                $savedAttCount = 0;
                $savedOtsCount = 0;

                // ============================================================
                // 1. UPDATE DATA ATTENDANCE REGULER
                // ============================================================
                foreach ($attendances as $att) {
                    if (empty($att['participant_code'])) {
                        continue;
                    }

                    $eventParticipant = EventParticipant::where('event_id', $att['event_id'])
                        ->whereHas('participant', function ($q) use ($att) {
                            $q->where('participant_code', $att['participant_code']);
                        })
                        ->first();

                    if (! $eventParticipant) {
                        \Log::warning('Reguler tidak ditemukan', [
                            'participant_code' => $att['participant_code'],
                            'event_id' => $att['event_id'],
                        ]);

                        continue;
                    }

                    $checkIn = Carbon::parse($att['check_in_time'])->format('Y-m-d H:i:s');
                    $checkOut = isset($att['check_out_time']) && $att['check_out_time'] != null
                                ? Carbon::parse($att['check_out_time'])->format('Y-m-d H:i:s')
                                : null;

                    $eventParticipant->update([
                        'check_in_at' => $checkIn,
                        'check_out_at' => $checkOut,
                        'is_attended' => true,
                    ]);

                    $attendanceRecord = Attendance::where('event_participant_id', $eventParticipant->id)->first();

                    if ($attendanceRecord) {
                        $existingCheckOut = $attendanceRecord->check_out_time;
                        if ($existingCheckOut) {
                            $attendanceRecord->update([
                                'check_in_time' => $checkIn,
                                'status' => 'present',
                            ]);
                        } else {
                            $attendanceRecord->update([
                                'check_in_time' => $checkIn,
                                'check_out_time' => $checkOut,
                                'status' => 'present',
                            ]);
                        }
                    } else {
                        Attendance::create([
                            'event_participant_id' => $eventParticipant->id,
                            'check_in_time' => $checkIn,
                            'check_out_time' => $checkOut,
                            'status' => 'present',
                            'check_in_method' => 'qr_code',
                        ]);
                    }

                    $savedAttCount++;
                }

                // ============================================================
                // 2. OTS: BUAT 1 EVENTPARTICIPANT, BANYAK PAYMENT & ATTENDANCE
                // ============================================================

                // 🔥 Cari atau buat participant aggregator untuk semua OTS manual
                $manualOtsParticipant = Participant::firstOrCreate(
                    ['participant_code' => Participant::OTS_AGGREGATOR_CODE],
                    [
                        'name' => 'Manual OTS NON MEMBER',
                        'is_active' => true,
                        'email' => 'manual.ots@sh3.com',
                        'phone' => null,
                    ]
                );

                // 🔥 Array untuk melacak event yang sudah diproses (agar increment total_events_participated hanya sekali)
                $processedEvents = [];

                foreach ($otsRegistrations as $ots) {
                    if (empty($ots['participant_code'])) {
                        continue;
                    }

                    $isManual = $ots['participant_code'] === Participant::OTS_AGGREGATOR_CODE;

                    // Tentukan participant
                    if ($isManual) {
                        $member = $manualOtsParticipant;
                    } else {
                        $member = Participant::firstOrCreate(
                            ['participant_code' => $ots['participant_code']],
                            [
                                'name' => $ots['member_name'] ?? 'Peserta OTS',
                                'email' => 'ots.'.strtolower($ots['participant_code']).'@sh3.com',
                            ]
                        );
                    }

                    $participantId = $member->id;

                    // 🔥 1. DAPATKAN ATAU BUAT EVENT PARTICIPANT (hanya 1 per participant per event)
                    $eventParticipant = EventParticipant::firstOrCreate(
                        [
                            'event_id' => $ots['event_id'],
                            'participant_id' => $participantId,
                        ],
                        [
                            'qr_code' => 'OTS-'.$ots['participant_code'].'EV'.$ots['event_id'],
                            'registration_type' => 'paid',
                            'amount' => Event::find($ots['event_id'])->price ?? 0,
                            'payment_status' => 'confirmed',
                            'is_attended' => true,
                            'check_in_at' => null,
                            'check_out_at' => null,
                        ]
                    );

                    // 🔥 2. INCREMENT total_events_participated (hanya sekali per event)
                    if (! in_array($ots['event_id'], $processedEvents)) {
                        $member->increment('total_events_participated');
                        $processedEvents[] = $ots['event_id'];
                    }

                    // 🔥 3. BUAT PAYMENT (1 per OTS scan)
                    Payment::create([
                        'participant_id' => $participantId,
                        'invoice_number' => 'INV-OTS-'.strtoupper(Str::random(8)),
                        'payment_type' => 'event_registration',
                        'paymentable_type' => 'App\Models\EventParticipant',
                        'paymentable_id' => $eventParticipant->id,
                        'amount' => $eventParticipant->amount,
                        'payment_method' => 'cash',
                        'payment_proof' => null,
                        'status' => 'confirmed',
                        'confirmed_by' => null,
                        'paid_at' => now(),
                    ]);

                    // 🔥 4. BUAT ATAU UPDATE ATTENDANCE (update jika sudah ada, create jika belum)
                    $checkInOts = Carbon::parse($ots['check_in_time'])->format('Y-m-d H:i:s');
                    $checkOutOts = isset($ots['check_out_time']) && $ots['check_out_time'] != null
                                   ? Carbon::parse($ots['check_out_time'])->format('Y-m-d H:i:s')
                                   : null;

                    $attendance = Attendance::where('event_participant_id', $eventParticipant->id)->first();

                    if ($attendance) {
                        $existingCheckOut = $attendance->check_out_time;

                        if ($existingCheckOut) {
                            $attendance->update([
                                'check_in_time' => $checkInOts,
                                'status' => 'present',
                            ]);
                        } else {
                            $attendance->update([
                                'check_in_time' => $checkInOts,
                                'check_out_time' => $checkOutOts,
                                'status' => 'present',
                            ]);
                        }
                    } else {
                        Attendance::create([
                            'event_participant_id' => $eventParticipant->id,
                            'check_in_time' => $checkInOts,
                            'check_out_time' => $checkOutOts,
                            'status' => 'present',
                            'check_in_method' => 'qr_code',
                            'notes' => $isManual ? 'Manual OTS: '.$ots['participant_code'] : 'OTS Member: '.$ots['participant_code'],
                        ]);
                    }

                    // 🔥 5. UPDATE check_in_at / check_out_at di EventParticipant (opsional: update dengan waktu terbaru)
                    // Kita update dengan waktu yang paling baru (bisa diambil dari attendance terakhir)
                    // Atau kita biarkan null, karena data waktu ada di Attendance.
                    // Untuk keperluan denormalisasi, kita update dengan waktu dari scan terakhir.
                    $eventParticipant->update([
                        'check_in_at' => $checkInOts,
                        'check_out_at' => $checkOutOts,
                    ]);

                    $savedOtsCount++;
                }

                return [
                    'att_count' => $savedAttCount,
                    'ots_count' => $savedOtsCount,
                ];
            });

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
