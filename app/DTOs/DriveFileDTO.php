<?php

namespace App\DTOs;

class DriveFileDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $mimeType,
        public readonly string $type,
    ) {}

    public static function fromDriveApi(array $file): ?self
    {
        $id = $file['id'] ?? null;

        if (! $id) {
            return null;
        }

        $name = $file['name'] ?? '';
        $mimeType = $file['mimeType'] ?? '';

        $type = match (true) {
            str_starts_with($mimeType, 'image/') => 'image',
            str_starts_with($mimeType, 'video/') => 'video',
            $mimeType === 'application/vnd.google-apps.folder' => 'folder',
            default => 'other',
        };

        return new self(id: $id, name: $name, mimeType: $mimeType, type: $type);
    }
}
