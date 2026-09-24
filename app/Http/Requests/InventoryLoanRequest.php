<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inventory_item_id' => ['required', 'integer', 'exists:inventory_items,id'],
            'borrower_type' => ['nullable', 'string', 'max:255'],
            'borrower_id' => ['nullable', 'integer'],
            'borrower_name' => ['required', 'string', 'max:255'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'borrow_date' => ['nullable', 'date'],
            'expected_return_date' => ['nullable', 'date', 'after_or_equal:borrow_date'],
        ];
    }
}
