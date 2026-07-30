<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipHistory extends Model
{
    protected $guarded = [];
    public $timestamps = false;

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
}
