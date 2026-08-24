<?php

namespace App\Services;

use App\Models\Category;
use App\Models\OrganizationMember;
use App\Services\MembershipService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    public function warm(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        try {
            $this->warmOrganizationTree();
            $this->warmOrganizationYears();
            $this->warmCategories();
            $this->warmMembershipPlans();
        } catch (\Throwable $e) {
            Log::warning('Cache warming failed: '.$e->getMessage());
        }
    }

    private function warmOrganizationTree(): void
    {
        Cache::remember('api:org:tree', 3600, fn () => app(\App\Repositories\OrganizationMemberRepository::class)->tree());
    }

    private function warmOrganizationYears(): void
    {
        Cache::remember('api:org:years', 3600, fn () => app(\App\Repositories\OrganizationMemberRepository::class)->years());
    }

    private function warmCategories(): void
    {
        Cache::tags(['api:categories'])->remember('api:categories:list', 3600, function () {
            return Category::orderBy('name')->get()->toArray();
        });
    }

    private function warmMembershipPlans(): void
    {
        // Cache the resolved plan ARRAY, not an Eloquent Collection.
        // With config/cache.php `serializable_classes => false`, a cached Collection
        // unserializes to __PHP_Incomplete_Class on read (the GET /membership/plans bug).
        // Use put() (not remember()) so a stale object from a previous deploy is
        // always overwritten when warming.
        Cache::put('api:membership:plans', app(MembershipService::class)->plans(), 3600);
    }
}
