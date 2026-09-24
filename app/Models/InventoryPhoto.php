<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryPhoto extends Model
{
    public const CONTEXTS = [
        'gallery',
        'before_loan',
        'during_loan',
        'return',
        'damage',
        'maintenance',
        'other',
    ];

    protected $guarded = [];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
