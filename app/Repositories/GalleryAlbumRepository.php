<?php

namespace App\Repositories;

use App\Models\GalleryAlbum;
use App\Support\Sort;

class GalleryAlbumRepository extends BaseRepository
{
    public function __construct(GalleryAlbum $galleryAlbum)
    {
        parent::__construct($galleryAlbum);
    }

    public function paginateWithRelations(int $perPage = 15)
    {
        $query = $this->model->with('event')
            ->withCount('galleries');

        Sort::apply($query, ['title', 'created_at'], 'created_at', 'desc');

        return $query->paginate($perPage)->withQueryString();
    }
}
