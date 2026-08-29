<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MerchandiseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'size_options' => ['nullable', 'json'],
            'stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
            'status' => ['nullable', 'in:available,sold_out,discontinued'],
            // Redemption config (Model B). points_required null => NOT redeemable.
            'points_required' => ['nullable', 'integer', 'min:1'],
            'price_after_points' => [
                'nullable',
                'numeric',
                'min:0',
                // Guard: config discount can never exceed the price.
                function ($attribute, $value, $fail) {
                    $price = $this->input('price');
                    if ($value !== null && $price !== null && (float) $value > (float) $price) {
                        $fail('price_after_points harus lebih kecil atau sama dengan price.');
                    }
                },
            ],
        ];
    }
}
