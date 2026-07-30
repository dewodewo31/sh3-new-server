<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchandiseOrder extends Model
{
    protected $guarded = [];
    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
            'quantity' => 'integer',
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
}
