<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GalleryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'event_id' => ['nullable', 'exists:events,id'],
            'gallery_album_id' => ['nullable', 'exists:gallery_albums,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'source' => ['required', 'in:local,gdrive'],
            'type' => ['nullable', 'in:image,video'],
            'is_featured' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];

        if ($this->input('source') === 'local') {
            $rules['file'] = ['required', 'file', 'max:10240'];
        }

        if ($this->input('source') === 'gdrive') {
            $rules['google_drive_url'] = ['required', 'string'];
        }

        return $rules;
    }
}
