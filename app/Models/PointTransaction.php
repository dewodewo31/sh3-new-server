<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointTransaction extends Model
{
    use HasFactory;

    public const TYPE_EARN = 'EARN';
    public const TYPE_REDEEM = 'REDEEM';
    public const TYPE_REVERSAL = 'REVERSAL';
    public const TYPE_ADJUSTMENT = 'ADJUSTMENT';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'point_rate' => 'integer',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function membershipPlan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function merchandiseOrder(): BelongsTo
    {
        return $this->belongsTo(MerchandiseOrder::class);
    }

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    public function isCredit(): bool
    {
        return $this->amount > 0;
    }

    public function isDebit(): bool
    {
        return $this->amount < 0;
    }
}
