<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MerchandiseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'size_options' => ['nullable', 'json'],
            'stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
            'status' => ['nullable', 'in:available,sold_out,discontinued'],
        ];
    }
}
