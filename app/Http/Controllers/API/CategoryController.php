<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Repositories\CategoryRepository;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryRepository $categoryRepository,
    ) {}

    public function index(): JsonResponse
    {
        $categories = $this->categoryRepository->findActive()->loadCount('events');

        $data = $categories->map(function ($category) {
            $resource = (new CategoryResource($category))->resolve();

            return array_merge($resource, ['events_count' => $category->events_count]);
        });

        return response()->json(['data' => $data]);
    }
}
