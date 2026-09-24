<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryLoan extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_BORROWED = 'borrowed';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_APPROVED,
        self::STATUS_BORROWED,
        self::STATUS_RETURNED,
        self::STATUS_CANCELLED,
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'borrow_date' => 'date',
            'expected_return_date' => 'date',
            'actual_return_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function borrower(): MorphTo
    {
        return $this->morphTo();
    }

    public function handovers(): HasMany
    {
        return $this->hasMany(InventoryHandover::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(InventoryDocument::class);
    }

    public function agreementDocument(): BelongsTo
    {
        return $this->belongsTo(InventoryDocument::class, 'agreement_document_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function returnedReceivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_received_by');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_BORROWED]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_BORROWED)
            ->whereNotNull('expected_return_date')
            ->where('expected_return_date', '<', now()->toDateString());
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_BORROWED
            && $this->expected_return_date !== null
            && $this->expected_return_date->lt(today());
    }

    public function canHandover(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_BORROWED], true);
    }

    public function canReturn(): bool
    {
        return $this->status === self::STATUS_BORROWED;
    }

    public function canCancel(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
