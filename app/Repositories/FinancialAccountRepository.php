<?php

namespace App\Repositories;

use App\Models\FinancialAccount;

class FinancialAccountRepository extends BaseRepository
{
    public function __construct(FinancialAccount $model)
    {
        parent::__construct($model);
    }

    public function findActive()
    {
        return $this->model->where('is_active', true)->orderBy('name')->get();
    }
}
