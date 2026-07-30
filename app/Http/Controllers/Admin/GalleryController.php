<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Repositories\GalleryRepository;
use App\Repositories\EventRepository;

class GalleryController extends Controller
{
    public function __construct(
        private GalleryRepository $galleryRepository,
        private EventRepository $eventRepository,
    ) {}

    public function index()
    {
        $galleries = $this->galleryRepository->paginate(15, ['event']);

        return view('galleries.index', compact('galleries'));
    }

    public function create()
    {
        $events = $this->eventRepository->all();

        return view('galleries.create', compact('events'));
    }

    public function store(\App\Http\Requests\GalleryRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        if ($request->hasFile('file')) {
            $data['file_path'] = ImageHelper::upload($request->file('file'), 'galleries');
        }
        unset($data['file']);

        $this->galleryRepository->create($data);

        return redirect()->route('admin.galleries.index')->with('success', 'Gallery berhasil ditambahkan');
    }

    public function destroy(int $id)
    {
        $gallery = $this->galleryRepository->findById($id);
        ImageHelper::delete($gallery->file_path);
        $this->galleryRepository->delete($gallery);

        return redirect()->route('admin.galleries.index')->with('success', 'Gallery berhasil dihapus');
    }
}
