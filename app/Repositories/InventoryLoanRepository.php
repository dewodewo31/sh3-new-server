<?php

namespace App\Repositories;

use App\Models\InventoryLoan;
use App\Support\Sort;

class InventoryLoanRepository extends BaseRepository
{
    public function __construct(InventoryLoan $inventoryLoan)
    {
        parent::__construct($inventoryLoan);
    }

    public function search(array $filters = [], int $perPage = 15)
    {
        $query = $this->model->with(['item', 'createdBy']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('borrower_name', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhereHas('item', function ($iq) use ($search) {
                        $iq->where('asset_code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['overdue'])) {
            $query->overdue();
        }

        Sort::apply($query, ['borrower_name', 'status', 'borrow_date', 'expected_return_date', 'created_at'], 'created_at', 'desc');

        return $query->paginate($perPage)
            ->withQueryString();
    }
}
