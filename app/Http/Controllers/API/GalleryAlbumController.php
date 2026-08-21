<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\GalleryAlbumResource;
use App\Repositories\GalleryAlbumRepository;
use Illuminate\Http\JsonResponse;

class GalleryAlbumController extends Controller
{
    public function __construct(
        private GalleryAlbumRepository $galleryAlbumRepository,
    ) {}

    public function index(): JsonResponse
    {
        $albums = $this->galleryAlbumRepository->allPublic();

        return response()->json([
            'data' => GalleryAlbumResource::collection($albums),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $album = $this->galleryAlbumRepository->findPublicDetail($id);

        return response()->json([
            'data' => new GalleryAlbumResource($album),
        ]);
    }
}
