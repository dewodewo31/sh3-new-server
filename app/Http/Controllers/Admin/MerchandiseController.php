<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchandiseRequest;
use App\Repositories\MerchandiseRepository;
use App\Services\FileService;

class MerchandiseController extends Controller
{
    public function __construct(
        private MerchandiseRepository $merchandiseRepository,
        private FileService $fileService,
    ) {}

    public function index()
    {
        $merchandise = $this->merchandiseRepository->paginateWithOrders();

        return view('merchandise.index', compact('merchandise'));
    }

    public function create()
    {
        return view('merchandise.create');
    }

    public function store(MerchandiseRequest $request)
    {
        $data = $this->prepareData($request, $request->validated());
        $data['created_by'] = auth()->id();

        if ($request->hasFile('image')) {
            $data['image'] = $this->fileService->upload($request->file('image'), 'merchandise');
        }

        $this->merchandiseRepository->create($data);

        return redirect()->route('admin.merchandise.index')->with('success', 'Merchandise berhasil ditambahkan');
    }

    public function edit(int $id)
    {
        $item = $this->merchandiseRepository->findById($id);

        return view('merchandise.edit', compact('item'));
    }

    public function update(int $id, MerchandiseRequest $request)
    {
        $item = $this->merchandiseRepository->findById($id);
        $data = $this->prepareData($request, $request->validated());

        if ($request->hasFile('image')) {
            $data['image'] = $this->fileService->uploadOrReplace(
                $item->image,
                $request->file('image'),
                'merchandise',
            );
        }

        $this->merchandiseRepository->update($item, $data);

        return redirect()->route('admin.merchandise.index')->with('success', 'Merchandise berhasil diupdate');
    }

    public function destroy(int $id)
    {
        $item = $this->merchandiseRepository->findById($id);

        $this->fileService->delete($item->image);

        $this->merchandiseRepository->delete($item);

        return redirect()->route('admin.merchandise.index')->with('success', 'Merchandise berhasil dihapus');
    }

    private function prepareData(MerchandiseRequest $request, array $data): array
    {
        if (! empty($data['size_options']) && is_string($data['size_options'])) {
            $decoded = json_decode($data['size_options'], true);

            if (is_array($decoded)) {
                $data['size_options'] = $decoded;
            }
        }

        return $data;
    }
}
