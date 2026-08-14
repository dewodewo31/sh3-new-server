<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Participant extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['hash_id'];

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
                $participant->hash_id = static::generateHashId();
            }
        });
    }

    public static function generateHashId(): string
    {
        do {
            $code = 'SH3'.Str::upper(Str::random(7));
        } while (static::where('hash_id', $code)->exists());

        return $code;
    }

    protected function hashId(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['hash_id'] ?? null,
        );
    }
}
