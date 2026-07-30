<?php

namespace App\Repositories;

use App\Models\Category;

class CategoryRepository extends BaseRepository
{
    public function __construct(Category $category)
    {
        parent::__construct($category);
    }

    public function findActive()
    {
        return $this->model->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function findBySlug(string $slug)
    {
        return $this->findFirstBy('slug', $slug);
    }
}
