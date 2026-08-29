<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchandiseOrder extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = [];
    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
            'quantity' => 'integer',
            'points_used' => 'integer',
            'discount_amount' => 'decimal:2',
            'unit_price_snapshot' => 'decimal:2',
            'quantity_snapshot' => 'integer',
            'points_per_unit_snapshot' => 'integer',
            'discount_per_unit_snapshot' => 'decimal:2',
            'cash_amount_snapshot' => 'decimal:2',
        ];
    }

    public function merchandise()
    {
        return $this->belongsTo(Merchandise::class);
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function pointTransactions()
    {
        return $this->hasMany(PointTransaction::class, 'merchandise_order_id');
    }

    public function markAsPaid(): void
    {
        if ($this->payment_status === self::STATUS_PENDING) {
            $this->update(['payment_status' => self::STATUS_PAID]);
        }
    }
}
