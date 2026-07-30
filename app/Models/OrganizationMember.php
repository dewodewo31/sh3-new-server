<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationMember extends Model
{
    protected $guarded = [];
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'period_start' => 'date',
            'period_end' => 'date',
            'sort_order' => 'integer',
        ];
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }
}
