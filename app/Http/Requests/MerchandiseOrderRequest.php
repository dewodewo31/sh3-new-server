<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MerchandiseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'merchandise_id' => ['required', 'integer', 'exists:merchandise,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_contact' => ['required', 'string', 'max:255'],
            'size' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
