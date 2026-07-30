<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'image' => ['nullable', 'image', 'max:2048'],
            'banner' => ['nullable', 'image', 'max:2048'],
            'key_points' => ['nullable', 'json'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'registration_start_date' => ['required', 'date'],
            'registration_end_date' => ['required', 'date', 'after:registration_start_date'],
            'quota' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_free_for_members' => ['boolean'],
            'status' => ['nullable', 'in:draft,publish,ongoing,completed,cancelled'],
        ];
    }
}
