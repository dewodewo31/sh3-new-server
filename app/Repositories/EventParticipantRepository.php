<?php

namespace App\Repositories;

use App\Models\EventParticipant;

class EventParticipantRepository extends BaseRepository
{
    public function __construct(EventParticipant $eventParticipant)
    {
        parent::__construct($eventParticipant);
    }

    public function findByEventAndParticipant(int $eventId, int $participantId)
    {
        return $this->model
            ->where('event_id', $eventId)
            ->where('participant_id', $participantId)
            ->first();
    }

    public function findParticipantsByEvent(int $eventId)
    {
        return $this->model
            ->with('participant')
            ->where('event_id', $eventId)
            ->get();
    }

    public function findEventsByParticipant(int $participantId)
    {
        return $this->model
            ->with('event')
            ->where('participant_id', $participantId)
            ->get();
    }

    public function countRegisteredForEvent(int $eventId): int
    {
        return $this->model
            ->where('event_id', $eventId)
            ->whereIn('payment_status', ['pending', 'confirmed'])
            ->count();
    }
}
