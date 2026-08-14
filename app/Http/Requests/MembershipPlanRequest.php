<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MembershipPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $planId = $this->route('id');

        return [
            'key' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', Rule::unique('membership_plans', 'key')->ignore($planId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            // pricing rules = single source of truth; `price` is derived from these
            'base_event_price' => ['required', 'integer', 'min:0'],
            'discount_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'reference_event_count' => ['required', 'integer', 'min:0'],
            'duration' => ['required', 'integer', 'min:1', 'max:365'],
            'duration_unit' => ['required', Rule::in(['days', 'months', 'years'])],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
