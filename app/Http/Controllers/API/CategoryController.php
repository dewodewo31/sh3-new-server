<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Repositories\CategoryRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryRepository $categoryRepository,
    ) {}

    public function index(): JsonResponse
    {
        $data = Cache::remember('api:categories', 3600, function () {
            $categories = $this->categoryRepository->findActive()->loadCount('events');

            return $categories->map(function ($category) {
                $resource = (new CategoryResource($category))->resolve();

                return array_merge($resource, ['events_count' => $category->events_count]);
            })->values()->all();
        });

        return response()->json(['data' => $data]);
    }
}
