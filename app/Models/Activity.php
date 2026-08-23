<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function bookkeepings()
    {
        return $this->hasMany(Bookkeeping::class, 'activity_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function eventBudgets()
    {
        return $this->hasMany(EventBudget::class);
    }
}
