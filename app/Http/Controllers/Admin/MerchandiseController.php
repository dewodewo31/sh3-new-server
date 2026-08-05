<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchandiseRequest;
use App\Repositories\MerchandiseRepository;

class MerchandiseController extends Controller
{
    public function __construct(
        private MerchandiseRepository $merchandiseRepository,
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
        $data = $request->validated();
        $data['created_by'] = auth()->id();

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
        $this->merchandiseRepository->update($item, $request->validated());

        return redirect()->route('admin.merchandise.index')->with('success', 'Merchandise berhasil diupdate');
    }

    public function destroy(int $id)
    {
        $item = $this->merchandiseRepository->findById($id);
        $this->merchandiseRepository->delete($item);

        return redirect()->route('admin.merchandise.index')->with('success', 'Merchandise berhasil dihapus');
    }
}
