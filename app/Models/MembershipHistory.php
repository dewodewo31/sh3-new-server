<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class MembershipHistory extends Model
{
    use HasFactory;

    protected $guarded = [];

    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'normal_end_date' => 'date',
            'eligible_event_count' => 'integer',
            'base_event_price' => 'integer',
            'discount_percentage' => 'integer',
            'effective_event_price' => 'integer',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_type', 'key');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'paymentable_id')
            ->where('paymentable_type', self::class);
    }

    public function payment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'paymentable');
    }

    public function markAsPaid(): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            return;
        }

        $this->update(['status' => self::STATUS_ACTIVE]);

        $this->participant->update([
            'membership_type' => $this->membership_type,
            'membership_start_date' => $this->start_date,
            'membership_end_date' => $this->end_date,
        ]);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->end_date->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->end_date->isPast();
    }

    public function planLabel(): string
    {
        return $this->plan?->name ?? $this->membership_type;
    }
}
