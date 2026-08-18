<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuestSponsorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->isMethod('put')) {
            return [
                'name' => ['nullable', 'string', 'max:191'],
                'password' => ['nullable', 'string', 'min:6'],
                'valid_from' => ['nullable', 'date'],
                'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
                'is_active' => ['sometimes', 'boolean'],
            ];
        }

        return [
            'sponsor_id' => ['required', 'integer', 'exists:sponsors,id'],
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'name' => ['nullable', 'string', 'max:191'],
            'username' => ['nullable', 'string', 'alpha_dash', 'max:50', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:191', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:6'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'sponsor_id.required' => 'Sponsor wajib dipilih.',
            'sponsor_id.exists' => 'Sponsor tidak valid.',
            'event_id.required' => 'Event wajib dipilih.',
            'event_id.exists' => 'Event tidak valid.',
            'username.unique' => 'Username sudah digunakan.',
            'username.alpha_dash' => 'Username hanya boleh huruf, angka, garis bawah, dan strip.',
            'email.email' => 'Email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'password.min' => 'Password minimal 6 karakter.',
            'valid_until.after_or_equal' => 'Masa berlaku harus sama atau setelah tanggal mulai.',
        ];
    }
}
