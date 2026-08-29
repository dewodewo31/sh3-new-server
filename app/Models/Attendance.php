<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected function casts(): array
    {
        return [
            'check_in_time' => 'datetime',
            'check_out_time' => 'datetime',
            'invalidated_at' => 'datetime',
            'is_invalid' => 'boolean',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    public function eventParticipant()
    {
        return $this->belongsTo(EventParticipant::class);
    }

    public function pointTransactions()
    {
        return $this->hasMany(PointTransaction::class, 'attendance_id');
    }
}
