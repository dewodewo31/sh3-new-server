<?php

namespace App\Models;

use App\Services\MembershipService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MembershipHistory extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const MEMBERSHIP_TAHUNAN = 'tahunan';

    public const MEMBERSHIP_SETENGAH_TAHUN = 'setengah_tahun';

    public const MEMBERSHIP_MINGGUAN = 'mingguan';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'price' => 'decimal:2',
        ];
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function plan()
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_type', 'key');
    }

    public function planLabel(): string
    {
        return $this->plan?->name
            ?? Str::title(str_replace('_', ' ', $this->membership_type));
    }

    public function payment()
    {
        return $this->morphOne(Payment::class, 'paymentable');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->end_date >= now()->toDateString();
    }

    public function markAsPaid(): void
    {
        app(MembershipService::class)->activate($this);
    }
}
