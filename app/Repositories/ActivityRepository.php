<?php

namespace App\Repositories;

use App\Models\Activity;

class ActivityRepository extends BaseRepository
{
    public function __construct(Activity $model)
    {
        parent::__construct($model);
    }

    public function findActive()
    {
        return $this->model->where('is_active', true)->orderBy('sort_order')->get();
    }
}
