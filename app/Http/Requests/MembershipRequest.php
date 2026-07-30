<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'membership_type' => ['required', 'in:tahunan,setengah_tahun,mingguan'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }
}
