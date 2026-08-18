<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuestSponsorQuotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sponsor_id' => ['required', 'integer', 'exists:sponsors,id'],
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'max_guest_accounts' => ['required', 'integer', 'min:0', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'sponsor_id.required' => 'Sponsor wajib dipilih.',
            'event_id.required' => 'Event wajib dipilih.',
            'max_guest_accounts.required' => 'Jumlah maksimum akun wajib diisi.',
            'max_guest_accounts.integer' => 'Jumlah maksimum akun harus angka.',
            'max_guest_accounts.min' => 'Jumlah maksimum akun minimal 0.',
        ];
    }
}
