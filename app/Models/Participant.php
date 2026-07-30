<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    protected $guarded = [];
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'membership_start_date' => 'date',
            'membership_end_date' => 'date',
            'is_active' => 'boolean',
            'total_events_participated' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function membershipHistories()
    {
        return $this->hasMany(MembershipHistory::class);
    }

    public function eventParticipants()
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function merchandiseOrders()
    {
        return $this->hasMany(MerchandiseOrder::class);
    }

    public function organizationMembers()
    {
        return $this->hasMany(OrganizationMember::class);
    }

    public function isMembershipActive(): bool
    {
        if ($this->membership_type === 'none') {
            return false;
        }

        return $this->membership_end_date && $this->membership_end_date >= now()->toDateString();
    }
}
