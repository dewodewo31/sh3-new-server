<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationMemberResource;
use App\Repositories\OrganizationMemberRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OrganizationController extends Controller
{
    public function __construct(
        private OrganizationMemberRepository $organizationMemberRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $members = $this->organizationMemberRepository->search([
            'search' => $request->query('search'),
            'year' => $request->query('year'),
            'level' => $request->query('level'),
        ]);

        return OrganizationMemberResource::collection($members)->response();
    }

    public function show(string $id): JsonResponse
    {
        $member = $this->organizationMemberRepository->findByIdWithHolder((int) $id);

        return response()->json(['data' => new OrganizationMemberResource($member)]);
    }

    public function stats(): JsonResponse
    {
        return response()->json(['data' => $this->organizationMemberRepository->stats()]);
    }

    public function tree(): JsonResponse
    {
        $data = Cache::remember('api:org:tree', 3600, fn () => $this->organizationMemberRepository->tree());

        return response()->json(['data' => $data]);
    }

    public function years(): JsonResponse
    {
        $data = Cache::remember('api:org:years', 3600, fn () => $this->organizationMemberRepository->years());

        return response()->json(['data' => $data]);
    }
}
