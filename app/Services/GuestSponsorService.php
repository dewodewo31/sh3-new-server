<?php

namespace App\Services;

use App\Models\Event;
use App\Models\GuestSponsor;
use App\Models\Sponsor;
use App\Models\User;
use App\Repositories\EventRepository;
use App\Repositories\GuestSponsorRepository;
use App\Repositories\SponsorRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GuestSponsorService
{
    public function __construct(
        private GuestSponsorRepository $guestSponsorRepository,
        private SponsorRepository $sponsorRepository,
        private EventRepository $eventRepository,
        private UserRepository $userRepository,
    ) {}

    public function createAccount(array $data, ?int $createdBy): GuestSponsor
    {
        $sponsor = $this->sponsorRepository->findById($data['sponsor_id']);
        $event = $this->eventRepository->findById($data['event_id']);

        return DB::transaction(function () use ($data, $sponsor, $event, $createdBy) {
            $quota = $this->guestSponsorRepository->quota($sponsor->id, $event->id);

            if ($quota['max'] <= 0) {
                throw ValidationException::withMessages([
                    'sponsor_id' => ['Kuota guest sponsor belum ditentukan untuk sponsor dan event ini.'],
                ]);
            }

            if ($quota['remaining'] <= 0) {
                throw ValidationException::withMessages([
                    'sponsor_id' => ['Kuota guest sponsor untuk sponsor dan event ini sudah penuh.'],
                ]);
            }

            $username = $data['username'] ?? $this->generateUsername($sponsor);
            $password = $data['password'] ?? Str::random(8);

            $user = $this->userRepository->create([
                'name' => $data['name'] ?? $sponsor->name.' (Guest Sponsor)',
                'username' => $username,
                'email' => $data['email'] ?? $username.'@guest.sh3.com',
                'password' => $password,
                'role' => 'guest_sponsor',
                'is_active' => true,
            ]);

            $guestSponsor = $this->guestSponsorRepository->create([
                'user_id' => $user->id,
                'sponsor_id' => $sponsor->id,
                'event_id' => $event->id,
                'qr_code' => $this->generateQrCode($sponsor->id, $event->id),
                'valid_from' => $data['valid_from'] ?? now()->toDateString(),
                'valid_until' => $data['valid_until'] ?? $event->end_date?->toDateString(),
                'is_active' => true,
                'created_by' => $createdBy,
            ]);

            $guestSponsor->setAttribute('username', $username);
            $guestSponsor->setAttribute('plain_password', $password);

            return $guestSponsor;
        });
    }

    public function generateUsername(Sponsor $sponsor): string
    {
        $base = 'gs_'.Str::slug($sponsor->name, '_');
        $base = Str::lower(preg_replace('/[^a-zA-Z0-9_]/', '', $base));
        $base = substr($base, 0, 25);

        $username = $base;
        $suffix = 1;

        while ($this->userRepository->findFirstBy('username', $username)) {
            $username = $base.'_'.$suffix;
            $suffix++;
        }

        return $username;
    }

    public function generateQrCode(int $sponsorId, int $eventId): string
    {
        $seq = $this->guestSponsorRepository->countActiveFor($sponsorId, $eventId) + 1;

        return sprintf('GS-%d-%d-%04d', $sponsorId, $eventId, $seq);
    }

    public function quota(int $sponsorId, int $eventId): array
    {
        return $this->guestSponsorRepository->quota($sponsorId, $eventId);
    }

    public function setQuota(int $sponsorId, int $eventId, int $max): void
    {
        $this->guestSponsorRepository->setQuota($sponsorId, $eventId, max(0, $max));
    }

    public function toggleActive(GuestSponsor $guestSponsor): bool
    {
        return $this->guestSponsorRepository->update($guestSponsor, [
            'is_active' => ! $guestSponsor->is_active,
        ]);
    }

    public function updateAccount(GuestSponsor $guestSponsor, array $data): void
    {
        $this->guestSponsorRepository->update($guestSponsor, [
            'valid_from' => $data['valid_from'] ?? $guestSponsor->valid_from?->toDateString(),
            'valid_until' => $data['valid_until'] ?? $guestSponsor->valid_until?->toDateString(),
            'is_active' => $data['is_active'] ?? $guestSponsor->is_active,
        ]);

        $userData = [];
        if (! empty($data['name'])) {
            $userData['name'] = $data['name'];
        }
        if (! empty($data['password'])) {
            $userData['password'] = $data['password'];
        }
        if ($userData) {
            $this->userRepository->update($guestSponsor->user, $userData);
        }
    }

    public function authenticate(string $username, string $password): User
    {
        $user = $this->userRepository->findFirstBy('username', $username);

        if (! $user || $user->role !== 'guest_sponsor' || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Username atau password salah.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'username' => ['Akun Anda telah dinonaktifkan.'],
            ]);
        }

        $guestSponsor = $user->guestSponsor;

        if (! $guestSponsor) {
            throw ValidationException::withMessages([
                'username' => ['Akun guest sponsor tidak ditemukan.'],
            ]);
        }

        if (! $this->isUsableForEvent($guestSponsor, $guestSponsor->event)) {
            throw ValidationException::withMessages([
                'username' => ['Akun guest sponsor sudah tidak berlaku atau event telah berakhir.'],
            ]);
        }

        $this->userRepository->updateLastLogin($user);

        return $user;
    }

    public function isUsableForEvent(GuestSponsor $guestSponsor, ?Event $event = null): bool
    {
        if (! $guestSponsor->isUsable()) {
            return false;
        }

        if (! $event) {
            $event = $guestSponsor->event;
        }

        if (! $event) {
            return false;
        }

        if (in_array($event->status, [Event::STATUS_COMPLETED, Event::STATUS_CANCELLED], true)) {
            return false;
        }

        if ($event->end_date && $event->end_date->isBefore(now())) {
            return false;
        }

        return true;
    }

    public function checkIn(GuestSponsor $guestSponsor, Event $event, array $data = [], ?int $scannedBy = null, ?string $ipAddress = null): void
    {
        if (! $this->isUsableForEvent($guestSponsor, $event)) {
            throw ValidationException::withMessages([
                'guest_sponsor' => ['Akun guest sponsor tidak berlaku atau event telah berakhir.'],
            ]);
        }

        $attendance = $this->guestSponsorRepository->findAttendanceByEvent($guestSponsor->id, $event->id);

        if ($attendance && $attendance->check_in_time) {
            throw ValidationException::withMessages([
                'guest_sponsor' => ['Guest sponsor sudah melakukan check-in.'],
            ]);
        }

        if (! $attendance) {
            $attendance = $this->guestSponsorRepository->createAttendance([
                'guest_sponsor_id' => $guestSponsor->id,
                'event_id' => $event->id,
                'status' => 'present',
                'check_in_method' => $data['method'] ?? 'qr_code',
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        }

        $this->guestSponsorRepository->updateAttendance($attendance, [
            'check_in_time' => now(),
            'status' => 'present',
        ]);

        $this->logAttendance($guestSponsor, $event, 'check_in', $data, $scannedBy, $ipAddress);
    }

    public function checkOut(GuestSponsor $guestSponsor, Event $event, ?int $scannedBy = null, ?string $ipAddress = null): void
    {
        if (! $this->isUsableForEvent($guestSponsor, $event)) {
            throw ValidationException::withMessages([
                'guest_sponsor' => ['Akun guest sponsor tidak berlaku atau event telah berakhir.'],
            ]);
        }

        $attendance = $this->guestSponsorRepository->findAttendanceByEvent($guestSponsor->id, $event->id);

        if (! $attendance || ! $attendance->check_in_time) {
            throw ValidationException::withMessages([
                'guest_sponsor' => ['Guest sponsor belum melakukan check-in.'],
            ]);
        }

        $this->guestSponsorRepository->updateAttendance($attendance, [
            'check_out_time' => now(),
        ]);

        $this->logAttendance($guestSponsor, $event, 'check_out', [], $scannedBy, $ipAddress);
    }

    public function scan(string $qrCode, ?int $eventId = null): array
    {
        $guestSponsor = $this->guestSponsorRepository->findByQr($qrCode);

        if (! $guestSponsor) {
            throw ValidationException::withMessages([
                'qr_code' => ['QR Code guest sponsor tidak dikenal.'],
            ]);
        }

        if ($eventId !== null && $guestSponsor->event_id !== $eventId) {
            throw ValidationException::withMessages([
                'qr_code' => ['QR Code ini tidak untuk event tersebut.'],
            ]);
        }

        $attendance = $this->guestSponsorRepository->findAttendanceByEvent($guestSponsor->id, $guestSponsor->event_id);

        return [
            'guest_sponsor_id' => $guestSponsor->id,
            'qr_code' => $guestSponsor->qr_code,
            'sponsor_name' => $guestSponsor->sponsor?->name,
            'event_id' => $guestSponsor->event_id,
            'event_title' => $guestSponsor->event?->title,
            'is_active' => (bool) $guestSponsor->is_active,
            'valid_until' => $guestSponsor->valid_until?->toDateString(),
            'usable' => $this->isUsableForEvent($guestSponsor, $guestSponsor->event),
            'check_in_time' => $attendance?->check_in_time?->toISOString(),
            'check_out_time' => $attendance?->check_out_time?->toISOString(),
        ];
    }

    public function history(GuestSponsor $guestSponsor)
    {
        return $this->guestSponsorRepository->attendanceHistory($guestSponsor->id);
    }

    private function logAttendance(GuestSponsor $guestSponsor, Event $event, string $type, array $data = [], ?int $scannedBy = null, ?string $ipAddress = null): void
    {
        $this->guestSponsorRepository->updateOrCreateAttendanceLog(
            [
                'guest_sponsor_id' => $guestSponsor->id,
                'event_id' => $event->id,
                'type' => $type,
            ],
            [
                'scan_time' => now(),
                'scanned_by' => $scannedBy,
                'qr_code' => $data['qr_code'] ?? $guestSponsor->qr_code,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'ip_address' => $ipAddress,
            ],
        );
    }
}
