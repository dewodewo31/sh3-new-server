<?php

namespace App\Console\Commands;

use App\Services\GalleryService;
use Illuminate\Console\Command;

class SyncGoogleDriveGalleryCommand extends Command
{
    protected $signature = 'gallery:sync-gdrive';

    protected $description = 'Sync gallery albums from Google Drive folders';

    public function handle(GalleryService $galleryService): int
    {
        $results = $galleryService->syncAllDriveAlbums();

        if (empty($results)) {
            $this->info('Tidak ada album dengan link Google Drive.');

            return self::SUCCESS;
        }

        $this->table(
            ['Album ID', 'Status', 'Info'],
            array_map(fn ($r) => [$r['album_id'], $r['status'], $r['count'] ?? $r['message'] ?? ''], $results)
        );

        return self::SUCCESS;
    }
}
