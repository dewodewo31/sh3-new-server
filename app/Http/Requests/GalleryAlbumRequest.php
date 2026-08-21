<?php

namespace App\Http\Requests;

use App\Models\GalleryAlbum;
use Illuminate\Foundation\Http\FormRequest;

class GalleryAlbumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_id' => ['nullable', 'exists:events,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'gdrive_folder_url' => ['nullable', 'string', 'max:2048', 'url', $this->gdriveFolderUrlRule()],
        ];
    }

    private function gdriveFolderUrlRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            if (! is_string($value) || $value === '') {
                return;
            }

            $albumId = $this->route('gallery_album');

            if ($albumId) {
                $album = GalleryAlbum::find($albumId);

                if ($album && $album->gdrive_folder_url === $value) {
                    return;
                }
            }

            if (! preg_match('/drive\.google\.com\/drive\/folders\/[a-zA-Z0-9_-]+/', $value)) {
                $fail('Link Google Drive harus berupa link folder (drive.google.com/drive/folders/...).');
            }
        };
    }
}
