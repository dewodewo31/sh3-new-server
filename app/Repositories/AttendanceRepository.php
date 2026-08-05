<?php

namespace App\Repositories;

use App\Models\Attendance;
use App\Models\Event;

class AttendanceRepository extends BaseRepository
{
    public function __construct(Attendance $attendance)
    {
        parent::__construct($attendance);
    }

    public function findByEventParticipant(int $eventParticipantId)
    {
        return $this->findFirstBy('event_participant_id', $eventParticipantId);
    }

    public function findByEvent(int $eventId)
    {
        return $this->model
            ->whereHas('eventParticipant', function ($q) use ($eventId) {
                $q->where('event_id', $eventId);
            })
            ->with('eventParticipant.participant')
            ->get();
    }

    public function findPresentByEvent(int $eventId)
    {
        return $this->model
            ->whereHas('eventParticipant', function ($q) use ($eventId) {
                $q->where('event_id', $eventId);
            })
            ->whereIn('status', ['present', 'late'])
            ->with('eventParticipant.participant')
            ->get();
    }

    public function reportStats(array $filters = [])
    {
        $events = Event::query()
            ->when(! empty($filters['event_id']), function ($q) use ($filters) {
                $q->where('id', $filters['event_id']);
            })
            ->when(! empty($filters['date_from']), function ($q) use ($filters) {
                $q->where('start_date', '>=', $filters['date_from']);
            })
            ->when(! empty($filters['date_to']), function ($q) use ($filters) {
                $q->where('start_date', '<=', $filters['date_to']);
            })
            ->with('eventParticipants.attendance')
            ->orderBy('start_date')
            ->get();

        return $events->map(function (Event $event) {
            $registrations = $event->eventParticipants;

            $present = $registrations->filter(
                fn ($registration) => $registration->attendance?->status === 'present'
            )->count();

            $late = $registrations->filter(
                fn ($registration) => $registration->attendance?->status === 'late'
            )->count();

            $leftEarly = $registrations->filter(
                fn ($registration) => $registration->attendance?->status === 'left_early'
            )->count();

            $registered = $registrations->count();
            $attended = $present + $late + $leftEarly;

            return [
                'event_id' => $event->id,
                'event_title' => $event->title,
                'start_date' => $event->start_date?->toISOString(),
                'end_date' => $event->end_date?->toISOString(),
                'registered' => $registered,
                'present' => $present,
                'late' => $late,
                'left_early' => $leftEarly,
                'absent' => max(0, $registered - $attended),
                'attendance_rate' => $registered > 0 ? round(($attended / $registered) * 100, 2) : 0,
            ];
        })->values();
    }

    public function findSyncDown(array $filters = [])
    {
        return $this->model
            ->with('eventParticipant')
            ->when(! empty($filters['event_id']), function ($q) use ($filters) {
                $q->whereHas('eventParticipant', function ($query) use ($filters) {
                    $query->where('event_id', $filters['event_id']);
                });
            })
            ->when(! empty($filters['since']), function ($q) use ($filters) {
                $q->where('updated_at', '>=', $filters['since']);
            })
            ->orderBy('updated_at')
            ->get();
    }
}
