<?php

namespace App\Repositories;

use App\Models\Merchandise;
use App\Support\Sort;

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
        $query = $this->model->with('orders');

        Sort::apply($query, ['name', 'price', 'stock', 'status', 'created_at'], 'created_at', 'desc');

        return $query->paginate($perPage)->withQueryString();
    }
}
