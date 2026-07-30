<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalleryAlbum extends Model
{
    protected $guarded = [];
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function galleries()
    {
        return $this->hasMany(Gallery::class);
    }
}
