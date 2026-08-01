<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'participant_id' => $this->participant_id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'position' => $this->position,
            'level' => $this->level,
            'role_description' => $this->role_description,
            'avatar' => $this->avatar,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'holder' => new ParticipantResource($this->whenLoaded('participant')),
            'children' => OrganizationMemberResource::collection($this->whenLoaded('children')),
        ];
    }
}
