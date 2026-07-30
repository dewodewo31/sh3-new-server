<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($userId)],
            'password' => [$this->isMethod('POST') ? 'required' : 'nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in([
                'admin_full_access', 'admin_laman', 'admin_member',
                'admin_bnh', 'organizer', 'bendahara', 'sponsor', 'merchandise',
            ])],
            'is_active' => ['boolean'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
