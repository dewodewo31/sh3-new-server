<?php

namespace App\Http\Requests;

use App\Models\InventoryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'condition_after' => ['required', Rule::in(InventoryItem::CONDITIONS)],
            'actual_return_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
