<?php

use App\Models\EventParticipant;
use App\Services\QRCodeService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each event registration now owns a UNIQUE ticket QR code
     * (format: SH3-{eventId}-{yy}-{token}) instead of reusing the
     * participant's identity hash. This migration:
     *   1. Backfills every existing registration with a unique code
     *      (OTS rows keep their own 'OTS-' format and are skipped).
     *   2. Adds a UNIQUE constraint to prevent collisions.
     */
    public function up(): void
    {
        $service = app(QRCodeService::class);

        EventParticipant::query()
            ->whereNull('qr_code')
            ->orWhere('qr_code', '')
            ->orWhere('qr_code', 'NOT LIKE', 'OTS-%')
            ->chunkById(200, function ($rows) use ($service) {
                foreach ($rows as $ep) {
                    $service->generate($ep);
                }
            });

        Schema::table('event_participants', function (Blueprint $table) {
            $table->unique('qr_code');
        });
    }

    public function down(): void
    {
        Schema::table('event_participants', function (Blueprint $table) {
            $table->dropUnique(['qr_code']);
        });
    }
};
