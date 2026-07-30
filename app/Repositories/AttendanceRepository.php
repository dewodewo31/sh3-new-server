<?php

namespace App\Repositories;

use App\Models\Attendance;

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
}
