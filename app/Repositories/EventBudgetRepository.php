<?php

namespace App\Repositories;

use App\Models\EventBudget;

class EventBudgetRepository extends BaseRepository
{
    public function __construct(EventBudget $model)
    {
        parent::__construct($model);
    }

    public function byEvent($eventId)
    {
        return $this->model->where('event_id', $eventId)->with('activity')->get();
    }
}
