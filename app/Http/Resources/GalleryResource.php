<?php

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GalleryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'album_id' => $this->gallery_album_id,
            'title' => $this->title,
            'description' => $this->description,
            'source' => $this->source,
            'url' => $this->source === 'gdrive'
                ? $this->google_drive_url
                : ImageHelper::getUrl($this->file_path),
            'thumb' => $this->source === 'gdrive'
                ? $this->google_drive_url
                : ($this->thumbnail_path
                    ? ImageHelper::getUrl($this->thumbnail_path)
                    : ImageHelper::getUrl($this->file_path)),
            'type' => $this->type,
            'is_featured' => $this->is_featured,
            'event' => $this->whenLoaded('event', function () {
                return [
                    'id' => $this->event->id,
                    'title' => $this->event->title,
                    'category' => $this->event->category?->name,
                    'status' => $this->event->status,
                ];
            }),
        ];
    }
}