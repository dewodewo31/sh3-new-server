<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Participant;
use App\Repositories\EventParticipantRepository;
use App\Repositories\AttendanceRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(
        private EventParticipantRepository $eventParticipantRepository,
        private AttendanceRepository $attendanceRepository,
        private QRCodeService $qrCodeService,
    ) {}

    public function checkIn(Event $event, Participant $participant, array $data = []): void
    {
        DB::transaction(function () use ($event, $participant, $data) {
            $registration = $this->eventParticipantRepository->findByEventAndParticipant(
                $event->id, $participant->id
            );

            if (!$registration) {
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

            if (!$attendance) {
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
        });
    }

    public function checkOut(Event $event, Participant $participant): void
    {
        DB::transaction(function () use ($event, $participant) {
            $registration = $this->eventParticipantRepository->findByEventAndParticipant(
                $event->id, $participant->id
            );

            if (!$registration) {
                throw ValidationException::withMessages([
                    'participant' => ['Peserta tidak terdaftar di event ini.'],
                ]);
            }

            $attendance = $this->attendanceRepository->findByEventParticipant($registration->id);

            if (!$attendance || !$attendance->check_in_time) {
                throw ValidationException::withMessages([
                    'participant' => ['Peserta belum melakukan check-in.'],
                ]);
            }

            $attendance->update(['check_out_time' => now()]);
            $registration->update(['check_out_at' => now()]);
        });
    }

    public function scanQRCode(string $qrData): array
    {
        $decoded = $this->qrCodeService->decode($qrData);

        if (!$decoded) {
            throw ValidationException::withMessages([
                'qr_code' => ['QR Code tidak valid.'],
            ]);
        }

        return $decoded;
    }
}
