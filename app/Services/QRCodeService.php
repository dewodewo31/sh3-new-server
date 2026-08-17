<?php

namespace App\Services;

use App\Models\EventParticipant;

class QRCodeService
{
    public function generate(EventParticipant $eventParticipant): string
    {
        $code = $eventParticipant->participant->participant_code;

        $eventParticipant->update(['qr_code' => $code]);

        return $code;
    }

    public function decode(string $qrData): ?array
    {
        if (preg_match('/^\d{4}$/', $qrData)) {
            return ['participant_code' => $qrData, 'status' => 'member'];
        }

        if (preg_match('/^NM\d{4}$/', $qrData)) {
            return ['participant_code' => $qrData, 'status' => 'non_member'];
        }

        return null;
    }
}
