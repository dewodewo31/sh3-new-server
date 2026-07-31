<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth,
            'address' => $this->address,
            'emergency_contact' => $this->emergency_contact,
            'emergency_phone' => $this->emergency_phone,
            'medical_conditions' => $this->medical_conditions,
            'blood_type' => $this->blood_type,
            'jersey_size' => $this->jersey_size,
            'membership_type' => $this->membership_type,
            'membership_plan_name' => $this->membershipPlan?->name,
            'membership_start_date' => $this->membership_start_date,
            'membership_end_date' => $this->membership_end_date,
            'is_active' => $this->is_active,
            'total_events_participated' => $this->total_events_participated,
            'is_membership_active' => $this->isMembershipActive(),
            'membership_histories' => MembershipHistoryResource::collection($this->whenLoaded('membershipHistories')),
            'created_at' => $this->created_at,
        ];
    }
}
