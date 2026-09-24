<?php

namespace App\Repositories;

use App\Models\InventoryExternalLoan;
use App\Support\Sort;

class InventoryExternalLoanRepository extends BaseRepository
{
    public function __construct(InventoryExternalLoan $inventoryExternalLoan)
    {
        parent::__construct($inventoryExternalLoan);
    }

    public function search(array $filters = [], int $perPage = 15)
    {
        $query = $this->model->with(['createdBy']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('external_party', 'like', "%{$search}%")
                    ->orWhere('items_description', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['overdue'])) {
            $query->overdue();
        }

        Sort::apply($query, ['external_party', 'status', 'borrow_date', 'expected_return_date', 'created_at'], 'created_at', 'desc');

        return $query->paginate($perPage)
            ->withQueryString();
    }
}
