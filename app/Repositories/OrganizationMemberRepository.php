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
}
