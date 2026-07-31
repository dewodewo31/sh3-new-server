<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscribeMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'membership_type' => ['required', Rule::exists('membership_plans', 'key')->where('is_active', true)],
            'payment_method' => ['required', Rule::in(['transfer', 'cash', 'qris'])],
            'payment_proof' => ['nullable', 'image', 'max:5120'],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:12'],
        ];
    }
}
