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

    public function findExpiringMembers(int $days = 7)
    {
        return $this->model->where('membership_type', '!=', 'none')
            ->whereDate('membership_end_date', '=', now()->addDays($days))
            ->get();
    }

    public function paginateWithMembership(int $perPage = 15)
    {
        return $this->model->with('membershipHistories')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
