<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GalleryAlbumRequest;
use App\Repositories\EventRepository;
use App\Repositories\GalleryAlbumRepository;
use App\Services\FileService;
use App\Services\GalleryService;
use App\Services\UserService;

class GalleryAlbumController extends Controller
{
    public function __construct(
        private GalleryAlbumRepository $galleryAlbumRepository,
        private EventRepository $eventRepository,
        private UserService $userService,
        private FileService $fileService,
        private GalleryService $galleryService,
    ) {}

    public function index()
    {
        $albums = $this->galleryAlbumRepository->paginateWithRelations(15);

        return view('gallery-albums.index', compact('albums'));
    }

    public function syncNow()
    {
        $results = $this->galleryService->syncAllDriveAlbums();

        $synced = count(array_filter($results, fn ($r) => $r['status'] === 'synced'));
        $failed = count(array_filter($results, fn ($r) => $r['status'] === 'error'));
        $skipped = count(array_filter($results, fn ($r) => $r['status'] === 'skipped'));

        if ($failed > 0) {
            $messages = collect($results)
                ->filter(fn ($r) => $r['status'] === 'error')
                ->pluck('message')
                ->implode('; ');

            return redirect()->route('admin.gallery-albums.index')
                ->with('error', "Sync Google Drive gagal di {$failed} album: {$messages}");
        }

        $suffix = $skipped > 0 ? ", {$skipped} dilewati karena sync sedang berjalan" : '';

        return redirect()->route('admin.gallery-albums.index')
            ->with('success', "Sync Google Drive selesai ({$synced} album tersinkron{$suffix}).");
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
            $data['cover_image'] = $this->fileService->upload($request->file('cover_image'), 'albums');
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
            $data['cover_image'] = $this->fileService->uploadOrReplace(
                $album->cover_image,
                $request->file('cover_image'),
                'albums',
            );
        }

        $this->galleryAlbumRepository->update($album, $data);

        $this->userService->logActivity(auth()->user(), 'update_album', ['album_id' => $album->id, 'title' => $album->title]);

        return redirect()->route('admin.gallery-albums.index')->with('success', 'Album berhasil diperbarui');
    }

    public function destroy(int $id)
    {
        $album = $this->galleryAlbumRepository->findById($id);

        $this->fileService->delete($album->cover_image);

        $this->galleryAlbumRepository->delete($album);

        $this->userService->logActivity(auth()->user(), 'delete_album', ['album_id' => $id, 'title' => $album->title]);

        return redirect()->route('admin.gallery-albums.index')->with('success', 'Album berhasil dihapus');
    }
}
