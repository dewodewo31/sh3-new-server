<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryItemRequest;
use App\Http\Requests\InventoryPhotoRequest;
use App\Models\InventoryCategory;
use App\Models\InventoryPhoto;
use App\Repositories\InventoryItemRepository;
use App\Services\FileService;
use App\Services\InventoryItemService;

class InventoryController extends Controller
{
    public function __construct(
        private InventoryItemRepository $inventoryItemRepository,
        private InventoryItemService $inventoryItemService,
        private FileService $fileService,
    ) {}

    public function index()
    {
        $filters = request()->only(['search', 'status', 'category_id', 'condition']);
        $items = $this->inventoryItemRepository->search($filters);
        $categories = $this->categories();

        return view('inventory.index', compact('items', 'categories', 'filters'));
    }

    public function create()
    {
        $categories = $this->categories();

        return view('inventory.create', compact('categories'));
    }

    public function store(InventoryItemRequest $request)
    {
        $this->inventoryItemService->create($request->validated());

        return redirect()->route('admin.inventory.index')->with('success', 'Item inventaris berhasil ditambahkan');
    }

    public function show(int $id)
    {
        $item = $this->inventoryItemRepository->findById($id, ['category', 'photos']);

        return view('inventory.show', compact('item'));
    }

    public function edit(int $id)
    {
        $item = $this->inventoryItemRepository->findById($id);
        $categories = $this->categories();

        return view('inventory.edit', compact('item', 'categories'));
    }

    public function update(int $id, InventoryItemRequest $request)
    {
        $item = $this->inventoryItemRepository->findById($id);

        $this->inventoryItemService->update($item, $request->validated());

        return redirect()->route('admin.inventory.index')->with('success', 'Item inventaris berhasil diupdate');
    }

    public function destroy(int $id)
    {
        $item = $this->inventoryItemRepository->findById($id, ['photos']);

        foreach ($item->photos as $photo) {
            $this->fileService->delete($photo->file_path);
        }

        $this->inventoryItemService->delete($item);

        return redirect()->route('admin.inventory.index')->with('success', 'Item inventaris berhasil dihapus');
    }

    public function storePhoto(int $id, InventoryPhotoRequest $request)
    {
        $item = $this->inventoryItemRepository->findById($id);

        $data = $request->validated();
        $data['file_path'] = $this->fileService->upload($request->file('photo'), 'inventory/photos');
        $data['uploaded_by'] = auth()->id();
        $data['context'] = $data['context'] ?? 'gallery';
        unset($data['photo']);

        $item->photos()->create($data);

        return redirect()->route('admin.inventory.show', $item->id)->with('success', 'Foto berhasil ditambahkan');
    }

    public function destroyPhoto(int $photoId)
    {
        $photo = InventoryPhoto::findOrFail($photoId);

        $this->fileService->delete($photo->file_path);

        $itemId = $photo->inventory_item_id;
        $photo->delete();

        return redirect()->route('admin.inventory.show', $itemId)->with('success', 'Foto berhasil dihapus');
    }

    private function categories()
    {
        return InventoryCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
