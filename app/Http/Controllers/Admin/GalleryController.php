<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GalleryRequest;
use App\Repositories\GalleryRepository;
use App\Repositories\EventRepository;
use App\Services\GalleryService;

class GalleryController extends Controller
{
    public function __construct(
        private GalleryRepository $galleryRepository,
        private EventRepository $eventRepository,
        private GalleryService $galleryService,
    ) {}

    public function index()
    {
        $galleries = $this->galleryRepository->paginate(15, ['event']);

        return view('galleries.index', compact('galleries'));
    }

    public function create()
    {
        $events = $this->eventRepository->all();
        $albums = \App\Models\GalleryAlbum::all();

        return view('galleries.create', compact('events', 'albums'));
    }

    public function store(GalleryRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $source = $request->input('source', 'local');

        if ($source === 'local') {
            $gallery = $this->galleryService->storeLocal($data, $request->file('file'));
        } else {
            $gallery = $this->galleryService->storeGoogleDrive($data);
        }

        return redirect()->route('admin.galleries.index')->with('success', 'Gallery berhasil ditambahkan');
    }

    public function show(int $id)
    {
        $gallery = $this->galleryRepository->findById($id);

        return view('galleries.show', compact('gallery'));
    }

    public function edit(int $id)
    {
        $gallery = $this->galleryRepository->findById($id);
        $events = $this->eventRepository->all();
        $albums = \App\Models\GalleryAlbum::all();

        return view('galleries.edit', compact('gallery', 'events', 'albums'));
    }

    public function update(int $id, GalleryRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $source = $request->input('source', 'local');

        if ($source === 'local' && $request->hasFile('file')) {
            $gallery = $this->galleryService->update($id, array_merge($data, ['file' => $request->file('file')]));
        } else {
            unset($data['file']);
            $gallery = $this->galleryService->update($id, $data);
        }

        return redirect()->route('admin.galleries.index')->with('success', 'Gallery berhasil diperbarui');
    }

    public function destroy(int $id)
    {
        $this->galleryService->delete($id);

        return redirect()->route('admin.galleries.index')->with('success', 'Gallery berhasil dihapus');
    }
}