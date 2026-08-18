<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestSponsorAttendanceLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'scan_time' => 'datetime',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    public function guestSponsor()
    {
        return $this->belongsTo(GuestSponsor::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function scannedBy()
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }
}
