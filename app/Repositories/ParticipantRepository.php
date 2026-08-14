<?php

namespace App\Repositories;

use App\Models\Participant;

class ParticipantRepository extends BaseRepository
{
    public function __construct(Participant $participant)
    {
        parent::__construct($participant);
    }

    public function findByEmail(string $email)
    {
        return $this->findFirstBy('email', $email);
    }

    public function findActiveMembers()
    {
        return $this->model->where('is_active', true)
            ->where('membership_type', '!=', 'none')
            ->whereDate('membership_end_date', '>=', now())
            ->get();
    }

    /**
     * Participants eligible for a new membership grant:
     * no membership history with status=active AND end_date >= today.
     * Backend filtering — the dropdown never receives ineligible participants.
     */
    public function eligibleForMembership(array $relations = [])
    {
        return $this->model->with($relations)
            ->eligibleForMembership()
            ->orderBy('name')
            ->get();
    }

    public function findExpiringMembers(int $days = 7)
    {
        return $this->model->where('membership_type', '!=', 'none')
            ->whereDate('membership_end_date', '=', now()->addDays($days))
            ->get();
    }

    public function paginateWithMembership(int $perPage = 15)
    {
        return $this->model->with(['membershipHistories', 'membershipPlan'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
