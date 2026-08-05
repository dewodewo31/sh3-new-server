<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Participant;
use App\Repositories\AttendanceRepository;
use App\Repositories\EventParticipantRepository;
use Illuminate\Support\Facades\DB;
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

    public function scanQRCode(string $qrData): array
    {
        $decoded = $this->qrCodeService->decode($qrData);

        if (! $decoded) {
            throw ValidationException::withMessages([
                'qr_code' => ['QR Code tidak valid.'],
            ]);
        }

        return $decoded;
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
