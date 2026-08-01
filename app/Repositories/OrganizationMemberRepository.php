<?php

namespace App\Repositories;

use App\Models\OrganizationMember;

class OrganizationMemberRepository extends BaseRepository
{
    public function __construct(OrganizationMember $organizationMember)
    {
        parent::__construct($organizationMember);
    }

    public function findActive()
    {
        return $this->model->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function search(array $filters = [], int $perPage = 15)
    {
        $query = $this->model->where('is_active', true);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhere('role_description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['year'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('period_start', '<=', $filters['year'].'-12-31')
                    ->where('period_end', '>=', $filters['year'].'-01-01');
            });
        }

        if (isset($filters['level']) && $filters['level'] !== '') {
            $query->where('level', (int) $filters['level']);
        }

        return $query->orderBy('sort_order')
            ->with('participant')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function stats(): array
    {
        $query = $this->model->query();

        return [
            'total' => $query->count(),
            'active' => (clone $query)->where('is_active', true)->count(),
            'with_holder' => $this->model->whereNotNull('participant_id')->count(),
            'by_level' => $this->model
                ->selectRaw('level, count(*) as total')
                ->groupBy('level')
                ->orderBy('level')
                ->pluck('total', 'level')
                ->mapWithKeys(fn ($total, $level) => [(int) $level => $total]),
            'by_period' => $this->model
                ->selectRaw('period_start, period_end, count(*) as total')
                ->whereNotNull('period_start')
                ->whereNotNull('period_end')
                ->groupBy('period_start', 'period_end')
                ->orderBy('period_start')
                ->get()
                ->map(fn ($row) => [
                    'period_start' => $row->period_start?->toDateString(),
                    'period_end' => $row->period_end?->toDateString(),
                    'total' => $row->total,
                ]),
        ];
    }

    public function tree()
    {
        $members = $this->model->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $grouped = $members->groupBy('parent_id');

        $build = function (int|string|null $parentId) use (&$build, $grouped): array {
            return $grouped->get((string) $parentId, collect())
                ->map(fn ($member) => [
                    'id' => $member->id,
                    'parent_id' => $member->parent_id,
                    'name' => $member->name,
                    'position' => $member->position,
                    'level' => $member->level,
                    'sort_order' => $member->sort_order,
                    'children' => $build($member->id),
                ])
                ->values()
                ->all();
        };

        return $build(null);
    }

    public function findByIdWithHolder(int $id)
    {
        return $this->findById($id, ['participant']);
    }
}
