<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipPlan extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'duration' => 'integer',
            'base_event_price' => 'integer',
            'discount_percentage' => 'integer',
            'reference_event_count' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * price is DERIVED from the pricing rules, never set by hand.
     * This keeps a single formula in MembershipPricingService as the source of truth.
     */
    protected static function booted(): void
    {
        static::saving(function (self $plan) {
            if (! is_null($plan->base_event_price)
                && ! is_null($plan->discount_percentage)
                && ! is_null($plan->reference_event_count)) {
                $plan->price = $plan->fullPackagePrice();
            }
        });
    }

    public function durationLabel(): string
    {
        return match ($this->duration_unit) {
            'days'   => $this->duration.' hari',
            'months' => $this->duration.' bulan',
            'years'  => $this->duration.' tahun',
            default  => $this->duration.' bulan',
        };
    }

    public function effectiveEventPrice(): int
    {
        return (int) round($this->base_event_price * (100 - $this->discount_percentage) / 100);
    }

    public function fullPackagePrice(): int
    {
        return $this->effectiveEventPrice() * (int) $this->reference_event_count;
    }

    public function priceLabel(): string
    {
        return 'Rp '.number_format($this->price, 0, ',', '.');
    }
}
