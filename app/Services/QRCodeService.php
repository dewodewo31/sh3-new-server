<?php

namespace App\Services;

use App\Models\EventParticipant;

class QRCodeService
{
    public function generate(EventParticipant $eventParticipant): string
    {
        $code = $eventParticipant->participant->hash_id;

        $eventParticipant->update(['qr_code' => $code]);

        return $code;
    }

    public function decode(string $qrData): ?array
    {
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
