<?php

namespace App\Repositories;

use App\Models\Event;
use App\Models\GuestSponsor;
use App\Models\GuestSponsorAttendance;
use App\Models\GuestSponsorAttendanceLog;

class GuestSponsorRepository extends BaseRepository
{
    public function __construct(GuestSponsor $guestSponsor)
    {
        parent::__construct($guestSponsor);
    }

    public function paginateSorted(array $allowed, int $perPage = 15, array $relations = [], string $default = 'created_at', string $defaultDirection = 'desc')
    {
        return parent::paginateSorted($allowed, $perPage, ['user', 'sponsor', 'event'], $default, $defaultDirection);
    }

    public function findByUser(int $userId): ?GuestSponsor
    {
        return $this->model->where('user_id', $userId)->first();
    }

    public function findByQr(string $qrCode): ?GuestSponsor
    {
        return $this->model->with(['sponsor', 'event'])->where('qr_code', $qrCode)->first();
    }

    public function countActiveFor(int $sponsorId, int $eventId): int
    {
        return $this->model->where('sponsor_id', $sponsorId)
            ->where('event_id', $eventId)
            ->where('is_active', true)
            ->count();
    }

    public function countAll(): int
    {
        return $this->model->count();
    }

    public function countActive(): int
    {
        return $this->model->where('is_active', true)->count();
    }

    public function countExpired(): int
    {
        return $this->model->where('is_active', true)
            ->where('valid_until', '<', now()->toDateString())
            ->count();
    }

    public function quotas(): array
    {
        return Event::query()
            ->with(['sponsors' => fn ($q) => $q->wherePivot('max_guest_accounts', '>', 0)])
            ->whereHas('sponsors', fn ($q) => $q->where('event_sponsors.max_guest_accounts', '>', 0))
            ->orderBy('start_date', 'desc')
            ->get()
            ->flatMap(fn ($event) => $event->sponsors->map(fn ($sp) => [
                'sponsor_id' => $sp->id,
                'sponsor_name' => $sp->name,
                'event_id' => $event->id,
                'event_title' => $event->title,
                'max' => (int) $sp->pivot->max_guest_accounts,
                'used' => $this->countActiveFor($sp->id, $event->id),
                'remaining' => max(0, (int) $sp->pivot->max_guest_accounts - $this->countActiveFor($sp->id, $event->id)),
            ]))
            ->all();
    }

    public function quota(int $sponsorId, int $eventId): array
    {
        $max = Event::query()
            ->where('id', $eventId)
            ->with(['sponsors' => fn ($q) => $q->where('sponsors.id', $sponsorId)])
            ->first()
            ?->sponsors
            ->first()
            ?->pivot
            ?->max_guest_accounts ?? 0;

        $used = $this->countActiveFor($sponsorId, $eventId);

        return [
            'max' => (int) $max,
            'used' => $used,
            'remaining' => max(0, (int) $max - $used),
        ];
    }

    public function setQuota(int $sponsorId, int $eventId, int $max): void
    {
        Event::query()
            ->findOrFail($eventId)
            ->sponsors()
            ->syncWithPivotValues([$sponsorId], ['max_guest_accounts' => $max], false);
    }

    public function findAttendanceByEvent(int $guestSponsorId, int $eventId): ?GuestSponsorAttendance
    {
        return GuestSponsorAttendance::where('guest_sponsor_id', $guestSponsorId)
            ->where('event_id', $eventId)
            ->first();
    }

    public function createAttendance(array $data): GuestSponsorAttendance
    {
        return GuestSponsorAttendance::create($data);
    }

    public function updateAttendance(GuestSponsorAttendance $attendance, array $data): bool
    {
        return $attendance->update($data);
    }

    public function updateOrCreateAttendanceLog(array $attributes, array $values): void
    {
        GuestSponsorAttendanceLog::updateOrCreate($attributes, $values);
    }

    public function attendanceHistory(int $guestSponsorId)
    {
        return GuestSponsorAttendance::with(['event'])
            ->where('guest_sponsor_id', $guestSponsorId)
            ->orderBy('check_in_time', 'desc')
            ->get();
    }
}
