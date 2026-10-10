<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class GalleryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Route::resource melewatkan {gallery} sebagai string id (tanpa model binding).
        $isCreate = $this->route('gallery') === null;

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
            // Edit: file boleh dikosongkan ("Kosongkan jika tidak diganti").
            // Ukuran: gambar 10MB, video 50MB (mengikuti konfigurasi aplikasi).
            $maxKb = $this->input('type') === 'video' ? 51200 : 10240;

            $rules['file'] = array_merge(
                [$isCreate ? 'required' : 'nullable', 'file', 'max:'.$maxKb],
                [$this->imageOrVideoRule()],
            );
        }

        if ($this->input('source') === 'gdrive') {
            $rules['google_drive_url'] = [
                'required',
                'string',
                'max:2048',
                'url',
                $this->gdriveUrlRule(),
            ];
        }

        return $rules;
    }

    /**
     * Upload lokal dibatasi gambar (JPG/PNG/WEBP) atau video (MP4/MOV)
     * sesuai dokumentasi modul Gallery — file lain (zip, php, dll) ditolak.
     */
    private function imageOrVideoRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            if (! $value instanceof UploadedFile) {
                return;
            }

            $mime = (string) $value->getMimeType();

            if (! str_starts_with($mime, 'image/') && ! str_starts_with($mime, 'video/')) {
                $fail('File harus berupa gambar (JPG, PNG, WEBP) atau video (MP4, MOV).');
            }
        };
    }

    /**
     * Validasi link Google Drive: harus domain drive.google.com dan harus
     * mengandung File ID — polanya sama dengan GalleryService::extractDriveFileId()
     * supaya link yang lolos validasi pasti bisa diproses menjadi 422, bukan 500.
     */
    private function gdriveUrlRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            if (! is_string($value) || $value === '') {
                return;
            }

            if (! preg_match('#^https?://drive\.google\.com/#i', $value)) {
                $fail('Link harus berupa link Google Drive (drive.google.com).');

                return;
            }

            $patterns = [
                '/\/file\/d\/([a-zA-Z0-9_-]+)/',
                '/[?&]id=([a-zA-Z0-9_-]+)/',
                '/\/open\?id=([a-zA-Z0-9_-]+)/',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    return;
                }
            }

            $fail('Link Google Drive tidak valid atau tidak mengandung File ID.');
        };
    }
}
