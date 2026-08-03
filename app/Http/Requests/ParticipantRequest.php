<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $participantId = $this->route('participant')?->id;

        return [
            'user_id' => ['nullable', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('participants')->ignore($participantId)],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'emergency_phone' => ['nullable', 'string', 'max:20'],
            'medical_conditions' => ['nullable', 'string'],
            'blood_type' => ['nullable', 'string', 'max:5'],
            'jersey_size' => ['nullable', Rule::in(['XS', 'S', 'M', 'L', 'XL', 'XXL'])],
            'membership_type' => ['nullable', Rule::in(['tahunan', 'setengah_tahun', 'mingguan', 'none'])],
        ];
    }
}
