<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationMember extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'period_start' => 'date',
            'period_end' => 'date',
            'sort_order' => 'integer',
            'level' => 'integer',
        ];
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function parent()
    {
        return $this->belongsTo(OrganizationMember::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(OrganizationMember::class, 'parent_id')->orderBy('sort_order');
    }
}
