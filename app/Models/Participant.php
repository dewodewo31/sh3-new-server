<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Participant extends Model
{
    use HasFactory;

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

    public function membershipPlan()
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_type', 'key');
    }

    public function membershipTypeLabel(): string
    {
        return $this->membershipPlan?->name
            ?? Str::title(str_replace('_', ' ', $this->membership_type));
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
