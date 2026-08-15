<?php

namespace App\Repositories;

use App\Models\MembershipHistory;
use App\Support\Sort;

class MembershipHistoryRepository extends BaseRepository
{
    public function __construct(MembershipHistory $membershipHistory)
    {
        parent::__construct($membershipHistory);
    }

    public function paginateWithParticipant(int $perPage = 15)
    {
        $query = $this->model->with(['participant', 'plan']);

        Sort::apply($query, ['membership_type', 'start_date', 'end_date', 'price', 'status', 'created_at'], 'created_at', 'desc');

        return $query->paginate($perPage)->withQueryString();
    }

    public function findByParticipant(int $participantId)
    {
        return $this->model->where('participant_id', $participantId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findByStatus(string $status)
    {
        return $this->model->where('status', $status)->get();
    }

    public function countByStatus(string $status): int
    {
        return $this->model->where('status', $status)->count();
    }

    public function countExpiringSoon(int $days = 7): int
    {
        return $this->model->where('status', MembershipHistory::STATUS_ACTIVE)
            ->whereBetween('end_date', [now()->toDateString(), now()->addDays($days)->toDateString()])
            ->count();
    }

    public function sumPriceByStatus(string $status): float
    {
        return (float) $this->model->where('status', $status)->sum('price');
    }

    public function markExpired(): int
    {
        return $this->model->where('status', MembershipHistory::STATUS_ACTIVE)
            ->where('end_date', '<', now()->toDateString())
            ->update(['status' => MembershipHistory::STATUS_EXPIRED]);
    }
}
