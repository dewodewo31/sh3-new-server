<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISH = 'publish';
    public const STATUS_ONGOING = 'ongoing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = [];
    protected static function booted()
    {
        static::creating(function ($event) {
            if (empty($event->slug)) {
                $event->slug = Str::slug($event->title) . '-' . Str::random(5);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'registration_start_date' => 'datetime',
            'registration_end_date' => 'datetime',
            'price' => 'decimal:2',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_free_for_members' => 'boolean',
            'key_points' => 'array',
            'quota' => 'integer',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function schedules()
    {
        return $this->hasMany(EventSchedule::class);
    }

    public function eventParticipants()
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function sponsors()
    {
        return $this->belongsToMany(Sponsor::class, 'event_sponsors')
            ->withPivot(['package', 'value', 'status'])
            ->withTimestamps();
    }

    public function galleries()
    {
        return $this->hasMany(Gallery::class);
    }

    public function galleryAlbums()
    {
        return $this->hasMany(GalleryAlbum::class);
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function participants()
    {
        return $this->belongsToMany(Participant::class, 'event_participants')
            ->withPivot(['registration_type', 'amount', 'payment_status', 'is_attended', 'check_in_at', 'check_out_at', 'qr_code', 'is_membership_free', 'payment_id'])
            ->withTimestamps();
    }

    public function remainingQuota(): int
    {
        if (!$this->quota) {
            return -1;
        }

        $registered = $this->eventParticipants()
            ->whereIn('payment_status', ['pending', 'confirmed'])
            ->count();

        return max(0, $this->quota - $registered);
    }
}
