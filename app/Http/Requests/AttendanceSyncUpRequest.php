<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceSyncUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'records' => ['required', 'array', 'min:1'],
            'records.*.event_id' => ['required', 'integer', 'exists:events,id'],
            'records.*.participant_id' => ['required', 'integer', 'exists:participants,id'],
            'records.*.type' => ['required', Rule::in(['check_in', 'check_out'])],
            'records.*.method' => ['nullable', Rule::in(['qr_code', 'manual', 'self_scan'])],
            'records.*.scanned_at' => ['nullable', 'date'],
            'records.*.latitude' => ['nullable', 'numeric'],
            'records.*.longitude' => ['nullable', 'numeric'],
            'records.*.qr_code' => ['nullable', 'string'],
        ];
    }
}
