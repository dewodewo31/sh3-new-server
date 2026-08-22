<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_id' => ['required', 'exists:events,id'],
            'activity_id' => ['required', 'exists:activities,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'event_id.required' => 'Event wajib diisi.',
            'event_id.exists' => 'Event tidak ditemukan.',
            'activity_id.required' => 'Aktivitas wajib diisi.',
            'activity_id.exists' => 'Aktivitas tidak ditemukan.',
            'amount.required' => 'Jumlah wajib diisi.',
            'amount.numeric' => 'Jumlah harus berupa angka.',
            'amount.min' => 'Jumlah tidak boleh kurang dari 0.',
            'notes.string' => 'Catatan harus berupa teks.',
        ];
    }
}
