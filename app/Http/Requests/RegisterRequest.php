<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email',
                Rule::unique('users'),
                Rule::unique('participants'),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'emergency_phone' => ['nullable', 'string', 'max:20'],
            'medical_conditions' => ['nullable', 'string'],
            'blood_type' => ['nullable', 'string', 'max:5'],
            'jersey_size' => ['nullable', Rule::in(['XS', 'S', 'M', 'L', 'XL', 'XXL'])],
            'password' => ['nullable', 'string', 'min:6'],
            'password_confirmation' => ['nullable', 'string', 'same:password'],
        ];
    }
}
