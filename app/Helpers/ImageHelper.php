<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class ImageHelper
{
    public static function upload($file, string $path = 'uploads'): string
    {
        return $file->store($path, 'public');
    }

    public static function delete(?string $path): bool
    {
        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }

    public static function getUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public static function gdriveThumbUrl(?string $url, ?string $fileId): ?string
    {
        if ($fileId) {
            return 'https://drive.google.com/thumbnail?id='.$fileId.'&sz=w800';
        }

        return $url;
    }

    public static function gdriveContentUrl(?string $fileId): ?string
    {
        return $fileId
            ? 'https://drive.google.com/uc?export=download&id='.$fileId.'&confirm=t'
            : null;
    }
}
