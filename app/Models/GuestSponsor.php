<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestSponsor extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sponsor()
    {
        return $this->belongsTo(Sponsor::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendances()
    {
        return $this->hasMany(GuestSponsorAttendance::class);
    }

    public function attendanceLogs()
    {
        return $this->hasMany(GuestSponsorAttendanceLog::class);
    }

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isBefore(now()->toDateString());
    }

    public function isUsable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        if ($this->valid_from && $this->valid_from->isAfter(now()->toDateString())) {
            return false;
        }

        return true;
    }
}
