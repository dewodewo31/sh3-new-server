<?php

namespace App\Http\Controllers\API;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Illuminate\Http\JsonResponse;

class GalleryController extends Controller
{
    public function index(): JsonResponse
    {
        $galleries = Gallery::with(['event.category', 'album'])
            ->where('type', 'image')
            ->orderBy('is_featured', 'desc')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $data = $galleries->map(fn (Gallery $gallery) => [
            'id' => $gallery->id,
            'event_id' => $gallery->event_id,
            'album_id' => $gallery->gallery_album_id,
            'title' => $gallery->title,
            'description' => $gallery->description,
            'url' => ImageHelper::getUrl($gallery->file_path),
            'thumb' => $gallery->thumbnail_path
                ? ImageHelper::getUrl($gallery->thumbnail_path)
                : ImageHelper::getUrl($gallery->file_path),
            'type' => $gallery->type,
            'is_featured' => $gallery->is_featured,
            'event' => $gallery->event ? [
                'id' => $gallery->event->id,
                'title' => $gallery->event->title,
                'category' => $gallery->event->category?->name,
                'status' => $gallery->event->status,
            ] : null,
        ]);

        return response()->json(['data' => $data]);
    }
}
