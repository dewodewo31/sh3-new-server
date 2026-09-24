<?php

namespace App\Http\Requests;

use App\Models\InventoryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryHandoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_location' => ['required', 'string', 'max:255'],
            'to_name' => ['nullable', 'string', 'max:255'],
            'receiver_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'receiver_name' => ['nullable', 'string', 'max:255'],
            'condition_at_handover' => ['required', Rule::in(InventoryItem::CONDITIONS)],
            'handed_over_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
