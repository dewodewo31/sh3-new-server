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
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function durationLabel(): string
    {
        return $this->duration_unit === 'days'
            ? $this->duration.' hari'
            : $this->duration.' bulan';
    }

    public function priceLabel(): string
    {
        return 'Rp '.number_format($this->price, 0, ',', '.');
    }
}
