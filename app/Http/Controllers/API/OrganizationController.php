<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Repositories\OrganizationMemberRepository;
use Illuminate\Http\JsonResponse;

class OrganizationController extends Controller
{
    public function __construct(
        private OrganizationMemberRepository $organizationMemberRepository,
    ) {}

    public function index(): JsonResponse
    {
        $members = $this->organizationMemberRepository->findActive();

        return response()->json(['data' => $members]);
    }
}
