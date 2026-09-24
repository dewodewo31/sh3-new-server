<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileService
{
    public function upload(UploadedFile $file, string $dir = 'uploads', string $disk = 'public'): string
    {
        return $file->store($dir, $disk);
    }

    public function uploadOrReplace(?string $existingPath, ?UploadedFile $file, string $dir): ?string
    {
        if (! $file) {
            return $existingPath;
        }

        if ($existingPath) {
            $this->delete($existingPath);
        }

        return $this->upload($file, $dir);
    }

    public function delete(?string $path, string $disk = 'public'): void
    {
        if ($path && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }

    public function getUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
