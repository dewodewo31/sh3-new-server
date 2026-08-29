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
            'point_per_event_checkin' => 'integer',
        ];
    }

    /**
     * price is the FINAL PACKAGE PRICE set by the admin, stored as-is.
     * It is intentionally NOT recalculated on save — editing a plan must not
     * overwrite the admin-defined price.
     *
     * fullPackagePrice()/effectiveEventPrice() below remain available purely
     * as informational helpers (preview, stats, breakdown) — they never write
     * back to the model.
     */
    protected static function booted(): void
    {
        // no-op: price is admin-defined, never auto-derived
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
