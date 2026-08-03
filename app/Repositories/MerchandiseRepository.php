<?php

namespace App\Repositories;

use App\Models\Merchandise;

class MerchandiseRepository extends BaseRepository
{
    public function __construct(Merchandise $merchandise)
    {
        parent::__construct($merchandise);
    }

    public function findAvailable()
    {
        return $this->model->where('status', 'available')->get();
    }

    public function paginateWithOrders(int $perPage = 15)
    {
        return $this->model->with('orders')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
