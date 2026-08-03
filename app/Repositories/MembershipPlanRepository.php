<?php

namespace App\Repositories;

use App\Models\MembershipPlan;
use Illuminate\Database\Eloquent\Collection;

class MembershipPlanRepository extends BaseRepository
{
    public function __construct(MembershipPlan $membershipPlan)
    {
        parent::__construct($membershipPlan);
    }

    public function allOrdered(): Collection
    {
        return $this->model->orderBy('sort_order')->orderBy('id')->get();
    }

    public function paginateFiltered(?string $search, ?string $status, int $perPage = 10)
    {
        $query = $this->model->newQuery();

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('key', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('is_active', $status === 'active');
        }

        return $query->orderBy('sort_order')->orderBy('id')->paginate($perPage);
    }

    public function nextSortOrder(): int
    {
        return (int) $this->model->max('sort_order') + 1;
    }

    public function activePlans(): Collection
    {
        return $this->model->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function findActiveByKey(string $key): ?MembershipPlan
    {
        return $this->model->where('key', $key)->where('is_active', true)->first();
    }
}
