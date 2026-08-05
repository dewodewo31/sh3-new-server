<?php

namespace App\Repositories;

use App\Models\MerchandiseOrder;

class MerchandiseOrderRepository extends BaseRepository
{
    public function __construct(MerchandiseOrder $merchandiseOrder)
    {
        parent::__construct($merchandiseOrder);
    }

    public function findByParticipant(int $participantId)
    {
        return $this->model->with(['merchandise', 'payment'])
            ->where('participant_id', $participantId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findOwnedByParticipant(int $participantId, int $id)
    {
        return $this->model->with(['merchandise', 'payment'])
            ->where('participant_id', $participantId)
            ->where('id', $id)
            ->first();
    }
}
