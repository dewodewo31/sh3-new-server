<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Participant;
use App\Models\Payment;
use App\Repositories\AttendanceRepository;
use App\Repositories\EventParticipantRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(
        private EventParticipantRepository $eventParticipantRepository,
        private AttendanceRepository $attendanceRepository,
        private QRCodeService $qrCodeService,
        private NotificationService $notificationService,
    ) {}

    public function checkIn(Event $event, Participant $participant, array $data = [], ?int $scannedBy = null, ?string $ipAddress = null): void
    {
        DB::transaction(function () use ($event, $participant, $data, $scannedBy, $ipAddress) {
            $registration = $this->eventParticipantRepository->findByEventAndParticipant(
                $event->id, $participant->id
            );

            if (! $registration) {
                throw ValidationException::withMessages([
                    'participant' => ['Peserta tidak terdaftar di event ini.'],
                ]);
            }

            if (! in_array($registration->payment_status, ['pending', 'confirmed'], true)) {
                throw ValidationException::withMessages([
                    'participant' => ['Pendaftaran ini ditolak/dibatalkan dan tidak dapat digunakan untuk check-in.'],
                ]);
            }

            $attendance = $this->attendanceRepository->findByEventParticipant($registration->id);

            if ($attendance && $attendance->check_in_time) {
                throw ValidationException::withMessages([
                    'participant' => ['Peserta sudah melakukan check-in.'],
                ]);
            }

            if (! $attendance) {
                $attendance = $this->attendanceRepository->create([
                    'event_participant_id' => $registration->id,
                    'status' => 'present',
                    'check_in_method' => $data['method'] ?? 'qr_code',
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]);
            }

            $attendance->update([
                'check_in_time' => now(),
                'status' => 'present',
            ]);

            $registration->update([
                'is_attended' => true,
                'check_in_at' => now(),
            ]);

            $this->logAttendance($registration, 'check_in', $data, $scannedBy, $ipAddress);

            $this->notificationService->notifyRoles(
                ['admin_full_access', 'admin_laman'],
                'Peserta check-in',
                $participant->name.' check-in di event '.$event->title.'.',
                'check',
                route('admin.attendance.by-event', $event->id),
            );
        });
    }

    /**
     * Admin QR scan: decode, validate registration, and auto-check-in.
     * Returns the result array with participant/event info for the controller response.
     */
    public function scanAndCheckInAdmin(int $eventId, string $qrCode): array
    {
        $decoded = $this->qrCodeService->decode(trim($qrCode));

        if (! $decoded) {
            throw ValidationException::withMessages([
                'qr_code' => ['QR Code tidak valid. Pastikan format kode benar (contoh: 3950 atau NM0001).'],
            ]);
        }

        $participant = Participant::where('hash_id', $decoded['hash_id'])->first();

        if (! $participant) {
            throw ValidationException::withMessages([
                'qr_code' => ['Kode peserta tidak dikenal.'],
            ]);
        }

        $registration = $this->eventParticipantRepository->findByEventAndParticipant(
            $eventId,
            $participant->id,
        );

        if (! $registration) {
            throw ValidationException::withMessages([
                'qr_code' => ['Peserta tidak terdaftar di event ini.'],
            ]);
        }

        if (! $registration->qr_code || $registration->qr_code !== $decoded['hash_id']) {
            throw ValidationException::withMessages([
                'qr_code' => ['QR Code tidak dikenali. Silakan gunakan QR terbaru milik peserta.'],
            ]);
        }

        $this->checkIn(
            $registration->event,
            $registration->participant,
            ['method' => 'qr_code'],
        );

        return [
            'participant_name' => $registration->participant->name,
            'event_title' => $registration->event->title,
            'check_in_time' => now()->format('d/m/Y H:i:s'),
            'already_checked_in' => false,
        ];
    }

    public function checkOut(Event $event, Participant $participant, ?int $scannedBy = null, ?string $ipAddress = null): void
    {
        DB::transaction(function () use ($event, $participant, $scannedBy, $ipAddress) {
            $registration = $this->eventParticipantRepository->findByEventAndParticipant(
                $event->id, $participant->id
            );

            if (! $registration) {
                throw ValidationException::withMessages([
                    'participant' => ['Peserta tidak terdaftar di event ini.'],
                ]);
            }

            if (! in_array($registration->payment_status, ['pending', 'confirmed'], true)) {
                throw ValidationException::withMessages([
                    'participant' => ['Pendaftaran ini ditolak/dibatalkan dan tidak dapat digunakan untuk check-out.'],
                ]);
            }

            $attendance = $this->attendanceRepository->findByEventParticipant($registration->id);

            if (! $attendance || ! $attendance->check_in_time) {
                throw ValidationException::withMessages([
                    'participant' => ['Peserta belum melakukan check-in.'],
                ]);
            }

            $attendance->update(['check_out_time' => now()]);
            $registration->update(['check_out_at' => now()]);

            $this->logAttendance($registration, 'check_out', [], $scannedBy, $ipAddress);
        });
    }

    public function scanQRCode(string $qrData, ?int $eventId = null): array
    {
        $decoded = $this->qrCodeService->decode($qrData);

        if (! $decoded) {
            throw ValidationException::withMessages([
                'qr_code' => ['QR Code tidak valid.'],
            ]);
        }

        $participant = Participant::where('hash_id', $decoded['hash_id'])->first();

        if (! $participant) {
            throw ValidationException::withMessages([
                'participant' => ['Kode peserta tidak dikenal.'],
            ]);
        }

        if ($eventId === null) {
            return [
                'hash_id' => $decoded['hash_id'],
                'name' => $participant->name,
                'status' => $decoded['status'],
                'registered_events' => $this->eventParticipantRepository
                    ->findEventsByParticipant($participant->id)
                    ->map(fn (EventParticipant $ep) => [
                        'event_id' => $ep->event_id,
                        'event_title' => $ep->event?->title,
                        'payment_status' => $ep->payment_status,
                        'is_attended' => (bool) $ep->is_attended,
                    ])
                    ->values()
                    ->all(),
            ];
        }

        $registration = $this->eventParticipantRepository->findByEventAndParticipant($eventId, $participant->id);

        if (! $registration) {
            throw ValidationException::withMessages([
                'participant' => ['Peserta tidak terdaftar di event ini.'],
            ]);
        }

        return [
            'hash_id' => $decoded['hash_id'],
            'name' => $participant->name,
            'status' => $decoded['status'],
            'event_id' => $eventId,
            'participant_id' => $participant->id,
            'registration_status' => $registration->payment_status,
            'is_attended' => (bool) $registration->is_attended,
            'check_in_time' => $registration->check_in_at?->toISOString(),
        ];
    }

    public function report(array $filters = []): array
    {
        return $this->attendanceRepository->reportStats($filters)->all();
    }

    public function syncDown(array $filters = []): array
    {
        return $this->attendanceRepository->findSyncDown($filters)
            ->map(function (Attendance $attendance) {
                return [
                    'event_id' => $attendance->eventParticipant?->event_id,
                    'participant_id' => $attendance->eventParticipant?->participant_id,
                    'hash_id' => $attendance->eventParticipant?->participant?->hash_id,
                    'status' => $attendance->status,
                    'check_in_time' => $attendance->check_in_time?->toISOString(),
                    'check_out_time' => $attendance->check_out_time?->toISOString(),
                    'check_in_method' => $attendance->check_in_method,
                    'latitude' => $attendance->latitude,
                    'longitude' => $attendance->longitude,
                    'notes' => $attendance->notes,
                    'updated_at' => $attendance->updated_at?->toISOString(),
                ];
            })
            ->values()
            ->all();
    }

    public function syncUp(array $records, ?int $scannedBy = null, ?string $ipAddress = null): array
    {
        $processed = 0;
        $skipped = 0;
        $details = [];

        foreach ($records as $record) {
            $registration = $this->eventParticipantRepository->findByEventAndParticipant(
                $record['event_id'],
                $record['participant_id'],
            );

            if (! $registration) {
                $skipped++;
                $details[] = [
                    'event_id' => $record['event_id'],
                    'participant_id' => $record['participant_id'],
                    'type' => $record['type'],
                    'status' => 'skipped',
                    'reason' => 'Peserta tidak terdaftar di event ini.',
                ];

                continue;
            }

            try {
                $event = $registration->event;
                $participant = $registration->participant;

                if ($record['type'] === 'check_out') {
                    $this->checkOut($event, $participant, $scannedBy, $ipAddress);
                } else {
                    $this->checkIn($event, $participant, $record, $scannedBy, $ipAddress);
                }

                $processed++;
                $details[] = [
                    'event_id' => $record['event_id'],
                    'participant_id' => $record['participant_id'],
                    'type' => $record['type'],
                    'status' => 'processed',
                    'reason' => null,
                ];
            } catch (ValidationException $e) {
                $skipped++;
                $details[] = [
                    'event_id' => $record['event_id'],
                    'participant_id' => $record['participant_id'],
                    'type' => $record['type'],
                    'status' => 'skipped',
                    'reason' => collect($e->errors())->flatten()->first(),
                ];
            }
        }

        return [
            'processed' => $processed,
            'skipped' => $skipped,
            'details' => $details,
        ];
    }

    /**
     * Sync offline attendance data — handles both regular attendance updates
     * and OTS (On The Spot) registrations with full participant/payment creation.
     */
    public function syncUpOffline(array $attendances, array $otsRegistrations): array
    {
        return DB::transaction(function () use ($attendances, $otsRegistrations) {
            $savedAttCount = 0;
            $savedOtsCount = 0;

            // ============================================================
            // 1. UPDATE DATA ATTENDANCE REGULER
            // ============================================================
            foreach ($attendances as $att) {
                if (empty($att['hash_id'])) {
                    continue;
                }

                $eventParticipant = EventParticipant::where('event_id', $att['event_id'])
                    ->whereHas('participant', function ($q) use ($att) {
                        $q->where('hash_id', $att['hash_id']);
                    })
                    ->first();

                if (! $eventParticipant) {
                    \Log::warning('Reguler tidak ditemukan', [
                        'hash_id' => $att['hash_id'],
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
                ['hash_id' => Participant::OTS_AGGREGATOR_CODE],
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
                if (empty($ots['hash_id'])) {
                    continue;
                }

                $isManual = $ots['hash_id'] === Participant::OTS_AGGREGATOR_CODE;

                // Tentukan participant
                if ($isManual) {
                    $member = $manualOtsParticipant;
                } else {
                    $member = Participant::firstOrCreate(
                        ['hash_id' => $ots['hash_id']],
                        [
                            'name' => $ots['member_name'] ?? 'Peserta OTS',
                            'email' => 'ots.'.strtolower($ots['hash_id']).'@sh3.com',
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
                        'qr_code' => 'OTS-'.$ots['hash_id'].'EV'.$ots['event_id'],
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
                        'notes' => $isManual ? 'Manual OTS: '.$ots['hash_id'] : 'OTS Member: '.$ots['hash_id'],
                    ]);
                }

                // 🔥 5. UPDATE check_in_at / check_out_at di EventParticipant (opsional: update dengan waktu terbaru)
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
    }

    private function logAttendance(EventParticipant $registration, string $type, array $data = [], ?int $scannedBy = null, ?string $ipAddress = null): void
    {
        AttendanceLog::updateOrCreate(
            [
                'event_id' => $registration->event_id,
                'participant_id' => $registration->participant_id,
                'type' => $type,
            ],
            [
                'scan_time' => now(),
                'scanned_by' => $scannedBy,
                'qr_code' => $data['qr_code'] ?? $registration->qr_code,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'ip_address' => $ipAddress,
            ],
        );
    }
}
