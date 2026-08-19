<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Repositories\CategoryRepository;
use App\Services\FileService;
use App\Services\UserService;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private UserService $userService,
        private FileService $fileService,
    ) {}

    public function index()
    {
        $categories = $this->categoryRepository->allSorted(
            ['name', 'slug', 'distance_km', 'sort_order', 'is_active'],
            'sort_order',
            'asc',
        );

        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(CategoryRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('banner')) {
            $data['banner'] = $this->fileService->upload($request->file('banner'), 'categories');
        }

        $category = $this->categoryRepository->create($data);

        $this->userService->logActivity(auth()->user(), 'create_category', ['category_id' => $category->id, 'name' => $category->name]);
        Cache::forget('api:categories');

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil dibuat');
    }

    public function edit(int $id)
    {
        $category = $this->categoryRepository->findById($id);

        return view('categories.edit', compact('category'));
    }

    public function update(int $id, CategoryRequest $request)
    {
        $category = $this->categoryRepository->findById($id);
        $data = $request->validated();

        if ($request->hasFile('banner')) {
            $data['banner'] = $this->fileService->uploadOrReplace(
                $category->banner,
                $request->file('banner'),
                'categories',
            );
        }

        $this->categoryRepository->update($category, $data);

        $this->userService->logActivity(auth()->user(), 'update_category', ['category_id' => $category->id, 'name' => $category->name]);
        Cache::forget('api:categories');

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil diupdate');
    }

    public function destroy(int $id)
    {
        $category = $this->categoryRepository->findById($id);

        $this->fileService->delete($category->banner);

        $this->categoryRepository->delete($category);

        $this->userService->logActivity(auth()->user(), 'delete_category', ['category_id' => $id, 'name' => $category->name]);
        Cache::forget('api:categories');

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil dihapus');
    }
}
