<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GalleryAlbum extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function galleries()
    {
        return $this->hasMany(Gallery::class);
    }
}
