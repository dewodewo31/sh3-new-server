<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sponsor extends Model
{
    protected $guarded = [];
    protected function casts(): array
    {
        return [
            'sponsorship_value' => 'decimal:2',
            'is_active' => 'boolean',
            'year' => 'integer',
        ];
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_sponsors')
            ->withPivot(['package', 'value', 'status'])
            ->withTimestamps();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
