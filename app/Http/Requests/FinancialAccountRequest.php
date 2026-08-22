<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinancialAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'type' => ['required', 'in:kas,bank,other'],
            'is_active' => ['boolean', 'nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.string' => 'Nama harus berupa teks.',
            'type.required' => 'Tipe wajib diisi.',
            'type.in' => 'Tipe tidak valid.',
            'is_active.boolean' => 'Status aktif harus berupa boolean.',
        ];
    }
}
