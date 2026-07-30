<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Repositories\MerchandiseRepository;
use Illuminate\Http\JsonResponse;

class MerchandiseController extends Controller
{
    public function __construct(
        private MerchandiseRepository $merchandiseRepository,
    ) {}

    public function index(): JsonResponse
    {
        $merchandise = $this->merchandiseRepository->findAvailable();

        return response()->json(['data' => $merchandise]);
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->merchandiseRepository->findById($id);

        return response()->json(['data' => $item]);
    }
}
