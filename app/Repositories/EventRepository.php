<?php

namespace App\Repositories;

use App\Models\Event;

class EventRepository extends BaseRepository
{
    public function __construct(Event $event)
    {
        parent::__construct($event);
    }

    public function findBySlug(string $slug)
    {
        return $this->findFirstBy('slug', $slug, ['category', 'schedules']);
    }

    public function findPublished(array $relations = ['category'])
    {
        return $this->model->with($relations)
            ->where('status', 'publish')
            ->orderBy('start_date')
            ->get();
    }

    public function findUpcoming(array $relations = ['category'])
    {
        return $this->model->with($relations)
            ->whereIn('status', ['publish', 'ongoing'])
            ->where('start_date', '>=', now())
            ->orderBy('start_date')
            ->get();
    }

    public function findOngoing()
    {
        return $this->model->where('status', 'ongoing')->get();
    }

    public function paginateWithCategory(int $perPage = 15)
    {
        return $this->model->with('category')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function search(array $filters = [], int $perPage = 15)
    {
        $query = $this->model->with('category');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findEventsByCategory(int $categoryId)
    {
        return $this->model->where('category_id', $categoryId)->get();
    }
}
