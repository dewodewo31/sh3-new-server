<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'participant_id' => ['required', 'exists:participants,id'],
            'payment_type' => ['required', 'in:event_registration,merchandise,membership'],
            'paymentable_type' => ['required', 'string'],
            'paymentable_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:transfer,cash,qris'],
            'payment_proof' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
