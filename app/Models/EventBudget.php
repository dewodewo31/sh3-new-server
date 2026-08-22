<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventBudget extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}
