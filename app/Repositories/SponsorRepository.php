<?php

namespace App\Repositories;

use App\Models\Sponsor;

class SponsorRepository extends BaseRepository
{
    public function __construct(Sponsor $sponsor)
    {
        parent::__construct($sponsor);
    }

    public function findActive()
    {
        return $this->model->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function findByTier(string $tier)
    {
        return $this->model->where('tier', $tier)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
