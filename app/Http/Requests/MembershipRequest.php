<?php

namespace App\Http\Requests;

use App\Models\Participant;
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
            'participant_id' => [
                'required',
                'exists:participants,id',
                // Layer 2 protection: re-checked at submit, cannot be bypassed
                // by an open form (race condition) or by manipulating the dropdown.
                function (string $attribute, mixed $value, \Closure $fail) {
                    $eligible = Participant::whereKey($value)
                        ->eligibleForMembership()
                        ->exists();

                    if (! $eligible) {
                        $fail('Participant sudah memiliki membership aktif.');
                    }
                },
            ],
            'membership_type' => ['required', Rule::exists('membership_plans', 'key')->where('is_active', true)],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:12'],
        ];
    }
}
