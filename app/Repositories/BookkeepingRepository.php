<?php

namespace App\Repositories;

use App\Models\Bookkeeping;
use App\Support\Sort;

class BookkeepingRepository extends BaseRepository
{
    public function __construct(Bookkeeping $model)
    {
        parent::__construct($model);
    }

    public function filtered(array $filters, int $perPage = 15)
    {
        $query = $this->model->with(['sponsor', 'event']);
        $this->applyFilters($query, $filters);

        Sort::apply($query, ['transaction_date', 'amount', 'type', 'category'], 'transaction_date', 'desc');

        return $query->paginate($perPage)
            ->withQueryString();
    }

    public function totals(array $filters): array
    {
        $query = $this->model->query();
        $this->applyFilters($query, $filters);

        $income = (float) (clone $query)->where('type', 'income')->sum('amount');
        $expense = (float) (clone $query)->where('type', 'expense')->sum('amount');

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ];
    }

    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['sponsor_id'])) {
            $query->where('sponsor_id', $filters['sponsor_id']);
        }

        if (! empty($filters['event_id'])) {
            $query->where('event_id', $filters['event_id']);
        }
    }
}
