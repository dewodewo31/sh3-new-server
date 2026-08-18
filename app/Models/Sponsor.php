<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sponsor extends Model
{
    use HasFactory;

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
            ->withPivot(['package', 'value', 'status', 'max_guest_accounts'])
            ->withTimestamps();
    }

    public function guestSponsors()
    {
        return $this->hasMany(GuestSponsor::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
