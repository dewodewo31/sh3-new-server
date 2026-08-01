<?php

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SponsorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'logo' => $this->logo,
            'logo_url' => ImageHelper::getUrl($this->logo),
            'website' => $this->website,
            'contact_person' => $this->contact_person,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'tier' => $this->tier,
            'year' => $this->year,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
