<?php

namespace App\Services;

use App\Exceptions\GoogleDriveApiException;
use App\Models\Gallery;
use App\Models\GalleryAlbum;
use App\Repositories\GalleryAlbumRepository;
use App\Repositories\GalleryRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GalleryService
{
    public function __construct(
        private GalleryRepository $galleryRepository,
        private GoogleDriveService $googleDriveService,
        private GalleryAlbumRepository $galleryAlbumRepository,
    ) {}

    public function getAllPublic(): object
    {
        return $this->galleryRepository->query()
            ->with(['event.category', 'album'])
            ->where('type', 'image')
            ->where('is_featured', true)
            ->orderBy('is_featured', 'desc')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function getByEvent(int $eventId): object
    {
        return $this->galleryRepository->query()
            ->where('event_id', $eventId)
            ->where('type', 'image')
            ->where('is_featured', true)
            ->orderBy('is_featured', 'desc')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function storeLocal(array $data, UploadedFile $file): Gallery
    {
        $filePath = $this->uploadFile($file, 'galleries');
        $thumbnailPath = $this->generateThumbnail($file, 'galleries/thumbnails');

        $data['file_path'] = $filePath;
        $data['thumbnail_path'] = $thumbnailPath;
        $data['source'] = 'local';
        $data['created_by'] = auth()->id();
        unset($data['file']);

        return $this->galleryRepository->create($data);
    }

    public function storeGoogleDrive(array $data): Gallery
    {
        $googleDriveUrl = $data['google_drive_url'];
        $fileId = $this->extractDriveFileId($googleDriveUrl);

        if (! $fileId) {
            throw new \InvalidArgumentException('Google Drive link is invalid or inaccessible.');
        }

        $data['google_drive_url'] = $googleDriveUrl;
        $data['google_drive_file_id'] = $fileId;
        $data['source'] = 'gdrive';
        $data['created_by'] = auth()->id();

        return $this->galleryRepository->create($data);
    }

    public function update(int $id, array $data): Gallery
    {
        $gallery = $this->galleryRepository->findById($id);

        if (isset($data['file']) && $data['file'] instanceof UploadedFile) {
            $this->deleteFile($gallery->file_path);
            $this->deleteFile($gallery->thumbnail_path);

            $filePath = $this->uploadFile($data['file'], 'galleries');
            $thumbnailPath = $this->generateThumbnail($data['file'], 'galleries/thumbnails');

            $data['file_path'] = $filePath;
            $data['thumbnail_path'] = $thumbnailPath;
            unset($data['file']);
        }

        return $this->galleryRepository->update($gallery, $data);
    }

    public function delete(int $id): bool
    {
        $gallery = $this->galleryRepository->findById($id);

        if ($gallery->source === 'local') {
            $this->deleteFile($gallery->file_path);
            $this->deleteFile($gallery->thumbnail_path);
        }

        return $this->galleryRepository->delete($gallery);
    }

    public function getAlbumsWithGalleries(): object
    {
        return $this->galleryRepository->query()
            ->with('album')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('gallery_album_id');
    }

    public function syncAllDriveAlbums(): array
    {
        $albums = $this->galleryAlbumRepository->allWithDriveFolder();
        $results = [];

        foreach ($albums as $album) {
            $results[] = $this->syncAlbumFromDrive($album);
        }

        return $results;
    }

    public function syncAlbumFromDrive(GalleryAlbum $album): array
    {
        $lock = Cache::lock('gallery:sync:'.$album->id, 300);

        if (! $lock->get()) {
            return [
                'album_id' => $album->id,
                'status' => 'skipped',
                'message' => 'Sync sudah berjalan.',
            ];
        }

        try {
            $folderId = $this->googleDriveService->extractFolderId($album->gdrive_folder_url);

            if (! $folderId) {
                $this->galleryAlbumRepository->update($album, ['gdrive_sync_error' => 'URL folder Google Drive tidak valid.']);
                Log::warning('Gallery Google Drive sync gagal', ['album_id' => $album->id, 'error' => 'URL folder Google Drive tidak valid.']);

                return [
                    'album_id' => $album->id,
                    'status' => 'error',
                    'message' => 'URL folder Google Drive tidak valid.',
                ];
            }

            try {
                $files = $this->googleDriveService->listFiles($folderId);
            } catch (GoogleDriveApiException $e) {
                $this->galleryAlbumRepository->update($album, ['gdrive_sync_error' => $e->getMessage()]);
                Log::warning('Gallery Google Drive sync gagal', ['album_id' => $album->id, 'error' => $e->getMessage()]);

                return [
                    'album_id' => $album->id,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }

            $keepIds = [];

            try {
                DB::transaction(function () use ($album, $files, &$keepIds) {
                    foreach ($files as $file) {
                        if ($file->type === 'folder' || $file->type === 'other') {
                            continue;
                        }

                        $this->galleryRepository->updateOrCreateByDriveFile(
                            $album->id,
                            $file->id,
                            [
                                'title' => $file->name,
                                'source' => 'gdrive',
                                'type' => $file->type,
                                'google_drive_url' => 'https://drive.google.com/file/d/'.$file->id.'/view',
                            ]
                        );

                        $keepIds[] = $file->id;
                    }

                    $this->galleryRepository->deleteStaleDriveFiles($album->id, $keepIds);
                    $this->galleryAlbumRepository->update($album, [
                        'last_synced_at' => now(),
                        'gdrive_sync_error' => null,
                    ]);
                });
            } catch (\Throwable $e) {
                $this->galleryAlbumRepository->update($album, ['gdrive_sync_error' => 'Sync gagal: terjadi kesalahan internal.']);
                Log::error('Gallery Google Drive sync error', ['album_id' => $album->id, 'error' => $e->getMessage()]);

                return [
                    'album_id' => $album->id,
                    'status' => 'error',
                    'message' => 'Sync gagal: terjadi kesalahan internal.',
                ];
            }

            Log::info('Gallery Google Drive sync sukses', ['album_id' => $album->id, 'count' => count($keepIds)]);

            return [
                'album_id' => $album->id,
                'status' => 'synced',
                'count' => count($keepIds),
            ];
        } finally {
            $lock->release();
        }
    }

    private function uploadFile(UploadedFile $file, string $path): string
    {
        return $file->store($path, 'public');
    }

    private function generateThumbnail(UploadedFile $file, string $path): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
            $sourcePath = $file->getPathname();

            match ($extension) {
                'jpg', 'jpeg' => $sourceImage = imagecreatefromjpeg($sourcePath),
                'png' => $sourceImage = imagecreatefrompng($sourcePath),
                'webp' => $sourceImage = imagecreatefromwebp($sourcePath),
                default => $sourceImage = null,
            };

            if (! $sourceImage) {
                return $this->uploadFile($file, $path);
            }

            $origWidth = imagesx($sourceImage);
            $origHeight = imagesy($sourceImage);
            $targetSize = 300;

            $thumbImage = imagecreatetruecolor($targetSize, $targetSize);
            imagecopyresampled(
                $thumbImage, $sourceImage,
                0, 0, 0, 0,
                $targetSize, $targetSize,
                $origWidth, $origHeight
            );

            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'_thumb.'.$extension;
            $storagePath = $path.'/'.$filename;
            $tempPath = sys_get_temp_dir().'/'.$filename;

            match ($extension) {
                'jpg', 'jpeg' => imagejpeg($thumbImage, $tempPath, 80),
                'png' => imagepng($thumbImage, $tempPath, 8),
                'webp' => imagewebp($thumbImage, $tempPath, 80),
            };

            Storage::disk('public')->put($storagePath, file_get_contents($tempPath));
            unlink($tempPath);
            imagedestroy($sourceImage);
            imagedestroy($thumbImage);

            return $storagePath;
        }

        return $this->uploadFile($file, $path);
    }

    private function extractDriveFileId(string $url): ?string
    {
        $patterns = [
            '/\/file\/d\/([a-zA-Z0-9_-]+)/',
            '/[?&]id=([a-zA-Z0-9_-]+)/',
            '/\/open\?id=([a-zA-Z0-9_-]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    private function deleteFile(?string $path): bool
    {
        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }
}
