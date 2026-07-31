<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'participant_id' => ['required', 'exists:participants,id'],
            'membership_type' => ['required', Rule::exists('membership_plans', 'key')->where('is_active', true)],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:12'],
        ];
    }
}
