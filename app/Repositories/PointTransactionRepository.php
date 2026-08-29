<?php

namespace App\Repositories;

use App\Models\PointTransaction;

class PointTransactionRepository extends BaseRepository
{
    public function __construct(PointTransaction $pointTransaction)
    {
        parent::__construct($pointTransaction);
    }

    public function findSource(?string $sourceType, ?int $sourceId): ?PointTransaction
    {
        if ($sourceType === null || $sourceId === null) {
            return null;
        }

        return $this->model
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();
    }

    public function historyForParticipant(int $participantId, int $perPage = 15)
    {
        return $this->model
            ->where('participant_id', $participantId)
            ->orderByDesc('id')
            ->with(['membershipPlan', 'event', 'merchandiseOrder'])
            ->paginate($perPage);
    }

    public function balanceFromLedger(int $participantId): int
    {
        return (int) $this->model
            ->where('participant_id', $participantId)
            ->sum('amount');
    }

    public function transaction(string $type, int $participantId, int $amount, array $extra = []): PointTransaction
    {
        return $this->model->create(array_merge([
            'participant_id' => $participantId,
            'type' => $type,
            'amount' => $amount,
        ], $extra));
    }
}
