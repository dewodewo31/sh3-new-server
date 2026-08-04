<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Repositories\CategoryRepository;
use App\Services\UserService;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private UserService $userService,
    ) {}

    public function index()
    {
        $categories = $this->categoryRepository->all();

        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(CategoryRequest $request)
    {
        $category = $this->categoryRepository->create($request->validated());

        $this->userService->logActivity(auth()->user(), 'create_category', ['category_id' => $category->id, 'name' => $category->name]);

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
        $this->categoryRepository->update($category, $request->validated());

        $this->userService->logActivity(auth()->user(), 'update_category', ['category_id' => $category->id, 'name' => $category->name]);

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil diupdate');
    }

    public function destroy(int $id)
    {
        $category = $this->categoryRepository->findById($id);
        $this->categoryRepository->delete($category);

        $this->userService->logActivity(auth()->user(), 'delete_category', ['category_id' => $id, 'name' => $category->name]);

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil dihapus');
    }
}
