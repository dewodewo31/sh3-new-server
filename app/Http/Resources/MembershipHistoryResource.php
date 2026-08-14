<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'membership_type' => $this->membership_type,
            'membership_plan_name' => $this->plan?->name,
            'start_date' => $this->start_date,
            'normal_end_date' => $this->normal_end_date,
            'end_date' => $this->end_date,
            'eligible_event_count' => $this->eligible_event_count,
            'base_event_price' => $this->base_event_price,
            'discount_percentage' => $this->discount_percentage,
            'effective_event_price' => $this->effective_event_price,
            'price' => $this->price,
            'status' => $this->status,
        ];
    }
}
