<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'is_active' => ['boolean', 'nullable'],
            'sort_order' => ['integer', 'nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.string' => 'Nama harus berupa teks.',
            'is_active.boolean' => 'Status aktif harus berupa boolean.',
            'sort_order.integer' => 'Urutan harus berupa angka.',
        ];
    }
}
