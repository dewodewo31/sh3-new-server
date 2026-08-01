<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SponsorResource;
use App\Repositories\SponsorRepository;
use Illuminate\Http\JsonResponse;

class SponsorController extends Controller
{
    public function __construct(
        private SponsorRepository $sponsorRepository,
    ) {}

    public function index(): JsonResponse
    {
        $sponsors = $this->sponsorRepository->findActive();

        return response()->json(['data' => SponsorResource::collection($sponsors)]);
    }
}
