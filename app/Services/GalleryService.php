<?php

namespace App\Services;

use App\Models\Gallery;
use App\Models\GalleryAlbum;
use App\Repositories\GalleryRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class GalleryService
{
    public function __construct(
        private GalleryRepository $galleryRepository,
    ) {}

    public function getAllPublic(): object
    {
        return $this->galleryRepository->model
            ->with(['event.category', 'album'])
            ->where('type', 'image')
            ->orderBy('is_featured', 'desc')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function getByEvent(int $eventId): object
    {
        return $this->galleryRepository->model
            ->where('event_id', $eventId)
            ->where('type', 'image')
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

        if (!$fileId) {
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
        return $this->galleryRepository->model
            ->with('album')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('gallery_album_id');
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

            if (!$sourceImage) {
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

            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_thumb.' . $extension;
            $storagePath = $path . '/' . $filename;
            $tempPath = sys_get_temp_dir() . '/' . $filename;

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