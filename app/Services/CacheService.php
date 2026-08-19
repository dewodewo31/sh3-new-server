<?php

namespace App\Services;

use App\Models\Category;
use App\Models\MembershipPlan;
use App\Models\OrganizationMember;
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
            return Category::orderBy('name')->get();
        });
    }

    private function warmMembershipPlans(): void
    {
        Cache::remember('api:membership:plans', 3600, function () {
            return MembershipPlan::orderBy('sort_order')->get();
        });
    }
}
