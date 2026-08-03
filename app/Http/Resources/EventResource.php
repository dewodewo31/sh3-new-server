<?php

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'location' => $this->location,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'image' => $this->image,
            'image_url' => ImageHelper::getUrl($this->image),
            'banner' => $this->banner,
            'banner_url' => ImageHelper::getUrl($this->banner),
            'key_points' => $this->key_points,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'registration_start_date' => $this->registration_start_date,
            'registration_end_date' => $this->registration_end_date,
            'quota' => $this->quota,
            'price' => $this->price,
            'is_free_for_members' => $this->is_free_for_members,
            'status' => $this->status,
            'remaining_quota' => $this->quota ? $this->remainingQuota() : -1,
            'registered_count' => $this->whenLoaded('eventParticipants', function () {
                return $this->eventParticipants
                    ->whereIn('payment_status', ['pending', 'confirmed'])
                    ->count();
            }, null),
            'creator' => $this->whenLoaded('createdBy', function () {
                return $this->createdBy ? ['id' => $this->createdBy->id, 'name' => $this->createdBy->name] : null;
            }, null),
            'schedules' => EventScheduleResource::collection($this->whenLoaded('schedules')),
            'galleries' => $this->whenLoaded('galleries', function () {
                return $this->galleries
                    ->where('type', 'image')
                    ->sortBy([
                        ['is_featured', 'desc'],
                        ['sort_order', 'asc'],
                        ['id', 'asc'],
                    ])
                    ->map(fn ($gallery) => ImageHelper::getUrl($gallery->file_path))
                    ->values();
            }, []),
            'created_at' => $this->created_at,
        ];
    }
}
