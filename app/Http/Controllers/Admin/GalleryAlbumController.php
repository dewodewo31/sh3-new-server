<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\GalleryAlbumRequest;
use App\Repositories\EventRepository;
use App\Repositories\GalleryAlbumRepository;
use App\Services\UserService;

class GalleryAlbumController extends Controller
{
    public function __construct(
        private GalleryAlbumRepository $galleryAlbumRepository,
        private EventRepository $eventRepository,
        private UserService $userService,
    ) {}

    public function index()
    {
        $albums = $this->galleryAlbumRepository->paginateWithRelations(15);

        return view('gallery-albums.index', compact('albums'));
    }

    public function create()
    {
        $events = $this->eventRepository->all();

        return view('gallery-albums.create', compact('events'));
    }

    public function store(GalleryAlbumRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = ImageHelper::upload($request->file('cover_image'), 'albums');
        }

        $album = $this->galleryAlbumRepository->create($data);

        $this->userService->logActivity(auth()->user(), 'create_album', ['album_id' => $album->id, 'title' => $album->title]);

        return redirect()->route('admin.gallery-albums.index')->with('success', 'Album berhasil dibuat');
    }

    public function edit(int $id)
    {
        $album = $this->galleryAlbumRepository->findById($id, ['event']);
        $events = $this->eventRepository->all();

        return view('gallery-albums.edit', compact('album', 'events'));
    }

    public function update(int $id, GalleryAlbumRequest $request)
    {
        $album = $this->galleryAlbumRepository->findById($id);
        $data = $request->validated();

        if ($request->hasFile('cover_image')) {
            if ($album->cover_image) {
                ImageHelper::delete($album->cover_image);
            }
            $data['cover_image'] = ImageHelper::upload($request->file('cover_image'), 'albums');
        }

        $this->galleryAlbumRepository->update($album, $data);

        $this->userService->logActivity(auth()->user(), 'update_album', ['album_id' => $album->id, 'title' => $album->title]);

        return redirect()->route('admin.gallery-albums.index')->with('success', 'Album berhasil diperbarui');
    }

    public function destroy(int $id)
    {
        $album = $this->galleryAlbumRepository->findById($id);

        if ($album->cover_image) {
            ImageHelper::delete($album->cover_image);
        }

        $this->galleryAlbumRepository->delete($album);

        $this->userService->logActivity(auth()->user(), 'delete_album', ['album_id' => $id, 'title' => $album->title]);

        return redirect()->route('admin.gallery-albums.index')->with('success', 'Album berhasil dihapus');
    }
}