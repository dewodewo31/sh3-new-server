<?php

namespace App\Http\Requests;

use App\Models\InventoryPhoto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'max:10240'],
            'context' => ['nullable', Rule::in(InventoryPhoto::CONTEXTS)],
            'caption' => ['nullable', 'string', 'max:255'],
        ];
    }
}
