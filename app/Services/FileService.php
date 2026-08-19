<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileService
{
    public function upload(UploadedFile $file, string $dir = 'uploads'): string
    {
        return $file->store($dir, 'public');
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

    public function delete(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
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
