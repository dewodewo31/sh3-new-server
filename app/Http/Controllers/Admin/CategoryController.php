<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Repositories\CategoryRepository;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryRepository $categoryRepository,
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
        $this->categoryRepository->create($request->validated());

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

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil diupdate');
    }

    public function destroy(int $id)
    {
        $category = $this->categoryRepository->findById($id);
        $this->categoryRepository->delete($category);

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil dihapus');
    }
}
