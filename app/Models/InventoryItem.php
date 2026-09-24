<?php

namespace App\Models;

use Database\Factories\InventoryItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    /** @use HasFactory<InventoryItemFactory> */
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_BORROWED = 'borrowed';

    public const STATUS_IN_MAINTENANCE = 'in_maintenance';

    public const STATUS_LOST = 'lost';

    public const STATUS_RETIRED = 'retired';

    public const CONDITION_EXCELLENT = 'excellent';

    public const CONDITION_GOOD = 'good';

    public const CONDITION_FAIR = 'fair';

    public const CONDITION_DAMAGED = 'damaged';

    public const CONDITION_CRITICAL = 'critical';

    public const STATUSES = [
        self::STATUS_AVAILABLE,
        self::STATUS_BORROWED,
        self::STATUS_IN_MAINTENANCE,
        self::STATUS_LOST,
        self::STATUS_RETIRED,
    ];

    public const CONDITIONS = [
        self::CONDITION_EXCELLENT,
        self::CONDITION_GOOD,
        self::CONDITION_FAIR,
        self::CONDITION_DAMAGED,
        self::CONDITION_CRITICAL,
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'purchase_price' => 'decimal:2',
            'warranty_expiry' => 'date',
        ];
    }

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function photos()
    {
        return $this->hasMany(InventoryPhoto::class);
    }

    public function loans()
    {
        return $this->hasMany(InventoryLoan::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
