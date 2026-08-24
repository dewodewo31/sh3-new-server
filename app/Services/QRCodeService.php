<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Repositories\EventParticipantRepository;
use Illuminate\Support\Str;

class QRCodeService
{
    public function __construct(
        private EventParticipantRepository $eventParticipantRepository,
    ) {}

    /**
     * Generate a unique ticket QR code for a single event registration.
     *
     * The QR payload is a ticket identifier (NOT the participant identity):
     *   SH3-{eventId}-{yearShort}-{secureRandomToken}
     * e.g. SH3-8-26-X7K92P
     *
     * The random token is generated with Str::random() (cryptographically
     * secure, backed by random_bytes) and is guaranteed unique across the
     * event_participants table via a retry loop.
     */
    public function generate(EventParticipant $eventParticipant): string
    {
        $event = $eventParticipant->event;
        $eventId = $event instanceof Event ? $event->id : $eventParticipant->event_id;
        $year = ($event instanceof Event && $event->start_date)
            ? $event->start_date->format('y')
            : now()->format('y');

        do {
            $token = strtoupper(Str::random(6));
            $code = "SH3-{$eventId}-{$year}-{$token}";
        } while ($this->eventParticipantRepository->findByQrCode($code) !== null);

        $eventParticipant->update(['qr_code' => $code]);

        return $code;
    }

    /**
     * Decode a scanned QR string.
     *
     * Returns one of:
     *  - ['qr_code' => 'SH3-...']            -> new ticket format
     *  - ['hash_id' => '3950', 'status' => 'member']     -> legacy member
     *  - ['hash_id' => 'NM0001', 'status' => 'non_member'] -> legacy non-member
     *  - null                                  -> unrecognized
     */
    public function decode(string $qrData): ?array
    {
        $qrData = trim($qrData);

        if (preg_match('/^SH3-\d+-\d{2}-[A-Z0-9]{6}$/', $qrData)) {
            return ['qr_code' => $qrData];
        }

        if (preg_match('/^\d{4}$/', $qrData)) {
            return ['hash_id' => $qrData, 'status' => 'member'];
        }

        if (preg_match('/^NM\d{4}$/', $qrData)) {
            return ['hash_id' => $qrData, 'status' => 'non_member'];
        }

        return null;
    }

    public function isGuestSponsorCode(string $qrData): bool
    {
        return (bool) preg_match('/^GS-\d+-\d+-\d{4}$/', $qrData);
    }
}
