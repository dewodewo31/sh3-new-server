<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Bookkeeping extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Financial status lifecycle constants
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_APPROVED,
        self::STATUS_PAID,
        self::STATUS_CANCELLED,
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
            'status' => 'string',
            'due_date' => 'date',
            'approved_at' => 'datetime',
            'financial_account_id' => 'integer',
            'activity_id' => 'integer',
            'approved_by' => 'integer',
            'reference_id' => 'integer',
        ];
    }

    public function sponsor()
    {
        return $this->belongsTo(Sponsor::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeNotCancelled($query)
    {
        return $query->where('status', '!=', self::STATUS_CANCELLED);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_PAID]);
    }

    public function scopeForAccount($query, $id)
    {
        return $query->where('financial_account_id', $id);
    }

    public function scopeForActivity($query, $id)
    {
        return $query->where('activity_id', $id);
    }

    public function scopeInPeriod($query, $from, $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canSubmit(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canApprove(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SUBMITTED], true);
    }

    public function canMarkPaid(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function canCancel(): bool
    {
        return ! $this->isPaid()
            && in_array($this->status, [
                self::STATUS_DRAFT,
                self::STATUS_SUBMITTED,
                self::STATUS_APPROVED,
            ], true);
    }

    public function canBeEdited(): bool
    {
        return ! $this->isPaid();
    }

    public function canBeDeleted(): bool
    {
        return ! $this->isPaid();
    }
}
