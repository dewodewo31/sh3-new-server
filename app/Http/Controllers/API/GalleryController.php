<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\GalleryResource;
use App\Services\GalleryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function __construct(
        private GalleryService $galleryService,
    ) {}

    public function index(): JsonResponse
    {
        $galleries = $this->galleryService->getAllPublic();

        return response()->json([
            'data' => GalleryResource::collection($galleries),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => ['nullable', 'exists:events,id'],
            'gallery_album_id' => ['nullable', 'exists:gallery_albums,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'source' => ['required', 'in:local,gdrive'],
            'type' => ['nullable', 'in:image,video'],
            'is_featured' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($validated['source'] === 'local') {
            $request->validate([
                'file' => ['required', 'file', 'max:10240'],
            ]);
        } else {
            $request->validate([
                'google_drive_url' => ['required', 'string'],
            ]);
        }

        if ($validated['source'] === 'local') {
            $gallery = $this->galleryService->storeLocal(
                array_merge($validated, ['type' => $validated['type'] ?? 'image']),
                $request->file('file')
            );
        } else {
            $gallery = $galleryService->storeGoogleDrive(
                array_merge($validated, ['type' => $validated['type'] ?? 'image'])
            );
        }

        return response()->json([
            'message' => 'Gallery uploaded successfully.',
            'data' => new GalleryResource($gallery->load(['event.category', 'album'])),
        ], 201);
    }
}