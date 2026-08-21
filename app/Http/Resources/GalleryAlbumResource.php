<?php

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GalleryAlbumResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'title' => $this->title,
            'description' => $this->description,
            'cover_image' => ImageHelper::getUrl($this->cover_image),
            'gdrive_folder_url' => $this->gdrive_folder_url,
            'galleries_count' => $this->whenCounted('galleries'),
            'galleries' => $this->whenLoaded('galleries', function () {
                return GalleryResource::collection($this->galleries);
            }),
            'event' => $this->whenLoaded('event', function () {
                return $this->event ? [
                    'id' => $this->event->id,
                    'title' => $this->event->title,
                ] : null;
            }),
        ];
    }
}
