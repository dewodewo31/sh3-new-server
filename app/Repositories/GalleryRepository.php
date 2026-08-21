<?php

namespace App\Repositories;

use App\Models\Gallery;

class GalleryRepository extends BaseRepository
{
    public function __construct(Gallery $gallery)
    {
        parent::__construct($gallery);
    }

    public function findByEvent(int $eventId)
    {
        return $this->model->where('event_id', $eventId)
            ->orderBy('sort_order')
            ->get();
    }

    public function findFeatured()
    {
        return $this->model->where('is_featured', true)->get();
    }

    public function findAlbumsWithGalleries()
    {
        return $this->model->with('album')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('gallery_album_id');
    }

    public function updateOrCreateByDriveFile(int $albumId, string $fileId, array $attributes)
    {
        return $this->model->updateOrCreate(
            ['gallery_album_id' => $albumId, 'google_drive_file_id' => $fileId],
            $attributes
        );
    }

    public function deleteStaleDriveFiles(int $albumId, array $keepFileIds): int
    {
        return $this->model
            ->where('gallery_album_id', $albumId)
            ->where('source', 'gdrive')
            ->whereNotIn('google_drive_file_id', $keepFileIds)
            ->delete();
    }
}
