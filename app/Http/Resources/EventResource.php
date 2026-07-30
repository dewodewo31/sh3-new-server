<?php

namespace App\Http\Resources;

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
            'banner' => $this->banner,
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
            'schedules' => EventScheduleResource::collection($this->whenLoaded('schedules')),
            'created_at' => $this->created_at,
        ];
    }
}
