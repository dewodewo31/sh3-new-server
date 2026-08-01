<?php

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchandiseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,
            'sizes' => $this->size_options ?? [],
            'colors' => [],
            'image' => $this->image,
            'image_url' => ImageHelper::getUrl($this->image),
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
