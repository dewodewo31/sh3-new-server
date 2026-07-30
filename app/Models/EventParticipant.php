<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventParticipant extends Model
{
    protected $guarded = [];
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_attended' => 'boolean',
            'is_membership_free' => 'boolean',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function attendance()
    {
        return $this->hasOne(Attendance::class);
    }
}
