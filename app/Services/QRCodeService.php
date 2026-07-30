<?php

namespace App\Services;

use App\Models\EventParticipant;
use Illuminate\Support\Str;

class QRCodeService
{
    public function generate(EventParticipant $eventParticipant): string
    {
        $data = [
            'ep_id' => $eventParticipant->id,
            'event_id' => $eventParticipant->event_id,
            'participant_id' => $eventParticipant->participant_id,
            'hash' => Str::random(10),
            'timestamp' => now()->timestamp,
        ];

        $qrString = 'SH3-' . $eventParticipant->event_id . '-' . $eventParticipant->participant_id . '-' . Str::random(8);

        $eventParticipant->update(['qr_code' => $qrString]);

        return $qrString;
    }

    public function decode(string $qrData): ?array
    {
        $parts = explode('-', $qrData);

        if (count($parts) !== 4 || $parts[0] !== 'SH3') {
            return null;
        }

        return [
            'event_id' => (int) $parts[1],
            'participant_id' => (int) $parts[2],
            'hash' => $parts[3],
        ];
    }
}
