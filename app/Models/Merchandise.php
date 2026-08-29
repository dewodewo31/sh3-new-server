<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Merchandise extends Model
{
    use HasFactory;

    protected $table = 'merchandise';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'size_options' => 'array',
            'points_required' => 'integer',
            'price_after_points' => 'decimal:2',
        ];
    }

    public function orders()
    {
        return $this->hasMany(MerchandiseOrder::class);
    }

    /**
     * Whether this merchandise is point-redeemable (a points_required is set).
     */
    public function isPointRedeemable(): bool
    {
        return $this->points_required !== null && $this->points_required > 0;
    }

    /**
     * Discount per unit (in Rupiah) that points cover.
     */
    public function discountPerUnit(): int
    {
        $discount = round(($this->price ?? 0) - ($this->price_after_points ?? 0));

        return max(0, (int) $discount);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
