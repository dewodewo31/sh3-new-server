<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ParticipantVerifyResetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'hash_id' => ['required', 'string', 'regex:/^\d{4}$|^NM\d{4}$/'],
        ];
    }
}
