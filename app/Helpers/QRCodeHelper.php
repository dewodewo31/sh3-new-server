<?php

namespace App\Helpers;

class QRCodeHelper
{
    public static function generate(int $eventId, int $participantId): string
    {
        $hash = substr(md5($eventId . $participantId . time()), 0, 8);

        return 'SH3-' . $eventId . '-' . $participantId . '-' . $hash;
    }

    public static function decode(string $qrCode): ?array
    {
        $parts = explode('-', $qrCode);

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
