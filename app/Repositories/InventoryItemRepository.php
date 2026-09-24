<?php

namespace App\Repositories;

use App\Models\InventoryItem;
use App\Support\Sort;

class InventoryItemRepository extends BaseRepository
{
    public function __construct(InventoryItem $inventoryItem)
    {
        parent::__construct($inventoryItem);
    }

    public function search(array $filters = [], int $perPage = 15)
    {
        $query = $this->model->with('category');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('asset_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['condition'])) {
            $query->where('condition', $filters['condition']);
        }

        Sort::apply($query, ['asset_code', 'name', 'status', 'condition', 'location', 'created_at'], 'created_at', 'desc');

        return $query->paginate($perPage)
            ->withQueryString();
    }
}
