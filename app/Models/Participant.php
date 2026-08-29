<?php

namespace App\Models;

use App\Services\ParticipantCodeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Participant extends Model
{
    use HasFactory;

    public const OTS_AGGREGATOR_CODE = 'NM0000';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'membership_start_date' => 'date',
            'membership_end_date' => 'date',
            'is_active' => 'boolean',
            'total_events_participated' => 'integer',
            'point_balance' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pointTransactions()
    {
        return $this->hasMany(PointTransaction::class);
    }

    /**
     * Hard invariant: the Manual-OTS aggregator pseudo-participant can represent
     * many on-the-spot attendees and must NEVER earn points. See H1 in plan.
     */
    public function isOtsAggregator(): bool
    {
        return $this->hash_id === self::OTS_AGGREGATOR_CODE;
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

    /**
     * Single source of truth for the admin grant participant dropdown:
     * participants WITHOUT a currently-active membership (status=active AND
     * end_date >= today). cancelled/expired/past-end_date histories do not
     * disqualify a participant. Used by both the create form query and the
     * POST validation, so frontend filtering can never be bypassed.
     */
    public function scopeEligibleForMembership(Builder $query): Builder
    {
        return $query->whereDoesntHave('membershipHistories', function (Builder $q) {
            $q->active(); // MembershipHistory::scopeActive — single source of truth
        });
    }

    protected static function booted(): void
    {
        static::creating(function (Participant $participant) {
            if (empty($participant->hash_id)) {
                $participant->hash_id = app(ParticipantCodeService::class)
                    ->next($participant->membership_type === 'none' ? 'NM' : '');
            }
        });
    }
}
